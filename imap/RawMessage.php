<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 *      http://creativecommons.org/licenses/by/4.0/
 *
*/

namespace dvc\imap;

use dvc\mail\attachment;
use sys;

class RawMessage {
	var $charset = '',
		$messageType = '',
		$message = '',
		$messageHTML = '',
		$attachments = [],
		$cids = [];

	function __construct( $stream, $email_number ) {
		$debug = false;
		// $debug = true;

		// BODY
		$s = imap_fetchstructure( $stream, $email_number );
		if ( !isset( $s->parts ) || !$s->parts )  // simple
			$this->getpart( $stream, $email_number, $s, 0 );  // pass 0 as part-number

		else {  // multipart: cycle through each part
			// sys::logger( sprintf('get parts :s: %s', __METHOD__));
			foreach ($s->parts as $partno0 => $p) {
				$this->getpart( $stream, $email_number, $p, $partno0+1 );

			}

			// sys::logger( sprintf('get parts :e: %s', __METHOD__));

		}

	}

	protected function getpart( $mbox, $mid, $p, $partno ) {
		$debug = false;
		// $debug = true;
		$debugPart = $debug;
		// $debugPart = true;

		// if ( $debug) sys::logger( sprintf( '%s, %s, $p, %s',  $mbox, $mid, $partno));

		// $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple

		// DECODE DATA
		$data = ($partno)?
			imap_fetchbody( $mbox, $mid, $partno):  // multipart
			imap_body( $mbox, $mid);  // simple

		//~ if ( $debug) sys::logger( sprintf( 'encoding : %s : %s',  $p->encoding, $data));
		//~ if ( $debug && $p->ifsubtype) sys::logger( sprintf( '    type :: subtype : %s :: %s',  $p->type, $p->subtype));

		// Any part may be encoded, even plain text messages, so check everything.
		if ($p->encoding==4) {
			if ( $debug) sys::logger( sprintf('quoted_printable_decode : %s', __METHOD__));
			// if ( $debug) sys::logger( sprintf('quoted_printable_decode : %s : %s', print_r( $p, true), __METHOD__));
			$data = quoted_printable_decode( $data);


		}
		elseif ( $p->encoding==3) {
			if ( $debug) sys::logger( sprintf('imap_base64 :s: %s', __METHOD__));
			// $data = imap_base64( $data);
			$data = base64_decode( $data);
			// die( $data);
			if ( $debug) sys::logger( sprintf('imap_base64 :e: %s', __METHOD__));

		}
		elseif ( $p->encoding == 1) {
			if ( $debug) sys::logger( sprintf('imap_8bit : %s', __METHOD__));
			$data = imap_8bit( $data);

		}
		elseif ( isset( $p->subtype) && 'plain' == strtolower( $p->subtype)) {
			if ( $debug) sys::logger( sprintf('plain text : %s', __METHOD__));
			// $data = quoted_printable_decode( $data);
			// $data = $data;

		}
		else {
			if ( $debug) sys::logger( sprintf('other encoding : %s', $p->encoding, __METHOD__));
			// if ( $debug) sys::logger( sprintf('other encoding : %s : %s', $p->encoding, print_r( $p, true), __METHOD__));
			// if ( $debug) sys::logger( sprintf('other encoding : %s : %s', $data, __METHOD__));
			// die( quoted_printable_decode( $data));
			$data = quoted_printable_decode( $data);

		}

		// return;

		// PARAMETERS : get all parameters, like charset, filenames of attachments, etc.
		$params = [];
		if ($p->parameters)
			foreach ($p->parameters as $x)
				$params[strtolower($x->attribute)] = $x->value;

		if ( isset( $p->dparameters )) {
			if ($p->dparameters) {
				foreach ($p->dparameters as $x)
					$params[strtolower($x->attribute)] = $x->value;

			}

		}

		// if ( $debug) sys::logger( sprintf( 'params : %s',  print_r( $params, true)));

		// ATTACHMENT
		// Any part with a filename is an attachment,
		// so an attached text file (type 0) is not mistaken as the message.

		/*
		 *	filename may be given as 'Filename' or 'Name' or both
		 *	filename may be encoded, so see imap_mime_header_decode()
		 */
		if ( strlen( $data ) > 0 ) {
			if ( isset( $params['filename'] )) {
				$filename = $params['filename'];
				$attach = new attachment;
				$attach->Name = $attach->ContentId = $filename;
				$attach->Content = $data;
				if ( isset( $p->id )) {
					$attach->ContentId = preg_replace( array( "@\<@", "@\>@" ), "", $p->id );

				}
				$this->attachments[] = $attach;

				// if ( !isset( $this->attachments[$filename] )) {
					// if ( isset( $p->id )) {
					// 	$this->cids[$filename] = preg_replace( array( "@\<@", "@\>@" ), "", $p->id );
					// 	if ( $debug) sys::logger( "a. $filename (" . strlen($data) . ") : id: " . $this->cids[$filename] );

					// }
					// else {
					// 	if ( $debug) sys::logger( "a. $filename (" . strlen($data) . ")" );

					// }

				// }

			}
			elseif ( isset( $params['name'])) {
				$filename = $params['name'];
				$attach = new attachment;
				$attach->Name = $attach->ContentId = $filename;
				$attach->Content = $data;
				if ( isset( $p->id )) {
					$attach->ContentId = preg_replace( array( "@\<@", "@\>@" ), "", $p->id );

				}
				$this->attachments[] = $attach;

				// if ( ! isset( $this->attachments[$filename] )) {
				// 	$this->attachments[$filename] = $data;  // this is a problem if two files have same name
				// 	if ( isset( $p->id ))
				// 		$this->cids[$filename] = preg_replace( array( "@\<@", "@\>@" ), "", $p->id );

				// if ( $debug) sys::logger( "b. $filename (" . strlen($data) . ")" );

				// }

			}
			elseif ( $p->type == 5 && $data && isset( $p->id)) {
				$id = preg_replace( array( "@(<|>)@" ), "", $p->id );
				$attach = new attachment;
				$attach->Name = $attach->ContentId = $id;
				$attach->Content = $data;
				$this->attachments[] = $attach;

				// if ( ! isset( $this->attachments[$id] )) {
				// 	$this->attachments[$id] = $data;  // this is a problem if two files have same name
				// 	$this->cids[$id] = $id;

				// 	if ( $debug) sys::logger( "c. $id(" . strlen($data) . " / " . $p->subtype  . ")" );

				// }

			}
			elseif ( $debug) {
				if ( $p->type == 5 && $data) {
					//~ foreach ( $params as $k => $v)
						//~ sys::logger( sprintf( "%s => %s", $k, $v));

					//~ sys::logger( sprintf( '%s, %s, $p, %s ( %s: %s )',  $mbox, $mid, $partno, $p->id, $p->type));
					sys::logger( "d. lost : " );

				}

			}

		}

		// TEXT
		if ($p->type==0 && $data) {
			// Messages may be split in different parts because of inline attachments,
			// so append parts together with blank row.

			//~ if ( $debug) sys::logger( sprintf( 'encoding : %s : %s',  $p->encoding, $data));
			//~ if ( $debug && $p->ifsubtype) sys::logger( sprintf( '    type :: subtype : %s :: %s',  $p->type, $p->subtype));

			if ( strtolower($p->subtype)=='plain') {
				$this->messageType = 'text';
				$this->message .= quoted_printable_decode( trim( $data)) . "\n\n";
				if ( $debugPart) sys::logger( sprintf( 'plain text : %s : %s', mb_detect_encoding( $data), __METHOD__ ));
				// die( $data);

			}
			elseif ( strtolower($p->subtype)=='rfc822-headers')
				$this->message .= "--[rfc822-headers]--\n\n" . trim( $data) . "\n\n";

			elseif ( strtolower($p->subtype)=='calendar') {
				$this->attachments[ 'calendar.ics'] = $data;  // this is a problem if two files have same name

			}

			else {
				$this->messageType = 'html';
				$this->messageHTML .= $data;	// . "<br /><br />";
				if ( $debugPart) sys::logger( sprintf( 'html : %s', __METHOD__ ));

			}

			if ( isset( $params['charset'])) {
				$this->charset = $params['charset'];  // assume all parts are same charset

			}

		}

		// EMBEDDED MESSAGE
		// Many bounce notifications embed the original message as type 2,
		// but AOL uses type 1 (multipart), which is not handled here.
		// There are no PHP functions to parse embedded messages,
		// so this just appends the raw source to the main message.
		elseif ($p->type==2 && $data) {
			$this->message .= $data."\n\n";
			if ( $debugPart) sys::logger( sprintf( 'part type 2 : %s', __METHOD__ ));

		}

		// SUBPART RECURSION
		if ( isset( $p->parts )) {
			if ( $p->parts ) {
				foreach ($p->parts as $partno0=>$p2)
					$this->getpart( $mbox, $mid, $p2, $partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.

			}

		}

		if ( $debug) sys::logger( sprintf('exit : %s', __METHOD__));

	}

}

<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/

	*/
namespace dvc\ews;

use \jamesiarmes\PhpEws;
use \jamesiarmes\PhpEws\ArrayType;
use \jamesiarmes\PhpEws\Enumeration;
use \jamesiarmes\PhpEws\Request;
use \jamesiarmes\PhpEws\Type;


class message extends \dvc\mail\message {
	//~ var $src = NULL;

	function __construct( Type\MessageType $src) {
		$this->src = $src;

		$this->ItemId = $src->ItemId;
		$this->MessageID = $src->InternetMessageId;
		$this->Recieved = $src->DateTimeReceived;
		$this->time = strtotime( $src->DateTimeReceived);

		if ( isset( $src->From)) {
			$this->fromEmail = $src->From->Mailbox->EmailAddress;
			// \sys::logger( $this->fromEmail);
			$this->fromName = $src->From->Mailbox->Name;
			$this->From = ( $this->fromName ? $this->fromName : $this->fromEmail);

		}

		$this->To = $src->DisplayTo;

		$encoding = mb_detect_encoding( $src->Subject);
		//~ \sys::logger( $encoding);
		if ( $encoding)
			$this->Subject = $src->Subject;
		else
			$this->Subject = utf8_encode( $src->Subject);

		if ( isset( $src->Body->_)) {
			$this->Body = $src->Body->_;
			$this->BodyType = utf8_encode( $src->Body->BodyType);

		}

		if ( 1 == (int)$src->IsRead)
			$this->seen = 'yes';

		if ( !empty($src->Attachments)) {
			// Iterate over the attachments for the message.
			foreach ($src->Attachments->FileAttachment as $attachment)
				$this->attachmentIDs[] = $attachment->AttachmentId->Id;

		}

		return;

	}

	static function header() {
		return ( sprintf( '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			'Date',
			'From',
			'Subject'
			));

	}

	function row() {
		$d = strtotime( $this->Recieved);
		if ( date('Y-m-d') == date( 'Y-m-d', $d))
			$d = date( 'g:i a', $d);
		elseif ( date('Y') == date( 'Y', $d))
			$d = date( 'd-M', $d);
		else
			$d = date( \config::$SHORTDATE_FORMAT, $d);

		return ( sprintf( '<tr><td class="text-center">%s</td><td>%s</td><td>%s</td></tr>',
			$d,
			$this->fromName,
			$this->Subject
			));

	}

	public function asArray() {
		// \sys::logger( $this->fromEmail);

		return [
			'received' => $this->Recieved,
			'messageid' => $this->MessageID,
			'to' => $this->To,
			'from' => $this->From,
			'fromEmail' => $this->fromEmail,
			'subject' => $this->Subject,
			'seen' => $this->seen,
			'folder' => $this->Folder,

		];

	}

	public function safehtml() {
		$debug = false;
		// $debug = true;

		if ( !$this->Body ) {
			$this->comments = sprintf( 'no html : %s %s', strlen($this->Body), __METHOD__);
			return;

		}

		$decodecs = array( '@(“|”|’|‘)@');
		$decodeca = array( '&rsquo;');

		/* and possibly these as well */
		//~ UPDATE wp_posts SET post_content = REPLACE(post_content, '–', '–');
		//~ UPDATE wp_posts SET post_content = REPLACE(post_content, '—', '—');
		//~ UPDATE wp_posts SET post_content = REPLACE(post_content, '-', '-');
		//~ UPDATE wp_posts SET post_content = REPLACE(post_content, '…', '…');

		$doc = new \DOMDocument();
		ini_set ('error_reporting', "5");
		//~ $doc->loadHTML( preg_replace( $safetyS, $safetyR, $this->Body ) );
		$doc->loadHTML( preg_replace( $decodecs, $decodeca, $this->Body));
		ini_set ('error_reporting', "6143");

		$unsets = array();

		if ( $debug) \sys::logger( "processing ..." );
		foreach($doc->getElementsByTagName('a') as $img)
			$img->setAttribute('target', '_blank');

		foreach($doc->getElementsByTagName('link') as $link) {
			if ( $link->hasAttributes()) {
				$href = $link->getAttribute('href');
				if ( $href) {
					$link->removeAttribute('href');
					$link->setAttribute('data-safe-href', $href);

				}

			}

		}

		foreach($doc->getElementsByTagName('a') as $anchor) {
			if ( $anchor->hasAttributes()) {
				$href = $anchor->getAttribute('href');
				if ( $href) {
					if ( preg_match( '@^mailto:@', $href)) {
						$anchor->removeAttribute('href');
						$anchor->setAttribute('data-role', 'email-link');
						$anchor->setAttribute('data-email', preg_replace( '@^mailto:@', '', $href));

					}

				}

			}

		}

		foreach($doc->getElementsByTagName('img') as $img) {
			if ( $img->hasAttributes()) {

				$src = $img->getAttribute('src');
				if ( $src) {
					// if ( $debug) \sys::logger( "processing .... :::" . count( $this->attachments));

					if ( preg_match( '@^data:image@', $src)) continue;

					$img->removeAttribute('src');
					$img->setAttribute('data-safe-src', $src);

					if ( $debug) \sys::logger( "processing .....$src" );
					foreach ( $this->attachments as $data ) {
						if ( !isset( $data->Name)) continue;
						if ( !isset( $data->ContentId)) continue;
						$name = $data->Name;
						if ( $debug) \sys::logger( "attachment .....$name, $data->ContentId" );

						if (
							$src == $name ||
							$src == $data->ContentId ||
							( strpos( $src, "cid:$name" ) !== false ) ||
							( strpos( $src, "cid:$data->ContentId" ) !== false )
							) {
							if ( $debug) \sys::logger( "processing .....$src, $name" );
							// \sys::dump( $data);

							if ( preg_match( "@.gif$@i", $name )) {
								$img->setAttribute('src', 'data:image/gif;base64,' . base64_encode( $data->Content ));
								$img->removeAttribute('data-safe-src');
							}
							elseif ( preg_match( "@.jpe?g$@i", $name )) {
								$img->setAttribute('src', 'data:image/jpeg;base64,' . base64_encode( $data->Content ));
								$img->removeAttribute('data-safe-src');
							}
							elseif ( preg_match( "@.png$@i", $name )) {
								$img->setAttribute('src', 'data:image/png;base64,' . base64_encode( $data->Content ));
								$img->removeAttribute('data-safe-src');
							}
							else {
								$finfo = new \finfo(FILEINFO_MIME);
								$mimetype = $finfo->buffer($data->Content);
								if ( preg_match( "@image/gif@i", $mimetype )) {
									$img->setAttribute('src', 'data:image/gif;base64,' . base64_encode( $data->Content ));
									$img->removeAttribute('data-safe-src');

								}
								elseif ( preg_match( "@image/jpe?g@i", $mimetype )) {
									$img->setAttribute('src', 'data:image/jpeg;base64,' . base64_encode( $data->Content ));
									$img->removeAttribute('data-safe-src');

								}
								elseif ( preg_match( "@image/png@i", $mimetype )) {
									$img->setAttribute('src', 'data:image/png;base64,' . base64_encode( $data->Content ));
									$img->removeAttribute('data-safe-src');

								}
								elseif ( $debug) {
									\sys::logger( $mimetype);
									\sys::logger( "what about : $src, $name, cid:$cid");

								}

							}

							$unsets[] = $name;
							//~ unset( $this->attachments[$name] );
							//~ if ( isset( $this->cids[$name] ))
								//~ unset( $this->cids[$name]);

						}
						else {
							if ( $debug) \sys::logger( sprintf( 'not processing .....%s == %s', $src, $name ));

						}

					}

					reset( $this->attachments);

				}

			}

		}

		foreach ( $unsets as $u ) {
			if ( isset( $this->attachments[$u] ))
				unset( $this->attachments[$u] );

			if ( isset( $this->cids[$u] ))
				unset( $this->cids[$u]);

		}

		return \strings::htmlSanitize( $doc->saveHTML());

	}

}

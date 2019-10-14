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

namespace dvc\mail;
use strings;
use sys;

class message {

    public $attachmentIDs = [];
	public $attachments = [];
	public $answered = 'no';
	public $flagged = 'no';
	public $forwarded = 'no';
	public $fromName = '';
	public $fromEmail = '';
	public $seen = 'no';
	public $tags = '';
	public $time = '';

	public $BodyType = '';
	public $Body = '';
	public $Folder = '';
	public $From = '';
	public $ItemId = '';
	public $MessageID = '';
	public $Recieved = '';
	public $Subject = '';
	public $To = '';
	public $CC = '';		// imap
	public $BCC = '';		// imap
	public $Uid = '';

	public $MSGNo = '';		// imap
	public $CharSet = '';	// imap
	public $in_reply_to = '';	// imap
	public $references = '';	// imap
	public $cids = '';	// imap

	protected function getHtmlHeader() {
		$search = array(
			'@.*<head[^>]*?>@si',		// before the head element
			'@</head>.*@si',			// after head element
			'@<meta[^>]*?>@si',			// strip meta tags
		);

		return( preg_replace($search, '', $this->Body));

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
			'uid' => $this->Uid,

		];

	}

	public function getMso() {
        $header = trim( $this->getHtmlHeader());
        if ( preg_match( '@^<!--\[if \!mso\]>@', $header)) {
            if ( strings::endswith( $header, '<![endif]-->')) {
				return $header;

            }

		}

		return '';

	}

	public function hasMso() {
		$header = trim( $this->getHtmlHeader());
        if ( preg_match( '@^<!--\[if \!mso\]>@', $header)) {
            if ( strings::endswith( $header, '<![endif]-->')) {
                return true;

            }

		}

		return false;

	}

	public function safehtml() {
		$debug = false;
		// $debug = true;

		if ( !$this->Body ) {
			$this->comments = sprintf( 'no html : %s %s', strlen($this->Body), __METHOD__);
			return;

		}

		$decodecs = [
			'@(“|”|’|‘|' . chr(146) . ')@',
			'@<!DOCTYPE[^>]*>@'
		];
		$decodeca = [
			'&rsquo;',
			''
		];

		/* and possibly these as well */
		//~ UPDATE wp_posts SET post_content = REPLACE(post_content, '–', '–');
		//~ UPDATE wp_posts SET post_content = REPLACE(post_content, '—', '—');
		//~ UPDATE wp_posts SET post_content = REPLACE(post_content, '-', '-');
		//~ UPDATE wp_posts SET post_content = REPLACE(post_content, '…', '…');

		$_string = preg_replace( $decodecs, $decodeca, $this->Body);

		$encoding = mb_detect_encoding($_string);
		if ( $encoding) {
			if ( !\in_array( strtolower( $encoding), [ 'ascii', 'utf-8'])) {
				sys::logger( sprintf('%s : %s', $encoding, __METHOD__));

			}
			if ( strtolower( $encoding) != 'utf-8') {
				$_string = mb_convert_encoding( $_string, 'utf-8', $encoding);

			}
			$_string = mb_convert_encoding( $_string, 'html-entities', 'utf-8');

		}
		else {
			sys::logger( sprintf('no encoding on string : %s', __METHOD__));

		}

		if ( $debug) {
			$f = sprintf('%s/temp-start.html', \config::dataPath());
			if ( \file_exists($f)) unlink( $f);
			\file_put_contents( $f, $_string);

		}

		// die( $_string . '<br />die...');

		$doc = new \DOMDocument;
		// ini_set ('error_reporting', "5");
		libxml_use_internal_errors(true);
		$doc->loadHTML( $_string);
		libxml_clear_errors();
		// ini_set ('error_reporting', "6143");

		$unsets = [];

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
					// $_attachments = [];
					foreach ( $this->attachments as $key => $data ) {
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

							$unsets[] = $key;
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

		// $html = $doc->saveHTML();
		$tmpfile = \tempnam( \config::dataPath(), 'msg_');
		$doc->saveHTMLfile( $tmpfile);
		$html = \file_get_contents( $tmpfile);
		// unlink( $tmpfile);

		// sys::logger( sprintf('%s : %s', mb_detect_encoding($html), __METHOD__));
		$html = preg_replace(
			[
				sprintf( '@%s@', chr(146)),
				sprintf( '@%s%s@', chr(194), chr(160)),
				'@’@',
			],
			[
				'&rsquo;',
				'&nbsp;',
				'&rsquo;',
			], $html);
		// sys::logger( sprintf('%s : %s', mb_detect_encoding($html), __METHOD__));
		// $html = str_replace( chr(146), '&rsquo;', $html);
		// $html = str_replace( chr(160), '&nbsp;', $html);

		if ( $debug) {
			$f = sprintf('%s/temp.txt', \config::dataPath());
			if ( \file_exists($f)) unlink( $f);
			\file_put_contents( $f, $html);

		}

		$encoding = mb_detect_encoding($html);
		if ( $encoding) {
			if ( !\in_array( strtolower( $encoding), [ 'ascii', 'utf-8'])) {
				sys::logger( sprintf('%s : %s', $encoding, __METHOD__));

			}
			$html = mb_convert_encoding( $html, 'html-entities', $encoding);

		}
		else {
			sys::logger( sprintf('no encoding on string : %s', __METHOD__));

		}

		$_html = \strings::htmlSanitize( $html);

		if ( $debug) {
			$f = sprintf('%s/temp-late.html', \config::dataPath());
			if ( \file_exists($f)) unlink( $f);
			\file_put_contents( $f, $_html);

		}

		return $_html;

	}

}
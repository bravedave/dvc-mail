<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace dvc\imap;

use dvc\mail\credentials;
use dvc\EmailAddress;
use sys;

class client {
	// static $debug = true;
	static $debug = false;

	protected $_account = '';

	protected $_error = '';

	protected $_folder = '';

	protected $_open = false;

	protected $_password = '';

	protected $_port = '143';

	protected $_stream = null;

	protected $_server = '';

	protected $_secure = 'tls';

	const INBOX = 'Inbox';

	protected static function _instance( credentials $cred = null ) {
		if ( is_null( $cred))
			$cred = credentials::getCurrentUser();

		if ( $cred) {
			// sys::dump( $cred);
			$client = new self(
				$cred->server,
				$cred->account,
				$cred->password

			);

			// if ( isset( \config::$exchange_verifySSL) && !\config::$exchange_verifySSL) {
			// 	$client->setCurlOptions([CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST  => false]);
			// 	sys::logger( 'ews\client :: disable verify SSL');

			// }

			return ( $client);

		}

		return ( false );

	}

	protected static function header_decode( $text) {
		$_texts = imap_mime_header_decode( $text);
		$_res = array();
		foreach ( $_texts as $_text)
			$_res[] = $_text->text;

		return ( implode( ' ', $_res));

	}

	protected static function ReplaceImap($txt) {
		$carimap = array("=2F", "=C3=A9", "=C3=A8", "=C3=AA", "=C3=AB", "=C3=A7", "=C3=A0", "=20", "=C3=80", "=C3=89", "=E2=80=8E", "=\r\n");
		$carhtml = array("/", "é", "è", "ê", "ë", "ç", "à", "&nbsp;", "À", "É", "", "" );
		$txt = str_replace($carimap, $carhtml, $txt);

		return $txt;

	}

	protected static function decodeMimeStr($string, $charset = 'UTF-8') {
		$newString = '';
		$elements = imap_mime_header_decode($string);
		for($i = 0; $i < count($elements); $i++) {
			if($elements[$i]->charset == 'default')
				$elements[$i]->charset = 'iso-8859-1';

			$newString .= iconv($elements[$i]->charset, $charset, $elements[$i]->text);

		}
		return $newString;

	}

	protected static function funnies( $string) {
		return str_replace(
			['’',	'…'],
			['\'',	'...'], $string);
	}

	static function default_folders() : array {
		return folders::$default_folders;

	}

	static function instance( credentials $cred = null ) {
		if ( $client = self::_instance( $cred)) {
			// if ( isset( \config::$exchange_timezone))
			// 	$client->setTimezone( \config::$exchange_timezone);

		}

		return ( $client);

	}

	static function instanceForDelete( credentials $cred = null ) {
		return ( self::_instance( $cred));

	}

	static function instanceForSync( credentials $cred = null ) {
		return ( self::_instance( $cred));

	}

	protected function _cache_prefix() {
		return strtolower( sprintf( '%s_%s',
			str_replace('\\', '_', $this->_account),
			preg_replace( '@[\\\/]@', '_', $this->_folder)));

	}

	protected function _cache_path( $uid, $txt = false) {
		if ( $txt) {
			return sprintf('%s%s_%s.txt',
				config::IMAP_CACHE(),
				$this->_cache_prefix(),
				$uid);

		}
		else {
			return sprintf('%s%s_%s.json',
				config::IMAP_CACHE(),
				$this->_cache_prefix(),
				$uid);

		}

	}

	protected function _flush_cache( string $uid = null) {
		if ( \is_null( $uid)) {
			if ( !config::$_imap_cache_flushing) return;

			$_path = config::IMAP_CACHE() . $this->_cache_prefix() . '*';
			$iterator = new \GlobIterator( $_path);
			foreach ($iterator as $item) {
				// \sys::logger( sprintf('flush <%s>  %s', $item->getRealPath(), __METHOD__));
				unlink( $item->getRealPath());

			}

			// \sys::logger( sprintf('flushed <%s>  %s', $_path, __METHOD__));

		}
		else {
			if ( file_exists($path = $this->_cache_path( $uid))) {
				unlink( $path);

			}

		}

	}

	protected function _getmessage( $msgno, $overview = false ) : \dvc\mail\message {
		$debug = false;
		// $debug = true;

		// HEADER
		// https://www.php.net/manual/en/function.imap-headerinfo.php#98809
		$_headers = imap_fetchheader( $this->_stream, $msgno, 0);
		$_headers_rfc822 = imap_rfc822_parse_headers( $_headers);
		// sys::dump( $_headers_rfc822, 'imap_rfc822_parse_headers', false);
		$_headerInfo = imap_headerinfo( $this->_stream, $msgno);

		if ( !$overview) {
			$overview = $this->_Overview( $msgno);

		}
		// sys::dump( $overview, 'overview');

		$overview = (object)$overview;

		/* add code here to get date, from, to, cc, subject... */
		$from = "";
		if ( isset( $_headers_rfc822->from ) && count( $_headers_rfc822->from )) {
			$afrom = array_shift( $_headers_rfc822->from );
			$from = $afrom->mailbox . "@" . $afrom->host;

			if ( isset( $afrom->personal )) {
				if ( preg_match( '/,/', $afrom->personal )) {
					$from = sprintf( '"%s" <%s>', $afrom->personal, $from);

				}
				else {
					$from = sprintf( '%s <%s>', $afrom->personal, $from);

				}

			}

		}

		$to = [];
		if ( isset( $_headers_rfc822->to ) && $_headers_rfc822->to) {
			foreach ( $_headers_rfc822->to as $e) {
				$s = '';
				if ( isset( $e->mailbox) && isset( $e->host))
					$s = $e->mailbox . "@" . $e->host;

				elseif ( isset( $e->mailbox))
					$s = $e->mailbox;

				if ( isset( $e->personal )) {
					if ( preg_match( '/,/', $e->personal )) {
						$s = sprintf( '"%s" <%s>', $e->personal, $s);

					}
					else {
						$s = sprintf( '%s <%s>', $e->personal, $s);

					}

				}

				$to[] = $s;

			}

		}
		$to = implode( ',', $to );

		$cc = [];
		if ( isset( $_headers_rfc822->cc ) && $_headers_rfc822->cc) {
			foreach ( $_headers_rfc822->cc as $e) {
				$s = '';
				if ( isset( $e->mailbox) && isset( $e->host))
					$s = $e->mailbox . "@" . $e->host;

				elseif ( isset( $e->mailbox))
					$s = $e->mailbox;

				if ( isset( $e->personal )) {
					if ( preg_match( '/,/', $e->personal )) {
						$s = sprintf( '"%s" <%s>', $e->personal, $s);

					}
					else {
						$s = sprintf( '%s <%s>', $e->personal, $s);

					}

				}

				$cc[] = $s;

			}

		}
		$cc = implode( ', ', $cc );

		if ( !isset( $_headers_rfc822->message_id)) {
			$_headers_rfc822->message_id = 'no-message-id';

		}

		$headerDate = '';
		if ( isset( $_headers_rfc822->date)) {
			$headerDate = $_headers_rfc822->date;

		}

		//~ sys::dump( $_headers_rfc822);
		if ( !isset( $_headers_rfc822->Subject)) {
			$_headers_rfc822->Subject = '(subject missing)';

		}

		$ret = new \dvc\mail\message;
		$ret->Subject = self::decodeMimeStr((string)$_headers_rfc822->Subject);

		$ret->From = self::decodeMimeStr((string)$from);
			$ea = new EmailAddress( $ret->From);
			$ret->fromEmail = $ea->email;

		$ret->To = self::decodeMimeStr((string)$to);
		$ret->CC = self::decodeMimeStr((string)$cc);
		$ret->MessageID = $_headers_rfc822->message_id;
		$ret->Recieved = $headerDate;
		$ret->headers = $_headerInfo;

		// sys::dump( $_headers_rfc822, 'imap_rfc822_parse_headers', true);
		// sys::dump( $_headers, 'imap_fetchheader', true);
		$ret->MSGNo = $_headerInfo->Msgno;
		$ret->Uid = imap_uid( $this->_stream, $_headerInfo->Msgno);
		$ret->answered = $_headerInfo->Answered == "A" ? 'yes' : 'no';
		$ret->seen = $_headerInfo->Unseen == "U" ? 'no' : 'yes';
		$ret->references = '';

		if ( isset($overview->in_reply_to)) $ret->in_reply_to = $overview->in_reply_to;
		if ( isset($overview->references)) $ret->references = $overview->references;
		if ( isset($overview->{'X-CMS-Draft'})) $ret->{'X-CMS-Draft'} = $overview->{'X-CMS-Draft'};

		$mess = new RawMessage( $this->_stream, $msgno );
		$ret->CharSet = $mess->charset;

		if ( $mess->messageHTML) {
			$ret->Body = $mess->messageHTML;
			$ret->BodyType = 'HTML';

		}
		else {
			$ret->BodyType = 'text';
			$ret->Body = $mess->message;

		}
		$ret->attachments = $mess->attachments;
		$ret->cids = $mess->cids;

		//~ if ( \currentUser::isDavid()) \sys::dump( $ret);
		if ( $debug) sys::logger( sprintf('exit : %s', __METHOD__));

		return ( $ret );

	}

	protected function _getmessageText( $msgno) {
		$debug = false;
		// $debug = true;

		$uid = \imap_uid( $this->_stream, $msgno);
		$txtFile = $this->_cache_path( $uid, true);
		if ( \file_exists( $txtFile)) {
			if ( $debug) \sys::logger( sprintf('<%s => from cache : %s> %s', $msgno, $txtFile, __METHOD__));
			return file_get_contents( $txtFile);

		}

		if ( $debug) \sys::logger( sprintf('<%s> %s', $msgno, __METHOD__));
		$msg = new RawMessage( $this->_stream, $msgno, RawMessage::PLAINTEXT);
		$text = '';
		if ( 'html' == $msg->messageType) {
			$text = \strings::html2text( $msg->messageHTML);

		}

		if ( !( strlen( $text) > 0)) {
			$text = $msg->message;

		}

		if ( $debug) \sys::logger( sprintf('<%s =>%s> : %s : %s', $msgno, $uid, \strlen( $text), __METHOD__));
		\file_put_contents( $txtFile, $text);

		return $text;

	}

	protected function _getMessageHeader( $id, $folder = "default" ) {
		//~ $stat = $this->_status( $folder );
		$total = imap_num_msg( $this->_stream);
		/**
		 * Reverse chunk sort in pages of 20
		 */
		$i = $total;
		$chunks = [];
		while ( $i > 0 ) {
			$end = $i;
			$i -= 19;
			$i = max([1, $i]);
			$chunks[] = implode( ',', range( $i, $end ));
			$i--;

		}

		foreach ( $chunks as $chunk) {
			// Fetch an overview for all messages in INBOX
			$overview = imap_fetch_overview( $this->_stream, $chunk, 0 );
			if ( count( $overview)) {
				foreach ( $overview as $msg) {
					if ( isset( $msg->message_id )) {
						if ( "{$msg->message_id}" == "{$id}" ) {
							// \sys::dump( $msg);
							return ( $msg);

						}

					}

				}

			}

		}

		if ( self::$debug) sys::logger( sprintf('not found : %s/%s : %s', $folder, $id, __METHOD__));

	}

	/*
	* get information for this specific email
	*/
	protected function _overview( $email_number = -1 ) : \dvc\mail\message {
		$debug = false;
		// $debug = true;

		if ( $email_number < 0 )
			return ( false );


		$ret = new \dvc\mail\message;
		$_cache = false;
		if ( $debug) \sys::logger( sprintf('<%s> [%s] %s', \application::timer()->elapsed(), $email_number, __METHOD__));
		$uid = imap_uid( $this->_stream, $email_number);
		if ( $debug) \sys::logger( sprintf('<%s> [%s:%s] %s', \application::timer()->elapsed(), $email_number, $uid, __METHOD__));

		$headers = imap_headerinfo( $this->_stream, $email_number, 1);
		$_cache = $this->_cache_path( $uid);
		if ( \file_exists( $_cache)) {
			$ret->fromJson( \file_get_contents( $_cache));
			// if ( isset( $headers->Unseen)) sys::logger( sprintf('seen <%s> : %s', $headers->Unseen, __METHOD__));
			if ( isset( $headers->Unseen)) {
				$ret->seen = ( 'U' == $headers->Unseen ? 'no' : 'yes');

			}

			if ( isset( $headers->Answered)) {
				$ret->answered = ( 'A' == $headers->Answered ? 'yes' : 'no');

			}

			// $forwarded = imap_fetchflag_full($this->_stream, $email_number, '$Forwarded');
			// \sys::logger( sprintf('<%s> %s', $forwarded, __METHOD__));


			// sys::logger( sprintf('seen <%s> : %s', $ret->seen, __METHOD__));
			if ( $debug) \sys::logger( sprintf('<cache:%s> [%s] %s', \application::timer()->elapsed(), $_cache, __METHOD__));
			return $ret;

		}

		// $headers = imap_headerinfo( $this->_stream, $email_number, 1);
		if ( $headers) {
			//~ print "<!-- " . print_r( $headers, TRUE ) . " -->\n";
			// sys::dump($headers);
			$ret->Uid = $uid;
			// $_cache = sprintf('%s%s_%s.json', config::IMAP_CACHE(), $this->_cache_prefix(), $ret->Uid);


			if ( isset( $headers->message_id)) $ret->MessageID = $headers->message_id;
			if ( isset( $headers->to)) {
				/**
				 *
				 * [to] => Array (
				 *		[0] => stdClass Object (
				 *		 	[personal] => Dingo Star
				*		 	[mailbox] => davbray
				*		 	[host] => gmail.com
				*		)
				*
				*		[1] => stdClass Object (
				*			[personal] => Digital Dave
				*			[mailbox] => davbray
				*			[host] => live.com
				*		)
				*
				* )
				*/
				// sys::dump($headers->to);
				$a = [];
				foreach( $headers->to as $to) {
					if ( isset( $to->personal)) {
						$name = $to->personal;
						if ( false != strstr( $name, "'")) {
							$name = sprintf( '"%s"', $name);

						}
						$a[] = sprintf('%s <%s@%s>', $name, $to->mailbox, $to->host);

					}
					else {
						if ( isset( $to->host)) {
							$a[] = sprintf('%s@%s', $to->mailbox, $to->host);

						}
						elseif ( isset( $to->mailbox)) {
							$a[] = $to->mailbox;

						}
						// sys::dump( $to);

					}

				}

				if ( $a) {
					$ret->To = implode( ',', $a);

				}

			}
			// sys::logger( sprintf('<headers> : %s', __METHOD__));

		}

		if ( $overview = imap_fetch_overview( $this->_stream, $email_number, 0)) {

			$msg = $overview[0];
			// sys::dump( $msg);
			if ( isset( $msg->seen)) $ret->seen = ( $msg->seen ? 'yes' : 'no' );
			if ( isset( $msg->answered)) $ret->answered = ( $msg->answered ? 'yes' : 'no' );

			if ( !$ret->To) {
				if ( isset( $msg->to)) $ret->To = self::decodeMimeStr((string)$msg->to);

			}

			if ( isset( $msg->subject)) $ret->Subject = self::decodeMimeStr($msg->subject);

			if ( isset( $msg->from)) {
				$ret->From = self::decodeMimeStr($msg->from);
				$ea = new EmailAddress( $ret->From);
				$ret->fromEmail = $ea->email;

			}

			if ( isset( $msg->date)) {
				$ret->Recieved = $msg->date;

				if ( preg_match( "/^Date:/", $msg->date ))
					$ret["Recieved"] = preg_replace( "/^Date:/", "", $msg->date );

			}

			if ( isset( $msg->in_reply_to)) $ret->in_reply_to = $msg->in_reply_to;
			if ( isset( $msg->references)) $ret->references = $msg->references;
			// sys::logger( sprintf('<overview> : %s', __METHOD__));


		}

		$_headers = imap_fetchheader( $this->_stream, $email_number);
		$headerLines = explode( "\n", $_headers);
		foreach ( $headerLines as $l) {
			// sys::logger($l);
			if ( preg_match( '/^Message-ID/', $l)) {
				/**
				 * I'm sure this is to cope with a one time bug ...
				 */
				if ( preg_match( '@"@', $ret->MessageID)) {
					$x = explode( ':', $l);
					$ret->MessageID = trim( array_pop( $x));
					// sys::dump( explode( "\n", $ret->MessageID),'Invalid MessageID');

				}

			}
			elseif ( preg_match( '/^X-CMS-Draft/', $l)) {
				$x = explode( ':', $l);
				$ret->{'X-CMS-Draft'} = trim( array_pop( $x));

			}

		}
		// sys::dump( imap_fetchheader( $this->_stream, $email_number));
		// sys::dump($ret);
		// sys::dump($overview);

		// /* output the email body */
		// $message = imap_fetchbody($inbox,$email_number,1);
		// $output.= '<div class="body"><pre>'.$message.'</pre></div>';

		if ( $_cache) {
			if ( file_exists( $_cache))
				\unlink( $_cache);

			\file_put_contents( $_cache, $ret->asJson());

		}

		return ( $ret );

	}

	protected function __construct( string $server, string $account, string $password) {
		if ( preg_match('@^ssl://@', $server)) {
			$this->_port = '993';
			$this->_secure = 'ssl';
			$server = preg_replace( '@^ssl://@', '', $server);

		}

		$this->_server = $server;
		$this->_account = $account;
		$this->_password = $password;

	}

	public function __destruct() {
		$this->close();

	}

	public function clearflag( $id, $flag) {
		$ret = false;
		$total = imap_num_msg( $this->_stream );
		$result = imap_fetch_overview( $this->_stream, "1:{$total}", 0 );
		foreach ( $result as $msg) {
			if ( isset($msg->message_id)) {
				if ( "{$msg->message_id}" == "{$id}" ) {

					$ret = $this->clearflagByUID( \imap_uid( $this->_stream, $msg->msgno), $flag);
					// imap_clearflag_full( $this->_stream, $msg->msgno, $flag);
					// $ret = true;

					break;

				}

			}

		}

		return $ret;

	}

	public function clearflagByUID( $uid, $flag) {
		// \sys::logger( sprintf('<%s> %s', $uid, __METHOD__));
		$this->_flush_cache( $uid);
		return imap_clearflag_full( $this->_stream, $uid, $flag, ST_UID);


	}

	public function close( $flag = 0) {
		if ( $this->_open ) {
			/* close the connection */
			imap_close( $this->_stream, $flag);
			$this->_open = false;

			if ( CL_EXPUNGE == $flag) {
				$this->_flush_cache();

			}

		}

	}

	public function createmailbox( $fldr) : bool {
		return @imap_createmailbox( $this->_stream, imap_utf7_encode( sprintf( '{%s}%s', $this->_server, $fldr)));

	}

	public function deletemailbox( $fldr) {
		return @imap_deletemailbox( $this->_stream, imap_utf7_encode( sprintf( '{%s}%s', $this->_server, $fldr)));

	}

	public function empty_trash() {
		$total = imap_num_msg( $this->_stream );
		return imap_delete( $this->_stream, "1:{$total}", 0);	// returns true

	}

	public function finditems( array $params) : array {
		$debug = false;
		// $debug = true;

		$options = (object)array_merge([
			'deep' => false,
			'page' => 0,
			'pageSize' => 20,
			'allPages' => false,

		], $params);

		if ( $debug) \sys::logger( sprintf('<%s> %s', \application::timer()->elapsed(), __METHOD__));
		$data = [
			'msgCount' => imap_num_msg( $this->_stream)

		];
		if ( $debug) \sys::logger( sprintf('<msgCount : %s> <%s> %s', $data['msgCount'], \application::timer()->elapsed(), __METHOD__));

		$ret = [];

		// \sys::logger( sprintf('<msgCount:%s> %s', $data['msgCount'], __METHOD__));
		if ( $data['msgCount'] > 500) {
			$start = max( $data['msgCount'] - ((int)$options->page * (int)$options->pageSize) - ((int)$options->page > 0 ? 1 : 0), 0);
			$emails = \range( $start, max( $start - $options->pageSize, 0), -1 );
			foreach ($emails as $email_number) {
				// \sys::logger( sprintf('<%s> %s', $email_number, __METHOD__));

				$msg = $this->_overview( $email_number);
				$msg->Folder = $this->_folder;
				$ret[] = $msg;

			}


		}
		else {
			if ($emails = imap_sort( $this->_stream, SORTARRIVAL, true, SE_NOPREFETCH )) {
				if ( $debug) \sys::logger( sprintf('<%s> [sorted] %s', \application::timer()->elapsed(), __METHOD__));
				// sys::dump( $emails);
				$start = $i = 0;
				$_start = (int)$options->page * (int)$options->pageSize;
				// \sys::logger( sprintf('<%s/%s> %s', $start, $_start, __METHOD__));

				foreach( $emails as $email_number) {
					if ( $debug) \sys::logger( sprintf('<%s> %s', $email_number, __METHOD__));

					if ( $start++ >= $_start ) {
						if ( $i++ >= $options->pageSize ) break;
						$msg = $this->_overview( $email_number);
						// \sys::logger( sprintf('<%s> [%s] %s', \application::timer()->elapsed(), $msg->Uid, __METHOD__));
						$msg->Folder = $this->_folder;
						$ret[] = $msg;

					}

				}

			}

		}

		if ( $debug) \sys::logger( sprintf('<%s> [fetched] %s', \application::timer()->elapsed(), __METHOD__));
		return $ret;

	}

	public function folders( $spec = '*') {
		return imap_list( $this->_stream, '{'.$this->_server.'}', $spec );

	}

	public function getmessage( $id, $folder = "default") {
		$ret = false;
		if ( $this->open( true, $folder )) {
			if ( $msg = $this->_getMessageHeader( $id, $folder )) {
				$ret = $this->_getmessage( $msg->msgno, $msg);
				$ret->Folder = $folder;

			}

			if ( !$ret) {
				if ( self::$debug) {
					sys::logger( sprintf('not found : %s/%s : %s', $folder, $id, __METHOD__));

				}

			}

			$this->close();

		}
		else {
			sys::logger( sprintf( 'failed to open folder : %s :: %s : %s', $folder, $this->_error, __METHOD__));

		}

		return ( $ret );

	}

	public function getmessageByMsgNo( $msgno, $folder = "default") {
		$ret = false;
		if ( $this->open( true, $folder )) {
			if ( $msgno > 0 ) {
				$ret = $this->_getmessage( $msgno);
				$ret->Folder = $folder;

			}

			if ( !$ret) {
				if ( self::$debug) {
					sys::logger( sprintf('not found : %s/%s : %s', $folder, $id, __METHOD__));

				}

			}

			$this->close();

		}
		else {
			sys::logger( sprintf( 'failed to open folder : %s :: %s : %s', $folder, $this->_error, __METHOD__));

		}

		return ( $ret );

	}

	public function getmessageByUID( $uid, $folder = "default") {
		$ret = false;
		if ( $this->open( true, $folder )) {
			$msgno = imap_msgno( $this->_stream, $uid);
			if ( $msgno > 0 ) {
				if ( $ret = $this->_getmessage( $msgno)) {
					$ret->Folder = $folder;
					if ( self::$debug) sys::logger( sprintf( 'retrieved msgno via imap : %s :: %s : %s', $uid, $folder, __METHOD__));

				}

			}

			if ( !$ret) {
				if ( self::$debug) sys::logger( sprintf('not found : %s/%s : %s', $folder, $uid, __METHOD__));

			}

			$this->close();

		}
		else {
			sys::logger( sprintf( 'failed to open folder : %s :: %s : %s', $folder, $this->_error, __METHOD__));

		}

		return ( $ret );

	}

	public function Info( $folder = "default") {
		$ret = false;
		if ( $this->open( true, $folder )) {
			$ret = imap_mailboxmsginfo( $this->_stream);
			$this->close();

		}
		else {
			sys::logger( sprintf( 'failed to open folder : %s :: %s : %s', $folder, $this->_error, __METHOD__));

		}

		return ( $ret );

	}

	public function move_message( $id, $target) {
		$ret = false;
		$total = imap_num_msg( $this->_stream );
		$result = imap_fetch_overview( $this->_stream, "1:{$total}", 0 );
		foreach ( $result as $msg) {
			if ( !isset($msg->message_id)) continue;

			if ( "{$msg->message_id}" == "{$id}" ) {
				$ret = $this->move_message_byUID( \imap_uid(  $this->_stream, $msg->msgno));
				// imap_mail_move( $this->_stream, $msg->msgno, $target);
				// imap_expunge( $this->_stream);
				// $ret = sprintf( 'moved to %s : %s', $target, __METHOD__ );

				break;

			}

		}

		return $ret;

	}

	public function move_message_byUID( $uid, $target) {
		$this->_flush_cache( $uid);
		if ($ret = imap_mail_move( $this->_stream, $uid, $target, CP_UID)) {
			imap_expunge( $this->_stream);
			$this->_flush_cache();	// generally flush cache after expunge if that is enabled

		}

		return $ret;

	}

	public function open( $full = true, &$folder = 'default' ) {
		$debug = self::$debug;
		// $debug = true;

		if ( $this->_server) {
			if ( $folder == 'default' )
				$folder = self::INBOX;

			$server = sprintf( '{%s:%s/%s/novalidate-cert}',
				$this->_server,
				$this->_port,
				$this->_secure
			);

			/**
			 * https://wordpress.org/support/topic/exchange-imap-kerberos-error-solved
			 *
			 * Exchange is notoriously bad at IMAP. however i found one post that didn't require recompiling php without Kerberos.
			 *
			 * Connecting with {myipaddress:143/service=imap/tls/novalidate-cert}
			 * Returned this error.
			 *
			 * PHP Notice: Unknown: Kerberos error: No credentials cache found (try
			 * running kinit) for ldnwpexch11.emea.akqa.local (errflg=1) in Unknown on
			 * line 0
			 *
			 * Which meant either creating tempory Kerberos keys or recompiling php.
			 *
			 * This however solved the problem just nicely
			 * In postieIMAP.php edit the imap_open command to include the following.
			 *
			 */
			$nogssapi = ['DISABLE_AUTHENTICATOR' => 'GSSAPI'];

			if ( $full ) {
				if ( $debug) sys::logger( sprintf( 'imap_open( %s, %s, %s)',
					$server . $folder, $this->_account, 'password' ));

				/* connect server */
				if ( $this->_stream = @imap_open($server . $folder, $this->_account, $this->_password, 0, 1, $nogssapi)) {
					$this->_open = true;
					$this->_folder = $folder;
					if ( $debug) sys::logger( sprintf( 'successfully opened:imap_open(%s,%s,%s)',
						$server . $folder,
						$this->_account,
						'password'));

				}
				else {
					$this->_error = sprintf( 'Cannot connect to %s :: %s', $server, imap_last_error());
					sys::logger( $this->_error);

				}

			}
			else {
				if ( $debug) sys::logger( sprintf( 'imap_open( %s, %s, %s, OP_HALFOPEN)',
					$server . $folder, $this->_account, 'password' ));

				/* connect server */
				if ( $this->_stream = @imap_open( $server . $folder, $this->_account, $this->_password, OP_HALFOPEN, 1, $nogssapi )) {
					$this->_open = true;
					$this->_folder = $folder;
					if ( $debug) sys::logger( sprintf( 'successfully half-opened:imap_open(%s,%s,%s)',
						$server . $folder,
						$this->_account,
						'password'));

				} else {
					$this->_error = sprintf( 'Cannot connect to %s :: %s', $server, imap_last_error());

				}

			}

			return ( $this->_open );

		}
		else {
			$this->_error = 'invalid server';

		}

		return false;

	}

	public function search( array $params) : array {
		$options = array_merge([
			'criteria' => [],
			'charset' => 'US-ASCII',
			'term' => '',
			'time_limit' => 120

		], $params);

		$ret = [];
		$results = [];
		foreach ( $options['criteria'] as $criteria) {

			set_time_limit( $options['time_limit']);

			if ( $emails = imap_search( $this->_stream, $criteria, SE_FREE, $options['charset'])) {
				foreach( $emails as $email_number) {
					if ( !in_array( $email_number, $results)) {
						if ( $options['term']) {
							if ( preg_match( '@^TEXT @', $criteria)) {
							// 	\sys::logger( sprintf('<%s> %s', $criteria, __METHOD__));
								$txt = $this->_getmessageText( $email_number);
								if ( false === strpos( $txt, $options['term'] )) {
									// \sys::logger( sprintf('<%s> not found in text %s', $options['term'], __METHOD__));
									continue;

								}


							}

						}

						$results[] = $email_number;

					}

				}

			}

		}

		foreach( $results as $email_number) {
			if ($msg = $this->_overview( $email_number)) {
				$msg->Folder = $this->_folder;
				$ret[] = $msg;

			}

		}

		return $ret;

	}

	public function server() {
		return $this->_server;

	}

	public function stream() {
		return $this->_stream;

	}

	public function setflag( $id, $flag) {
		$ret = false;
		$total = imap_num_msg( $this->_stream );
		$result = imap_fetch_overview( $this->_stream, "1:{$total}", 0 );
		foreach ( $result as $msg) {
			if ( isset($msg->message_id)) {
				if ( "{$msg->message_id}" == "{$id}" ) {
					$ret = $this->setflagByUID( imap_uid( $this->_stream, $msg->msgno), $flag);
					// imap_setflag_full( $this->_stream, $msg->msgno, $flag);
					// $ret = true;
					break;

				}

			}

		}

		return $ret;

	}

	public function setflagByUID( $uid, $flag) {
		$this->_flush_cache( $uid);
		return imap_setflag_full( $this->_stream, $uid, $flag, ST_UID);

	}

	public function subscribe( $fldr) {
		return imap_subscribe($this->_stream, sprintf( '{%s}%s', $this->_server, $fldr));

	}

}

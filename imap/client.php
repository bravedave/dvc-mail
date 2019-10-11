<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/

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

	static protected function _instance( credentials $cred = null ) {
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

	protected function _getmessage( $msgno, $overview = false ) : \dvc\mail\message {
		$debug = false;
		// $debug = true;

		// HEADER
		$headers = imap_headerinfo( $this->_stream, $msgno, 1);
		if ( !$overview) {
			$overview = $this->_Overview( $msgno);

		}

		$overview = (object)$overview;

		/* add code here to get date, from, to, cc, subject... */
		$from = "";
		if ( isset( $headers->from ) && count( $headers->from )) {
			$afrom = array_shift( $headers->from );
			$from = $afrom->mailbox . "@" . $afrom->host;
			if ( isset( $afrom->personal ))
				$from = $afrom->personal . " <$from>";

		}

		// die( "<pre>" . print_r( $headers, TRUE ) . "</pre>");
		$mess = new RawMessage( $this->_stream, $msgno );
		// die( "<pre>" . print_r( $mess, TRUE ) . "</pre>");

		$to = [];
		if ( isset( $headers->to ) && $headers->to) {
			foreach ( $headers->to as $e) {
				$s = '';
				if ( isset( $e->mailbox) && isset( $e->host))
					$s = $e->mailbox . "@" . $e->host;

				elseif ( isset( $e->mailbox))
					$s = $e->mailbox;

				if ( isset( $e->personal ))
					$s = sprintf( '%s <%s>', $e->personal, $s);

				$to[] = $s;

			}

		}
		$to = implode( ',', $to );

		$cc = [];
		if ( isset( $headers->cc ) && $headers->cc) {
			foreach ( $headers->cc as $e) {
				$s = '';
				if ( isset( $e->mailbox) && isset( $e->host))
					$s = $e->mailbox . "@" . $e->host;

				elseif ( isset( $e->mailbox))
					$s = $e->mailbox;

				if ( isset( $e->personal ))
					$s = sprintf( '%s <%s>', $e->personal, $s);

				$cc[] = $s;

			}

		}
		$cc = implode( ', ', $cc );

		//~ sys::dump( $headers);
		if ( !isset( $headers->subject)) {
			$headers->Subject = '(subject missing)';

		}

		if ( !isset( $headers->message_id)) {
			$headers->message_id = 'no-message-id';

		}

		$headerDate = '';
		if ( isset( $headers->date)) {
			$headerDate = $headers->date;

		}

		$ret = new \dvc\mail\message;
		if ( isset( $headers->subject)) {
			$ret->Subject = self::decodeMimeStr((string)$headers->subject);

		}

		$ret->From = self::decodeMimeStr((string)$from);
			$ea = new EmailAddress( $ret->From);
			$ret->fromEmail = $ea->email;

		$ret->To = self::decodeMimeStr((string)$to);
		$ret->CC = self::decodeMimeStr((string)$cc);
		$ret->MessageID = $headers->message_id;
		$ret->MSGNo = $headers->Msgno;
		$ret->Uid = imap_uid( $this->_stream, $headers->Msgno);
		$ret->Recieved = $headerDate;
		$ret->headers = $headers;
		$ret->CharSet = $mess->charset;
		$ret->seen = $headers->Unseen == "U" ? 'no' : 'yes';
		$ret->references = '';
		if ( $mess->messageHTML) {
			// sys::logger('has messageHTML');
			// $ret->Body = utf8_decode( $mess->messageHTML);
			$ret->Body = $mess->messageHTML;

		}
		else {
			sys::logger('no messageHTML');

		}

		if ( $ret->Body) {
			$ret->BodyType = 'HTML';

		}
		else {
			$ret->BodyType = 'text';
			// $ret->Body = utf8_decode( $mess->message);
			$ret->Body = $mess->message;

		}
		$ret->attachments = $mess->attachments;
		$ret->cids = $mess->cids;

		if ( isset($overview->in_reply_to)) $ret->in_reply_to = $overview->in_reply_to;
		if ( isset($overview->references)) $ret->references = $overview->references;
		if ( isset($overview->{'X-CMS-Draft'})) $ret->{'X-CMS-Draft'} = $overview->{'X-CMS-Draft'};

		//~ if ( \currentUser::isDavid()) \sys::dump( $ret);
		if ( $debug) sys::logger( sprintf('exit : %s', __METHOD__));


		return ( $ret );

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
							return ( $msg);

						}

					}

				}

			}

		}

		if ( self::$debug) sys::logger( sprintf('not found : %s/%s : %s', $folder, $id, __METHOD__));

	}

	protected function _overview( $email_number = -1 ) : \dvc\mail\message {
		if ( $email_number < 0 )
			return ( false );

		/* get information for this specific email */
		$overview = imap_fetch_overview( $this->_stream, $email_number, 0);
		//~ $message = imap_fetchbody($stream,$email_number,1);
		$headers = imap_headerinfo( $this->_stream, $email_number, 1);
		// sys::dump( $headers);
		//~ print "<!-- " . print_r( $headers, TRUE ) . " -->\n";
		// $ret = array(
		// 	'seen' => '',
		// 	'Subject' => '',
		// 	'From' => '',
		// 	'To' => '',
		// 	'MessageID' => '',
		// 	'xmessage_id' => '',
		// 	'Recieved' => '',
		// 	'in_reply_to' => '',
		// 	'references' => '',
		// 	'X-CMS-Draft' => '' );

		$ret = new \dvc\mail\message;

		if ( isset( $overview[0])) {
			$msg = $overview[0];
			// sys::dump( $msg);
			if ( isset( $msg->seen)) $ret->seen = ( $msg->seen ? 'yes' : 'no' );

			if ( isset( $msg->to)) $ret->To = self::decodeMimeStr((string)$msg->to);

			if ( isset( $msg->subject)) $ret->Subject = self::decodeMimeStr($msg->subject);

			if ( isset( $msg->from)) {
				$ret->From = self::decodeMimeStr($msg->from);
				$ea = new EmailAddress( $ret->From);
				$ret->fromEmail = $ea->email;

			}

			if ( isset( $headers->message_id)) $ret->MessageID = $headers->message_id;

			if ( isset( $msg->message_id)) $ret->xmessage_id = $msg->message_id;

			if ( isset( $msg->date)) {
				$ret->Recieved = $msg->date;

				if ( preg_match( "/^Date:/", $msg->date ))
					$ret["Recieved"] = preg_replace( "/^Date:/", "", $msg->date );

			}

			if ( isset( $msg->in_reply_to)) $ret->in_reply_to = $msg->in_reply_to;
			if ( isset( $msg->references)) $ret->references = $msg->references;

		}

		$headerLines = explode( "\n", imap_fetchheader( $this->_stream, $email_number));
		foreach ( $headerLines as $l) {
			//~ sys::logger($l);
			if ( preg_match( '/^X-CMS-Draft/', $l)) {
				$x = explode( ':', $l);
				$ret->{'X-CMS-Draft'} = trim( array_pop( $x));

			}

		}
		// sys::dump( imap_fetchheader( $this->_stream, $email_number));
		// sys::dump($ret);
		// sys::dump($headers);
		// sys::dump($overview);

		// /* output the email body */
		// $message = imap_fetchbody($inbox,$email_number,1);
		// $output.= '<div class="body"><pre>'.$message.'</pre></div>';

		$ret->Uid = imap_uid( $this->_stream, $headers->Msgno);

		return ( $ret );

	}

	protected function __construct( $server, $account, $password) {
		$this->_server = $server;
		$this->_account = $account;
		$this->_password = $password;


	}

	public function __destruct() {
		$this->close();

	}

	public function close() {
		if ( $this->_open ) {
			/* close the connection */
			imap_close($this->_stream);
			$this->_open = false;

		}

	}

	public function createmailbox( $fldr) : bool {
		return @imap_createmailbox( $this->_stream, imap_utf7_encode( sprintf( '{%s}%s', $this->_server, $fldr)));

	}

	public function deletemailbox( $fldr) {
		return @imap_deletemailbox( $this->_stream, imap_utf7_encode( sprintf( '{%s}%s', $this->_server, $fldr)));

	}

	public function finditems( array $params) : array {
		$options = (object)array_merge([
			'deep' => false,
			'page' => 0,
			'pageSize' => 20,
			'allPages' => false

		], $params);

		$ret = [];

		if ($emails = imap_sort( $this->_stream, SORTARRIVAL, true, SE_NOPREFETCH )) {
			// sys::dump( $emails);
			$start = $i = 0;
			$_start = (int)$options->page * (int)$options->pageSize;
			foreach( $emails as $email_number) {
				if ( $start++ >= $_start ) {
					if ( $i++ >= $options->pageSize ) break;
					$msg = $this->_overview( $email_number);
					$msg->Folder = $this->_folder;
					$ret[] = $msg;

				}

			}

		}

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
					sys::logger( sprintf( 'retrieved msgno via imap : %s :: %s : %s', $uid, $folder, __METHOD__));

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

	public function move_message( $id, $target) {
		$ret = false;
		$total = imap_num_msg( $this->_stream );
		$result = imap_fetch_overview( $this->_stream, "1:{$total}", 0 );
		foreach ( $result as $msg) {
			if ( "{$msg->message_id}" == "{$id}" ) {
				imap_mail_move( $this->_stream, $msg->msgno, $target);
				imap_expunge( $this->_stream);
				$ret = sprintf( 'moved to %s : %s', $target, __METHOD__ );
				break;

			}

		}

		return $ret;

	}

	public function open( $full = true, &$folder = 'default' ) {
		$debug = false;
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
			$nogssapi = array("DISABLE_AUTHENTICATOR" => "GSSAPI");

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
					imap_setflag_full( $this->_stream, $msg->msgno, $flag);
					$ret = true;
					break;

				}

			}

		}

		return $ret;

	}

	public function subscribe( $fldr) {
		return imap_subscribe($this->_stream, sprintf( '{%s}%s', $this->_server, $fldr));

	}

}

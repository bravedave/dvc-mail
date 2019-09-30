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

class client {
	protected $_account = '';

	protected $_error = '';

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
			// \sys::dump( $cred);
			$client = new self(
				$cred->server,
				$cred->account,
				$cred->password

			);

			// if ( isset( \config::$exchange_verifySSL) && !\config::$exchange_verifySSL) {
			// 	$client->setCurlOptions([CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST  => false]);
			// 	\sys::logger( 'ews\client :: disable verify SSL');

			// }

			return ( $client);

		}

		return ( false );

	}

	static function instance( credentials $cred = null ) {
		if ( $client = self::_instance( $cred)) {
			if ( isset( \config::$exchange_timezone))
				$client->setTimezone( \config::$exchange_timezone);

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

	protected function _Overview( $email_number = -1 ) : \dvc\mail\message {
		if ( $email_number < 0 )
			return ( false );

		/* get information for this specific email */
		$overview = imap_fetch_overview( $this->_stream, $email_number, 0);
		//~ $message = imap_fetchbody($stream,$email_number,1);
		$headers = imap_headerinfo( $this->_stream, $email_number, 1);
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
			if ( isset( $overview[0]->seen)) $ret->seen = ( $overview[0]->seen ? 'yes' : 'no' );

			if ( isset( $overview[0]->to)) $ret->To = (string)$overview[0]->to;

			if ( isset( $overview[0]->subject)) $ret->Subject = $overview[0]->subject;

			if ( isset( $overview[0]->from)) $ret->From = $this->ReplaceImap( imap_utf8($overview[0]->from));

			if ( isset( $headers->message_id)) $ret->MessageID = $headers->message_id;

			if ( isset( $overview[0]->message_id)) $ret->xmessage_id = $overview[0]->message_id;

			if ( isset( $overview[0]->date)) {
				$ret->Recieved = $overview[0]->date;

				if ( preg_match( "/^Date:/", $overview[0]->date ))
					$ret["Recieved"] = preg_replace( "/^Date:/", "", $overview[0]->date );

			}

			if ( isset( $overview[0]->in_reply_to)) $ret->in_reply_to = $overview[0]->in_reply_to;
			if ( isset( $overview[0]->references)) $ret->references = $overview[0]->references;

		}

		$headerLines = explode( "\n", imap_fetchheader( $this->_stream, $email_number));
		foreach ( $headerLines as $l) {
			//~ \sys::logger($l);
			if ( preg_match( '/^X-CMS-Draft/', $l)) {
				$x = explode( ':', $l);
				$ret->{'X-CMS-Draft'} = trim( array_pop( $x));

			}

		}
		//~ \sys::dump( imap_fetchheader( $this->_stream, $email_number));
		//~ \sys::dump($ret);
		//~ \sys::dump($headers);
		//~ \sys::dump($overview);

		//~ /* output the email body */
		//~ $message = imap_fetchbody($inbox,$email_number,1);
		//~ $output.= '<div class="body"><pre>'.$message.'</pre></div>';

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

	public function finditems( array $params) : array {
		$options = (object)array_merge([
			'deep' => false,
			'page' => 0,
			'pageSize' => 20,
			'allPages' => false

		], $params);

		$ret = [];

		if ($emails = imap_sort( $this->_stream, SORTARRIVAL, TRUE, SE_NOPREFETCH )) {
			$i = 0;
			foreach($emails as $email_number) {
				if ( $i++ > 9 ) break;
				$ret[] = $this->_Overview($email_number);

			}

		}

		// \sys::dump( $ret);

		return $ret;

	}

	public function folders( $spec = '*') {
		return imap_list( $this->_stream, '{'.$this->_server.'}', $spec );

	}

	public function open( $full = true, $folder = 'default' ) {
		$debug = false;
		$debug = true;

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
				if ( $debug) \sys::logger( sprintf( 'imap_open( %s, %s, %s)',
					$server . $folder, $this->_account, 'password' ));

				/* connect server */
				if ( $this->_stream = @imap_open($server . $folder, $this->_account, $this->_password, 0, 1, $nogssapi)) {
					$this->_open = true;
					if ( $debug) \sys::logger( sprintf( 'successfully opened:imap_open(%s,%s,%s)',
						$server . $folder,
						$this->_account,
						'password'));

				}
				else {
					$this->_error = sprintf( 'Cannot connect to %s :: %s', $server, imap_last_error());
					\sys::logger( $this->_error);

				}

			}
			else {
				if ( $debug) \sys::logger( sprintf( 'imap_open( %s, %s, %s, OP_HALFOPEN)',
					$server . $folder, $this->_account, 'password' ));

				/* connect server */
				if ( $this->_stream = @imap_open( $server . $folder, $this->_account, $this->_password, OP_HALFOPEN, 1, $nogssapi ))
					$this->_open = true;

				else
					$this->_error = sprintf( 'Cannot connect to %s :: %s', $server, imap_last_error());

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

}

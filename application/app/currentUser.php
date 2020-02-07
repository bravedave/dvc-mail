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

use dvc\imap\account;

abstract class currentUser extends dvc\currentUser {
	static public function exchangeAuth() {
		/**
		 * return the credentials of the current account
		 * */
		if ( dvc\ews\account::$ENABLED) {
			$creds = new dvc\mail\credentials(
				dvc\ews\account::$USERNAME,
				dvc\ews\account::$PASSWORD,
				dvc\ews\account::$SERVER);

			$creds->interface = dvc\mail\credentials::ews;

		}

		return null;

	}

	static function name() {
		return ( self::user()->name) ;

	}

	static public function email() {
		return self::user()->email;

	}

	static public function option( $key, $value = null) {
		if ( self::user()->valid())
			return ( self::user()->option( $key, $value));

		return ( false);

	}

	static public function mailer() {
		/*
		 *	Return the appropriate PHP-Mailer object
		 */
		$mail = \sys::mailer();
		$mail->From     = currentUser::email();
		$mail->FromName = currentUser::name();
		$mail->Sender	= $mail->From;

		$mail->isSMTP(); // use smtp with server set to mail

		if ( account::$SMTP_SERVER) {
			$mail->Host = account::$SMTP_SERVER;

		}

		if ( account::$SMTP_PORT) {
			$mail->Port = account::$SMTP_PORT;

		}

		if ( account::$USERNAME && account::$PASSWORD) {
			$mail->SMTPAuth = true;
			$mail->Username = account::$SMTP_USERNAME;
			$mail->Password = account::$SMTP_PASSWORD;

		}

		return ( $mail);

	}

}

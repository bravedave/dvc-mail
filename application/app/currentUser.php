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

	static public function option( $key, $value = null) {
		if ( self::user()->valid())
			return ( self::user()->option( $key, $value));

		return ( false);

	}

}

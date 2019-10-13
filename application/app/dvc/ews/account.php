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

namespace dvc\ews;
use bCrypt;

abstract class account {
	static $SERVER = '';
	static $USERNAME = '';
	static $PASSWORD = '';
	static $TYPE = '';
	static $ENABLED = false;

    static function config() {
		return config::EWS_DATA() . 'ews-account.json';

	}

	static function account_init() {
        if ( file_exists( $config = self::config())) {
			$j = json_decode( file_get_contents( $config));

			if ( isset( $j->server)) self::$SERVER = $j->server;
			if ( isset( $j->type)) self::$TYPE = $j->type;
			if ( isset( $j->username)) self::$USERNAME = $j->username;
			if ( isset( $j->password)) self::$PASSWORD = bCrypt::decrypt( $j->password);

			self::$ENABLED = ( (bool)self::$SERVER && (bool)self::$USERNAME && (bool)self::$PASSWORD);

		}

	}

}

account::account_init();

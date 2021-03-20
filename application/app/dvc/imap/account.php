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
use dvc\bCrypt;

abstract class account {
	static $SERVER = '';
	static $TYPE = '';
	static $NAME = '';
	static $EMAIL = '';
	static $USERNAME = '';
	static $PASSWORD = '';
	static $SMTP_SERVER = '';
	static $SMTP_PORT = '';
	static $SMTP_USERNAME = '';
	static $SMTP_PASSWORD = '';
	static $PROFILE = '';

	static $ENABLED = false;

    static function config() {
		return config::IMAP_DATA() . 'imap-account.json';

	}

    static function profile( string $profile) : string {
		if ( $_profile = preg_replace( '@[^0-9a-zA-Z]@', '', $profile)) {
			return sprintf( '%simap-profile-%s.json', config::IMAP_DATA(), $_profile);

		}

		return '';

	}

    static function profiles() : array {
		$ret = [];

		$iterator = new \Globiterator( sprintf( '%simap-profile-*.json', config::IMAP_DATA()));
		foreach ( $iterator as $config) {
			$j = json_decode( file_get_contents( $config));
			if ( isset( $j->profile)) {
				$ret[] = (object)[
					'name' => $config->getFilename(),
					'profile' => $j->profile,
					'path' => $config->getPathname(),

				];

			}

		}

		return $ret;

	}

	static function account_init() {
        if ( file_exists( $config = self::config())) {
			$j = json_decode( file_get_contents( $config));

			if ( isset( $j->server)) self::$SERVER = $j->server;
			if ( isset( $j->type)) self::$TYPE = $j->type;
			if ( isset( $j->name)) self::$NAME = $j->name;
			if ( isset( $j->email)) self::$EMAIL = $j->email;
			if ( isset( $j->username)) self::$USERNAME = $j->username;
			if ( isset( $j->password)) self::$PASSWORD = bCrypt::decrypt( $j->password);
			if ( isset( $j->smtp_server)) self::$SMTP_SERVER = $j->smtp_server;
			if ( isset( $j->smtp_port)) self::$SMTP_PORT = $j->smtp_port;
			if ( isset( $j->smtp_username)) self::$SMTP_USERNAME = $j->smtp_username;
			if ( isset( $j->smtp_password)) self::$SMTP_PASSWORD = bCrypt::decrypt( $j->smtp_password);
			if ( isset( $j->profile)) self::$PROFILE = $j->profile;

			self::$ENABLED = ( (bool)self::$SERVER && (bool)self::$USERNAME && (bool)self::$PASSWORD);

		}

	}

}

account::account_init();

// \sys::dump( account::$SERVER);

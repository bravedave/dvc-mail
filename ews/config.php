<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/

	*/
namespace dvc\ews;
use bCrypt;

abstract class config extends \config {
	const ews_route = 'ews';
	const imap_route = 'imap';

	static $WEBNAME = 'EWS Interface for DVC';

	static protected $_EWS_VERSION = 0;

	static function EWS_DATA() {
		$data = self::dataPath() . DIRECTORY_SEPARATOR . 'ews' . DIRECTORY_SEPARATOR;

		if ( ! is_dir( $data)) {
			mkdir( $data);
			chmod( $data, 0777);

		}

		if ( ! is_writable( $data))
			throw new Exception( $data . ' is not writable, please update permissions to allow');

		return ( $data);

	}

	static protected function ews_config() {
		return sprintf( '%s%sews.json', self::EWS_DATA(), DIRECTORY_SEPARATOR);

	}

	static function ews_init() {
		if ( file_exists( $config = self::ews_config())) {
			$j = json_decode( file_get_contents( $config));

			if ( isset( $j->ews_version)) self::$_EWS_VERSION = (float)$j->ews_version;

		}

	}

}

config::ews_init();

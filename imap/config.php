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

namespace dvc\imap;
// use bCrypt;

abstract class config extends \config {
	const imap_route = 'imap';

	static $WEBNAME = 'IMAP Interface for DVC';

	static protected $_IMAP_VERSION = 0;

	static protected $_imap_cleaned_up = false;
	static protected $_imap_cache_max_age = 86400;

	static function IMAP_CACHE() {
		$data = self::IMAP_DATA() . '_cache' . DIRECTORY_SEPARATOR;

		if ( ! is_dir( $data)) {
			mkdir( $data);
			chmod( $data, 0777);

		}

		if ( ! is_writable( $data))
			throw new Exception( $data . ' is not writable, please update permissions to allow');

		if ( !self::$_imap_cleaned_up) {
			self::$_imap_cleaned_up = true;

			// clean this folder
			// \sys::logger( sprintf('<%s> %s', 'please clean up', __METHOD__));
			$iterator = new \GlobIterator($data . '*');
			$n = 0;
			foreach ($iterator as $item) {
				$age = time() - $item->getMTime();

				if ( $age > self::$_imap_cache_max_age) {
					// \sys::logger( sprintf('<%s> %s', sprintf("[%d] %s : delete : %s", ++$n, $item->getRealPath(), $age), __METHOD__));
					unlink( $item->getRealPath());

				}
				else {
					// \sys::logger( sprintf('<%s> %s', sprintf("[%d] %s : good : %s", ++$n, $iterator->key(), $age), __METHOD__));

				}

			}
			// \sys::logger( sprintf('<%s> %s', 'please clean [done]', __METHOD__));

		}

		return ( $data);

	}

	static function IMAP_DATA() {
		$data = self::dataPath() . DIRECTORY_SEPARATOR . 'imap' . DIRECTORY_SEPARATOR;

		if ( ! is_dir( $data)) {
			mkdir( $data);
			chmod( $data, 0777);

		}

		if ( ! is_writable( $data))
			throw new Exception( $data . ' is not writable, please update permissions to allow');

		return ( $data);

	}

	static protected function imap_config() {
		return sprintf( '%s%simap.json', self::IMAP_DATA(), DIRECTORY_SEPARATOR);

	}

	static function imap_init() {
		if ( file_exists( $config = self::imap_config())) {
			$j = json_decode( file_get_contents( $config));

			if ( isset( $j->imap_version)) self::$_IMAP_VERSION = (float)$j->imap_version;

		}

	}

}

config::imap_init();

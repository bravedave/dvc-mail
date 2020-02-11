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

abstract class config extends \config {
	const imap_route = 'imap';

	static $WEBNAME = 'IMAP Interface for DVC';

	static protected $_IMAP_VERSION = 0;

	static protected $_imap_cleaned_up = false;
	// static protected $_imap_cache_max_age = 14400;	// 4 hours
	static protected $_imap_cache_max_age = 43200;	// 12 hours
	// static protected $_imap_cache_max_age = 259200;	// 3 days

	static $_imap_cache_flushing = false;	// enforces cache flush on purge
	// static $_imap_cache_flushing = true;

	static function IMAP_CACHE() {
		$data = self::IMAP_DATA() . '_cache' . DIRECTORY_SEPARATOR;

		if ( ! is_dir( $data)) {
			mkdir( $data);
			chmod( $data, 0777);

		}

		if ( ! is_writable( $data)) {
			throw new Exception( $data . ' is not writable, please update permissions to allow');

		}

		$runCleanUp = true;
		$Semaphor = $data . 'semaphor.dat';
		if ( \file_exists( $Semaphor)) {
			$age = time() - \filemtime( $Semaphor);
			$runCleanUp = ( $age > 600);

		}

		if ( $runCleanUp && !self::$_imap_cleaned_up) {
			self::$_imap_cleaned_up = true;

			if ( \file_exists( $Semaphor)) unlink( $Semaphor);
			\file_put_contents( $Semaphor, date('c'));

			// clean this folder
			$iterator = new \GlobIterator($data . '*');
			foreach ($iterator as $item) {
				if ( file_exists( $item->getRealPath())) {

					$age = time() - $item->getMTime();
					if ( $age > self::$_imap_cache_max_age) unlink( $item->getRealPath());

				}

			}

		}

		return ( $data);

	}

	static function IMAP_DATA() {
		$data = rtrim( self::dataPath(), '/ ') . DIRECTORY_SEPARATOR . 'imap' . DIRECTORY_SEPARATOR;

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

<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International License.
		http://creativecommons.org/licenses/by/4.0/

	*/
abstract class config extends dvc\config {

	static $DATE_FORMAT = 'd/m/Y';
	static $DATETIME_FORMAT = 'd/m/Y g:ia';

	static $TIMEZONE = 'Australia/Brisbane';

	static function MESSAGE_STORE() {
		$data = self::dataPath() . DIRECTORY_SEPARATOR . 'message-store' . DIRECTORY_SEPARATOR;

		if ( ! is_dir( $data)) {
			mkdir( $data);
			chmod( $data, 0777);

		}

		if ( ! is_writable( $data))
			throw new Exception( $data . ' is not writable, please update permissions to allow');

		return ( $data);

	}

}

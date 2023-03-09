<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace dvc\mail;

use RuntimeException;

class credentials {
	public $account = '';

	public $interface = 0;

	public $password = '';

	public $server = '';

	public $user_id = 0;

	const imap = 1;

	const ews = 2;

	function __construct($_user, $_pass, $_server = null) {
		$this->account = $_user;
		$this->password = $_pass;

		if (is_null($_server)) {
			if (isset(\config::$exchange_server)) {
				$this->interface = self::ews;
				$this->server = \config::$exchange_server;
			} else {

				throw new RuntimeException('Invalid Exchange Server');
			}
		} else {

			$this->server = $_server;
		}
	}

	static function getCurrentUser() {

		return \currentUser::exchangeAuth();
	}
}

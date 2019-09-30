<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 * 		http://creativecommons.org/licenses/by/4.0/
 *
 * */

use dvc\imap\account;
use dvc\mail\credentials;

class imap extends dvc\imap\controller {
	protected function before() {
		parent::before();

        /**
         * in the development environment this
         * establishes a local account
         *
         * use this area to establish an account
         *
         */

	}

	protected function postHandler() {
		$action = $this->getPost('action');

		if ( 'save-account' == $action) {
			// \sys::dump( $this->getPost());
			$a = (object)[
				'server' => $this->getPost('server'),
				'username' => $this->getPost('username'),
				'password' => $this->getPost('password'),

			];

			// sys::dump( $a);

			if ( !trim( $a->password, '- ')) {
				$a->password = account::$PASSWORD;

			}

			if ( $a->password) $a->password = bCrypt::crypt( $a->password);

			$config = account::config();
			if ( file_exists( $config)) unlink( $config);

			file_put_contents( $config, json_encode( $a, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			// sys::dump( $a, $config);

			Response::redirect( strings::url( $this->route));

        }
        else { parent::postHandler(); }

	}

	public function account() {
		$this->data = (object)[
			'account' => (object)[
				'server' => account::$SERVER,
				'username' => account::$USERNAME,
				'password' => account::$PASSWORD,

			]

		];

		$this->render([
			'title' => $this->title = 'Account Settings',
			'primary' => 'account',
			'secondary' => ['index']

		]);

	}

}

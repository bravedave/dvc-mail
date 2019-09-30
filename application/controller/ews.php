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

use dvc\ews\account;
use dvc\mail\credentials;

class ews extends dvc\ews\controller {
    protected function _index() {
		Response::redirect( strings::url());

	}

	protected function before() {
		parent::before();

        /**
         * in the development environment this
         * establishes a local account
         *
         * use this area to establish an account
         *
         */
        if ( account::$ENABLED) {
			sys::logger( sprintf( 'account enabled : %s', __METHOD__));

			$this->creds = new credentials(
				account::$USERNAME,
				account::$PASSWORD,
				account::$SERVER

			);

		}

		// sys::logger( var_export( $this->creds, true));

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

			if ( !trim( $a->password, '- ')) {
				$a->password = account::$PASSWORD;

			}

			if ( $a->password) $a->password = bCrypt::crypt( $a->password);

			$config = account::config();
			if ( file_exists( $config)) unlink( $config);

			file_put_contents( $config, json_encode( $a, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			//~ sys::dump( $a);

			Response::redirect( strings::url( $this->route));

        }
        else {
            parent::postHandler();

        }

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

    public function agenda() {
		// $debug = true;
		$debug = false;

		$options = (object)[
			'date' => date( 'Y-m-d'),
			'end' => date( 'Y-m-d'),
			'addheader' => true

		];

		$this->data = (object)[
            'agenda' => dvc\ews\calendar::agenda( (object)[
                    'start' => $options->date,
                    'end' => $options->end
                ])

            ];

		$this->render([
			'title' => $this->title = 'Agenda',
			'primary' => 'agenda',
			'secondary' => ['index']

		]);

	}

	function inbox() {
		sys::dump( $this->_messages());

	}

	public function webmail() {
		$this->_webmail( $this->creds);

	}

}

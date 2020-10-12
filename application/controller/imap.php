<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

use dvc\bCrypt;
use dvc\imap\account;
use dvc\imap\folders;

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

		if ( 'delete-profile' == $action) {
			if ( $profile = $this->getPost('profile')) {
				if ( $profile_config = account::profile( $profile)) {
					if ( file_exists( $profile_config)) {
						unlink( $profile_config);
						\Json::ack( $action);

					} else { \Json::nak( sprintf( 'profile %s not found : %s', $profile_config, $action)); }

				} else { \Json::nak( sprintf( 'invalid profile name : %s', $action)); }

			} else { \Json::nak( $action); }

		}
		elseif ( 'load-profile' == $action) {
			if ( $profile = $this->getPost('profile')) {
				if ( $profile_config = account::profile( $profile)) {
					if ( file_exists( $profile_config)) {
						$config = account::config();
						if ( file_exists( $config)) unlink( $config);

						if ( copy( $profile_config, $config)) {
							// \sys::logger( sprintf('<load profile : %s> %s', $profile, __METHOD__));
							\Json::ack( $action);

						} else { \Json::nak( sprintf( 'failed to copy profile to default : %s', $action)); }

					} else { \Json::nak( sprintf( 'profile %s not found : %s', $profile_config, $action)); }

				} else { \Json::nak( sprintf( 'invalid profile name : %s', $action)); }

			} else { \Json::nak( $action); }

		}
		elseif ( 'save-account' == $action) {
			// \sys::dump( $this->getPost());
			$a = (object)[
				'server' => $this->getPost('server'),
				'name' => $this->getPost('name'),
				'email' => $this->getPost('email'),
				'username' => $this->getPost('username'),
				'password' => $this->getPost('password'),
				'smtp_server' => $this->getPost('smtp_server'),
				'smtp_port' => $this->getPost('smtp_port'),
				'smtp_username' => $this->getPost('smtp_username'),
				'smtp_password' => $this->getPost('smtp_password'),
				'type' => $this->getPost('type'),
				'profile' => $this->getPost('profile'),

			];

			// sys::dump( $a);

			if ( !trim( $a->password, '- ')) {
				$a->password = account::$PASSWORD;

			}

			if ( !trim( $a->smtp_password, '- ')) {
				$a->smtp_password = account::$SMTP_PASSWORD;

			}

			if ( $a->password) $a->password = bCrypt::crypt( $a->password);
			if ( $a->smtp_password) $a->smtp_password = bCrypt::crypt( $a->smtp_password);

			$config = account::config();

			if ( file_exists( $config)) unlink( $config);
			file_put_contents( $config, json_encode( $a, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			if ( '' != $a->profile && 'default' != $a->profile) {
				if ( $profile_config = account::profile( $a->profile)) {
					if ( file_exists( $profile_config)) unlink( $profile_config);
					file_put_contents( $profile_config, json_encode( $a, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

				}

			}

			// sys::dump( $a, $config);

			Response::redirect( strings::url( $this->route));

		}
		elseif ( 'verify' == $action) {
      $email_type = $this->getPost( 'email_type');
      if ( !$email_type) $email_type = 'imap';

      if ( \in_array( $email_type, ['imap', 'exchange'])) {
        $email_server = $this->getPost( 'email_server');
        $email_account = $this->getPost( 'email_account');
        $email_password = $this->getPost( 'email_password');

        if ( $email_server && $email_account && $email_password) {
          $creds = new credentials(
            $email_account,
            $email_password,
            $email_server

          );

          $creds->interface = credentials::imap;
          if ( 'exchange' == $email_type) {
            folders::changeDefaultsToExchange();

          }

          if ( $inbox = dvc\mail\inbox::instance( $creds)) {
            if ( $inbox->verify()) {
              Json::ack( $action);

            } else { Json::nak( sprintf( 'fail open - %s', $action)); }

          } else { Json::nak( sprintf( 'fail - %s', $action)); }

        } else { Json::nak( sprintf( 'missing param %s', $action)); }

      } else { Json::nak( sprintf( 'invalid type ($s) - %s', $email_type, $action)); }

    }
    else { parent::postHandler(); }

	}

	public function account() {
		$this->data = (object)[
			'account' => (object)[
				'server' => account::$SERVER,
				'type' => account::$TYPE,
				'name' => account::$NAME,
				'email' => account::$EMAIL,
				'username' => account::$USERNAME,
				'password' => account::$PASSWORD,
				'smtp_server' => account::$SMTP_SERVER,
				'smtp_port' => account::$SMTP_PORT,
				'smtp_username' => account::$SMTP_USERNAME,
				'smtp_password' => account::$SMTP_PASSWORD,
				'profile' => account::$PROFILE,

			]

		];

		$this->render([
			'title' => $this->title = 'Account Settings',
			'primary' => 'account',
			'secondary' => ['index']

		]);

	}

}

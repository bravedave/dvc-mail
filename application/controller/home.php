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

use dvc\mail\credentials;

class home extends dvc\mail\controller {
    protected function _index() {
		$this->render([
			'title' => $this->title = $this->label,
			'primary' => 'blank',
			'secondary' =>'index'

		]);

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

		if ( dvc\mail\config::$ENABLED) {

			if ( 'ews' == dvc\mail\config::$MODE) {
				$this->creds = currentUser::exchangeAuth();

			}
			elseif ( 'imap' == dvc\mail\config::$MODE) {
				if ( dvc\imap\account::$ENABLED) {
					$this->creds = new credentials(
						dvc\imap\account::$USERNAME,
						dvc\imap\account::$PASSWORD,
						dvc\imap\account::$SERVER

					);

					$this->creds->interface = dvc\mail\credentials::imap;

					// sys::dump( $this->creds);

				}

			}

		}

	}

	protected function postHandler() {
		$action = $this->getPost('action');

		if ( 'save-settings' == $action) {
			$a = (object)[
				'mode' => $this->getPost('mode'),

			];

			$config = dvc\mail\config::config();
			if ( file_exists( $config)) unlink( $config);

			file_put_contents( $config, json_encode( $a, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			//~ sys::dump( $a);

			Response::redirect( strings::url());

		}
		else {
			parent::postHandler();

		}

	}

	public function folders() {
		sys::dump( $this->_folders());

	}

	public function localjs() {
		printf( '
		$(document).on( \'mail-messages-context\', function( e, params) {
			let options = $.extend({
				element : false,
				context : false,

			}, params);

			// console.log( options);

			if ( !!options.context) {
				let ctrl = $(\'<a href="#">noice</a>\');
				ctrl.on( \'click\', function( e) {
					e.preventDefault();

					_brayworth_.growl( $(this).html());

					options.context.close();

				});

				options.context.prepend(ctrl);

			}

		});

		$(document).ready( function() {
			console.log( \'-- add scripts here -- : %s\');

		});
		', __METHOD__);

	}

	public function inbox() {
		sys::dump( $this->_messages([
			'folder' => 'Inbox'
		]));

	}

	public function message() {
		$this->_view([
			'msg' => '<FE4CC86AFE7A55418896B8277E783C5046E63484@BNE3-0004SMBX.services.admin-domain.net>'

		]);

	}

	public function settings() {
		$this->render([
			'title' => $this->title = 'Global Settings',
			'primary' => 'settings',
			'secondary' => ['index']

		]);

	}

}

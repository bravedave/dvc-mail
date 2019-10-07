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
		elseif ( 'save-to-file' == $action) {
			if ( $itemID = $this->getPost('id')) {
				if ( $folder = $this->getPost('folder')) {
					$inbox = dvc\mail\inbox::instance( $this->creds);
					if ( $msg = $inbox->GetItemByMessageID( $itemID)) {
						$inbox->SaveToFile( $msg, \config::MESSAGE_STORE() . trim( $msg->MessageID, ' ><'));
						Json::ack( $action);

					} else { Json::nak( $action); }

				} else { Json::nak( sprintf( 'missing folder : %s', $action)); }

			} else { Json::nak( sprintf( 'missing id : %s', $action)); }

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

			if ( !!options.context && !!options.element) {
				let _data = $(options.element).data();
				// console.log( _data);

				let ctrl = $(\'<a href="#">save to file</a>\');
				ctrl.on( \'click\', function( e) {
					e.preventDefault();

					_brayworth_.post({
						url : _brayworth_.url(\'/\'),
						data : {
							action : \'save-to-file\',
							folder : _data.message.folder,
							id : _data.message.messageid,

						}

					}).then( function(d) {
						_brayworth_.growl( d);

					});

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
			'msg' => '<0a65046c-ce7c-4c8b-a93f-3b71e9777d20@W2008k.ashgrove.darcy.com.au>'

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

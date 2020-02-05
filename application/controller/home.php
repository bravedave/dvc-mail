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

class home extends dvc\mail\controller {
	const allowedOptions = [
		'email-autoloadnext',
		'email-expand-recipients',

	];

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
					if ( 'exchange' == dvc\imap\account::$TYPE) {
						dvc\imap\folders::$delimiter = '/';									// for exchange server
						dvc\imap\folders::$default_folders['Trash'] = 'Deleted Items';		// for exchange server
						dvc\imap\folders::$default_folders['Sent'] = 'Sent Items';			// for exchange server
						dvc\imap\folders::$type = dvc\imap\account::$TYPE;					// for exchange server

					}

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
					if ( $msg = $inbox->GetItemByMessageID( $itemID, true, $folder)) {
						$inbox->SaveToFile( $msg, \config::MESSAGE_STORE() . trim( $msg->MessageID, ' ><'));
						Json::ack( $action);

					} else { Json::nak( $action); }

				} else { Json::nak( sprintf( 'missing folder : %s', $action)); }

			} else { Json::nak( sprintf( 'missing id : %s', $action)); }

		}
		elseif ( 'set-option' == $action) {
			if ( $key = $this->getPost('key')) {
				if ( in_array( $key, self::allowedOptions)) {
					currentUser::option( $key, $this->getPost('val'));
					Json::ack( $action);

				} else { Json::nak( sprintf( 'invalid key : %s', $action)); }

			} else { Json::nak( $action); }

		}
		else {
			parent::postHandler();

		}

	}

	public function changes() {
		$this->render([
			'title' => $this->title = 'Change Log',
			'primary' => 'changes',
			'secondary' =>'index'

		]);

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
			_brayworth_.currentUser = {
				email : "%s",
				email_822 : "%s <%s>"

			};

			console.log( \'-- add scripts here -- : %s\');

		});
		',
		htmlspecialchars( account::$EMAIL),
		htmlspecialchars( account::$NAME),
		htmlspecialchars( account::$EMAIL),
		__METHOD__);

	}

	public function fileload() {
		// $msgID = 'CANptC-Wn6zZWLSc63b13g7FLWXiNviJrZeZ9if6yTQkYOzY7RQ@mail.gmail.com';
		// $msgID = 'CANptC-U-5mc8qXfSzj2E9xpr6GNG=TCyor2Bf6S_ae9grXcbqA@mail.gmail.com';
		// $msgID = '39748623a5c3225e833f08b4eec3fbb3@cmss.darcy.com.au';
		$msgID = 'BEBACA374AC3834BA15CE0AF411051F0099B8254@w2008k.ashgrove.darcy.com.au';
		$inbox = dvc\mail\inbox::instance( $this->creds);
		sys::dump( $inbox->ReadFromFile( \config::MESSAGE_STORE() . $msgID));

	}

	public function message() {
		$this->_view([
			'msg' => '<D4BB1C8D1A502348BC4DACAC06A932050BB5D723@w2008k.ashgrove.darcy.com.au>'

		]);

	}

	public function options() {
		$this->render([
			'title' => $this->title = 'Global Options',
			'primary' => 'options',
			'secondary' => ['index']

		]);

	}

	public function settings() {
		$this->render([
			'title' => $this->title = 'Global Settings',
			'primary' => 'settings',
			'secondary' => ['index']

		]);

	}

	public function tests( $test = null) {

		if ( 'emailsplit' == $test) {
			$test = 'dave@domain.tld, David Bray <david@brayworth.com.au> dave@domain.tld "Bray, David" <david@brayworth.com.au>, "Bray, Dave" <david@brayworth.com.au>; davbray@domain.tld';
			$test = '"Ruggieri, Stephanie" <stephanie.ruggieri@bhp.com>';
			Response::text_headers();
			print_r( \dvc\mail\strings::splitEmails( $test));
			print htmlentities( \dvc\mail\strings::splitEmails( $test)[0]);

		}
		elseif ( 'folders' == $test) {
			sys::dump( $this->_folders());

		}
		elseif ( 'info' == $test) {
			phpinfo();

		}
		elseif ( 'messages' == $test) {
			sys::dump( $this->_messages([
				'folder' => 'Inbox'
				]),
				sprintf( 'elapsed : %s<br />', $this->timer->elapsed()),
				false

			);
			// sys::dump( $this->_messages([
			// 	'folder' => 'Deleted Items',
			// 	'page' => 2
			// 	]),
			// 	sprintf( 'elapsed : %s<br />', $this->timer->elapsed()),
			// 	false

			// );

		}
		elseif ( 'sentmessages' == $test) {
			$msg = $this->_messages([
				'folder' => 'Sent'
			]);

			Response::text_headers();
			print_r( $msg);
			printf( "elapsed : %s\n", $this->timer->elapsed());

		}
		elseif ( 'searchall' == $test) {
			sys::dump( $this->_searchall([
				'term' => 'error',

			]));

		}

	}

}

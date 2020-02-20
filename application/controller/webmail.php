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

use dvc\imap\account;
use dvc\mail\credentials;

class webmail extends dvc\mail\controller {
	protected function _index() {
		$this->_webmail( $this->creds);

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

		if ( 'save-to-file' == $action) {
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
		else {
			parent::postHandler();

		}

    }

	public function fileload() {
		// $msgID = 'CANptC-Wn6zZWLSc63b13g7FLWXiNviJrZeZ9if6yTQkYOzY7RQ@mail.gmail.com';
		// $msgID = 'CANptC-U-5mc8qXfSzj2E9xpr6GNG=TCyor2Bf6S_ae9grXcbqA@mail.gmail.com';
		// $msgID = '39748623a5c3225e833f08b4eec3fbb3@cmss.darcy.com.au';
		$msgID = 'BEBACA374AC3834BA15CE0AF411051F0099B8254@w2008k.ashgrove.darcy.com.au';
		$inbox = dvc\mail\inbox::instance( $this->creds);
		sys::dump( $inbox->ReadFromFile( \config::MESSAGE_STORE() . $msgID));

	}

}

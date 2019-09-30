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

namespace dvc\ews;

use dvc\mail\credentials;

use bCrypt;
use Json;
use Response;
use strings;
use sys;
use url;

class controller extends \Controller {
	protected $route = config::ews_route;
	protected $creds = null;	// credentials

	protected function _webmail( credentials $creds) {
		$dump = false;
		// $dump = true;

		if ( $dump) {
			$Inbox = new inbox( $creds);
			$response = $Inbox->finditems([
				'deep' => true,
				'folder' => 'INBOX',
				'pageSize' => 1

			]);

			$msg = $response[0];
			$msg->Body = 'unset';
			$msg->src = 'unset';
			sys::dump( $msg, null, false);

		}
		else {
			\dvc\pages\_page::$momentJS = true;
			$this->render([
				'title' => $this->title = $this->label,
				'content' => 'inbox'

			]);

		}

	}

	protected function getView( $viewName = 'index', $controller = null ) {
		$view = sprintf( '%s/views/%s.php', __DIR__, $viewName );		// php
		if ( file_exists( $view))
			return ( $view);

		return parent::getView( $viewName, $controller);

	}

	protected function postHandler() {
		$action = $this->getPost('action');

		if ( 'delete-message' == $action) {
			if ( $msgID = $this->getPost('id')) {
				// if ( $debug) sys::logger( sprintf( '%s : ok :: %s', $action, $itemID));

				$inbox = new inbox( $this->creds);
				if ( $m = $inbox->FindItemByMessageID( $msgID)) {
					query::DeleteItem( $m->ItemId->Id, $this->creds);
					\Json::ack( sprintf( '%s : gave it a shot', $action));

				}
				else {
					// if ( $debug) sys::logger( sprintf( '%s : not found', $action));
					Json::nak( sprintf( '%s : not found', $action));

				}

			}
			else {
				Json::nak( $action);

			}

		}
		elseif ( 'get-folders' == $action) {
			$folders = new folders( $this->creds);
			Json::ack( $action)
				->add( 'folders', $folders->getAll('json'));

		}
		elseif ( 'get-message' == $action) {
			if ( $itemID = $this->getPost('id')) {
				// sys::logger( sprintf( '%s : %s', $itemID, __METHOD__));

				$inbox = new inbox( $this->creds);
				if ( $msg = $inbox->GetItemByMessageID( $itemID)) {

					// sys::logger( sprintf( 'found : %s : %s', $itemID, __METHOD__));

					$msg->Body = strings::htmlSanitize( $msg->Body);
					Json::ack( $action)->add( 'message', $msg);

				}
				else {
					Json::nak( $action);

				}

			}
			else {
				Json::nak( $action);

			}

		}
		elseif ( 'get-message-by-id' == $action) {
			if ( $id = $this->getPost( 'id')) {
				$inbox = new inbox( $this->creds);
				$msg = $inbox->GetItemByID( $id);
				unset( $msg->src);
				//~ \Json::ack( $action);
				\Json::ack( $action)
					->add('message', $msg);

			} else { \Json::nak( $action); }

		}
		elseif ( 'get-messages' == $action) {

			// todo: creds
			$params = [
				'creds' => $this->creds,
				'folder' => $this->getPost('folder', 'default'),
				'deep' => false

			];

			Json::ack( $action)
				->add( 'folder', $params['folder'])
				->add( 'messages', $this->_messages( $params));

		}
		elseif ( 'move-message' == $action) {
			if ( $msgID = $this->getPost('messageid')) {
				// sys::logger( sprintf( '%s : %s', $itemID, __METHOD__));

				$srcFolder = $this->getPost('folder', 'INBOX');

				$inbox = new inbox( $this->creds);
				if ( $msg = $inbox->GetItemByMessageID( $msgID, false, $srcFolder)) {

					if ( $targetFolder = $this->getPost('targetFolder')) {
						$moved = false;
						$folders = new folders( $this->creds);
						foreach( $_fldrs = $folders->getAll() as $_fldr) {
							if ( $targetFolder == $_fldr->map) {
								if ( $inbox->MoveItem( $msg, $targetFolder)) {
									$moved = true;
									\Json::ack( $action);
									break;

								}

							}

						}

						if ( !$moved) { \Json::nak( sprintf('%s : message not moved', $action)); }

					} else { \Json::nak( sprintf('%s : folder not found', $action)); }

				} else { \Json::nak( sprintf('%s : message not found in %s', $action, $srcFolder)); }

			}

		}
        else { parent::postHandler(); }

	}

	protected function _messages( array $params = []) : array {

		$options = array_merge([
			'creds' => $this->creds,
			'folder' => 'default',
			'deep' => false

		], $params);

		$inbox = new inbox( $options['creds']);
		$messages = (array)$inbox->finditems( $params);
		//~ sys::dump( $messages);

		$a = [];
		foreach ( $messages as $message)
			$a[] = $message->asArray();

		return $a;

	}

	public function __construct( $rootPath ) {
		$this->label = config::$WEBNAME;
		parent::__construct( $rootPath);

	}

	public function view() {
		if ( $msg = $this->getParam('msg')) {
			$folder = $this->getParam('folder','INBOX');
			$inbox = new inbox( $this->creds);
			if ( $msg = $inbox->GetItemByMessageID( $msg, $includeAttachments = true, $folder)) {
				$this->data = (object)[
					'message' => $msg

				];

				$this->render([
					'title' => $this->title = $msg->Subject,
					'template' => 'dvc\mail\pages\minimal',
					'content' => 'message',
					'navbar' => []

				]);

				// $msg->Body = strings::htmlSanitize( $msg->Body);
				// Json::ack( $action)->add( 'message', $msg);

			}
			else {
				$this->render([
					'title' => $this->title = 'View Message',
					'content' => 'not-found'

				]);

			}

		}
		else {
			$this->render([
				'title' => $this->title = 'View Message',
				'content' => 'missing-information'

			]);

		}

	}

	public function folders() {
		$folders = new folders( $this->creds);
		sys::dump( $folders->getAll());

	}

	public function js( $scope = 'global') {
		if ( 'local' == $scope) {
			js::liblocal();

		}
		else {
			js::lib();

		}


	}

}
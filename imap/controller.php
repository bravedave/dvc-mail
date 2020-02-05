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

namespace dvc\imap;
use dvc\mail\credentials;
use bCrypt;
use Json;
use Response;
use strings;
use sys;

class controller extends \Controller {
	protected $route = config::imap_route;
	private $creds = null;	// credentials

	protected function _index() {
		$this->render([
			'title' => $this->title = $this->label,
			'primary' => 'blank',
			'secondary' =>'index'

		]);

	}

	protected function getView( $viewName = 'index', $controller = null, $logMissingView = true) {
		$view = sprintf( '%s/views/%s.php', __DIR__, $viewName );		// php
		if ( file_exists( $view))
			return ( $view);

		return parent::getView( $viewName, $controller, $logMissingView);

	}

	protected function postHandler() {
		$action = $this->getPost('action');

		if ( 'get-folders' == $action) {
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

				} else { Json::nak( $action); }

			} else { Json::nak( $action); }

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
				'folder' => $this->getPost('folder', 'INBOX'),
				'deep' => false

			];

			$inbox = new inbox( $this->creds);
			$messages = (array)$inbox->finditems( $params);
			//~ sys::dump( $messages);

			$a = [];
			foreach ( $messages as $message)
				$a[] = $message->asArray();

			Json::ack( $action)
				->add( 'folder', $params['folder'])
				->add( 'messages', $a);

		}
		// elseif ( 'move-message' == $action) {
		// 	if ( $msgID = $this->getPost('messageid')) {
		// 		// sys::logger( sprintf( '%s : %s', $itemID, __METHOD__));

		// 		$srcFolder = $this->getPost('folder', 'INBOX');

		// 		$inbox = new inbox( $this->creds);
		// 		if ( $msg = $inbox->GetItemByMessageID( $msgID, false, $srcFolder)) {

		// 			if ( $targetFolder = $this->getPost('targetFolder')) {
		// 				$moved = false;
		// 				$folders = new folders( $this->creds);
		// 				foreach( $_fldrs = $folders->getAll() as $_fldr) {
		// 					if ( $targetFolder == $_fldr->map) {
		// 						if ( $inbox->MoveItem( $msg, $targetFolder)) {
		// 							$moved = true;
		// 							\Json::ack( $action);
		// 							break;

		// 						}

		// 					}

		// 				}

		// 				if ( !$moved) { \Json::nak( sprintf('%s : message not moved', $action)); }

		// 			} else { \Json::nak( sprintf('%s : folder not found', $action)); }

		// 		} else { \Json::nak( sprintf('%s : message not found in %s', $action, $srcFolder)); }

		// 	}

		// }
        else { parent::postHandler(); }

	}

	public function __construct( $rootPath ) {
		$this->label = config::$WEBNAME;
		parent::__construct( $rootPath);

	}

	public function view() {
		if ( $msg = $this->getParam('msg')) {
			$folder = $this->getParam('folder','INBOX');
			$inbox = new inbox;
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

	// public function folders() {
	// 	$folders = new folders( $this->creds);
	// 	sys::dump( $folders->getAll());

	// }

	public function js( $scope = 'global') {
		if ( 'local' == $scope) {
			js::liblocal();

		}
		else {
			js::lib();

		}


	}

}
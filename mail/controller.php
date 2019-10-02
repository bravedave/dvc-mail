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

namespace dvc\mail;

// use dvc\mail\credentials;

// use bCrypt;
use Json;
// use Response;
use strings;
use sys;
// use url;

class controller extends \Controller {
	protected function before() {
		$this->route = get_class( $this);
		parent::before();

	}

	protected function postHandler() {
		$action = $this->getPost('action');

		if ( 'delete-message' == $action) {
			if ( $msgID = $this->getPost('id')) {
				if ( $folder = $this->getPost('folder')) {
					// if ( $debug) sys::logger( sprintf( '%s : ok :: %s', $action, $itemID));

					// $inbox = inbox::instance( $this->creds);
					// if ( $res = $inbox->MoveItem( $msgID, $folder, 'Deleted Items')) {
					// 	\Json::ack( $action);

					// } else { \Json::nak( $action); }

					Json::nak( $action);
					sys::logger( sprintf('%s : we are working on it :) : %s', $action, __METHOD__));


				} else { Json::nak( $action); }

			} else { Json::nak( $action); }

		}
		elseif ( 'create-folder' == $action) {
			if ( $folder = (string)$this->getPost( 'folder')) {
				$parent = (string)$this->getPost( 'parent');
				$folders = folders::instance( $this->creds);
				if ( $folders->create( $folder, $parent)) {
					\Json::ack( $action);

				}
				else {
					\Json::nak( $action);

				}

			} else { \Json::nak( sprintf( 'specifiy a folder name: %s', $action)); }

		}
		elseif ( 'delete-folder' == $action) {
			if ( $folder = (string)$this->getPost( 'folder')) {
				$folders = folders::instance( $this->creds);
				if ( $folders->delete( $folder)) {
					\Json::ack( $action);

				}
				else {
					\Json::nak( $action)
						->add('errors', $folders->errors);

				}

			} else { \Json::nak( sprintf( 'specifiy a folder name: %s', $action)); }

		}
		elseif ( 'get-folders' == $action) {
			Json::ack( $action)
				->add( 'folders', $this->_folders());

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

				$srcFolder = $this->getPost('folder', 'default');
				if ( $targetFolder = $this->getPost('targetFolder')) {

					$inbox = inbox::instance( $this->creds);
					if ( $res = $inbox->MoveItem( $msgID, $srcFolder, $targetFolder)) {
						\Json::ack( $action);

					} else { \Json::nak( $action); }

				} else { \Json::nak( sprintf('missing target folder : %s', $action)); }

			} else { \Json::nak( sprintf('invalid message : %s', $action)); }

		}
		else {
			parent::postHandler();

		}

	}

	protected function _folders( $format = 'json') {
		$folders = folders::instance( $this->creds);
		return $folders->getAll( $format);

	}

	protected function _messages( array $params = []) : array {

		$options = array_merge([
			'creds' => $this->creds,
			'folder' => 'default',
			'deep' => false

		], $params);

		$inbox = inbox::instance( $options['creds']);
		$messages = (array)$inbox->finditems( $options);
		// sys::dump( $messages);

		$a = [];
		foreach ( $messages as $message)
			$a[] = $message->asArray();

		return $a;
		// return $messages;

	}

	protected function _view( array $params = []) {
		$options = array_merge([
			'creds' => $this->creds,
			'folder' => 'default',
			'msg' => false

		], $params);

		$inbox = inbox::instance( $options['creds']);
		if ( $msg = $inbox->GetItemByMessageID( $options['msg'], $includeAttachments = true, $options['folder'])) {
			// unset( $msg->attachments);
			// sys::dump( $msg);

			$this->data = (object)[ 'message' => $msg ];

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

	protected function _webmail( credentials $creds) {
		$dump = false;
		// $dump = true;

		if ( $dump) {
			// sys::dump( $creds, __METHOD__);

			$Inbox = inbox::instance( $creds);
			// sys::dump( $Inbox, __METHOD__);
			$response = $Inbox->finditems([
				'deep' => true,
				'folder' => $Inbox->defaults()->inbox,
				'pageSize' => 2

			]);

			sys::dump( $response, null, false);

		}
		else {
			$this->data = (object)[
				'user_id' => $creds->user_id

			];

			\dvc\pages\_page::$momentJS = true;
			$this->render([
				'title' => $this->title = $this->label,
				'scripts' => [
					strings::url( sprintf( '%s/localjs', $this->route))

				],
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

	public function localjs() {
		print '/* placeholder for local scripts */';

	}

	public function view() {
		if ( $msg = $this->getParam('msg')) {
			$this->_view([
				'creds' => $this->creds,
				'folder' => $this->getParam('folder','default'),
				'msg' => $msg,

			]);

		}
		else {
			$this->render([
				'title' => $this->title = 'View Message',
				'content' => 'missing-information'

			]);

		}

	}

	public function webmail() {
		$this->_webmail( $this->creds);

	}

}

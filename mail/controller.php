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

		if ( 'get-folders' == $action) {
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

				$inbox = inbox::instance( $this->creds);
				if ( $msg = $inbox->GetItemByMessageID( $msgID, false, $srcFolder)) {

					if ( $targetFolder = $this->getPost('targetFolder')) {
						$moved = false;
						$folders = folders::instance( $this->creds);
						foreach( $_fldrs = $folders->getAll('json') as $_fldr) {
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

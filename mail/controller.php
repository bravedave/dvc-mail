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
use Response;
use strings;
use sys;
// use url;

class controller extends \Controller {
	protected function postHandler() {
		$action = $this->getPost('action');

		if ( 'create-folder' == $action) {
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
				'page' => (int)$this->getPost('page'),
				'deep' => false

			];

			Json::ack( $action)
				->add( 'folder', $params['folder'])
				->add( 'messages', $this->_messages( $params));

		}
		elseif ( 'mark-seen' == $action) {
			if ( $msgID = $this->getPost('messageid')) {
				$folder = $this->getPost('folder', 'default');

				$inbox = inbox::instance( $this->creds);
				if ( $res = $inbox->setflag( $msgID, $folder, '\seen')) {
					Json::ack( $action);

				} else { Json::nak( $action); }

			} else { Json::nak( $action); }

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
		elseif ( 'search-messages' == $action) {
			// todo: creds
			$params = [
				'creds' => $this->creds,
				'folder' => $this->getPost('folder', 'default'),
				'term' => $this->getPost('term'),

			];

			Json::ack( $action)
				->add( 'messages', $this->_search( $params));

		}
		else {
			parent::postHandler();

		}

	}

	protected function _file( array $params = []) {
		$options = array_merge([
			'creds' => $this->creds,
			'item' => false,
			'folder' => 'default',
			'msg' => false,
			'uid' => false

		], $params);

		$inbox = inbox::instance( $options['creds']);
		if ( $options['msg']) {
			$msg = $inbox->GetItemByMessageID(
				$options['msg'],
				$includeAttachments = true,
				$options['folder']

			);

			if ( $msg) {
				sys::dump( $msg->attachments);

			}
			else {
				sys::logger( sprintf('%s/%s : %s',
					$options['folder'],
					$options['msg'],
					__METHOD__

				));

				$this->render([
					'title' => $this->title = 'Message not found',
					'content' => 'not-found'

				]);

			}

		}
		elseif ( $options['uid']) {
			$msg = $inbox->GetItemByUID(
				$options['uid'],
				$includeAttachments = true,
				$options['folder']);

			if ( $msg) {
				if ( count( $msg->attachments)) {
					// printf( '%s<br />', $options['item']);
					$finfo = new \finfo(FILEINFO_MIME);
					foreach ( $msg->attachments as $attachment) {
						if ( 'object' == gettype( $attachment)) {
							// printf( '%s<br />', $attachment->ContentId);
							if ( $options['item'] == $attachment->ContentId) {
								header( sprintf( 'Content-type: %s', $finfo->buffer( $attachment->Content)));
								header( sprintf( 'Content-Disposition: inline; filename="%s"', $attachment->Name));
								print $attachment->Content;
								return;

								// printf( 'found %s', $finfo->buffer( $attachment->Content));

							}

						}
						elseif ( 'string' == gettype( $attachment)) {
							if ( preg_match( '@^BEGIN:VCALENDAR@', $attachment)) {
								header( 'Content-type: text/calendar');
								header( 'Content-Disposition: inline; filename="invite.ics"');
								print $attachment;

							}
							else {
								print $attachment;

							}

						}
						else {
							print gettype( $attachment);
							return;

						}

					}

				}

				$this->render([
					'title' => $this->title = 'item not found',
					'content' => 'not-found'

				]);
			}
			else {
				sys::logger( sprintf('%s/%s : %s',
					$options['folder'],
					$options['uid'],
					__METHOD__));

				$this->render([
					'title' => $this->title = 'Message not found',
					'content' => 'not-found'

				]);

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
			'page' => 0,
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

	protected function _search( array $params = []) : array {

		$options = array_merge([
			'creds' => $this->creds,
			'folder' => 'default',
			'term' => ''

		], $params);

		$inbox = inbox::instance( $options['creds']);
		$messages = (array)$inbox->search( $options);
		// sys::dump( $messages);

		$a = [];
		foreach ( $messages as $message)
			$a[] = $message->asArray();

		return $a;
		// return $messages;

	}

	protected function _searchAllDeep( $fldr, &$options, &$messages) {
		$msgs = $this->_search([
			'creds' => $options['creds'],
			'folder' => $fldr->fullname,
			'term' => $options['term'],

		]);

		foreach ( $msgs as $msg) {
			$messages[] = $msg;

		}

		if ( isset( $fldr->subFolders)) {
			foreach( $fldr->subFolders as $folder) {
				$this->_searchAllDeep( $folder, $options, $messages);

			}

		}

	}

	protected function _searchall( array $params = []) : array {

		$options = array_merge([
			'creds' => $this->creds,
			'term' => ''

		], $params);


		$folders = $this->_folders( 'json');
		// sys::dump( $folders);

		$messages = [];

		foreach( $folders as $folder) {
			$this->_searchAllDeep( $folder, $options, $messages);

		}

		// return $folders;
		return $messages;

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
		if ( $msg = $inbox->GetItemByMessageID(
			$options['msg'],
			$includeAttachments = true,
			$options['folder'])) {
			// unset( $msg->attachments);
			// sys::dump( $msg);
			// print $msg->safehtml();
			// return;

			if ( $msg->hasMso()) {
				pages\minimal::$docType = Response::mso_docType();

			}

			$this->data = (object)[
				'default_folders' => inbox::default_folders( $this->creds),
				'message' => $msg

			];

			// sys::dump( $msg);

			$this->render([
				'css' => [
					sprintf('<link type="text/css" rel="stylesheet" media="all" href="%s" />',
						strings::url( $this->route . '/normalizecss')

					)

				],
				'title' => $this->title = $msg->Subject,
				'template' => 'dvc\mail\pages\minimal',
				'content' => 'message',
				'navbar' => [],
				'charset' => $msg->CharSet,

			]);

			// $msg->Body = strings::htmlSanitize( $msg->Body);
			// Json::ack( $action)->add( 'message', $msg);

		}
		else {
			sys::logger( sprintf('%s/%s : %s', $options['folder'], $options['msg'], __METHOD__));

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
				'user_id' => $creds->user_id,
				'default_folders' => inbox::default_folders( $this->creds)

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

	protected function page( $params) {
		$p = parent::page( $params);
		if (  isset( $params['charset'])) {
			if (  $params['charset']) {
				$p->charset = $params['charset'];
				sys::logger( sprintf('%s : %s', $params['charset'], __METHOD__));

			}

		}

		return ( $p);

	}

	protected function getView( $viewName = 'index', $controller = null ) {
		$view = sprintf( '%s/views/%s.php', __DIR__, $viewName );		// php
		if ( file_exists( $view))
			return ( $view);

		return parent::getView( $viewName, $controller);

	}

	public function file() {
		$msg = $this->getParam('msg');
		$uid = $this->getParam('uid');
		if ( $msg || $uid) {
			if ( $file = $this->getParam('item')) {
				$this->_file([
					'creds' => $this->creds,
					'folder' => $this->getParam('folder','default'),
					'item' => $this->getParam('item'),
					'msg' => $msg,
					'uid' => $uid

				]);

			}
			else {
				$this->render([
					'title' => $this->title = 'attachment - file',
					'content' => 'missing-information'

				]);

			}

		}
		else {
			$this->render([
				'title' => $this->title = 'attachment - uid',
				'content' => 'missing-information'

			]);

		}

	}

	public function localjs() {
		print '/* placeholder for local scripts */';

	}

	public function normalizecss() {
		\cssmin::viewcss([
			'debug' => false,
			'libName' => 'mail/normalise',
			'cssFiles' => [__DIR__ . '/normalize.css'],
			'libFile' => \config::tempdir()  . '_mail_normalize.css'

		]);

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

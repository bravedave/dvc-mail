<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace dvc\mail;

// use dvc\mail\credentials;

use Json;
use Response;
use strings;
use sys;
// use url;

class controller extends \Controller {
	protected $creds = null;	// credentials

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
		elseif ( 'empty-trash' == $action) {
			if ( $folder = (string)$this->getPost( 'folder')) {
				$inbox = inbox::instance( $this->creds);
				if ($inbox->EmptyTrash( $folder)) {
					\Json::ack( sprintf( 'perhaps : %s', $action));

				} else { \Json::nak( sprintf( 'error: %s', $action)); }

			} else { \Json::nak( sprintf( 'missing folder name: %s', $action)); }

		}
		elseif ( 'get-default-folders' == $action) {
			Json::ack( $action)
				->add( 'data', $this->_getDefaultFolders());

		}
		elseif ( 'get-folders' == $action) {
			Json::ack( $action)
				->add( 'folders', $this->_folders());

		}
		elseif ( 'get-messages' == $action) {
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
		elseif ( 'get-info' == $action) {
			$folder = $this->getPost('folder', 'default');
			$inbox = inbox::instance( $this->creds);
			Json::ack( $action)
				->add( 'folder', $folder)
				->add( 'messages', $inbox->Info( $folder));

		}
		elseif ( 'mark-seen' == $action || 'mark-unseen' == $action) {
			$msgID = $this->getPost('messageid');
			$uid = $this->getPost('uid');

			if ( $msgID || $uid) {
				$folder = $this->getPost('folder', 'default');

				$inbox = inbox::instance( $this->creds);
				if ( 'mark-unseen' == $action) {
					$res = $uid ?
						$inbox->clearflagByUID( $uid, $folder, '\seen') :
						$res = $inbox->clearflag( $msgID, $folder, '\seen');

				}
				else{
					$res = $uid ?
						$inbox->setflagByUID( $uid, $folder, '\seen') :
						$res = $inbox->setflag( $msgID, $folder, '\seen');

				}

				if ( $res) {
					Json::ack( $action);

				} else { Json::nak( $action); }

			} else { Json::nak( $action); }

		}
		elseif ( 'move-message' == $action) {
			$msgID = $this->getPost('messageid');
			$uid = $this->getPost('uid');

			if ( $msgID || $uid) {

				$srcFolder = $this->getPost('folder', 'default');
				if ( $targetFolder = $this->getPost('targetFolder')) {

					$inbox = inbox::instance( $this->creds);
					$res = $uid ?
						$inbox->MoveItemByUID( $uid, $srcFolder, $targetFolder) :
						$inbox->MoveItem( $msgID, $srcFolder, $targetFolder);

					if ( $res) {
						\Json::ack( $action);

					} else { \Json::nak( $action); }

				} else { \Json::nak( sprintf('missing target folder : %s', $action)); }

			} else { \Json::nak( sprintf('invalid message : %s', $action)); }

		}
		elseif ( 'search-all-messages' == $action) {
			// todo: creds
			$params = [
				'creds' => $this->creds,
				'folder' => $this->getPost('folder', 'default'),
				'term' => $this->getPost('term'),
				'from' => $this->getPost('from'),
				'to' => $this->getPost('to'),

			];

			Json::ack( $action)
				->add( 'messages', $this->_search( $params));

		}
		elseif ( 'search-messages' == $action) {
			// todo: creds
			$params = [
				'creds' => $this->creds,
				'folder' => $this->getPost('folder', 'default'),
				'term' => $this->getPost('term'),
				'from' => $this->getPost('from'),
				'to' => $this->getPost('to'),

			];

			Json::ack( $action)
				->add( 'messages', $this->_search( $params));

		}
		elseif ( 'send email' == $action) {
			$to = $this->getPost( 'to');
			$subject = $this->getPost( 'subject');
			$message = $this->getPost( 'message');

			/**----------------------------------- */
			$mail = \currentUser::mailer();
			$mail->AddAddress( $to);

			$mail->Subject  = $subject;

			// $mail->CharSet = 'UTF-8';
			// $mail->Encoding = 'quoted-printable';
			// $mail->msgHTML( $message);
			$mail->Body = $message;

			if ( $mail->send()) {
				Json::ack( $action);

			}
			else {
				Json::nak( $mail->ErrorInfo);

			}

			/**----------------------------------- */


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

	protected function _getDefaultFolders( $format = 'json') {
		$folders = folders::instance( $this->creds);
		return $folders::$default_folders;

	}

	protected function _index() {
		$this->_webmail( $this->creds);

	}

	protected function _messages( array $params = []) : array {

		$options = array_merge([
			'creds' => $this->creds,
			'folder' => 'default',
			'page' => 0,
			'deep' => false

		], $params);

		$inbox = inbox::instance( $options['creds']);
		// \sys::logger( sprintf('<%s> %s', $this->timer->elapsed(), __METHOD__));
		$messages = (array)$inbox->finditems( $options);
		// \sys::logger( sprintf('<%s> %s', $this->timer->elapsed(), __METHOD__));
		// sys::dump( $messages);

		$a = [];
		foreach ( $messages as $message)
			$a[] = $message->asArray();

		return $a;
		// return $messages;

	}

	protected function _search( array $params = []) : array {
		$options = array_merge([
			'folder' => 'default',
			'term' => ''

		], $params);

		$sb = new search( $this->creds, $options);
		return $sb->search( $options);

	}

	protected function _view( array $params = []) {
		$options = array_merge([
			'creds' => $this->creds,
			'folder' => 'default',
			'msg' => false,
			'uid' => false,

		], $params);

		$msg = false;
		$inbox = inbox::instance( $options['creds']);
		if ( $options['msg']) {
			$msg = $inbox->GetItemByMessageID(
				$options['msg'],
				$includeAttachments = true,
				$options['folder']

			);

		}
		elseif ( $options['uid']) {
			$msg = $inbox->GetItemByUID(
				$options['uid'],
				$includeAttachments = true,
				$options['folder']

			);

		}

		if ( $msg) {
			// unset( $msg->attachments);
			// sys::dump( $msg);
			// print $msg->safehtml();
			// return;

			if ( $msg->hasMso()) {
				pages\minimal::$docType = Response::mso_docType();

			}

			$this->data = (object)[
				'user_id' => $this->creds->user_id,
				'default_folders' => inbox::default_folders( $this->creds),
				'message' => $msg

			];

			$this->render([
				'css' => [
					sprintf('<link type="text/css" rel="stylesheet" media="all" href="%s" />',
						strings::url( $this->route . '/normalizecss')

					)

				],
				'title' => $this->title = $msg->Subject,
				'template' => 'dvc\mail\pages\minimal',
				'content' => ['message'],
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
				'title' => $this->title = 'mail',
				'scripts' => [
					strings::url( sprintf( '%s/localjs', $this->route)),
					strings::url( sprintf( '%s/mailjs', $this->route))

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
				// sys::logger( sprintf('%s : %s', $params['charset'], __METHOD__));

			}

		}

		return ( $p);

	}

	protected function getView( $viewName = 'index', $controller = null, $logMissingView = true) {
		$view = sprintf( '%s/views/%s.php', __DIR__, $viewName );		// php
		if ( file_exists( $view))
			return ( $view);

		return parent::getView( $viewName, $controller, $logMissingView);

	}

	public function compose() {
		$this->data = (object)[
			'to' => '',
			'subject' => '',
			'message' => '',
		];

		$this->modal([
			'title' => $this->title = 'Email',
			'class' => 'modal-lg',
			'load' => 'email-dialog'

		]);

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

	public function mailjs() {
		// 'leadKey' => '00.js',
		\jslib::viewjs([
			'debug' => false,
			'libName' => 'mailjs',
			'jsFiles' => sprintf( '%s//js/*.js', __DIR__ ),
			'libFile' => \config::tempdir()  . '_mailjs_tmp.js'

		]);

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
		$msg = $this->getParam('msg');
		$uid = $this->getParam('uid');

		if ( $msg || $uid) {
			$this->_view([
				'creds' => $this->creds,
				'folder' => $this->getParam('folder','default'),
				'msg' => $msg,
				'uid' => $uid,

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

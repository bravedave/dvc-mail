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

use strings, sys;
use bravedave\dvc\{
  cache,
  cssmin,
  jslib,
  json,
  logger,
  Response
};
use dvc\imap\config;

class controller extends \Controller {
  protected $creds = null;  // credentials
  protected $label = 'webmail';
  protected $viewPath = __DIR__ . '/views';

  protected static function formatBytes($bytes, $precision = 2) {
    $units = ['b', 'kb', 'mb', 'gb', 'tb'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
  }

  protected function postHandler() {
    $action = $this->getPost('action');

    if ('attachments-get' == $action) {
      if ($tmpdir = $this->getPost('tmpdir')) {
        $dir = config::tempdir() . $tmpdir;
        if (\is_dir($dir)) {

          // logger::info( sprintf('<%s> %s', $dir, __METHOD__));
          $it = new \FilesystemIterator($dir);
          $a = [];
          foreach ($it as $attachment) {
            $a[] = (object)[
              'name' => $attachment->getFilename(),
              'size' => self::formatBytes($attachment->getSize(), 0)

            ];
          }

          // $iterator = new \Globiterator( $dir . DIRECTORY_SEPARATOR . '*');
          // $a = [];
          // foreach ( $iterator as $attachment) {
          // 	// logger::info( sprintf('<%s> %s', $attachment->getFilename(), __METHOD__));
          // 	$a[] = (object)[
          // 		'name' => $attachment->getFilename(),
          // 		'size' => self::formatBytes( $attachment->getSize(), 0)

          // 	];

          // }

          json::ack($action)
            ->add('attachments', $a);
        } else {
          json::ack($action)
            ->add('attachments', []);
        }
      } else {
        json::nak($action);
      }
    } elseif ('attachments-upload' == $action) {
      // $debug = true;
      $debug = false;

      /*--- ---[uploads]--- ---*/
      $j = json::ack($action);

      if (!($tmpdir = $this->getPost('tmpdir')))
        $tmpdir = "email_" . time();

      $j->add('tmpdir', $tmpdir);

      $UploadDir = config::tempdir() . $tmpdir;
      if (!is_dir($UploadDir)) {
        mkdir($UploadDir);
        chmod($UploadDir, 0777);
      }

      if ($debug) sys::logger(sprintf('%s : %s', $UploadDir, __METHOD__));

      $good = [];
      $bad = [];

      foreach ($_FILES as $file) {
        if ($debug) sys::logger(sprintf('%s : %s', $file['name'], __METHOD__));
        if (is_uploaded_file($file['tmp_name'])) {
          // $strType = $file['type'];
          $strType = mime_content_type($file['tmp_name']);
          if ($debug) sys::logger(sprintf('%s (%s) : %s', $file['name'], $strType, __METHOD__));

          $ok = true;
          $accept = [
            'image/heic',
            'image/png',
            'image/x-png',
            'image/jpeg',
            'image/pjpeg',
            'image/tiff',
            'image/gif',
            'text/plain',
            'application/pdf',
            'application/x-zip-compressed',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

          ];

          if (in_array($strType, $accept)) {
            $source = $file['tmp_name'];
            $target = sprintf('%s/%s', $UploadDir, $file['name']);

            if (file_exists($target)) unlink($target);
            if (move_uploaded_file($source, $target)) {
              chmod($target, 0666);
              $good[] = ['name' => $file['name'], 'result' => 'uploaded'];
            } else {
              $bad[] = ['name' => $file['name'], 'result' => 'nak'];
            }
          } elseif (!$strType) {
            sys::logger(sprintf('%s invalid file type : %s', $file['name'], __METHOD__));
            $bad[] = ['name' => $file['name'], 'result' => 'invalid file type'];
          } else {
            sys::logger(sprintf('%s invalid file type - %s : %s', $file['name'], $strType, __METHOD__));
            $bad[] = ['name' => $file['name'], 'result' => 'invalid file type : ' . $strType];
          }
        } elseif (UPLOAD_ERR_INI_SIZE == $file['error']) {
          sys::logger(sprintf('%s size exceeds ini size', $file['name'], __METHOD__));
          $bad[] = ['name' => $file['name'], 'result' => 'size exceeds ini size'];
        } else {
          sys::logger(sprintf('is not an uploaded file ? : %s : %s', $file['name'], __METHOD__));
        }
      }
      /*--- ---[uploads]--- ---*/
    } elseif ('cleanup-temp' == $action) {
      if ($tmpdir = $this->getPost('tmpdir')) {
        $dir = config::tempdir() . $tmpdir;
        if (\is_dir($dir)) {
          $it = new \FilesystemIterator($dir);
          foreach ($it as $fileinfo) {
            unlink($fileinfo->getRealPath());
          }

          rmdir($dir);
        }

        json::ack($action);
      } else {
        json::nak($action);
      }
    } elseif ('copy-message' == $action) {
      $msgID = $this->getPost('messageid');
      $uid = $this->getPost('uid');

      if ($msgID || $uid) {
        $srcFolder = $this->getPost('folder', 'default');
        if ($targetFolder = $this->getPost('targetFolder')) {
          $inbox = inbox::instance($this->creds);
          $res = $uid ?
            $inbox->CopyItemByUID($uid, $srcFolder, $targetFolder) :
            $inbox->CopyItem($msgID, $srcFolder, $targetFolder);

          if ($res) {
            json::ack($action);
          } else {
            json::nak($action);
          }
        } else {
          json::nak(sprintf('missing target folder : %s', $action));
        }
      } else {
        json::nak(sprintf('invalid message : %s', $action));
      }
    } elseif ('create-folder' == $action) {
      if ($folder = (string)$this->getPost('folder')) {
        $parent = (string)$this->getPost('parent');
        $folders = folders::instance($this->creds);
        if ($folders->create($folder, $parent)) {
          json::ack($action);
        } else {
          json::nak($action);
        }
      } else {
        json::nak(sprintf('specifiy a folder name: %s', $action));
      }
    } elseif ('delete-folder' == $action) {
      if ($folder = (string)$this->getPost('folder')) {
        $folders = folders::instance($this->creds);
        if ($folders->delete($folder)) {
          json::ack($action);
        } else {
          json::nak($action)
            ->add('errors', $folders->errors);
        }
      } else {
        json::nak(sprintf('specifiy a folder name: %s', $action));
      }
    } elseif ('delete-message' == $action) {
      $msgID = $this->getPost('messageid');
      $uid = $this->getPost('uid');

      if ($msgID || $uid) {
        $srcFolder = $this->getPost('folder', 'default');
        $inbox = inbox::instance($this->creds);
        $res = $uid ?
          $inbox->DeleteItemByUID($uid, $srcFolder) :
          $inbox->DeleteItem($msgID, $srcFolder);

        if ($res) {
          json::ack($action);
        } else {
          json::nak($action);
        }
      } else {
        json::nak(sprintf('invalid message : %s', $action));
      }
    } elseif ('empty-trash' == $action) {
      if ($folder = (string)$this->getPost('folder')) {
        $inbox = inbox::instance($this->creds);
        if ($inbox->EmptyTrash($folder)) {
          json::ack(sprintf('perhaps : %s', $action));
        } else {
          json::nak(sprintf('error: %s', $action));
        }
      } else {
        json::nak(sprintf('missing folder name: %s', $action));
      }
    } elseif ('get-default-folders' == $action) {
      json::ack($action)
        ->add('data', $this->_getDefaultFolders());
    } elseif ('get-folders' == $action) {
      json::ack($action)
        ->add('folders', $this->_folders());
    } elseif ('get-folders-learnasham' == $action) {
      if ($folder = $this->_folder_learnasham()) {
        json::ack($action)
          ->add('folder', $folder);
      } else {
        json::nak($action);
      }
    } elseif ('get-folders-learnasspam' == $action) {
      if ($folder = $this->_folder_learnasspam()) {
        json::ack($action)
          ->add('folder', $folder);
      } else {
        json::nak($action);
      }
    } elseif ('get-messages' == $action) {
      $params = [
        'creds' => $this->creds,
        'folder' => $this->getPost('folder', 'default'),
        'page' => (int)$this->getPost('page'),
        'deep' => false

      ];

      if ($pageSize = (int)$this->getPost('pageSize')) {
        $params['pageSize'] = $pageSize;
      }

      json::ack($action)
        ->add('folder', $params['folder'])
        ->add('messages', $this->_messages($params));
    } elseif ('get-info' == $action) {
      $folder = $this->getPost('folder', 'default');

      $pageSize = config::$IMAP_PAGE_SIZE;
      if ($i = (int)$this->getPost('pageSize')) $pageSize = $i;

      $inbox = inbox::instance($this->creds);
      if ($data = $inbox->Info($folder)) {
        $data->pages = 0;
        if (isset($data->Nmsgs)) {
          $data->pages = $pageSize ? round(($data->Nmsgs / $pageSize) + .5, 0) : 0;
        }

        json::ack($action)
          ->add('folder', $folder)
          ->add('data', $data);
      } else {
        json::nak($action);
      }
    } elseif ('get-status' == $action) {
      $folder = $this->getPost('folder', 'default');

      $pageSize = config::$IMAP_PAGE_SIZE;
      if ($i = (int)$this->getPost('pageSize')) $pageSize = $i;

      $inbox = inbox::instance($this->creds);
      if ($data = $inbox->status($folder)) {
        $data->pages = 0;
        if (isset($data->messages)) {
          $data->pages = $pageSize ? round(($data->messages / $pageSize) + .5, 0) : 0;
        }

        json::ack($action)
          ->add('folder', $folder)
          ->add('data', $data);
      } else {
        json::nak($action);
      }
    } elseif ('flag' == $action || 'flag-undo' == $action) {

      $msgID = $this->getPost('messageid');
      $uid = $this->getPost('uid');

      if ($msgID || $uid) {

        $folder = $this->getPost('folder', 'default');
        $inbox = inbox::instance($this->creds);
        if ('flag-undo' == $action) {

          $res = $uid ?
            $inbox->clearflagByUID($uid, $folder, '\Flagged') :
            $res = $inbox->clearflag($msgID, $folder, '\Flagged');
        } else {

          $res = $uid ?
            $inbox->setflagByUID($uid, $folder, '\Flagged') :
            $res = $inbox->setflag($msgID, $folder, '\Flagged');
        }

        if ($res) {

          json::ack($action);
        } else {

          json::nak($action);
        }
      } else {

        json::nak($action);
      }
    } elseif ('mark-seen' == $action || 'mark-unseen' == $action) {

      $msgID = $this->getPost('messageid');
      $uid = $this->getPost('uid');

      if ($msgID || $uid) {

        $folder = $this->getPost('folder', 'default');
        $inbox = inbox::instance($this->creds);
        // logger::info( sprintf('<%s> %s', $this->creds->account, logger::caller()));


        if ('mark-unseen' == $action) {

          $res = $uid ?
            $inbox->clearflagByUID($uid, $folder, '\Seen') :
            $res = $inbox->clearflag($msgID, $folder, '\Seen');
        } else {

          $res = $uid ?
            $inbox->setflagByUID($uid, $folder, '\Seen') :
            $res = $inbox->setflag($msgID, $folder, '\Seen');
        }

        if ($res) {

          json::ack($action);
        } else {

          json::nak($action);
        }
      } else {

        json::nak($action);
      }
    } elseif ('move-message' == $action) {
      $msgID = $this->getPost('messageid');
      $uid = $this->getPost('uid');

      if ($msgID || $uid) {

        $srcFolder = $this->getPost('folder', 'default');
        if ($targetFolder = $this->getPost('targetFolder')) {

          $inbox = inbox::instance($this->creds);
          $res = $uid ?
            $inbox->MoveItemByUID($uid, $srcFolder, $targetFolder) :
            $inbox->MoveItem($msgID, $srcFolder, $targetFolder);

          if ($res) {
            json::ack($action);
          } else {
            json::nak($action);
          }
        } else {
          json::nak(sprintf('missing target folder : %s', $action));
        }
      } else {
        json::nak(sprintf('invalid message : %s', $action));
      }
    } elseif ('search-all-messages' == $action) {

      // todo: creds
      $params = [
        'creds' => $this->creds,
        'folder' => $this->getPost('folder', 'default'),
        'term' => $this->getPost('term'),
        'from' => $this->getPost('from'),
        'to' => $this->getPost('to'),
        'body' => $this->getPost('search-body'),
        'time_limit' => 240,
      ];

      logger::info(sprintf('<%s> %s', $params['body'], __METHOD__));

      json::ack($action)
        ->add('messages', $this->_search($params));
    } elseif ('search-messages' == $action) {

      // todo: creds
      $params = [
        'creds' => $this->creds,
        'folder' => $this->getPost('folder', 'default'),
        'term' => $this->getPost('term'),
        'from' => $this->getPost('from'),
        'to' => $this->getPost('to'),
        'body' => $this->getPost('search-body'),
        'time_limit' => 240,
      ];

      json::ack($action)
        ->add('messages', $this->_search($params));
      // logger::info(sprintf('<%s - %s (%s)> %s', $action, $params['folder'], $this->timer->elapsed(), __METHOD__));
    } elseif ('send email' == $action) {
      $to = $this->getPost('to');
      $subject = $this->getPost('subject');
      $message = $this->getPost('message');

      /**----------------------------------- */
      $mail = \currentUser::mailer();
      $mail->AddAddress($to);

      $mail->Subject  = $subject;

      $mail->CharSet = 'UTF-8';
      $mail->Encoding = 'quoted-printable';
      $mail->msgHTML($message);
      // $mail->Body = $message;

      if ($tmpdir = (string)$this->getPost('tmpdir')) {
        $tmpdir = config::tempdir()  . $tmpdir;
        if (is_dir($tmpdir)) {
          $it = new \FilesystemIterator($tmpdir);
          foreach ($it as $fileinfo) {
            $mail->AddAttachment($fileinfo->getRealPath());             // attachment

          }
        }
      }

      if ($mail->send()) {
        json::ack($action);
      } else {
        json::nak($mail->ErrorInfo);
        logger::info(sprintf('<failed : %s> %s', $mail->ErrorInfo, __METHOD__));
      }

      /**----------------------------------- */
    } else {
      parent::postHandler();
    }

    // logger::info( sprintf('<%s (%s)> %s', $action, $this->timer->elapsed(), __METHOD__));

  }

  protected function _file(array $params = []) {
    $options = array_merge([
      'creds' => $this->creds,
      'item' => false,
      'folder' => 'default',
      'msg' => false,
      'uid' => false,
      'stream' => false,

    ], $params);

    $inbox = inbox::instance($options['creds']);
    if ($options['msg']) {
      $msg = $inbox->GetItemByMessageID(
        $options['msg'],
        $includeAttachments = true,
        $options['folder']

      );

      if ($msg) {
        sys::dump($msg->attachments);
      } else {
        sys::logger(sprintf(
          '%s/%s : %s',
          $options['folder'],
          $options['msg'],
          __METHOD__

        ));

        $this->render([
          'title' => $this->title = 'Message not found',
          'content' => 'not-found'

        ]);
      }
    } elseif ($options['uid']) {

      $msg = $inbox->GetItemByUID(
        $options['uid'],
        $includeAttachments = true,
        $options['folder']
      );

      if ($msg) {
        if (count($msg->attachments)) {
          // logger::info(sprintf(
          //   '<%s/%s => %s> %s',
          //   $options['folder'],
          //   $options['uid'],
          //   $options['item'],
          //   __METHOD__
          // ));

          // printf( '%s<br />', $options['item']);
          $finfo = new \finfo(FILEINFO_MIME);
          foreach ($msg->attachments as $attachment) {
            $attachmentType = gettype($attachment);
            // logger::info(sprintf('<%s> %s', $attachmentType, __METHOD__));

            if ('object' == $attachmentType) {
              // printf( '%s<br />', $attachment->ContentId);
              if ($options['item'] == $attachment->ContentId) {

                $type = $finfo->buffer($attachment->Content);
                if (false) {
                  /**
                   * There is potential here to view the attachment ...
                   */
                  if (preg_match('@message/rfc822@', $type)) {
                    $parser = new parser;
                    $parser->setText($attachment->Content);
                    $msg = $parser->asMessage();

                    $this->data = (object)[
                      'user_id' => $this->creds->user_id,
                      'default_folders' => inbox::default_folders($this->creds),
                      'message' => $msg

                    ];

                    $this->render([
                      'css' => [
                        sprintf(
                          '<link type="text/css" rel="stylesheet" media="all" href="%s" />',
                          strings::url($this->route . '/normalizecss?t=' . $this->_get_normalizecss_timestamp())
                        )
                      ],
                      'title' => $this->title = $msg->Subject,
                      'template' => 'dvc\mail\pages\minimal',
                      'content' => ['message'],
                      'navbar' => '',

                    ]);
                    // 'charset' => $msg->CharSet,

                    logger::info(sprintf('<%s> %s', $type, __METHOD__));
                    return;
                  }
                }

                header(sprintf('Content-type: %s', $type));
                if (!$options['stream']) header(sprintf('Content-Disposition: attachment; filename="%s"', $attachment->Name));
                print $attachment->Content;
                return;
              }
            } elseif ('string' == $attachmentType) {
              if (preg_match('@^BEGIN:VCALENDAR@', $attachment)) {
                header('Content-type: text/calendar');
                header('Content-Disposition: inline; filename="invite.ics"');
                print $attachment;
              } else {
                print $attachment;
              }
            } else {
              print gettype($attachment);
              return;
            }
          }
        }

        $this->render([
          'title' => $this->title = 'item not found',
          'content' => 'not-found'

        ]);
      } else {
        sys::logger(sprintf(
          '%s/%s : %s',
          $options['folder'],
          $options['uid'],
          __METHOD__
        ));

        $this->render([
          'title' => $this->title = 'Message not found',
          'content' => 'not-found'

        ]);
      }
    }
  }

  protected function _folders($format = 'json') {
    $folders = folders::instance($this->creds);
    return $folders->getAll($format);
  }

  protected function _folder_learnasham() {
    $folders = folders::instance($this->creds);
    $folder = $folders->getByPath('LearnAsHam');
    // logger::info( sprintf('<%s> %s', $folder, __METHOD__));

    return $folder;
  }

  protected function _folder_learnasspam() {
    $folders = folders::instance($this->creds);
    return $folders->getByPath('LearnAsSpam');
  }

  protected function _getDefaultFolders($format = 'json') {
    $folders = folders::instance($this->creds);
    return $folders::$default_folders;
  }

  protected function _index() {
    $this->_webmail($this->creds);
  }

  protected function _headers(array $params = []): array {
    $options = array_merge([
      'creds' => $this->creds,
      'folder' => 'default'

    ], $params);

    $inbox = inbox::instance($options['creds']);
    logger::info(sprintf('<%s> %s', $this->timer->elapsed(), __METHOD__));
    $headers = (array)$inbox->headers($options);

    logger::info(sprintf('<%s> %s', $this->timer->elapsed(), __METHOD__));
    return $headers;
  }

  protected function _messages(array $params = []): array {

    $options = array_merge([
      'creds' => $this->creds,
      'folder' => 'default',
      'page' => 0,
      'deep' => false

    ], $params);

    // logger::info( sprintf('<%s(%s)> %s', $options['folder'], $options['page'], __METHOD__));
    $inbox = inbox::instance($options['creds']);
    // logger::info( sprintf('<%s> %s', $this->timer->elapsed(), __METHOD__));
    $messages = (array)$inbox->finditems($options);
    // logger::info( sprintf('<%s> %s', $this->timer->elapsed(), __METHOD__));
    // sys::dump( $messages);

    $a = [];
    foreach ($messages as $message) $a[] = $message->asArray();
    return $a;
  }

  protected function _search(array $params = []): array {
    $options = array_merge([
      'folder' => 'default',
      'term' => ''

    ], $params);

    $sb = new search($this->creds, $options);
    return $sb->search($options);
  }

  protected function _view(array $params = []) {

    $options = array_merge([
      'creds' => $this->creds,
      'folder' => 'default',
      'msg' => false,
      'uid' => false,
    ], $params);

    $msg = false;
    $inbox = inbox::instance($options['creds']);
    if ($options['msg']) {

      logger::info(sprintf('<byMSG %s> %s', $options['msg'], __METHOD__));

      $msg = $inbox->GetItemByMessageID(
        $options['msg'],
        $includeAttachments = true,
        $options['folder']
      );
    } elseif ($options['uid']) {

      // logger::info( sprintf('<byUID %s> %s', $options['uid'], __METHOD__));

      $msg = $inbox->GetItemByUID(
        $options['uid'],
        $includeAttachments = true,
        $options['folder']
      );
    }

    // logger::info( sprintf('<done got msg> %s', __METHOD__));

    if ($msg) {
      // unset( $msg->attachments);
      // sys::dump( $msg);
      // print $msg->safehtml();
      // return;

      if ($msg->hasMso()) {
        pages\minimal::$docType = Response::mso_docType();
      }

      $this->data = (object)[
        'user_id' => $this->creds->user_id,
        'default_folders' => inbox::default_folders($this->creds),
        'message' => $msg

      ];

      $this->render([
        'css' => [
          sprintf(
            '<link type="text/css" rel="stylesheet" media="all" href="%s" />',
            strings::url($this->route . '/normalizecss?t=' . $this->_get_normalizecss_timestamp())
          )
        ],
        'title' => $this->title = $msg->Subject,
        'template' => 'dvc\mail\pages\minimal',
        'content' => ['message'],
        'navbar' => '',
        'charset' => $msg->CharSet,
      ]);

      // $msg->Body = strings::htmlSanitize( $msg->Body);
      // json::ack( $action)->add( 'message', $msg);

    } else {
      // sys::logger(sprintf('%s/%s : %s', $options['folder'], $options['msg'], __METHOD__));

      $this->render([
        'title' => $this->title = 'View Message',
        'content' => 'not-found'

      ]);
    }
  }

  protected function _webmail(credentials $creds) {
    $dump = false;
    // $dump = true;

    if ($dump) {
      // sys::dump( $creds, __METHOD__);

      $Inbox = inbox::instance($creds);
      // sys::dump( $Inbox, __METHOD__);
      $response = $Inbox->finditems([
        'deep' => true,
        'folder' => $Inbox->defaults()->inbox,
        'pageSize' => 2
      ]);

      sys::dump($response, null, false);
    } else {
      $this->data = (object)[
        'user_id' => $creds->user_id,
        'default_folders' => inbox::default_folders($creds)

      ];

      $folders = folders::instance($creds);
      $folders->checkDefaultFoldersExist($this->data->default_folders);

      $cache = cache::instance();

      $localTimeStamp = max([$this->_get_local_js_timestamp(), $cacheLocal = $cache->get('mail-js-local-timestamp')]);
      $mailTimeStamp = max([$this->_get_mailjs_timestamp(), $cacheMail = $cache->get('mail-js-mail-timestamp')]);
      if (config::$DB_CACHE == 'APC') {

        if ($cacheLocal != $localTimeStamp) $cache->set('mail-js-local-timestamp', $localTimeStamp, 600);
        if ($cacheMail != $mailTimeStamp) $cache->set('mail-js-mail-timestamp', $mailTimeStamp, 600);
      }

      $this->render([
        'title' => $this->title = $this->label,
        'scripts' => [
          strings::url(sprintf('%s/localjs?t=%s', $this->route, $localTimeStamp)),
          strings::url(sprintf('%s/mailjs?t=%s', $this->route, $mailTimeStamp)),
        ],
        'content' => 'inbox'
      ]);
    }
  }

  public function compose() {
    $this->data = (object)[
      'title' => $this->title = 'Email',
      'to' => '',
      'subject' => '',
      'message' => '',
    ];

    $this->load('email-dialog');
  }

  public function file() {
    $msg = $this->getParam('msg');
    $uid = $this->getParam('uid');
    if ($msg || $uid) {
      if ($file = $this->getParam('item')) {
        $this->_file([
          'creds' => $this->creds,
          'folder' => $this->getParam('folder', 'default'),
          'item' => $file,
          'msg' => $msg,
          'uid' => $uid,
          'stream' => 'yes' == $this->getParam('stream'),
        ]);
      } else {
        $this->render([
          'title' => $this->title = 'attachment - file',
          'content' => 'missing-information'

        ]);
      }
    } else {
      $this->render([
        'title' => $this->title = 'attachment - uid',
        'content' => 'missing-information'

      ]);
    }
  }

  public function localjs() {
    print '/* placeholder for local scripts */';
  }

  protected function _get_local_js_timestamp(): string {
    return '';
  }

  protected function _get_mailjs_timestamp(): string {
    $file = config::tempdir()  . '_mailjs_tmp.js';
    if (file_exists($file)) return filemtime($file);
    return '';
  }

  public function mailjs() {
    // 'leadKey' => '00.js',
    jslib::viewjs([
      'debug' => false,
      'libName' => 'mailjs',
      'jsFiles' => sprintf('%s/js/*.js', __DIR__),
      'libFile' => config::tempdir()  . '_mailjs_tmp.js'
    ]);
  }

  protected function _get_normalizecss_timestamp(): string {

    $file = config::tempdir()  . '_mail_normalize.css';
    if (file_exists($file)) {

      $mtime = filemtime($file);
      $cache = cache::instance();
      $TS = max([$mtime, $cacheLocal = $cache->get('mail-normalizecss-timestamp')]);
      if (config::$DB_CACHE == 'APC') {

        if ($cacheLocal != $TS) $cache->set('mail-js-local-timestamp', $TS, 600);
      }

      return $mtime;
    }
    return '';
  }

  public function normalizecss() {

    cssmin::viewcss([
      'debug' => false,
      'libName' => 'mail/normalise',
      'cssFiles' => [__DIR__ . '/normalize.css'],
      'libFile' => config::tempdir()  . '_mail_normalize.css'
    ]);
  }

  public function view() {

    $msg = $this->getParam('msg');
    $uid = $this->getParam('uid');

    if ($msg || $uid) {

      $this->_view([
        'creds' => $this->creds,
        'folder' => $this->getParam('folder', 'default'),
        'msg' => $msg,
        'uid' => $uid,
      ]);
    } else {

      $this->render([
        'title' => $this->title = 'View Message',
        'content' => 'missing-information'
      ]);
    }
  }

  public function webmail() {
    $this->_webmail($this->creds);
  }
}

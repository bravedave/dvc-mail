<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace dvc\imap;

use sys;

class inbox {
	protected $_client = null;

	protected $_creds = null;

	var $errors = [];

	function __construct($creds = null) {
		// sys::dump( $creds);
		$this->_creds = $creds;
		$this->_client = client::instance($creds);
	}

	public function clearflag($id, $folder, $flag) {
		if ($this->_client->open(true, $folder)) {
			$ret = $this->_client->clearflag($id, $flag);
			$this->_client->close();

			return $ret;
		}

		return false;
	}

	public function clearflagByUID($uid, $folder, $flag) {
		if ($this->_client->open(true, $folder)) {
			$ret = $this->_client->clearflagByUID($uid, $flag);
			$this->_client->close();

			return $ret;
		}

		return false;
	}

	public function CopyItem(
		string $itemID,
		string $folder = "default",
		string $archiveFolder
	) {

		$ret = false;

		if ($this->_client->open(true, $folder)) {
			$ret = $this->_client->copy_message($itemID, $archiveFolder);
			$this->_client->close();
		} else {
			sys::logger(sprintf('can\'t open folder %s : %s', $folder, __METHOD__));
		}

		return ($ret);
	}

	public function CopyItemByUID(
		string $uid,
		string $folder = "default",
		string $archiveFolder
	) {

		$ret = false;

		if ($this->_client->open(true, $folder)) {
			$ret = $this->_client->copy_message_byUID($uid, $archiveFolder);
			$this->_client->close();
		} else {
			sys::logger(sprintf('can\'t open folder %s : %s', $folder, __METHOD__));
		}

		return ($ret);
	}

	public function defaults() {
		return (object)[
			'inbox' => client::INBOX

		];
	}

	public function DeleteItem(
		string $itemID,
		string $folder = "default"
	) {

		$ret = false;

		if ($this->_client->open(true, $folder)) {
			$ret = $this->_client->delete_message($itemID);
			$this->_client->close();
		} else {
			sys::logger(sprintf('can\'t open folder %s : %s', $folder, __METHOD__));
		}

		return ($ret);
	}

	public function DeleteItemByUID(
		string $uid,
		string $folder = "default"
	) {

		$ret = false;

		if ($this->_client->open(true, $folder)) {
			$ret = $this->_client->delete_message_byUID($uid);
			$this->_client->close();
		} else {
			sys::logger(sprintf('can\'t open folder %s : %s', $folder, __METHOD__));
		}

		return ($ret);
	}

	public function EmptyTrash($folder) {
		$ret = false;
		$folders = new folders($this->_creds);
		if ($folder == $folders::$default_folders['Trash']) {
			if ($this->_client->open(true, $folder)) {
				$ret = $this->_client->empty_trash();
				$this->_client->close(CL_EXPUNGE);
			} else {
				sys::logger(sprintf('can\'t open folder %s : %s', $folder, __METHOD__));
			}

			return $ret;
		}

		return $ret;
	}

	public function finditems($params) {
		// 'pageSize' => 20,  need to specify ? it's the default
		$options = array_merge([
			'deep' => false,
			'page' => 0,
			'folder' => $this->defaults()->inbox,
			'allPages' => false

		], $params);

		// sys::dump( $options);
		// \sys::logger( sprintf('<%s> %s', $options['folder'], __METHOD__));

		$ret = [];
		try {
			if ($this->_client->open(true, $options['folder'])) {
				$ret = $this->_client->finditems($options);
				$this->_client->close();
			}
		} catch (\Throwable $th) {
			\sys::logger(sprintf('<%s> %s', $th->getMessage(), __METHOD__));
		}

		return ($ret);
	}

	public function FindItemByMessageID(
		$MessageID,
		$includeAttachments = false,
		$folder = 'default'
	) {

		$ret = false;

		return $ret;
	}

	public function GetItemByMessageID(
		$MessageID,
		$includeAttachments = false,
		$folder = 'default'
	) {

		// sys::logger( sprintf('%s/%s :s: %s', $folder, $MessageID, __METHOD__));
		$ret = $this->_client->getmessage($MessageID, $folder);
		// sys::logger( sprintf('%s/%s :e: %s', $folder, $MessageID, __METHOD__));
		// sys::dump( $ret);

		return $ret;
	}

	public function GetItemByUID(
		$UID,
		$includeAttachments = false,
		$folder = 'default'
	) {

		// sys::logger( sprintf('%s/%s :s: %s', $folder, $MessageID, __METHOD__));
		$ret = $this->_client->getmessageByUID($UID, $folder);
		// sys::logger( sprintf('%s/%s :e: %s', $folder, $MessageID, __METHOD__));

		return $ret;
	}

	public function headers($params) {
		$options = array_merge([
			'folder' => $this->defaults()->inbox,

		], $params);

		// sys::dump( $options);

		$ret = [];
		try {
			$ret = $this->_client->headers($options['folder']);

			if ($errors = imap_errors()) {
				foreach ($errors as $error) {
					\sys::logger(sprintf('<%s> %s', $error, __METHOD__));
					\sys::logger(sprintf('<%s> %s', $options['folder'], __METHOD__));
				}
			}
		} catch (\Throwable $th) {
			\sys::logger(sprintf('<%s> %s', $th->getMessage(), __METHOD__));
		}

		return ($ret);
	}

	public function Info($folder = 'default') {
		$ret = $this->_client->Info($folder);
		return $ret;
	}

	public function MoveItem(
		string $itemID,
		string $folder = "default",
		string $archiveFolder
	) {

		$ret = false;

		if ($this->_client->open(true, $folder)) {
			$ret = $this->_client->move_message($itemID, $archiveFolder);
			$this->_client->close();
		} else {
			sys::logger(sprintf('can\'t open folder %s : %s', $folder, __METHOD__));
		}

		return ($ret);
	}

	public function MoveItemByUID(
		string $uid,
		string $folder = "default",
		string $archiveFolder
	) {

		$ret = false;

		if ($this->_client->open(true, $folder)) {
			$ret = $this->_client->move_message_byUID($uid, $archiveFolder);
			$this->_client->close();
		} else {
			sys::logger(sprintf('can\'t open folder %s : %s', $folder, __METHOD__));
		}

		return ($ret);
	}

	public function SaveToFile($message, $msgStore) {
		$debug = false;
		//~ $debug = true;

		if (!is_dir($msgStore)) {
			mkdir($msgStore, 0777);
			chmod($msgStore, 0777);
		}

		if (!is_writable($msgStore)) throw new Exceptions\DirNotWritable($msgStore);

		$attachmentPath = sprintf('%s/attachments', $msgStore);
		if (!file_exists($attachmentPath)) {
			mkdir($attachmentPath, 0777);
			chmod($attachmentPath, 0777);
		}

		if (!is_writable($attachmentPath)) throw new Exceptions\DirNotWritable($attachmentPath);

		$j = [
			'answered' => $message->answered,
			'flagged' => $message->flagged,
			'forwarded' => $message->forwarded,
			'fromName' => $message->fromName,
			'fromEmail' => $message->fromEmail,
			'time' => $message->time,
			'Body' => $message->Body,
			'Folder' => $message->Folder,
			'From' => $message->From,
			'MessageID' => $message->MessageID,
			'Recieved' => $message->Recieved,
			'Subject' => $message->Subject,
			'To' => $message->To

		];

		$file = sprintf('%s/msg.json', $msgStore);
		if (file_exists($file)) {
			if ($debug) \sys::logger(sprintf('msg exists : %s :: %s', $file, __METHOD__));
		} else {
			file_put_contents($file, json_encode($j, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
			if ($debug) \sys::logger(sprintf('save msg : %s :: %s', $file, __METHOD__));
		}

		$fileName = 0;
		foreach ($message->attachments as $attachment) {

			$file = sprintf('%s/%s', $attachmentPath, $attachment->Name ?? 'attachment' . $fileName++);
			if (file_exists($file)) {

				if ($debug) \sys::logger(sprintf('attachment exists : %s :: %s', $file, __METHOD__));
			} else {

				file_put_contents($file, $attachment->Content);
				if ($debug) \sys::logger(sprintf('saved attachment : %s :: %s', $file, __METHOD__));
			}
		}

		// Make sure the destination directory exists and is writeable.

	}

	public function setflag($id, $folder, $flag) {

		if ($this->_client->open(true, $folder)) {

			$ret = $this->_client->setflag($id, $flag);
			$this->_client->close();

			return $ret;
		}

		return false;
	}

	public function setflagByUID($uid, $folder, $flag) {
		if ($this->_client->open(true, $folder)) {
			$ret = $this->_client->setflagByUID($uid, $flag);
			$this->_client->close();

			return $ret;
		}

		return false;
	}

	public function search($params) {
		$options = array_merge([
			'folder' => $this->defaults()->inbox,
			'term' => '',
			'criteria' => [],
			'body' => 'no',

		], $params);

		// sys::dump( $options);

		$ret = [];
		if ($options['term']) {
			$text = '';
			$term = \str_replace('"', '', $options['term']);
			$terms = explode(',', $term);

			// \sys::logger( sprintf('%s : %s', $options['folder'], __METHOD__));
			$_from = [];
			$_subject = [];
			$_text = [];
			foreach ($terms as $_term) {
				if (folders::$default_folders['Sent'] ==  $options['folder']) {
					$_from[] = sprintf('TO "%s"', trim($_term));
				} else {
					$_from[] = sprintf('FROM "%s"', trim($_term));
				}
				$_subject[] = sprintf('SUBJECT "%s"', trim($_term));
				if ('yes' == $options['body']) {
					// \sys::logger( sprintf('<%s> %s', 'add body', __METHOD__));
					$_text[] = sprintf('BODY "%s"', trim($_term));
				}
			}
			$from = implode(' ', $_from);
			$subject = implode(' ', $_subject);
			if ($_text) $text = implode(' ', $_text);

			if (isset($options['from']) && strtotime($options['from']) > 0) {
				$since = strtotime($options['from']);
				$from .= sprintf(' SINCE "%s"', date('d-M-Y', $since));
				$subject .= sprintf(' SINCE "%s"', date('d-M-Y', $since));
				$text .= sprintf(' SINCE "%s"', date('d-M-Y', $since));
			}

			if (isset($options['to']) && strtotime($options['to']) > 0) {
				$before = strtotime($options['to']);
				$from .= sprintf(' BEFORE "%s"', date('d-M-Y', $before));
				$subject .= sprintf(' BEFORE "%s"', date('d-M-Y', $before));
				$text .= sprintf(' BEFORE "%s"', date('d-M-Y', $before));
			}


			/**
			 * We are searching
			 * - depending on wether this is the sent items or not - in the from or to fields
			 * - in the subject
			 * - in the text ? where is this
			 */
			$options['criteria'][] = $from;
			$options['criteria'][] = $subject;
			if ($text) $options['criteria'][] = $text;

			// sys::logger( sprintf('%s : %s', $from, __METHOD__));


			if ($this->_client->open(true, $options['folder'])) {
				$ret = $this->_client->search($options);
				$this->_client->close();

				// added this 2022.04.12
				if ($errors = imap_errors()) {
					\sys::logger(sprintf('<%s> %s', $options['folder'], __METHOD__));
					foreach ($errors as $error) {
						sys::logger(sprintf('<%s> %s', $error, __METHOD__));
					}
				}
			}
		}

		return ($ret);
	}

	public function status(string $folder = 'default') {
		return $this->_client->status($folder);
	}

	public function verify(): bool {
		if ($this->_client->open(true)) {
			return true;
		}

		return false;
	}
}

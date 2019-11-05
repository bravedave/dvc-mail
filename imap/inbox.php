<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/

	*/
namespace dvc\imap;
use sys;

class inbox {
	protected $_client = null;

	var $errors = [];

	function __construct( $creds = null) {
		// sys::dump( $creds);
		$this->_client = client::instance( $creds);

	}

    public function defaults() {
		return (object)[
			'inbox' => client::INBOX

		];

    }

	public function finditems( $params) {
		$options = array_merge([
			'deep' => false,
			'page' => 0,
			'pageSize' => 20,
			'folder' => $this->defaults()->inbox,
			'allPages' => false

		], $params);

		// sys::dump( $options);

		$ret = [];
		if ( $this->_client->open( true, $options['folder'] )) {
			$ret = $this->_client->finditems( $options);
			$this->_client->close();

		}

		return ( $ret );

	}

	public function FindItemByMessageID(
		$MessageID,
		$includeAttachments = false,
		$folder = 'default' ) {

		$ret = false;

		return $ret;

	}

	public function GetItemByMessageID(
		$MessageID,
		$includeAttachments = false,
		$folder = 'default' ) {

		// sys::logger( sprintf('%s/%s :s: %s', $folder, $MessageID, __METHOD__));
		$ret = $this->_client->getmessage( $MessageID, $folder);
		// sys::logger( sprintf('%s/%s :e: %s', $folder, $MessageID, __METHOD__));
		// sys::dump( $ret);

		return $ret;

	}

	public function GetItemByUID(
		$UID,
		$includeAttachments = false,
		$folder = 'default' ) {

		// sys::logger( sprintf('%s/%s :s: %s', $folder, $MessageID, __METHOD__));
		$ret = $this->_client->getmessageByUID( $UID, $folder);
		// sys::logger( sprintf('%s/%s :e: %s', $folder, $MessageID, __METHOD__));

		return $ret;

	}

	public function MoveItem(
		string $itemID,
		string $folder = "default",
		string $archiveFolder ) {

		$ret = false;

		if ( $this->_client->open( true, $folder )) {
			$ret = $this->_client->move_message( $itemID, $archiveFolder);
			$this->_client->close();

		}
		else {
			sys::logger( sprintf( 'can\'t open folder %s : %s', $folder, __METHOD__ ));

		}

		return ( $ret);

	}

	public function ReadFromFile( $msgStore) {
		$debug = false;
		//~ $debug = true;

		$attachmentPath = implode([$msgStore, DIRECTORY_SEPARATOR, 'attachments']);

		$file = implode([$msgStore, DIRECTORY_SEPARATOR, 'msg.json']);
		if ( file_exists( $file)) {
			$j = json_decode( file_get_contents( $file));
			$j->attachments = [];

			$it = new \FilesystemIterator( $attachmentPath);
			foreach ($it as $fileinfo) {
				$j->attachments[] = (object)[
					'name' => $fileinfo->getFilename(),
					'path' => $fileinfo->getPathname()

				];

			}

			return $j;

		}

		return false;

	}

	public function SaveToFile( $message, $msgStore) {
		$debug = false;
		//~ $debug = true;

		if ( !is_dir( $msgStore)) {
			mkdir( $msgStore, 0777);
			chmod( $msgStore, 0777);

		}

		if ( !is_writable($msgStore))
			throw new Exceptions\DirNotWritable( $msgStore);

		$attachmentPath = sprintf( '%s/attachments', $msgStore);
		if ( !file_exists( $attachmentPath)) {
			mkdir( $attachmentPath, 0777);
			chmod( $attachmentPath, 0777);

		}

		if ( !is_writable( $attachmentPath))
			throw new Exceptions\DirNotWritable( $attachmentPath);

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

		$file = sprintf( '%s/msg.json', $msgStore);
		if ( file_exists( $file)) {
			if ( $debug) \sys::logger( sprintf( 'msg exists : %s :: %s', $file, __METHOD__));

		}
		else {
			file_put_contents( $file, json_encode( $j, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
			if ( $debug) \sys::logger( sprintf( 'save msg : %s :: %s', $file, __METHOD__));

		}

		foreach ( $message->attachments as $attachment) {
			$file = sprintf( '%s/%s', $attachmentPath, $attachment->Name);
			if ( file_exists( $file)) {
				if ( $debug) \sys::logger( sprintf( 'attachment exists : %s :: %s', $file, __METHOD__));

			}
			else {
				file_put_contents( $file, $attachment->Content);
				if ( $debug) \sys::logger( sprintf( 'saved attachment : %s :: %s', $file, __METHOD__));

			}

		}

		// Make sure the destination directory exists and is writeable.

	}

	public function setflag( $id, $folder, $flag) {
		if ( $this->_client->open( true, $folder )) {
			$ret = $this->_client->setflag( $id, $flag);
			$this->_client->close();

			return $ret;

		}

		return false;


	}

	public function search( $params) {
		$options = array_merge([
			'folder' => $this->defaults()->inbox,
			'term' => '',
			'criteria' => [],

		], $params);

		// sys::dump( $options);

		$ret = [];
		if ( $options['term']) {
			$terms = [];
			$term = \str_replace( '"', '', $options['term']);
			$options['criteria'][] = sprintf( 'FROM "%s"', $term);
			$options['criteria'][] = sprintf( 'SUBJECT "%s"', $term);

			if ( $this->_client->open( true, $options['folder'] )) {
				$ret = $this->_client->search( $options);
				$this->_client->close();

			}

		}

		return ( $ret );

	}

}
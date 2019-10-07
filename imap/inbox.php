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

		$ret = false;

		// if ( $this->_client->open( true, $folder)) {
			$ret = $this->_client->getmessage( $MessageID, $folder);
			// $this->_client->close();

		// }

		// sys::logger( sprintf('%s/%s : %s', $folder, $MessageID, __METHOD__));


		return $ret;

	}

	protected function GetAttachments( $attachmentIDs) {
		// Build the request to get the attachments.
		$request = new Request\GetAttachmentType;
		$request->AttachmentIds = new ArrayType\NonEmptyArrayOfRequestAttachmentIdsType;

		// Iterate over the attachments for the message.
		foreach ($attachmentIDs as $attachmentID) {
			$id = new Type\RequestAttachmentIdType;
			$id->Id = $attachmentID;

			$request->AttachmentIds->AttachmentId[] = $id;
			// \sys::logger( sprintf('%s : %s', $attachmentID, __METHOD__));

		}

		try {
			$response = $this->client->GetAttachment( $request);
			//~ return ( $response);
			//~ return ( $response->ResponseMessages);
			//~ return ( $response->ResponseMessages->GetAttachmentResponseMessage);
			//~ return ( $response->ResponseMessages->GetAttachmentResponseMessage[0]);
			//~ return ( $response->ResponseMessages->GetAttachmentResponseMessage[0]->Attachments);
			// return ( $response->ResponseMessages->GetAttachmentResponseMessage[0]->Attachments->FileAttachment);
			$ret = [];
			foreach ( $response->ResponseMessages->GetAttachmentResponseMessage as $responseMsg) {
				$ret[] = $responseMsg->Attachments->FileAttachment[0];

			}

			return $ret;

		}
		catch (Exception $e) {
			\sys::logger( "GetAttachments : ResponseCode:" . print_r( $response, true ));	//] => NoError

		}


	}

	public function GetItemByID( $itemID, $includeAttachments = false) {

		// Build the request for the parts.
		$request = new Request\GetItemType;
		$request->ItemShape = new Type\ItemResponseShapeType;
		$request->ItemShape->BaseShape = Enumeration\DefaultShapeNamesType::ALL_PROPERTIES;

		// You can get the body as HTML, text or "best".
		$request->ItemShape->BodyType = Enumeration\BodyTypeResponseType::BEST;
		//~ $request->ItemShape->BodyType = Enumeration\BodyTypeResponseType::TEXT;

		// Add the body property.
		$body_property = new Type\PathToUnindexedFieldType;
		$body_property->FieldURI = Enumeration\UnindexedFieldURIType::ITEM_BODY;
		$request->ItemShape->AdditionalProperties = new ArrayType\NonEmptyArrayOfPathsToElementType;
		//~ $request->ItemShape->AdditionalProperties->FieldURI = array($body_property);
		$request->ItemShape->AdditionalProperties->FieldURI = [$body_property];

		$request->ItemIds = new ArrayType\NonEmptyArrayOfBaseItemIdsType;
		$request->ItemIds->ItemId = [];

		// Add the message to the request.
		$message_item = new Type\ItemIdType;
		$message_item->Id = $itemID;
		$request->ItemIds->ItemId[] = $message_item;

		/*
			try to figure if email has been replied to
		// http://jamesarmes.com/php-ews/doc/0.1/examples/email-retrieve-extended-properties.html
		$request->ItemShape->AdditionalProperties = new ArrayType\NonEmptyArrayOfPathsToElementType();
		// set fields we want to request
		$subject = new  Type\PathToUnindexedFieldType();
			$subject->FieldURI = 'item:Subject';
		$date = new Type\PathToUnindexedFieldType();
			$date->FieldURI = 'item:DateTimeReceived';
		$message_id = new  Type\PathToUnindexedFieldType();
			$message_id->FieldURI = 'message:InternetMessageId';
		$is_read = new  Type\PathToUnindexedFieldType();
			$is_read->FieldURI = 'message:IsRead';
		$request->ItemShape->AdditionalProperties->FieldURI = [$subject, $date, $message_id, $is_read];

		$status = new Type\PathToExtendedFieldType();
		$status->PropertyTag = "0x1081";
		$status->PropertyType = Enumeration\MapiPropertyTypeType::INTEGER;
		$request->ItemShape->AdditionalProperties->ExtendedFieldURI = [$status];
		*/


		//~ \sys::logger( $itemID);

		try {
			$response = $this->client->GetItem( $request);
			//~ return ( $response->ResponseMessages->GetItemResponseMessage[0]->Items);
			$msg = new message( $response->ResponseMessages->GetItemResponseMessage[0]->Items->Message[0]);
			if ( $includeAttachments) {
				if ( count( $msg->attachmentIDs))
					$msg->attachments = $this->GetAttachments( $msg->attachmentIDs);

			}

			return ( $msg);

		}
		catch (Exception $e) {
			\sys::logger( "Could Not Get Body of Message : ResponseCode:" . print_r( $response, true ));	//] => NoError
			//~ throw new Exception( $e );

		}

		try {
			$item->notes = $response->ResponseMessages->GetItemResponseMessage->Items->CalendarItem->Body->_;

		}
		catch (Exception $e) {
			\sys::logger( "Could Not Get Body of Agenda : ResponseCode:" . print_r( $response, true ));	//] => NoError
			//~ throw new Exception( $e );

		}

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

	public function SaveToFile( $message, $msgStore) {
		// TODO: make sure this works
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

}
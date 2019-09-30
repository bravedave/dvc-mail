<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/

	*/
namespace dvc\ews;

use \jamesiarmes\PhpEws;
use \jamesiarmes\PhpEws\ArrayType;
use \jamesiarmes\PhpEws\Enumeration;
use \jamesiarmes\PhpEws\Request;
use \jamesiarmes\PhpEws\Type;

class inbox {
	protected $client;
	var $errors = [];

	function __construct( $creds = null) {
		$this->client = client::instance( $creds);

	}

    public function defaults() {
		return (object)[
			'inbox' => 'INBOX'

		];

    }

	public function finditems( $params) {
		$options = (object)array_merge([
			'deep' => false,
			'page' => 0,
			'pageSize' => 20,
			'folder' => $this->defaults()->inbox,
			'allPages' => false

		], $params);

		// Build the request.
		$request = new Request\FindItemType;
		$request->ParentFolderIds = new ArrayType\NonEmptyArrayOfBaseFolderIdsType;
		$request->Traversal = Enumeration\ItemQueryTraversalType::SHALLOW;

		// Return all message properties.
		$request->ItemShape = new Type\ItemResponseShapeType;
		$request->ItemShape->BaseShape = Enumeration\DefaultShapeNamesType::ALL_PROPERTIES;

		if ( $options->folder == $this->defaults()->inbox || $options->folder == 'default') {
			$folder_id = new Type\DistinguishedFolderIdType;
			$folder_id->Id = Enumeration\DistinguishedFolderIdNameType::INBOX;
			$request->ParentFolderIds->DistinguishedFolderId[] = $folder_id;

		}
		elseif ( $options->folder == 'DELETED') {
			$folder_id = new Type\DistinguishedFolderIdType;
			$folder_id->Id = Enumeration\DistinguishedFolderIdNameType::DELETED;
			$request->ParentFolderIds->DistinguishedFolderId[] = $folder_id;

		}
		else {
			$folders = new folders;
			if ( $folder = $folders->getByPath( $options->folder)) {
				$folder_id = new Type\FolderIdType;
				$folder_id->Id = $folder->id->Id;
				$request->ParentFolderIds->FolderId[] = $folder_id;

			}
			// throw new \Exception( 'ews_feature_not_written :: select other folders');

		}

		$request->QueryString = new Type\QueryStringType;
		$request->QueryString->_ = '(kind:messages)';


		// Limits the number of items retrieved
		$request->IndexedPageItemView = new Type\IndexedPageViewType;
		$request->IndexedPageItemView->BasePoint = Enumeration\IndexBasePointType::BEGINNING;
		$request->IndexedPageItemView->Offset = $options->page;
		$request->IndexedPageItemView->MaxEntriesReturned = $options->pageSize;

		$response = $this->client->FindItem( $request);
		//~ \sys::dump( $response);

		// Iterate over the results, printing any error messages or message subjects.
		$response_messages = $response->ResponseMessages->FindItemResponseMessage;

		$messages = [];
		foreach ( $response_messages as $response_message) {
			if ( $response_message->ResponseClass != Enumeration\ResponseClassType::SUCCESS) {
				$code = $response_message->ResponseCode;
				$message = $response_message->MessageText;
				$this->errors[] = sprintf( 'Failed to search for messages with "%s: %s"', $code, $message );
				continue;

			}

			// Set the base values from the first page of results.
			$messages = $response_message->RootFolder->Items->Message;
			$last_page = $response_message->RootFolder->IncludesLastItemInRange;
			if ( $options->allPages) {
				for ( $page_number = 1; !$last_page; ++$page_number) {
					//~ break;

					//~ if ( $page_number > 2 ) break;

					// Until we have the last page, keep requesting the next page of messages.
					// Request the next page.
					$request->IndexedPageItemView->Offset = self::$page_size * $page_number;
					//~ \sys::logger( sprintf( '%s : %s', $page_number, $request->IndexedPageItemView->Offset));
					$response = $this->client->FindItem( $request);

					// Add the messages to the list of messages retrieved. If the total
					// number of messages is large, you could easily run out of memory here.
					// It is advised that you perform you operations on messages when you
					// retrieve them rather than keeping a list of them in memory.
					$response_message = $response->ResponseMessages->FindItemResponseMessage[0];

					$messages = array_merge(
						$messages,
						$response_message->RootFolder->Items->Message
					);

					// Store the updated last page value.
					$last_page = $response_message->RootFolder->IncludesLastItemInRange;

				}

			}

		}

		$msgs = [];
		foreach ( $messages as $message) {
			$m = new message( $message);
			$m->Folder = $options->folder;
			unset( $m->src);
			$msgs[] = $m;

		}

		//~ \sys::logger( sprintf( 'ews\inbox->finditems :: %d', count( $messages)));
		//~ return ( $messages);

		if ( $options->deep) {
			$ms = [];
			foreach ( $msgs as $msg) {
				$m = $this->GetItemByID( $msg->ItemId->Id);
				$m->Folder = $options->folder;
				$ms[] = $m;

			}

			return ( $ms);

		}

		return ( $msgs);

	}

	public function FindItemByMessageID(
		$MessageID,
		$includeAttachments = false,
		$folder = 'default' ) {

		// Build the get item request.
		$request = new Request\FindItemType;
		$request->ParentFolderIds = new ArrayType\NonEmptyArrayOfBaseFolderIdsType;

		// Return all message properties.
		$request->ItemShape = new Type\ItemResponseShapeType;
		//~ $request->ItemShape->BaseShape = Enumeration\DefaultShapeNamesType::ALL_PROPERTIES;
		$request->ItemShape->BaseShape = Enumeration\DefaultShapeNamesType::ID_ONLY;

		$request->Traversal = Enumeration\ItemQueryTraversalType::SHALLOW;	// Search recursively.
		//~ $request->Traversal = Enumeration\ItemQueryTraversalType::DEEP;

		// \sys::logger(sprintf( 'folder: %s : %s', $folder, __METHOD__));
		if ( $folder == $this->defaults()->inbox || $folder == 'default') {
			$folder_id = new Type\DistinguishedFolderIdType;
			$folder_id->Id = Enumeration\DistinguishedFolderIdNameType::INBOX;
			$request->ParentFolderIds->DistinguishedFolderId[] = $folder_id;

		}
		elseif ( $folder == 'DELETED') {
			$folder_id = new Type\DistinguishedFolderIdType;
			$folder_id->Id = Enumeration\DistinguishedFolderIdNameType::DELETED;
			$request->ParentFolderIds->DistinguishedFolderId[] = $folder_id;

		}
		else {
			$folders = new folders;
			if ( $_folder = $folders->getByPath( $folder)) {
				$folder_id = new Type\FolderIdType;
				$folder_id->Id = $_folder->id->Id;
				$request->ParentFolderIds->FolderId[] = $folder_id;

			}
			// throw new \Exception( 'ews_feature_not_written :: select other folders');

		}

		$request->Restriction = new Type\RestrictionType;

		// Build the messageID restriction;
		$equalTo = new Type\IsEqualToType;
		$equalTo->FieldURI = new Type\PathToUnindexedFieldType();
		$equalTo->FieldURI->FieldURI = Enumeration\UnindexedFieldURIType::MESSAGE_INTERNET_MESSAGE_ID;
		$equalTo->FieldURIOrConstant = new Type\FieldURIOrConstantType;
		$equalTo->FieldURIOrConstant->Constant = new Type\ConstantValueType;
		$equalTo->FieldURIOrConstant->Constant->Value = $MessageID;

		$request->Restriction->IsEqualTo = $equalTo;

		/*--- ---[FindItemType]--- ---*/

		try {
			$response = $this->client->FindItem($request);
			//~ return ( $response);
			//~ return ( $response->ResponseMessages);
			//~ return ( $response->ResponseMessages->FindItemResponseMessage);
			//~ return ( $response->ResponseMessages->FindItemResponseMessage[0]);
			//~ return ( $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder);
			//~ return ( $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder->Items);
			//~ return ( $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder->Items->Message);
			$msgs = $response->ResponseMessages->FindItemResponseMessage[0]->RootFolder->Items->Message;
			if ( count( $msgs))
				return $msgs[0];

			//~ return ( $response->ResponseMessages->FindItemResponseMessage->Message);

			return ( false);

		}
		catch (Exception $e) {
			\sys::logger( "GetItemByMessageID : ResponseCode:" . print_r( $response, TRUE ));	//] => NoError

		}

	}

	public function GetItemByMessageID(
		$MessageID,
		$includeAttachments = false,
		$folder = 'default' ) {

		if ( $msg = $this->FindItemByMessageID( $MessageID, $includeAttachments, $folder)) {
			// \sys::logger( 'found message');
			return $this->GetItemByID( $msg->ItemId->Id, $includeAttachments);

		}
		else {
			// \sys::logger( sprintf('message %s not found in %s : %s', $MessageID, $folder, __METHOD__));

		}

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

	public function MoveItem( $item, $FolderPath ) {
		$dao = new folders;
		if ( $fldr = $dao->getByPath( $FolderPath)) {
			// Form the move request.
			$request = new Request\MoveItemType;
			$request->ItemIds = (array)$item;
			$request->ToFolderId = new Type\TargetFolderIdType;
			$request->ToFolderId->FolderId = $fldr->id;

			return query::MoveItem( $request);

		}

		return ( false);

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
			file_put_contents( $file, json_encode( $j));
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
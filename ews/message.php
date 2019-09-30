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


class message extends \dvc\mail\message {
	//~ var $src = NULL;

	function __construct( Type\MessageType $src) {
		$this->src = $src;

		$this->ItemId = $src->ItemId;
		$this->MessageID = $src->InternetMessageId;
		$this->Recieved = $src->DateTimeReceived;
		$this->time = strtotime( $src->DateTimeReceived);

		if ( isset( $src->From)) {
			$this->fromEmail = $src->From->Mailbox->EmailAddress;
			// \sys::logger( $this->fromEmail);
			$this->fromName = $src->From->Mailbox->Name;
			$this->From = ( $this->fromName ? $this->fromName : $this->fromEmail);

		}

		$this->To = $src->DisplayTo;

		$encoding = mb_detect_encoding( $src->Subject);
		//~ \sys::logger( $encoding);
		if ( $encoding)
			$this->Subject = $src->Subject;
		else
			$this->Subject = utf8_encode( $src->Subject);

		if ( isset( $src->Body->_)) {
			$this->Body = $src->Body->_;
			$this->BodyType = utf8_encode( $src->Body->BodyType);

		}

		if ( 1 == (int)$src->IsRead)
			$this->seen = 'yes';

		if ( !empty($src->Attachments)) {
			// Iterate over the attachments for the message.
			foreach ($src->Attachments->FileAttachment as $attachment)
				$this->attachmentIDs[] = $attachment->AttachmentId->Id;

		}

		return;

	}

	static function header() {
		return ( sprintf( '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			'Date',
			'From',
			'Subject'
			));

	}

	function row() {
		$d = strtotime( $this->Recieved);
		if ( date('Y-m-d') == date( 'Y-m-d', $d))
			$d = date( 'g:i a', $d);
		elseif ( date('Y') == date( 'Y', $d))
			$d = date( 'd-M', $d);
		else
			$d = date( \config::$SHORTDATE_FORMAT, $d);

		return ( sprintf( '<tr><td class="text-center">%s</td><td>%s</td><td>%s</td></tr>',
			$d,
			$this->fromName,
			$this->Subject
			));

	}

	public function asArray() {
		// \sys::logger( $this->fromEmail);

		return [
			'received' => $this->Recieved,
			'messageid' => $this->MessageID,
			'to' => $this->To,
			'from' => $this->From,
			'fromEmail' => $this->fromEmail,
			'subject' => $this->Subject,
			'seen' => $this->seen,
			'folder' => $this->Folder,

		];

	}

}

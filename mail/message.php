<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 *      http://creativecommons.org/licenses/by/4.0/
 *
*/

namespace dvc\mail;

class message {

    public $attachmentIDs = [];
	public $attachments = [];
	public $answered = 'no';
	public $flagged = 'no';
	public $forwarded = 'no';
	public $fromName = '';
	public $fromEmail = '';
	public $seen = 'no';
	public $tags = '';
	public $time = '';

	public $BodyType = '';
	public $Body = '';
	public $Folder = '';
	public $From = '';
	public $ItemId = '';
	public $MessageID = '';
	public $Recieved = '';
	public $Subject = '';
	public $To = '';
	public $Uid = '';

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
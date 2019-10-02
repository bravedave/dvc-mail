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

class folders {
	protected $client;
	var $errors = [];

	static $default_folders = [
		'Inbox' => client::INBOX,

		'Sent' => 'Sent',

		'Trash' => 'Trash'

	];

	function __construct( $creds = null) {
		$this->client = client::instance( $creds);

	}

	static function map( $fldr, $fldrs, $level) {
		foreach ( $fldrs as $f) {
			if ( $f->id->Id == $fldr->parent_id) {
				if ( $level > 6)
					return ( sprintf( '%s;%s', $f->name, $fldr->name));
				else
					return ( sprintf( '%s;%s', folders::map( $f, $fldrs, $level + 1), $fldr->name));

			}

		}

		return $fldr->name;

	}


	protected function _getAll() {

		// Build the request.
		$request = new Request\FindFolderType;
		$request->FolderShape = new Type\FolderResponseShapeType;
		$request->FolderShape->BaseShape = Enumeration\DefaultShapeNamesType::ALL_PROPERTIES;
		$request->ParentFolderIds = new ArrayType\NonEmptyArrayOfBaseFolderIdsType;
		//~ $request->Restriction = new Type\RestrictionType;

		// Search recursively.
		$request->Traversal = Enumeration\FolderQueryTraversalType::DEEP;

		// Search within the root folder. Combined with the traversal set above, this
		// should search through all folders in the user's mailbox.
		$parent = new Type\DistinguishedFolderIdType();
		$parent->Id = Enumeration\DistinguishedFolderIdNameType::ROOT;
		$request->ParentFolderIds->DistinguishedFolderId[] = $parent;

		$response = $this->client->FindFolder( $request);

		$response_messages = $response->ResponseMessages->FindFolderResponseMessage;

		$folders = [];
		foreach ($response_messages as $response_message) {
			// Make sure the request succeeded.
			if ($response_message->ResponseClass != Enumeration\ResponseClassType::SUCCESS) {
				$code = $response_message->ResponseCode;
				$message = $response_message->MessageText;
				sys::logger( sprintf( 'dvc\ews\folders :: Failed to find folders with "%s: %s"', $code, $message));
				continue;

			}

			/*
			* folders could be of any type,
			* combine all of them into a single
			* array to iterate over them.
			*/
			$_fldrs = array_merge(
				$response_message->RootFolder->Folders->CalendarFolder,
				$response_message->RootFolder->Folders->ContactsFolder,
				$response_message->RootFolder->Folders->Folder,
				$response_message->RootFolder->Folders->SearchFolder,
				$response_message->RootFolder->Folders->TasksFolder

			);

			$_fldrs = $response_message->RootFolder->Folders->Folder;	// message folders
			//~ return ( $response_message->RootFolder->Folders);
			//~ return ( $_fldrs);

			// Iterate over the found folders.
			$_folders = [];
			foreach ( $_fldrs as $fldr) {
				if ( 'IPF.Note' !== $fldr->FolderClass) continue;
				if ( 'Sharing' === $fldr->DisplayName) continue;
				if ( 'Server Failures' === $fldr->DisplayName) continue;
				if ( 'Local Failures' === $fldr->DisplayName) continue;
				if ( 'Conflicts' === $fldr->DisplayName) continue;
				if ( 'Sync Issues' === $fldr->DisplayName) continue;
				if ( 'Outbox' === $fldr->DisplayName && 0 == (int)$fldr->TotalCount) continue;

				$_folders[] = (object)[
					'name' => $fldr->DisplayName,
					'class' => $fldr->FolderClass,
					'id' => $fldr->FolderId,
					'TotalCount' => (int)$fldr->TotalCount,
					'parent_id' => $fldr->ParentFolderId->Id];

			}

			foreach ( $_folders as $fldr) {
				$fldr->map = folders::map( $fldr, $_folders, 0);
				$folders[] = $fldr;

			}

			usort( $folders, function( $a, $b) {
				if ( 'inbox' == strtolower($a->name))
					return -1;
				if ( 'inbox' == strtolower($b->name))
					return 1;

				if ( 'drafts' == strtolower($a->name))
					return -1;
				if ( 'drafts' == strtolower($b->name))
					return 1;

				if ( 'sent items' == strtolower($a->name))
					return -1;
				if ( 'sent items' == strtolower($b->name))
					return 1;

				return strcmp($a->map, $b->map);

			});

		}

		return ( $folders);

	}

	protected function _allToJson( $fldrs) {

		$d = [];
		foreach ( $fldrs as $f)
			$d[] = $f->map;

		$obj = function( $txt, $map) {
			return (object)[
				'name' => $txt,
				'map' => $map,
				'fullname' => str_replace( ';', '/', $map),
				'type' => 0,
				'delimiter' => '/'
			];

		};

		$last = [];
		$dX = [];
		foreach ( $fldrs as $f) {
			if ( count( $last) && $last[count($last)-1]->map . ';' == substr( $f->map, 0, strlen( $last[count($last)-1]->map)+1)) {
				if ( !isset( $last[count($last)-1]->subFolders))
					$last[count($last)-1]->subFolders = [];

				$o = $obj( $f->name, $f->map);

				$last[count($last)-1]->subFolders[] = $o;
				$last[] = $o;

			}
			else {

				$processed = false;
				while ( count( $last)) {
					array_pop( $last);

					if ( count( $last) && $last[count($last)-1]->map . ';' == substr( $f->map, 0, strlen( $last[count($last)-1]->map)+1)) {

						if ( !isset( $last[count($last)-1]->subFolders))
							$last[count($last)-1]->subFolders = [];

						$o = $obj( $f->name, $f->map);

						$last[count($last)-1]->subFolders[] = $o;
						$last[] = $o;

						$processed = TRUE;
						break;

					}

				}

				if ( $processed) continue;

				$o = $obj( $f->name, $f->map);

				$dX[] = $o;
				$last[] = $o;

			}

		}

		return ( $dX);

	}

	function getByPath( $FolderPath) {
		if ( $fldrs = $this->getAll()) {
			foreach ( $fldrs as $fldr) {
				if ( $FolderPath == $fldr->map) {
					return ( $fldr);

				}

			}

		}

		return ( false);

	}

	function getAll( $format = '') {
		$res = $this->_getAll();
		return ( $format == 'json' ? $this->_allToJson( $res) : $res);

	}

}


<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
 * composer require webklex/php-imap
*/

use dvc\mail\credentials;

class test extends dvc\mail\controller {

  protected function __getFolders($_folders) {
    $folders = [];
    //Loop through every Mailbox
    /** @var \Webklex\PHPIMAP\Folder $folder */
    foreach($_folders as $_folder){
      $f = (object)[
        'name' => $_folder->name,
        'map' => $_folder->path,
        'fullname' => $_folder->path,
        'type' => 0,
        'delimiter' => '.',

      ];

      if ( $sub = $this->__getFolders( $_folder->children)) {
        $f->subFolders = $sub;

      }

      if ( preg_match( "@^inbox@i", $f->name)) {
        array_unshift( $folders, $f);

      }
      else {
        $folders[] = $f;

      }

    }

    return $folders;

  }

  protected function __Webklex_Client() {

    if ( dvc\imap\account::$ENABLED) {
      $host = preg_replace( '@^ssl://@', '', $this->creds->server);
      $port = preg_match( '@^ssl://@', $this->creds->server) ? 993 : 143;
      $encryption = preg_match( '@^ssl://@', $this->creds->server) ? 'ssl' : false;

      $cm = new Webklex\PHPIMAP\ClientManager($options = []);
      // or create a new instance manually
      $client = $cm->make([
        'host'          => $host,
        'port'          => $port,
        'encryption'    => $encryption,
        'validate_cert' => true,
        'username'      => $this->creds->account,
        'password'      => $this->creds->password,
        'protocol'      => 'imap'

        ]

      );

      return $client;

    }

    return null;

  }

  protected function __Webklex_Folders() {

    $folders = [];

    if ( $client = $this->__Webklex_Client()) {
      $client->connect();
      //Get all Mailboxes
      $_folders = $client->getFolders(); /** @var \Webklex\PHPIMAP\Support\FolderCollection $folders */
      $folders = $this->__getFolders( $_folders);

      $client->disconnect();

    }

    return $folders;

  }

  protected function __Webklex_Messages( $mailbox) {
    $messages = [];

    if ( $client = $this->__Webklex_Client()) {
      $client->connect();
      if ( $folder = $client->getFolderByName($mailbox)) {
        // $messages = $folder->overview($sequence = "187:*");

        $_messages = $folder->query()->where(['SUBJECT' => 'Hey - are we there yet'])->get();//->all();

        // $_messages = $folder->messages()->all()->get();

        /** @var \Webklex\PHPIMAP\Message $message */
        foreach($_messages as $_message){
          // $_message->setAvailableFlags( '\Answered \Flagged \Deleted \Seen \Draft $Forwarded');
          $msg = (object)[
            'flaglist' => Webklex\PHPIMAP\ClientManager::get('flags'),
            'flags' => $_message->getFlags()->all(),
            'subject' => $_message->get('subject')->get(),
          ];

          $messages[] = $msg;

        }

      }

      $client->disconnect();

    }

    return $messages;

  }

	protected function before() {
		parent::before();

        /**
         * in the development environment this
         * establishes a local account
         *
         * use this area to establish an account
         *
         */

		if ( dvc\mail\config::$ENABLED) {

			if ( 'ews' == dvc\mail\config::$MODE) {
				$this->creds = currentUser::exchangeAuth();

			}
			elseif ( 'imap' == dvc\mail\config::$MODE) {
				if ( dvc\imap\account::$ENABLED) {
					$this->creds = new credentials(
						dvc\imap\account::$USERNAME,
						dvc\imap\account::$PASSWORD,
						dvc\imap\account::$SERVER

					);

					$this->creds->interface = dvc\mail\credentials::imap;
					if ( 'exchange' == dvc\imap\account::$TYPE) {
            dvc\imap\folders::changeDefaultsToExchange();

					}
					// sys::dump( $this->creds);

				}

			}

		}

	}

  protected function getFlagsOfMsg( $id) {
    $host = preg_replace( '@^ssl://@', '', $this->creds->server);
    $port = preg_match( '@^ssl://@', $this->creds->server) ? 993 : 143;

    $imap = new dvc\imap\ImapSocket([
      'server' => $host,
      'port' => $port,
      'login' => $this->creds->account,
      'password' => $this->creds->password,
      'tls' => false,
      'ssl' => (bool)preg_match( '@^ssl://@', $this->creds->server),

    ], 'INBOX');

    return $imap->get_flags( $id);

  }

  protected function _index() {
    // $res = $this->__Webklex_Folders);
    // $res = $this->_folders();
    // $res = $this->__Webklex_Messages('INBOX');

    $res = $this->getFlagsOfMsg(187);

    sys::dump( $res, application::timer()->elapsed(), false);

  }

}

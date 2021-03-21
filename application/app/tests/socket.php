<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace tests;

use dvc\mail\config;
use dvc\mail\credentials;
use dvc\imap\account;
use dvc\imap\ImapSocket;

class socket {
  public static function instance( $mailbox) : ?ImapSocket {
		if ( config::$ENABLED) {

			if ( 'imap' == config::$MODE) {
				if ( account::$ENABLED) {
					$creds = new credentials(
						account::$USERNAME,
						account::$PASSWORD,
						account::$SERVER

					);

					$creds->interface = credentials::imap;

          $host = preg_replace( '@^ssl://@', '', $creds->server);
          $port = preg_match( '@^ssl://@', $creds->server) ? 993 : 143;

          return new ImapSocket([
            'server' => $host,
            'port' => $port,
            'login' => $creds->account,
            'password' => $creds->password,
            'tls' => false,
            'ssl' => (bool)preg_match( '@^ssl://@', $creds->server),

          ], $mailbox);

				}

			}

		}

    return null;

  }

}

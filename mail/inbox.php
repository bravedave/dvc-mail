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

abstract class inbox {
    static public function instance( credentials $creds) {
        switch ($creds->interface) {
            case credentials::imap :
                return new \dvc\imap\inbox( $creds);
                break;

            case credentials::ews :
                // self::$_defaults = (object)[
                //     'inbox' => 'INBOX'

                // ];
                return new \dvc\ews\inbox( $creds);
                break;


        }

        return false;

    }

    static public function default_folders( $creds) : array {
        switch ($creds->interface) {
            case credentials::imap :
                return \dvc\imap\client::default_folders();
                break;

            case credentials::ews :
                return \dvc\ews\client::default_folders();
                break;

        }

        return false;

    }

	static public function ReadFromFile( $msgStore) {
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

}
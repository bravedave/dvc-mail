<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
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

	static public function FiledMessageExists( $msgStore) {
		$file = implode([$msgStore, DIRECTORY_SEPARATOR, 'msg.json']);
		if ( file_exists( $file)) {
      /**
       * based on I had a message that was 416k - it
       * was sent from an iPhone with a
       * single attachment
       */
      return ( \filesize( $file) > 384);
      // return ( \filesize( $file) > 1024);

    }

    return false;

	}

	static public function ReadFromFile( $msgStore) {
        if ( self::FiledMessageExists( $msgStore)) {
            $debug = false;
            //~ $debug = true;

            $file = implode([$msgStore, DIRECTORY_SEPARATOR, 'msg.json']);
            $j = json_decode( file_get_contents( $file));
            // \sys::logger( sprintf('<%s / %s> %s', $file, gettype( $j), __METHOD__));

            $j->attachments = [];

            $attachmentPath = implode([$msgStore, DIRECTORY_SEPARATOR, 'attachments']);
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

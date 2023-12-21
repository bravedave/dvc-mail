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
  static public function instance(credentials $creds) {

    switch ($creds->interface) {

      case credentials::imap:
        return new \dvc\imap\inbox($creds);
        break;

      case credentials::ews:
        return new \dvc\ews\inbox($creds);
        break;
    }

    return false;
  }

  static public function default_folders($creds): array {
    switch ($creds->interface) {
      case credentials::imap:
        return \dvc\imap\client::default_folders();
        break;

      case credentials::ews:
        return \dvc\ews\client::default_folders();
        break;
    }

    return false;
  }

  static public function FiledMessageExists($msgStore) {
    $file = implode([$msgStore, DIRECTORY_SEPARATOR, 'msg.json']);
    if (file_exists($file)) {
      /**
       * based on I had a message that was 416k - it
       * was sent from an iPhone with a
       * single attachment
       */
      return (\filesize($file) > 384);
      // return ( \filesize( $file) > 1024);

    }

    return false;
  }

  static public function ReadFromFile($msgStore) {

    if (self::FiledMessageExists($msgStore)) {

      $debug = false;
      //~ $debug = true;

      $file = implode([$msgStore, DIRECTORY_SEPARATOR, 'msg.json']);
      $j = json_decode(file_get_contents($file));
      // \sys::logger( sprintf('<%s / %s> %s', $file, gettype( $j), __METHOD__));

      $j->attachments = $j->attachments ?? [];
      if (!is_array($j->attachments)) $j->attachments = [];

      $attachmentPath = implode([$msgStore, DIRECTORY_SEPARATOR, 'attachments']);
      $it = new \FilesystemIterator($attachmentPath);

      foreach ($it as $fileinfo) {

        $idx = array_search($fileinfo->getFilename(), array_column($j->attachments, 'Name'));
        if ($idx !== false) {

          if ($debug) \sys::logger(sprintf('<%s> %s', $fileinfo->getFilename(), __METHOD__));
          $j->attachments[$idx]->Content = file_get_contents($fileinfo->getPathname());
          $j->attachments[$idx]->path = $fileinfo->getPathname();
        } else {

          if ($debug) \sys::logger(sprintf('<%s> %s', $fileinfo->getFilename(), __METHOD__));
          $j->attachments[] = (object)[
            'Name' => $fileinfo->getFilename(),
            'ContentId' => $fileinfo->getFilename(),
            'Content' => file_get_contents($fileinfo->getPathname()),
            'path' => $fileinfo->getPathname()
          ];
        }
      }

      return $j;
    }

    return false;
  }
}

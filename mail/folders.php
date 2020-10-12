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

abstract class folders {
  static function instance( credentials $creds) {
    switch ($creds->interface) {
      case credentials::imap :
        return new \dvc\imap\folders( $creds);
        break;

      case credentials::ews :
        // self::$_defaults = (object)[
        //     'inbox' => 'INBOX'

        // ];
        return new \dvc\ews\folders( $creds);
        break;

    }

    return false;

  }

}

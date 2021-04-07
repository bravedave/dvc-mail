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

use dvc;

class tests extends dvc\service {
  public static function socket() {
    $app = new self( \application::startDir());
    $app->_socket();

  }

  protected function _socket() {

    if ( $sock = socket::instance('INBOX')) {
      // print_r( $sock->get_flags( 187));  // of the first message

      for ($i=1; $i <= 187; $i++) {
        print_r( $sock->get_overview( $i));  // of the first message

      }
      // print_r( $sock->get_overview( 187));  // of the first message
      // print_r( $sock->get_overview( 27));  // of the first message
      // print_r( $sock->get_overview( 18));  // of the first message

    }
    else {
      echo 'could not establish instance ...';

    }

  }

  public static function status() {
    $app = new self( \application::startDir());
    $app->_status();

  }

  protected function _status() {

    if ( $imap = imap::instance('INBOX')) {
      print_r( $imap->Info());
      print_r( $imap->status());

    }
    else {
      echo 'could not establish instance ...';

    }

  }

}

<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace dvc\imap;

use bravedave\dvc\logger;
use dvc\mail\attachment;
use strings;
use sys;

use Sabre\VObject;

class RawMessage {
  public $charset = '',
    $messageType = '',
    $message = '',
    $messageHTML = '',
    $attachments = [],
    $cids = [];

  protected $plainText = self::HTML;

  const HTML = 0;
  const PLAINTEXT = 1;

  // $debug = true;
  protected $debug = false;
  // protected $debug = true;

  function __construct($stream, $email_number, $plainText = self::HTML) {
    $debug = $this->debug;
    // $debug = true;

    $this->plainText = $plainText;

    // BODY
    if ($debug) logger::debug(sprintf('<-----------------[debug]-----------------> %s', __METHOD__));

    $s = imap_fetchstructure($stream, $email_number);
    if (!isset($s->parts) || !$s->parts) { // simple
      if ($debug) sys::logger(sprintf('simple : %s', __METHOD__));
      $this->getpart($stream, $email_number, $s, 0);  // pass 0 as part-number
      if ($debug) logger::debug(sprintf('exit : %s : %s', $this->messageType, __METHOD__));
    } else {  // multipart: cycle through each part
      foreach ($s->parts as $partno0 => $p) {
        if ($debug) logger::debug(sprintf('<type %s> %s', $p->type, __METHOD__));
        $this->getpart($stream, $email_number, $p, $partno0 + 1);
      }

      if ($debug) {
        logger::debug(sprintf('get parts :e: %s', __METHOD__));
        logger::debug(sprintf('exit : %s : %s', $this->messageType, __METHOD__));
        // \sys::trace( sprintf('exit : %s', __METHOD__));
        // \sys::dump( $this);

      }
    }

    // \sys::dump( $this);

  }

  protected function getpart($mbox, $mid, $p, $partno) {
    $debug = $this->debug;
    $debugPart = $debug;
    // $debugPart = true;
    // $debug = 0 == $p->type;
    // $debug = true;

    if ($debug) sys::logger(sprintf('%s, %s, %s, %s',  $mbox, $mid, $p->type, $partno));

    // $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple

    // DECODE DATA
    $data = ($partno) ?
      imap_fetchbody($mbox, $mid, $partno, FT_PEEK) :  // multipart
      imap_body($mbox, $mid, FT_PEEK);  // simple

    //~ if ( $debug) sys::logger( sprintf( 'encoding : %s : %s',  $p->encoding, $data));
    //~ if ( $debug && $p->ifsubtype) sys::logger( sprintf( '    type :: subtype : %s :: %s',  $p->type, $p->subtype));

    // Any part may be encoded, even plain text messages, so check everything.
    if (4 == $p->encoding) {
      if ($debug) sys::logger(sprintf('quoted_printable_decode : %s', __METHOD__));
      // if ( $debug) sys::logger( sprintf('quoted_printable_decode : %s : %s', print_r( $p, true), __METHOD__));

      // logger::debug( sprintf('<%s> %s', mb_detect_encoding($data), __METHOD__));
      // $f = sprintf('%s/temp-0-rawmessage.txt', \config::dataPath());
      // if ( \file_exists($f)) unlink( $f);
      // \file_put_contents( $f, $data);

      if ($debug) logger::debug(sprintf('<%s> %s', $this->plainText ? 'text' : 'html', __METHOD__));

      $data = quoted_printable_decode($data);

      // logger::info( sprintf('<%s> %s', mb_detect_encoding($data), __METHOD__));
      // $f = sprintf('%s/temp-1-%s-rawmessage.txt', \config::dataPath(), $partno);
      // if ( \file_exists($f)) unlink( $f);
      // \file_put_contents( $f, $data);
      // logger::info( sprintf('<%s> %s', $f, __METHOD__));

    } elseif ($p->encoding == 3) {
      // if ( $debug) sys::logger( sprintf('imap_base64 :s: %s', __METHOD__));
      // $data = imap_base64( $data);
      $data = base64_decode($data);
      if ($debug) sys::logger(sprintf('base64_decode :e: %s', __METHOD__));

      // $f = sprintf('%s/temp-1-base64_decode.txt', \config::dataPath());
      // if ( \file_exists($f)) unlink( $f);
      // \file_put_contents( $f, $data);
      // die( $data);

    } elseif ($p->encoding == 1) {
      if ($debug) sys::logger(sprintf('imap_8bit : %s', __METHOD__));
      $data = imap_8bit($data);
      // sys::dump( $data);
      $data = quoted_printable_decode($data);
      // sys::dump( $data);
      // sys::dump( $p);

    } elseif (isset($p->subtype) && 'plain' == strtolower($p->subtype)) {
      if ($debug) sys::logger(sprintf('plain text : %s', __METHOD__));
      // $data = quoted_printable_decode( $data);
      // $data = $data;

    } else {

      $encoding = mb_detect_encoding($data, config::mb_detect_encoding_array);
      if ($encoding) {
        if (!\in_array(strtolower($encoding), ['ascii', 'utf-8'])) {
          sys::logger(sprintf('%s : %s', $encoding, __METHOD__));
        }

        if ($debug) sys::logger(sprintf('mb_convert_encoding : %s (%d)', $encoding, \strlen($data), __METHOD__));
        if (strtolower($encoding) != 'utf-8') {
          $data = mb_convert_encoding($data, 'utf-8', $encoding);
        }
        $data = mb_convert_encoding($data, 'html-entities', 'utf-8');
      } else {
        // this probably should just be raw ..
        $data = quoted_printable_decode($data);
      }

      if ($debug) sys::logger(sprintf('other encoding : %s (%d)', $p->encoding, \strlen($data), __METHOD__));
      // \sys::dump( $data);
      // \sys::dump( $p);
      // if ( $debug) sys::logger( sprintf('other encoding : %s : %s', $p->encoding, print_r( $p, true), __METHOD__));
      // if ( $debug) sys::logger( sprintf('other encoding : %s : %s', $data, __METHOD__));
      // die( quoted_printable_decode( $data));

    }

    // return;

    // PARAMETERS : get all parameters, like charset, filenames of attachments, etc.
    $params = [];
    if ($p->parameters) {
      foreach ($p->parameters as $x) {
        $params[strtolower($x->attribute)] = $x->value;
        // if ( $debug) logger::debug( sprintf('parameter %s => %s : %s', $x->attribute, $x->value, __METHOD__));

      }
    }

    if (isset($p->dparameters)) {
      if ($p->dparameters) {
        foreach ($p->dparameters as $x)
          $params[strtolower($x->attribute)] = $x->value;
      }
    }

    // if ( $debug) sys::logger( sprintf( 'params : %s',  print_r( $params, true)));

    // ATTACHMENT
    // Any part with a filename is an attachment,
    // so an attached text file (type 0) is not mistaken as the message.

    /*
		 *	filename may be given as 'Filename' or 'Name' or both
		 *	filename may be encoded, so see imap_mime_header_decode()
		 */
    if (self::PLAINTEXT != $this->plainText) {

      if (strlen($data) > 0) {

        if (isset($params['filename'])) {

          if ($debug) logger::debug(sprintf('<%s> <filename> %s', $params['filename'], __METHOD__));

          $filename = $params['filename'];
          $attach = new attachment;
          $attach->Name = $attach->ContentId = $filename;
          $attach->Content = $data;
          if (isset($p->id)) {
            $attach->ContentId = preg_replace(array("@\<@", "@\>@"), "", $p->id);
          }

          /**
           * 25/09/2021
           * Weird file name with encoding in the string
           * could this be double encoded ?
           */
          // logger::info(sprintf('<%s> %s', $filename, __METHOD__));
          // logger::info(sprintf('<%s> %s', imap_utf8($filename), __METHOD__));
          // \sys::dump($p);

          /**
           * 15/04/2022
           * https://cmss.darcy.com.au/forum/view/9713
           *
           * added the imap_utf8() call to the filename,
           * this probably should have been always here and
           * the test for encoding can be removed,
           * imap_utf8() seems to do the job
           */
          $filename = imap_utf8($filename);

          if (preg_match('/^=\?ISO-8859-1/i', $filename)) {

            $filename = trim(mb_decode_mimeheader($filename));

            /**
             * https://cmss.darcy.com.au/forum/view/9990
             *
             * Origin_electricity_invoice_400037334069_22062022_211241. pdf
             * should be => Origin_electricity_invoice_400037334069_22062022_211241.pdf
             * so;
             *    is there a dot + space and lookahead for a 3 character extension
             *    if so, then remove the space after the dot
             */
            if (preg_match('/\.\s(?=[a-zA-Z]{3}$)/', $filename)) {
              $filename = preg_replace('/\.\s(?=[a-zA-Z]{3}$)/i', '.', $filename);
            }
          }

          // this probably should be removed
          // $filename = trim($filename, '?=');
          // if (preg_match('/ISO-8859-1/i', $filename)) {
          //   $filename = preg_replace('/ISO-8859-1/i', '', $filename);
          //   $filename = preg_replace('/\?[^\?]*\?/i', '', $filename);
          // }
          // end : this probably should be removed

          $filename = strings::safe_file_name($filename);
          if ($filename) $attach->Name = $filename;

          $this->attachments[] = $attach;
        } elseif (isset($params['name'])) {

          if ($debug) logger::debug(sprintf('<%s> <name> %s', $params['name'], __METHOD__));

          $filename = $params['name'];
          $attach = new attachment;
          $attach->Name = $attach->ContentId = $filename;
          $attach->Content = $data;
          if (isset($p->id)) {
            $attach->ContentId = preg_replace(array("@\<@", "@\>@"), "", $p->id);
          }
          $this->attachments[] = $attach;
        } elseif ($p->type == 5 && $data && isset($p->id)) {

          if ($debug) logger::debug(sprintf('<%s> %s', 'type 5', __METHOD__));

          $id = preg_replace(array("@(<|>)@"), "", $p->id);
          $attach = new attachment;
          $attach->Name = $attach->ContentId = $id;
          $attach->Content = $data;
          $this->attachments[] = $attach;
        } else {
          if ($debug) logger::debug(sprintf('<%s> %s', 'not plaintext attachment', __METHOD__));
          // sys::dump($p, null, false);
          // sys::dump($data);
        }
      } elseif ($debug) {
        if ($p->type == 5 && $data) {
          //~ foreach ( $params as $k => $v)
          //~ sys::logger( sprintf( "%s => %s", $k, $v));

          //~ sys::logger( sprintf( '%s, %s, $p, %s ( %s: %s )',  $mbox, $mid, $partno, $p->id, $p->type));
          sys::logger("d. lost : ");
        }
      }
    }

    // TEXT
    if ($p->type == 0 && $data) {
      /**
       * Messages may be split in different parts because
       * of inline attachments, so append parts together
       * with blank row.
       *  */

      // if ( $debug) sys::logger( sprintf( 'encoding : %s : %s',  $p->encoding, $data));
      //~ if ( $debug && $p->ifsubtype) sys::logger( sprintf( '    type :: subtype : %s :: %s',  $p->type, $p->subtype));

      if ('plain' == strtolower($p->subtype)) {
        if ($debug) logger::debug(sprintf('<%s> %s', 'plaintext - plain', __METHOD__));

        $this->messageType = 'text';
        $this->message .= $data;  // . "\n\n";

        // $f = sprintf('%s/temp-0-rawmessage.txt', \config::dataPath());
        // if ( \file_exists($f)) unlink( $f);
        // \file_put_contents( $f, $data);

        // $tplus = quoted_printable_decode( trim( $data));

        // \file_put_contents( $f, $tplus);

        // $this->message .= $tplus . "\n\n";
        if ($debug) sys::logger(sprintf('plain text : %s : %s', mb_detect_encoding($data), __METHOD__));
      } elseif (strtolower($p->subtype) == 'rfc822-headers')
        $this->message .= "--[rfc822-headers]--\n\n" . trim($data) . "\n\n";

      elseif (strtolower($p->subtype) == 'calendar') {
        if (self::PLAINTEXT != $this->plainText) {
          $this->attachments['calendar.ics'] = $data;  // this is a problem if two files have same name

        }
      } else {
        if ('directory' == strtolower($p->subtype) && 'BEGIN:VCARD' == substr($data, 0, 11)) {
          $v = VObject\Reader::read($data);
          if ($debugPart) sys::logger(sprintf('<%s> : %s', $data, __METHOD__));
          if (isset($v->FN)) {
            $a = [
              '<div style="width: 1.2em; display: inline-block; text-align: center; border-top: 3px solid silver;">n.</div>' . $v->FN
            ];
            if (isset($v->TEL)) $a[] = '<div style="width: 1.2em; display: inline-block; text-align: center;">t.</div>' . $v->TEL;
            if (isset($v->EMAIL)) $a[] = '<div style="width: 1.2em; display: inline-block; text-align: center;">e.</div>' . $v->EMAIL;
            $a[] = '';

            $this->messageType = 'html';
            $this->messageHTML .= implode('<br>', $a);
          }
        } else {
          if (isset($p->disposition) && 'attachment' == $p->disposition) {
            if ($debugPart) {
              logger::debug(sprintf('<%s attachment> %s', strtolower($p->subtype), __METHOD__));
              // \sys::dump( $p, null, false);

            }

            if (isset($p->id)) {
              $id = preg_replace(array("@(<|>)@"), "", $p->id);
              $attach = new attachment;
              $attach->Name = $attach->ContentId = $id;
              $attach->Content = $data;
              $this->attachments[] = $attach;
            }
          } else {

            // sys::dump($p);

            $this->messageType = 'html';
            if (($params['charset'] ?? '') == 'windows-874') {
              // sys::dump($data);
              $this->messageHTML .= util::decodeWin874($data);  // . "<br /><br />";
            } else {
              $this->messageHTML .= \str_replace(chr(146), "'", $data);  // . "<br /><br />";
            }

            if ($debugPart) sys::logger(sprintf(
              'html(%s)[%s] [%s]: %s',
              strlen($data),
              $p->subtype,
              $params['charset'] ?? '',
              __METHOD__
            ));
            // die( $this->messageHTML);

          }
        }
      }

      if (isset($params['charset'])) {
        $this->charset = $params['charset'];  // assume all parts are same charset
        if ($debugPart) sys::logger(sprintf('charset : %s : %s', $this->charset, __METHOD__));
      }
    }

    // EMBEDDED MESSAGE
    // Many bounce notifications embed the original message as type 2,
    // but AOL uses type 1 (multipart), which is not handled here.
    // There are no PHP functions to parse embedded messages,
    // so this just appends the raw source to the main message.
    elseif ($p->type == 2 && $p->subtype == 'RFC822' && $data) {
      /**
       * embedded message "send as attachment"
       *
       * Return-Path: <davidb@darcy.com.au>
       * Received: (from root@localhost)
       * 	by fed17.ashgrove.darcy.com.au (8.15.2/8.15.2/Submit) id 016DKpa8023804;
       * 	Thu, 6 Feb 2020 23:20:51 +1000
       * To: davidb@darcy.com.au
       * Subject: DIG GA PHP Error
       * From: DIG GA <webmaster@darcy.com.au>
       * Reply-To: DIG GA <davidb@darcy.com.au>
       * Content-Type: text/plain
       * Date: Thu, 06 Feb 2020 23:19:51 +1000
       * Message-ID: <20200206231951TheSystem@>
       * X-Mailer: PHP v7.3.13
       *
       * dvc-Exceptions-UnableToSelectDatabase (db.php ~ 30)(0)
       * /opt/data/core/vendor/bravedave/dvc/dvc/db.php(30)
       * #0 /opt/data/core/vendor/bravedave/dvc/dvc/dbi.php(56): dvc\db->__construct('mysql.internal', 'cmss', 'daCMS', 'daCMS')
       * #1 /opt/data/core/vendor/bravedave/dvc/dvc/sys.php(25): dvc\dbi->__construct()
       * #2 /opt/data/core/vendor/bravedave/dvc/dvc/core/application.php(499): dvc\sys::dbi()
       * #3 /opt/data/core/vendor/bravedave/dvc/dvc/core/controller.php(52): dvc\core\application->dbi()
       * #4 /opt/data/core/cms/application/app/Controller.php(78): dvc\core\controller->__construct('/opt/data/core/...')
       * #5 /opt/data/core/cms/application/app/service.php(32): Controller->__construct('/opt/data/core/...')
       * #6 /opt/data/core/cms/application/services/market-activity-email.php(15): service->run('marketactivity', 'diffmailSchedul...')
       * #7 {main}
       */
      if ($debugPart) sys::logger(sprintf('part type 2/RFC822(%s) : %s', strlen($data), __METHOD__));
      // sys::logger( sprintf( 'part type 2/RFC822(%s) : %s', strlen( $data), __METHOD__ ));
      // \file_put_contents( config::dataPath() . '/you_want_this.dat', $data);
      // sys::dump( $data);

      if ('html' == $this->messageType) {
        /**
         * this is an attachment
         */

        // sys::dump( $data);
        // sys::dump( $p);

        $attach = new attachment;
        if (isset($p->id)) {
          $id = preg_replace(array("@(<|>)@"), "", $p->id);
          $attach->Name = $attach->ContentId = $id;
        } else {
          $attach->Name = $attach->ContentId = sprintf('message-%d.eml', count($this->attachments) + 1);
        }

        $attach->Content = $data;
        $this->attachments[] = $attach;

        if ($debugPart) sys::logger(sprintf('part type 2/RFC822(%s) - html : %s', strlen($data), __METHOD__));
      } else {
        $msg = new MimeMessage($data);
        $this->message .= $msg->getMessage() . "\n\n";
      }
      if ($debugPart) sys::logger(sprintf('part type 2 : %s', __METHOD__));
    } else {
      if ($data) {
        if ($p->type == 2) {
          // sys::dump( $params);
          // sys::dump( [$p, $data]);
          $this->message .= $data . "\n\n";
          if ($debugPart) sys::logger(sprintf('part type 2(%s) : %s', strlen($data), __METHOD__));
        }
      } else {
        if ($debug) logger::debug(sprintf('<%s> %s', 'attachment with no data', __METHOD__));
      }

      if (isset($p->parts) && $p->parts) { // SUBPART RECURSION
        foreach ($p->parts as $partno0 => $p2) {
          // logger::info( sprintf('<%s -----------------------------------------------------------------------------> %s', $p2->type, __METHOD__));
          // \sys::dump( $p2);
          $this->getpart($mbox, $mid, $p2, $partno . '.' . ($partno0 + 1));  // 1.2, 1.2.1, etc.
          // logger::info( sprintf('</%s -----------------------------------------------------------------------------> %s', $p2->type, __METHOD__));

        }
      }
    }

    if ($debug) {
      sys::logger(sprintf('messagetype : %s :: %s', $this->messageType, __METHOD__));
      sys::logger(sprintf('exit : %s', __METHOD__));
    }
  }
}

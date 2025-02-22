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

use dvc\mail\credentials;
use bravedave\dvc\{EmailAddress, logger};
use currentUser;
use strings, sys;

class client {
  // static $debug = true;
  static $debug = false;

  protected $_account = '';

  protected $_error = '';

  protected $_folder = '';

  protected $_interface = 0;

  protected $_open = false;

  protected $_password = '';

  protected $_port = '143';

  protected $_socket = null;

  protected $_stream = null;

  protected $_server = '';

  protected $_server_path = '';

  protected $_secure = 'tls';

  const INBOX = 'Inbox';

  protected static function _instance(credentials $cred = null): ?self {
    if (is_null($cred))
      $cred = credentials::getCurrentUser();

    if ($cred) {
      // sys::dump( $cred);
      $client = new self(
        $cred->server,
        $cred->account,
        $cred->password,
        $cred->interface

      );

      // if ( isset( \config::$exchange_verifySSL) && !\config::$exchange_verifySSL) {
      // 	$client->setCurlOptions([CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST  => false]);
      // 	logger::info( 'ews\client :: disable verify SSL');

      // }

      return $client;
    }

    return null;
  }

  protected static function header_decode($text) {
    $_texts = imap_mime_header_decode($text);
    $_res = array();
    foreach ($_texts as $_text)
      $_res[] = $_text->text;

    return (implode(' ', $_res));
  }

  protected static function ReplaceImap($txt) {
    $carimap = array("=2F", "=C3=A9", "=C3=A8", "=C3=AA", "=C3=AB", "=C3=A7", "=C3=A0", "=20", "=C3=80", "=C3=89", "=E2=80=8E", "=\r\n");
    $carhtml = array("/", "é", "è", "ê", "ë", "ç", "à", "&nbsp;", "À", "É", "", "");
    $txt = str_replace($carimap, $carhtml, $txt);

    return $txt;
  }

  static function default_folders(): array {
    return folders::$default_folders;
  }

  static function instance(credentials $cred = null): ?self {
    if ($client = self::_instance($cred)) {
      // if ( isset( \config::$exchange_timezone))
      // 	$client->setTimezone( \config::$exchange_timezone);

    }

    return $client;
  }

  static function instanceForDelete(credentials $cred = null): ?self {
    return self::_instance($cred);
  }

  static function instanceForSync(credentials $cred = null): ?self {
    return self::_instance($cred);
  }

  protected function _cache_prefix() {
    return strtolower(sprintf(
      '%s_%s',
      str_replace('\\', '_', $this->_account),
      preg_replace('@[\\\/]@', '_', $this->_folder)
    ));
  }

  protected function _cache_path($uid, $txt = false) {
    if ($txt) {
      return sprintf(
        '%s%s_%s.txt',
        config::IMAP_CACHE(),
        $this->_cache_prefix(),
        $uid
      );
    } else {
      return sprintf(
        '%s%s_%s.json',
        config::IMAP_CACHE(),
        $this->_cache_prefix(),
        $uid
      );
    }
  }

  protected function _flush_cache(string $uid = null) {
    if (\is_null($uid)) {
      if (!config::$_imap_cache_flushing) return;

      $_path = config::IMAP_CACHE() . $this->_cache_prefix() . '*';
      $iterator = new \GlobIterator($_path);
      foreach ($iterator as $item) {
        // logger::info( sprintf('flush <%s>  %s', $item->getRealPath(), __METHOD__));
        unlink($item->getRealPath());
      }

      // logger::info( sprintf('flushed <%s>  %s', $_path, __METHOD__));

    } else {
      if (file_exists($path = $this->_cache_path($uid))) {
        unlink($path);
      }
    }
  }

  protected function _getheaders() {
    return imap_headers($this->_stream);
  }

  protected function _getmessage($msgno, $overview = false): \dvc\mail\message {
    $debug = false;
    // $debug = true;

    // HEADER
    // https://www.php.net/manual/en/function.imap-headerinfo.php#98809

    $_headers = imap_fetchheader($this->_stream, $msgno, 0);
    // Split on \n
    $h_array = explode("\n", $_headers);
    $_spamStatus = '';
    $_spamScore = '';
    $_spamDetail = [];
    $f = false;
    foreach ($h_array as $value) {

      if ($f) {

        if (preg_match('/^(\s|\t)/', $value)) {

          $_spamDetail[] = trim($value);
        } else {

          // logger::info( sprintf('<%s> %s', $value, __METHOD__));
          $f = false;
        }
      } else {

        if (preg_match('/^X-Spam-Flag: /', $value)) $_spamStatus = trim(str_replace('X-Spam-Flag: ', '', $value));
        if (preg_match('/^X-Spam-Status: /', $value)) $_spamScore = trim(preg_replace(['/^.*score=/', '/required=.*$/'], '', $value));

        if (preg_match('/^X-Spam-Details: /', $value)) {

          $f = true;
          $_spamDetail[] = trim(str_replace('X-Spam-Details: ', '', $value));
        }
      }
    }

    // if (currentUser::isDavid()) sys::dump($_spamDetails, '_spam_details : ' . $_spamStatus . ' : ' . $_spamScore);
    // if (currentUser::isDavid()) sys::dump($_headers, 'headers');
    // if (currentUser::isDavid()) sys::dump($h_array);

    $_headers_rfc822 = imap_rfc822_parse_headers($_headers);
    if ($errors = imap_errors()) array_walk($errors, fn ($error) => logger::info(sprintf('<%s> %s', $error, __METHOD__)));

    if (!$overview) $overview = $this->_Overview($msgno);
    // if (currentUser::isDavid()) sys::dump($overview, 'overview');
    // sys::dump($_headers_rfc822, 'overview');

    $overview = (object)$overview;

    /* add code here to get date, from, to, cc, subject... */
    $from = '';
    if (isset($_headers_rfc822->from) && count($_headers_rfc822->from)) {

      $_mbox = array_shift($_headers_rfc822->from);
      if (isset($_mbox->host)) {

        $from = util::decodeMimeStr($_mbox->mailbox) . '@' . util::decodeMimeStr($_mbox->host);
      } else {

        $from = util::decodeMimeStr($_mbox->mailbox);
      }

      if (isset($_mbox->personal)) $from = strings::rfc822($from, util::decodeMimeStr($_mbox->personal));
    }
    if ($debug) logger::debug(sprintf('<from : %s> %s', $from, __METHOD__));

    $replyto = '';
    if (isset($_headers_rfc822->reply_to) && count($_headers_rfc822->reply_to)) {

      $_mbox = array_shift($_headers_rfc822->reply_to);
      $replyto = util::decodeMimeStr($_mbox->mailbox) . '@' . util::decodeMimeStr($_mbox->host);

      if (isset($_mbox->personal)) $replyto = strings::rfc822($replyto, util::decodeMimeStr($_mbox->personal));
    }

    $to = [];
    if (isset($_headers_rfc822->to) && $_headers_rfc822->to) {

      foreach ($_headers_rfc822->to as $e) {

        $s = '';
        if (isset($e->mailbox) && isset($e->host)) {
          $s = $e->mailbox . "@" . $e->host;
        } elseif (isset($e->mailbox)) {
          $s = $e->mailbox;
        }

        if (isset($e->personal)) {
          if (preg_match('/,/', $e->personal)) {
            $s = sprintf('"%s" <%s>', $e->personal, $s);
          } else {
            $s = sprintf('%s <%s>', $e->personal, $s);
          }
        }

        $to[] = $s;
      }
    }
    $to = implode(',', $to);
    if ($debug) logger::debug(sprintf('<to : %s> %s', $to, __METHOD__));

    $cc = [];
    if (isset($_headers_rfc822->cc) && $_headers_rfc822->cc) {
      foreach ($_headers_rfc822->cc as $e) {

        $s = '';
        if (isset($e->mailbox) && isset($e->host)) $s = $e->mailbox . "@" . $e->host;
        elseif (isset($e->mailbox)) $s = $e->mailbox;

        if (isset($e->personal)) {

          if (preg_match('/,/', $e->personal)) {
            $s = sprintf('"%s" <%s>', $e->personal, $s);
          } else {
            $s = sprintf('%s <%s>', $e->personal, $s);
          }
        }

        $cc[] = $s;
      }
    }
    $cc = implode(', ', $cc);

    // if ( currentUser::isDavid()) sys::dump( $_headers_rfc822);
    if (!isset($_headers_rfc822->message_id)) $_headers_rfc822->message_id = 'no-message-id';

    $headerDate = '';
    if (isset($_headers_rfc822->date)) $headerDate = $_headers_rfc822->date;
    if (!isset($_headers_rfc822->Subject)) $_headers_rfc822->Subject = '(subject missing)';

    $ret = new \dvc\mail\message;
    $ret->SpamStatus = $_spamStatus;
    $ret->SpamScore = $_spamScore;
    $ret->SpamDetail = $_spamDetail;

    $ret->Subject = util::decodeMimeStr((string)$_headers_rfc822->Subject);

    $ret->From = $from;
    $ea = new EmailAddress($from);
    $ret->fromEmail = $ea->email;

    if ($replyto) {
      $ea = new EmailAddress($replyto);
      if ($ret->fromEmail != $ea->email) {
        $ret->ReplyTo = $replyto;
      }
    }

    $ret->To = util::decodeMimeStr((string)$to);
    $ret->CC = util::decodeMimeStr((string)$cc);
    $ret->MessageID = $_headers_rfc822->message_id;
    $ret->Received = $headerDate;

    if ($ret->headers = imap_headerinfo($this->_stream, $msgno)) {
      $ret->MSGNo = $ret->headers->Msgno;
      $ret->Uid = imap_uid($this->_stream, $ret->headers->Msgno);
      $ret->answered = $ret->headers->Answered == "A" ? 'yes' : 'no';
      $ret->seen = $ret->headers->Unseen == "U" ? 'no' : 'yes';
    }

    $ret->references = '';

    if (isset($overview->in_reply_to)) $ret->in_reply_to = $overview->in_reply_to;
    if (isset($overview->references)) $ret->references = $overview->references;
    if (isset($overview->{'X-CMS-Draft'})) $ret->{'X-CMS-Draft'} = $overview->{'X-CMS-Draft'};

    $mess = new RawMessage($this->_stream, $msgno);
    $ret->CharSet = $mess->charset;

    $ret->Text = $mess->message;

    if ($mess->messageHTML) {
      $ret->Body = $mess->messageHTML;
      $ret->BodyType = 'HTML';
    } else {
      $ret->BodyType = 'text';
      $ret->Body = $mess->message;
    }
    $ret->attachments = $mess->attachments;
    $ret->cids = $mess->cids;

    //~ if ( \currentUser::isDavid()) \sys::dump( $ret);
    // \sys::dump($ret);
    if ($debug) logger::debug(sprintf('exit : %s', __METHOD__));

    return ($ret);
  }

  protected function _getmessageText($msgno) {
    $debug = false;
    // $debug = true;

    $uid = \imap_uid($this->_stream, $msgno);
    $txtFile = $this->_cache_path($uid, true);
    if (\file_exists($txtFile)) {
      if ($debug) logger::debug(sprintf('<%s => from cache : %s> %s', $msgno, $txtFile, __METHOD__));
      return file_get_contents($txtFile);
    }

    if ($debug) logger::debug(sprintf('<%s> %s', $msgno, __METHOD__));
    $msg = new RawMessage($this->_stream, $msgno, RawMessage::PLAINTEXT);
    $text = '';
    if ('html' == $msg->messageType) {
      $text = \strings::html2text($msg->messageHTML);
    }

    if (!(strlen($text) > 0)) {
      $text = $msg->message;
    }

    if ($debug) logger::debug(sprintf('<%s =>%s> : %s : %s', $msgno, $uid, \strlen($text), __METHOD__));
    \file_put_contents($txtFile, $text);

    return $text;
  }

  protected function _getMessageHeader($id) {

    $total = imap_num_msg($this->_stream);
    /**
     * Reverse chunk sort in pages of 20
     */
    $i = $total;
    $chunks = [];
    while ($i > 0) {
      $end = $i;
      $i -= 19;
      $i = max([1, $i]);
      $chunks[] = implode(',', range($i, $end));
      $i--;
    }

    foreach ($chunks as $chunk) {
      // Fetch an overview for all messages in INBOX
      $overview = imap_fetch_overview($this->_stream, $chunk, 0);
      if (count($overview)) {
        foreach ($overview as $msg) {
          if (isset($msg->message_id)) {
            if ("{$msg->message_id}" == "{$id}") {
              // \sys::dump( $msg);
              return ($msg);
            }
          }
        }
      }
    }

    if (self::$debug) logger::debug(sprintf('not found : %s : %s', $id, __METHOD__));
  }

  /** get information for this specific email */
  protected function _overview($email_number = -1): \dvc\mail\message {

    $debug = false;
    // $debug = true;
    // $debug = currentUser::isDavid();

    if ($email_number < 0) return (false);

    $socket = $this->_socket();

    $ret = new \dvc\mail\message;
    $_cache = false;
    if ($debug) logger::debug(sprintf('<%s> [%s] %s', \application::timer()->elapsed(), $email_number, __METHOD__));
    $uid = imap_uid($this->_stream, $email_number);
    if ($debug) logger::debug(sprintf('<%s> [%s:%s] %s', \application::timer()->elapsed(), $email_number, $uid, __METHOD__));

    $headers = imap_headerinfo($this->_stream, $email_number, 1);
    $_cache = $this->_cache_path($uid);
    if ($debug && \file_exists($_cache)) unlink($_cache);
    // if (currentUser::isDavid() && \file_exists($_cache)) {

    //   logger::info(sprintf('<not using cache> %s', logger::caller()));
    //   unlink($_cache);
    // }

    if (\file_exists($_cache)) {

      $ret->fromJson(\file_get_contents($_cache));
      // if ( isset( $headers->Unseen)) logger::info( sprintf('seen <%s> : %s', $headers->Unseen, __METHOD__));

      if (isset($headers->Unseen)) $ret->seen = ('U' == $headers->Unseen ? 'no' : 'yes');
      if (isset($headers->Answered)) $ret->answered = ('A' == $headers->Answered ? 'yes' : 'no');

      if ($socket) {
        if ($debug) logger::debug(sprintf('<%s> [%s] %s', \application::timer()->elapsed(), 'flags ...', __METHOD__));
        $flags = (array)$socket->get_flags($email_number);
        if ($debug) logger::debug(sprintf('<%s> [%s] %s', \application::timer()->elapsed(), implode(', ', $flags), __METHOD__));
        $ret->forwarded = in_array('$Forwarded', $flags) ? 'yes' : 'no';
      }

      if ($debug) logger::debug(sprintf('<cache:%s> [%s] %s', \application::timer()->elapsed(), $_cache, __METHOD__));
      return $ret;
    } else {

      if ($debug) logger::debug(sprintf('<file was not cached:%s> %s', \application::timer()->elapsed(), __METHOD__));
    }

    if ($headers) {

      if ($debug) logger::debug(sprintf('<have headers:%s> %s', \application::timer()->elapsed(), __METHOD__));
      $ret->Uid = $uid;

      if (isset($headers->message_id)) $ret->MessageID = $headers->message_id;
      // foreach ($headers as $k => $v) {
      //   # code...
      //   if ('string' == gettype($v)) {
      //     logger::info(sprintf('<%s => %s> %s', $k, $v, __METHOD__));
      //   }
      // }

      if (isset($headers->to)) {
        /**
         *
         * [to] => Array (
         *		[0] => stdClass Object (
         *		 	[personal] => Dingo Star
         *		 	[mailbox] => davbray
         *		 	[host] => gmail.com
         *		)
         *
         *		[1] => stdClass Object (
         *			[personal] => Digital Dave
         *			[mailbox] => davbray
         *			[host] => live.com
         *		)
         *
         * )
         */
        $a = [];
        foreach ($headers->to as $to) {


          if (isset($to->personal)) {

            $name = util::decodeMimeStr((string)$to->personal);
            // logger::info( sprintf('<%s> %s', $name, __METHOD__));

            /**
             * I may regret this, but can't think why I have encoded it like this ...
             */
            // if (false != strstr($name, "'")) $name = sprintf('"%s"', $name);
            $a[] = sprintf('%s <%s@%s>', $name, $to->mailbox, $to->host);
          } else {


            if (isset($to->host)) {

              $a[] = sprintf('%s@%s', $to->mailbox, $to->host);
              if ($debug) logger::debug(sprintf('<no personal ..: %s@%s %s> %s', $to->mailbox, $to->host, \application::timer()->elapsed(), __METHOD__));
            } elseif (isset($to->mailbox)) {

              if ($debug) logger::debug(sprintf('<no personal ..:%s> %s', \application::timer()->elapsed(), __METHOD__));
              $a[] = $to->mailbox;
            }
          }
        }

        if ($a) {
          $ret->To = implode(',', $a);
          // logger::info( sprintf('<%s> %s', $ret->To, __METHOD__));
        }
      }

      if (isset($headers->reply_toaddress)) {

        if ($debug) logger::debug(sprintf('<reply address ..: %s %s> %s', $headers->reply_toaddress, \application::timer()->elapsed(), __METHOD__));
        $ret->ReplyTo = util::decodeMimeStr($headers->reply_toaddress);
        if ($debug) logger::debug(sprintf('<reply address ..: %s %s> %s', $ret->ReplyTo, \application::timer()->elapsed(), __METHOD__));
        // logger::info( sprintf('<%s> %s', $ret->ReplyTo, __METHOD__));

      }
    }
    if ($debug) logger::debug(sprintf('<done headers:%s> %s', \application::timer()->elapsed(), __METHOD__));

    if ($overview = imap_fetch_overview($this->_stream, $email_number, 0)) {

      // if ( currentUser::isDavid()) \sys::dump( $overview);

      $msg = $overview[0];
      if (isset($msg->flagged)) $ret->flagged = ($msg->flagged  ? 'yes' : 'no');
      if ($debug) logger::debug(sprintf('<flagged %s> %s', $ret->flagged, __METHOD__));
      if (isset($msg->seen)) $ret->seen = ($msg->seen ? 'yes' : 'no');
      if (isset($msg->answered)) $ret->answered = ($msg->answered ? 'yes' : 'no');

      if (!$ret->To) {
        if (isset($msg->to)) $ret->To = util::decodeMimeStr((string)$msg->to);
        // logger::info( sprintf('<%s> %s', $msg->to, __METHOD__));

      }

      if (isset($msg->subject)) $ret->Subject = util::decodeMimeStr($msg->subject);
      if ($debug) logger::debug(sprintf('<Subject %s> %s', $ret->Subject, __METHOD__));

      if (isset($msg->from)) {
        $ret->From = util::decodeMimeStr($msg->from);
        if ($debug) logger::debug(sprintf('<%s> %s', $msg->from, __METHOD__));
        // logger::debug(sprintf('<%s> %s', $msg->from, __METHOD__));
        $ea = new EmailAddress($ret->From);
        $ret->fromEmail = $ea->email;
      }

      if (isset($msg->date)) {
        $ret->Received = $msg->date;

        if (preg_match("/^Date:/", $msg->date))
          $ret["Received"] = preg_replace("/^Date:/", "", $msg->date);
      }

      if (isset($msg->in_reply_to)) $ret->in_reply_to = $msg->in_reply_to;
      if (isset($msg->references)) $ret->references = $msg->references;
    }

    if ($errors = imap_errors()) {

      array_walk($errors, function ($error) {

        if (str_starts_with($error, 'Unterminated mailbox:')) return;
        if (str_starts_with($error, 'Must use comma to separate addresses:')) return;
        logger::info(sprintf('<overview : %s> %s', $error, __METHOD__));
      });
    }

    $_headers = imap_fetchheader($this->_stream, $email_number);
    $headerLines = explode("\n", $_headers);
    foreach ($headerLines as $l) {
      // logger::info($l);
      if (preg_match('/^Message-ID/', $l)) {
        /**
         * I'm sure this is to cope with a one time bug ...
         */
        if (preg_match('@"@', $ret->MessageID)) {
          $x = explode(':', $l);
          $ret->MessageID = trim(array_pop($x));
          // sys::dump( explode( "\n", $ret->MessageID),'Invalid MessageID');

        }
      } elseif (preg_match('/^X-CMS-Draft/', $l)) {
        $x = explode(':', $l);
        $ret->{'X-CMS-Draft'} = trim(array_pop($x));
      }
    }

    if ($socket) {
      $flags = $socket->get_flags($email_number);
      $ret->forwarded = in_array('$Forwarded', $flags) ? 'yes' : 'no';
    }

    // /* output the email body */
    // $message = imap_fetchbody($inbox,$email_number,1);
    // $output.= '<div class="body"><pre>'.$message.'</pre></div>';

    if ($_cache) {

      if (file_exists($_cache)) {

        unlink($_cache);
        clearstatcache(true);
      }

      if ($json = $ret->asJson()) {

        file_put_contents($_cache, $json);
      } else {

        logger::info(sprintf('<did not cache message> %s', json_last_error_msg(), __METHOD__));
        logger::info(sprintf('<%s> %s', json_last_error_msg(), __METHOD__));
      }
    }

    if ($errors = imap_errors()) {

      array_walk($errors, fn ($error) => logger::info(sprintf('<error : %s> <%s> %s', $error, $this->_account, __METHOD__)));
      logger::info(sprintf('<errors on account %s/%s> %s', $this->_account, $this->_folder, __METHOD__));
    }

    // logger::info(sprintf('<%s> %s', $this->_account, __METHOD__));
    return ($ret);
  }

  protected function _socket() {

    $debug = self::$debug;
    // $debug = true;

    if ($this->_socket) return $this->_socket;

    if ($this->_server) {

      if ('w2008k.ashgrove.darcy.com.au' == $this->_server) return false;

      $this->_socket = new ImapSocket([
        'server' => $this->_server,
        'port' => (int)$this->_port,
        'login' => $this->_account,
        'password' => $this->_password,
        'tls' => false,
        'ssl' => 'ssl' == $this->_secure,

      ], $this->_folder);

      if ($debug) logger::debug(sprintf('<opened imap socket %s:%s> %s', $this->_server, $this->_port, __METHOD__));

      return $this->_socket;
    } else {
      $this->_error = 'invalid server';
    }

    return false;
  }

  protected function __construct(string $server, string $account, string $password, int $interface) {
    if (preg_match('@^ssl://@', $server)) {
      $this->_port = '993';
      $this->_secure = 'ssl';
      $server = preg_replace('@^ssl://@', '', $server);
    }

    $this->_server = $server;
    $this->_account = $account;
    $this->_password = $password;
    $this->_interface = $interface;

    if (self::$debug) logger::debug(sprintf('<%s> %s', $this->_port, __METHOD__));
  }

  public function __destruct() {
    $this->close();
  }

  public function accountName(): string {
    return $this->_account;
  }

  public function clearflag($id, $flag) {
    $ret = false;
    $total = imap_num_msg($this->_stream);
    $result = imap_fetch_overview($this->_stream, "1:{$total}", 0);
    foreach ($result as $msg) {
      if (isset($msg->message_id)) {
        if ("{$msg->message_id}" == "{$id}") {

          $ret = $this->clearflagByUID(\imap_uid($this->_stream, $msg->msgno), $flag);
          // imap_clearflag_full( $this->_stream, $msg->msgno, $flag);
          // $ret = true;

          break;
        }
      }
    }

    return $ret;
  }

  public function clearflagByUID($uid, $flag) {
    // logger::info( sprintf('<%s> %s', $uid, __METHOD__));
    $this->_flush_cache($uid);
    return imap_clearflag_full($this->_stream, $uid, $flag, ST_UID);
  }

  public function close($flag = 0) {
    if ($this->_open) {
      /* close the connection */
      imap_close($this->_stream, $flag);
      $this->_open = false;

      if (CL_EXPUNGE == $flag) {
        $this->_flush_cache();
      }
    }
  }

  public function copy_message($id, $target) {
    $ret = false;
    $total = imap_num_msg($this->_stream);
    $result = imap_fetch_overview($this->_stream, "1:{$total}", 0);
    foreach ($result as $msg) {
      if (!isset($msg->message_id)) continue;

      if ("{$msg->message_id}" == "{$id}") {
        $ret = $this->copy_message_byUID(\imap_uid($this->_stream, $msg->msgno), $target);
        break;
      }
    }

    return $ret;
  }

  public function copy_message_byUID($uid, $target) {
    $this->_flush_cache($uid);
    if ($ret = imap_mail_copy($this->_stream, $uid, $target, CP_UID)) {
      imap_expunge($this->_stream);
      $this->_flush_cache();  // generally flush cache after expunge if that is enabled

    }

    return $ret;
  }

  public function createmailbox($fldr): bool {
    return @imap_createmailbox($this->_stream, imap_utf7_encode(sprintf('{%s}%s', $this->_server, $fldr)));
  }

  public function deletemailbox($fldr) {
    return @imap_deletemailbox($this->_stream, imap_utf7_encode(sprintf('{%s}%s', $this->_server, $fldr)));
  }

  public function delete_message($id) {
    $ret = false;
    $total = imap_num_msg($this->_stream);
    $result = imap_fetch_overview($this->_stream, "1:{$total}", 0);
    foreach ($result as $msg) {
      if (!isset($msg->message_id)) continue;

      if ("{$msg->message_id}" == "{$id}") {
        $ret = $this->delete_message_byUID(\imap_uid($this->_stream, $msg->msgno));
        break;
      }
    }

    return $ret;
  }

  public function delete_message_byUID($uid) {
    $this->_flush_cache($uid);
    if ($ret = imap_delete($this->_stream, $uid, FT_UID)) {
      imap_expunge($this->_stream);
      $this->_flush_cache();  // generally flush cache after expunge if that is enabled

    }

    return $ret;
  }

  public function empty_trash() {
    $total = imap_num_msg($this->_stream);
    return imap_delete($this->_stream, "1:{$total}", 0);  // returns true

  }

  public function finditems(array $params): array {
    $debug = false;
    // $debug = true;
    // $debug = currentUser::isDavid();

    $options = (object)array_merge([
      'deep' => false,
      'page' => 0,
      'pageSize' => config::$IMAP_PAGE_SIZE,
      'allPages' => false,

    ], $params);

    if ($debug) logger::debug(sprintf('<%s> %s', \application::timer()->elapsed(), __METHOD__));
    $data = [
      'msgCount' => imap_num_msg($this->_stream)

    ];
    if ($debug) logger::debug(sprintf('<msgCount : %s> <%s> %s', $data['msgCount'], \application::timer()->elapsed(), __METHOD__));

    // logger::info( sprintf('<%s> <msgCount:%s> %s', \application::timer()->elapsed(), $data['msgCount'], __METHOD__));
    // $headers = $this->_getheaders();
    // logger::info( sprintf('<%s> <headers:%s> %s', \application::timer()->elapsed(), count( $headers), __METHOD__));

    $ret = [];
    if ($data['msgCount'] > config::$IMAP_PAGE_SORT_THRESHOLD) {

      $start = max($data['msgCount'] - ((int)$options->page * (int)$options->pageSize) - ((int)$options->page > 0 ? 1 : 0), 0);
      $emails = \range($start, max($start - $options->pageSize, 0), -1);
      foreach ($emails as $email_number) {
        // logger::info( sprintf('<%s> %s', $email_number, __METHOD__));

        $msg = $this->_overview($email_number);
        $msg->Folder = $this->_folder;
        $ret[] = $msg;
      }
    } else {

      // logger::info( sprintf('<%s> <msgCount:%s> %s', \application::timer()->elapsed(), $data['msgCount'], __METHOD__));
      if ($emails = imap_sort($this->_stream, SORTARRIVAL, true, SE_NOPREFETCH)) {

        if ($debug) logger::debug(sprintf('<%s> [sorted] %s', \application::timer()->elapsed(), __METHOD__));
        // sys::dump( $emails);
        $start = $i = 0;
        $_start = (int)$options->page * (int)$options->pageSize;
        // logger::info( sprintf('<%s/%s> %s', $start, $_start, __METHOD__));

        foreach ($emails as $email_number) {

          if ($debug) logger::debug(sprintf('<%s> %s', $email_number, __METHOD__));

          if ($start++ >= $_start) {

            if ($i++ >= $options->pageSize) break;
            if ($debug) logger::debug(sprintf('<going for overview %s> %s', $email_number, __METHOD__));
            $msg = $this->_overview($email_number);
            if ($debug) logger::debug(sprintf('<got overview %s> %s', $email_number, __METHOD__));
            // logger::info( sprintf('<%s> [%s] %s', \application::timer()->elapsed(), $email_number, __METHOD__));
            $msg->Folder = $this->_folder;
            $ret[] = $msg;
          }
        }
      }
    }

    if ($debug) logger::debug(sprintf('<%s> [fetched] %s', \application::timer()->elapsed(), __METHOD__));
    return $ret;
  }

  public function folders($spec = '*') {
    return imap_list($this->_stream, '{' . $this->_server . '}', $spec);
  }

  public function getmessage($id, $folder = "default") {

    $ret = false;
    if ($this->open(true, $folder)) {
      if ($msg = $this->_getMessageHeader($id, $folder)) {
        $ret = $this->_getmessage($msg->msgno, $msg);
        $ret->Folder = $folder;
      }

      if (!$ret) {
        if (self::$debug) {

          logger::debug(sprintf('not found : %s/%s : %s', $folder, $id, __METHOD__));
        }
      }

      $this->close();
    } else {

      logger::info(sprintf('failed to open folder : %s :: %s : %s', $folder, $this->_error, __METHOD__));
    }

    return ($ret);
  }

  public function getmessageByMsgNo($msgno, $folder = "default") {

    $ret = false;
    if ($this->open(true, $folder)) {
      if ($msgno > 0) {
        $ret = $this->_getmessage($msgno);
        $ret->Folder = $folder;
      }

      if (!$ret) {
        if (self::$debug) {

          logger::debug(sprintf('not found : %s/%s : %s', $folder, $msgno, __METHOD__));
        }
      }

      $this->close();
    } else {

      logger::info(sprintf('failed to open folder : %s :: %s : %s', $folder, $this->_error, __METHOD__));
    }

    return ($ret);
  }

  public function getmessageByUID($uid, $folder = "default") {

    $ret = false;
    if ($this->open(true, $folder)) {

      $msgno = imap_msgno($this->_stream, $uid);
      if ($msgno > 0) {

        if ($ret = $this->_getmessage($msgno)) {

          $ret->Folder = $folder;
          if (self::$debug) logger::debug(sprintf('retrieved msgno via imap : %s :: %s : %s', $uid, $folder, __METHOD__));
        }
      }

      if (!$ret) {

        if (self::$debug) logger::debug(sprintf('not found : %s/%s : %s', $folder, $uid, __METHOD__));
      }

      $this->close();
    } else {

      logger::info(sprintf('failed to open folder : %s :: %s : %s', $folder, $this->_error, __METHOD__));
    }

    return ($ret);
  }

  public function headers($folder = "default") {
    $ret = false;
    if ($this->open(true, $folder)) {
      $ret = $this->_getheaders();
      $this->close();
    } else {
      logger::info(sprintf('failed to open folder : %s :: %s : %s', $folder, $this->_error, __METHOD__));
    }

    return ($ret);
  }

  public function Info($folder = "default") {
    $ret = false;
    if ($this->open(true, $folder)) {
      $ret = imap_mailboxmsginfo($this->_stream);
      $this->close();
    } else {
      logger::info(sprintf('failed to open folder : %s :: %s : %s', $folder, $this->_error, __METHOD__));
    }

    return ($ret);
  }

  public function move_message($id, $target) {
    $ret = false;
    $total = imap_num_msg($this->_stream);
    $result = imap_fetch_overview($this->_stream, "1:{$total}", 0);
    foreach ($result as $msg) {
      if (!isset($msg->message_id)) continue;

      if ("{$msg->message_id}" == "{$id}") {
        $ret = $this->move_message_byUID(\imap_uid($this->_stream, $msg->msgno), $target);
        break;
      }
    }

    return $ret;
  }

  public function move_message_byUID($uid, $target) {
    $this->_flush_cache($uid);
    if ($ret = imap_mail_move($this->_stream, $uid, $target, CP_UID)) {
      imap_expunge($this->_stream);
      $this->_flush_cache();  // generally flush cache after expunge if that is enabled

    }

    return $ret;
  }

  public function open($full = true, &$folder = 'default') {
    $debug = self::$debug;
    // $debug = true;

    if ($this->_server) {
      if ($folder == 'default')
        $folder = self::INBOX;

      $this->_server_path = sprintf(
        '{%s:%s/%s/novalidate-cert}',
        $this->_server,
        $this->_port,
        $this->_secure
      );

      /**
       * https://wordpress.org/support/topic/exchange-imap-kerberos-error-solved
       *
       * Exchange is notoriously bad at IMAP. however i found one post that didn't require recompiling php without Kerberos.
       *
       * Connecting with {myipaddress:143/service=imap/tls/novalidate-cert}
       * Returned this error.
       *
       * PHP Notice: Unknown: Kerberos error: No credentials cache found (try
       * running kinit) for ldnwpexch11.emea.akqa.local (errflg=1) in Unknown on
       * line 0
       *
       * Which meant either creating tempory Kerberos keys or recompiling php.
       *
       * This however solved the problem just nicely
       * In postieIMAP.php edit the imap_open command to include the following.
       *
       */
      $nogssapi = ['DISABLE_AUTHENTICATOR' => 'GSSAPI'];

      if ($full) {
        if ($debug) {
          logger::debug(sprintf(
            '<imap_open( %s, %s, %s)> %s',
            $this->_server_path . $folder,
            $this->_account,
            'password',
            __METHOD__
          ));
        }

        /* connect server */
        if ($this->_stream = @imap_open($this->_server_path . $folder, $this->_account, $this->_password, 0, 1, $nogssapi)) {
          $this->_open = true;
          $this->_folder = $folder;

          if ($debug) {
            logger::debug(sprintf(
              '<successfully opened:imap_open( %s, %s, %s)> %s',
              $this->_server_path . $folder,
              $this->_account,
              'password',
              __METHOD__
            ));
          }
        } else {
          if ($errors = imap_errors()) {

            array_walk($errors, fn ($error) => logger::info(sprintf('<error ::: %s> %s', $error, __METHOD__)));
            if ($debug) sys::trace('imap open error');
          }

          $this->_error = sprintf('Cannot connect to %s :: %s', $this->_server_path, imap_last_error());
          logger::info(sprintf('<%s> %s', $this->_error, __METHOD__));
        }
      } else {
        if ($debug) logger::debug(sprintf(
          '<imap_open( %s, %s, %s, OP_HALFOPEN)> %s',
          $this->_server_path . $folder,
          $this->_account,
          'password',
          __METHOD__
        ));

        /* connect server */
        if ($this->_stream = @imap_open($this->_server_path . $folder, $this->_account, $this->_password, OP_HALFOPEN, 1, $nogssapi)) {
          $this->_open = true;
          $this->_folder = $folder;

          if ($debug) logger::debug(sprintf(
            '<successfully half-opened:imap_open(%s,%s,%s)> %s',
            $this->_server_path . $folder,
            $this->_account,
            'password',
            __METHOD__
          ));
        } else {
          if ($errors = imap_errors()) {
            foreach ($errors as $error) {
              logger::info(sprintf('<%s> %s', $error, __METHOD__));
            }
            if ($debug) sys::trace('imap open error');
          }

          $this->_error = sprintf('Cannot connect to %s :: %s', $this->_server_path, imap_last_error());
          logger::info(sprintf('<%s> %s', $this->_error, __METHOD__));
        }
      }

      return ($this->_open);
    } else {
      $this->_error = 'invalid server';
      logger::info(sprintf('<%s> %s', $this->_error, __METHOD__));
    }

    return false;
  }

  public function renamemailbox($fldr, $fldrNew) {
    $_fldr = imap_utf7_encode(sprintf('{%s}%s', $this->_server, $fldr));
    $_fldrNew = imap_utf7_encode(sprintf('{%s}%s', $this->_server, $fldrNew));
    // \logger::info( sprintf('<%s => %s> %s', $_fldr, $_fldrNew, __METHOD__));
    return @imap_renamemailbox($this->_stream, $_fldr, $_fldrNew);
    // return false;


  }

  public function search(array $params): array {

    $options = array_merge([
      'criteria' => [],
      'charset' => 'US-ASCII',
      'term' => '',
      'time_limit' => 120
    ], $params);

    $ret = [];
    $results = [];
    foreach ($options['criteria'] as $criteria) {

      // logger::info( sprintf('<%s> %s', $criteria, __METHOD__));

      set_time_limit($options['time_limit']);

      if ($emails = imap_search($this->_stream, $criteria, SE_FREE, $options['charset'])) {

        foreach ($emails as $email_number) {

          if (!in_array($email_number, $results)) {

            if ($options['term']) {

              if (preg_match('@^TEXT @', $criteria)) {

                // 	logger::info( sprintf('<%s> %s', $criteria, __METHOD__));
                $txt = $this->_getmessageText($email_number);
                if (false === strpos($txt, $options['term'])) {
                  // logger::info( sprintf('<%s> not found in text %s', $options['term'], __METHOD__));
                  continue;
                }
              }
            }

            $results[] = $email_number;
          }
        }
      }
    }

    foreach ($results as $email_number) {

      if ($msg = $this->_overview($email_number)) {
        $msg->Folder = $this->_folder;
        $ret[] = $msg;
      }
    }

    return $ret;
  }

  public function server() {
    return $this->_server;
  }

  public function stream() {
    return $this->_stream;
  }

  public function setflag($id, $flag) {
    $ret = false;
    $total = imap_num_msg($this->_stream);
    $result = imap_fetch_overview($this->_stream, "1:{$total}", 0);
    foreach ($result as $msg) {
      if (isset($msg->message_id)) {
        if ("{$msg->message_id}" == "{$id}") {
          $ret = $this->setflagByUID(imap_uid($this->_stream, $msg->msgno), $flag);
          // imap_setflag_full( $this->_stream, $msg->msgno, $flag);
          // $ret = true;
          break;
        }
      }
    }

    return $ret;
  }

  public function setflagByUID($uid, $flag) {
    $this->_flush_cache($uid);
    return imap_setflag_full($this->_stream, $uid, $flag, ST_UID);
  }

  public function status($folder = "default") {

    if ($folder == 'default') $folder = self::INBOX;

    $ret = false;
    $_fake = '';
    if ($this->open(false, $_fake)) {

      if ($ret = imap_status($this->_stream, $this->_server_path . $folder, SA_ALL)) {
        $ret->folder = $folder;
      }

      $this->close();
    } else {
      logger::info(sprintf('<failed to open folder : %s> <%s> %s', $folder, $this->_error, __METHOD__));
    }

    return ($ret);
  }

  public function subscribe($fldr) {
    return imap_subscribe($this->_stream, sprintf('{%s}%s', $this->_server, $fldr));
  }
}

<?php
/*
  https://stackoverflow.com/questions/3285690/how-to-get-imap-flags
  imap = new ImapSocket([
    'server' => 'localhost',
    'port' => 143,
    'login' => 'login',
    'password' => 'secret',
    'tls' => false,
  ], 'INBOX');
  var_dump($imap->get_flags(0));

*/
declare(strict_types=1);

namespace dvc\imap;

class ImapSocket {
  private $socket;

  public function __construct($params, $mailbox = '') {
    $options = array_merge([
      'server' => 'localhost',
      'port' => 143,
      'login' => 'login',
      'password' => 'secret',
      'tls' => false,
      'ssl' => false,

    ], $params);

    $this->socket = $this->connect($options['server'], $options['port'], $options['tls'], $options['ssl']);
    $this->login($options['login'], $options['password']);

    if ($mailbox !== null) {
        $this->select_mailbox($mailbox);
    }

  }

  private function connect(string $server, int $port, bool $tls, bool $ssl) {
    if ($tls === true) {
        $server = "tls://$server";
    }
    elseif ($ssl === true) {
        $server = "ssl://$server";
    }

    $fd = fsockopen($server, $port, $errno);
    if (!$errno) {
      return $fd;
    }
    else {
      throw new \Exception('Unable to connect');

    }

  }

  private function login(string $login, string $password): void {
    $result = $this->send("LOGIN \"$login\" \"$password\"");
    $result = array_pop($result);

    if (substr($result, 0, 5) !== '. OK ') {
      throw new \Exception('Unable to login');

    }

  }

  public function __destruct() {
    fclose($this->socket);

  }

  public function select_mailbox(string $mailbox): void {
    $result = $this->send("SELECT \"$mailbox\"");
    $result = array_pop($result);

    if (substr($result, 0, 5) !== '. OK ') {
      throw new \Exception("Unable to select mailbox '$mailbox'");

    }

  }

  public function get_flags(int $id): array {
    $result = $this->send("FETCH $id (FLAGS)");
    preg_match_all("|\\* \\d+ FETCH \\(FLAGS \\((.*)\\)\\)|", $result[0], $matches);
    if (isset($matches[1][0])) {
      return explode(' ', $matches[1][0]);

    }
    else {
      return [];

    }

  }

  public function get_overview(int $id) {

    $result = $this->send("FETCH $id (UID FLAGS BODY.PEEK[HEADER.FIELDS (Date To CC From Subject Message-ID)])");

    $uid = preg_replace( '@(^.*\(UID | FLAGS .*$)@', '', $result[0]);
    // \sys::logger( sprintf('<%s> %s', $uid, __METHOD__));

    $headers = [$result[1]];
    while ( $str = trim( fgets($this->socket))) {
      if ( FALSE !== \strstr( $str, 'OK Fetch completed')) break;
      if ( ')' == $str) continue; // end is nigh
      $headers[] = $str;

    }

    $_headers = [
      'id' => $id,
      'uid' => $uid,
      'From' => '',
      'Date' => '',
      'Message-ID' => '',
      'Subject' => '',
      'To' => '',
      'CC' => '',
      'flags' => []

    ];

    foreach ($headers as $header) {
      if ( $str = $header) {

        if ( preg_match('@^From:@', $str)) {
          $_headers['From'] = trim( str_replace( 'From:', '', $str));

        }
        elseif ( preg_match('@^Date:@', $str)) {
          $_headers['Date'] = trim( str_replace( 'Date:', '', $str));

        }
        elseif ( preg_match('@^Message-ID:@', $str)) {
          $_headers['Message-ID'] = trim( str_replace( 'Message-ID:', '', $str));

        }
        elseif ( preg_match('@^Subject:@', $str)) {
          $_headers['Subject'] = trim( str_replace( 'Subject:', '', $str));

        }
        elseif ( preg_match('@^To:@', $str)) {
          $_headers['To'] = trim( str_replace( 'To:', '', $str));

        }
        elseif ( preg_match('@^CC:@', $str)) {
          $_headers['CC'] = trim( str_replace( 'CC:', '', $str));

        }
        else {
          \sys::logger( sprintf('<%s> UNKNOWN %s', $str, __METHOD__));

        }

      }

    }

    $flags = preg_replace( '@(^.*FLAGS \(|\) BODY\[.*$)@', '', $result[0]);
    if (isset($flags)) $_headers['flags'] = explode(' ', $flags);

    return $_headers;

  }

  private function send(string $cmd, string $uid = '.') {
    $query = "$uid $cmd\r\n";
    $count = fwrite($this->socket, $query);
    if ($count === strlen($query)) {
      return $this->gets();

    }
    else {
      throw new \Exception("Unable to execute '$cmd' command");

    }

  }

  private function gets() {
    $debug = false;
    // $debug = true;

    $result = [];

    $start = false;
    while ( $str = fgets($this->socket)) {
      if ( $debug) \sys::logger( sprintf('<%s> %s', $str ? $str : '(bool)false', __METHOD__));

      if ( preg_match( '/NO Mailbox doesn\'t exist/', $str)) {
        if ( $debug) \sys::logger( sprintf('<exit - no mailbox> %s', __METHOD__));
        break;

      }
      elseif (substr($str, 0, 1) == '*') {
        $result[] = substr($str, 0, -2);
        $start = true;

      }
      elseif ( $start) {
        break;

      }

    }

    $result[] = substr($str, 0, -2);
    return $result;

  }

}

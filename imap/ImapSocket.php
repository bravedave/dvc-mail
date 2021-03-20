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
    $result = $this->send("LOGIN $login $password");
    $result = array_pop($result);

    if (substr($result, 0, 5) !== '. OK ') {
      throw new \Exception('Unable to login');

    }

  }

  public function __destruct() {
    fclose($this->socket);

  }

  public function select_mailbox(string $mailbox): void {
    $result = $this->send("SELECT $mailbox");
    $result = array_pop($result);

    if (substr($result, 0, 5) !== '. OK ') {
      throw new \Exception("Unable to select mailbox '$mailbox'");

    }

  }

  public function get_flags(int $uid): array {
    $result = $this->send("FETCH $uid (FLAGS)");
    preg_match_all("|\\* \\d+ FETCH \\(FLAGS \\((.*)\\)\\)|", $result[0], $matches);
    if (isset($matches[1][0])) {
      return explode(' ', $matches[1][0]);

    }
    else {
      return [];

    }

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
    $result = [];

    while (substr($str = fgets($this->socket), 0, 1) == '*') {
      $result[] = substr($str, 0, -2);

    }
    $result[] = substr($str, 0, -2);

    return $result;

  }

}

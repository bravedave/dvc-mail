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

abstract class config extends \config {
  const imap_route = 'imap';

  const mb_detect_encoding_array = [
    'UTF-8',
    'ASCII',
    'ISO-8859-1',
    'ISO-8859-2',
    'ISO-8859-3',
    'ISO-8859-4',
    'ISO-8859-5',
    'ISO-8859-6',
    'ISO-8859-7',
    'ISO-8859-8',
    'ISO-8859-9',
    'ISO-8859-10',
    'ISO-8859-13',
    'ISO-8859-14',
    'ISO-8859-15',
    'ISO-8859-16',
    'Windows-1251',
    'Windows-1252',
    'Windows-1254'
  ];

  static $WEBNAME = 'IMAP Interface for DVC';
  static $IMAP_PAGE_SIZE = 20;

  static protected $_IMAP_VERSION = 0;

  static protected $_imap_cleaned_up = false;
  // static protected $_imap_cache_max_age = 14400;	// 4 hours
  static protected $_imap_cache_max_age = 43200;  // 12 hours
  // static protected $_imap_cache_max_age = 259200;	// 3 days

  static $_imap_cache_flushing = false;  // enforces cache flush on purge
  // static $_imap_cache_flushing = true;

  static function IMAP_CACHE() {
    $data = self::IMAP_DATA() . '_cache' . DIRECTORY_SEPARATOR;

    if (!is_dir($data)) {
      mkdir($data);
      chmod($data, 0777);
    }

    if (!is_writable($data)) throw new Exceptions\DirNotWritable($data);

    $runCleanUp = true;
    $Semaphor = $data . 'semaphor.dat';
    if (\file_exists($Semaphor)) {
      $age = time() - \filemtime($Semaphor);
      $runCleanUp = ($age > 600);
    }

    if ($runCleanUp && !self::$_imap_cleaned_up) {
      self::$_imap_cleaned_up = true;

      if (\file_exists($Semaphor)) unlink($Semaphor);
      \file_put_contents($Semaphor, date('c'));

      // clean this folder
      $iterator = new \GlobIterator($data . '*');
      foreach ($iterator as $item) {
        if (file_exists($item->getRealPath())) {

          $age = time() - $item->getMTime();
          if ($age > self::$_imap_cache_max_age) unlink($item->getRealPath());
        }
      }
    }

    return ($data);
  }

  static function IMAP_DATA() {
    $data = rtrim(self::dataPath(), '/ ') . DIRECTORY_SEPARATOR . 'imap' . DIRECTORY_SEPARATOR;

    if (!is_dir($data)) {
      mkdir($data);
      chmod($data, 0777);
    }

    if (!is_writable($data))
      throw new Exceptions\DirNotWritable($data);

    return ($data);
  }

  static protected function imap_config() {
    return sprintf('%s%simap.json', self::IMAP_DATA(), DIRECTORY_SEPARATOR);
  }

  static function imap_init() {
    if (file_exists($config = self::imap_config())) {
      $j = json_decode(file_get_contents($config));

      if (isset($j->imap_version)) self::$_IMAP_VERSION = (float)$j->imap_version;
    }
  }
}

config::imap_init();

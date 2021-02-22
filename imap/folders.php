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
use sys;

class folders {
	protected $_client;
	public $errors = [];

	static $delimiter = '.';

	static $type = 'linux';

	static $default_folders = [
		'Inbox' => client::INBOX,

		'Sent' => 'Sent',

		'Trash' => 'Trash'

	];

	function __construct( $creds = null) {
		$this->_client = client::instance( $creds);

	}

  static function changeDefaultsToExchange() {
    // for exchange server

    self::$delimiter = '/';
    self::$default_folders['Trash'] = 'Deleted Items';
    self::$default_folders['Sent'] = 'Sent Items';
    self::$type = 'exchange';

  }

  public function checkDefaultFoldersExist( array $defaultFolders) {
    if ( $folders = $this->getAll()) {

      // $defaultFolders['Frank'] = 'Frank';
      // \sys::logger( sprintf('<%s> %s', print_r( $defaultFolders, true), __METHOD__));

      foreach ($defaultFolders as $k => $v) {
        if( false === array_search( strtolower( $v), array_map('strtolower', array_column($folders, 'name')))) {
          // \sys::logger( sprintf('<%s => %s> <❌> %s', $k, $v, __METHOD__));
          $this->create( $v, '');
          \sys::logger( sprintf('<%s => %s> <created> %s', $k, $v, __METHOD__));

        }
        // else {
        //   \sys::logger( sprintf('<%s => %s> <✔> %s', $k, $v, __METHOD__));

        // }

      }

    }

  }

  static function map( $fldr, $fldrs, $level) {
		foreach ( $fldrs as $f) {
			if ( $f->id->Id == $fldr->parent_id) {
				if ( $level > 6)
					return ( sprintf( '%s;%s', $f->name, $fldr->name));
				else
					return ( sprintf( '%s;%s', folders::map( $f, $fldrs, $level + 1), $fldr->name));

			}

		}

		return $fldr->name;

	}

	protected function _getAll() {
		$ret = [];
		if ( $this->_client->open( false)) {
			if ( $list = $this->_client->folders('*')) {
				// sys::dump( $list);
				sort($list);

				$aR = [ sprintf( '@{%s}@', $this->_client->server())];

				$fldr = '';
				$a = [];
				$append = false;
				foreach ($list as $val) {
					if ( !preg_match( '@contacts|calendar|notes|tasks|journal|outbox|rss\s|sync(.*)@i', $val )) {
						$fn = preg_replace( $aR, '', imap_utf7_decode($val));
						// sys::logger( sprintf('=> %s : %s', $fn, __METHOD__));
						// sys::logger( "=>".$fn . " => @^" . $fldr . "(.|/)@" );
						// sys::logger( sprintf('%s : %s', $fn, __METHOD__));
						if ( self::$delimiter == '/' && $fldr && preg_match( sprintf( '@^%s/@', $fldr), $fn )) {
							/**
							 * exchange type server
							 */
							$sub = trim( preg_replace( sprintf( '@^%s/@', $fldr), '', $fn ), '/ ');
							$a['subfolders'][] = $sub;
							// sys::logger( sprintf('%s => %s : %s', $fn, $sub, __METHOD__));

						}
						elseif ( self::$delimiter == '.' &&  $fldr && preg_match( sprintf( '@^%s\.@', $fldr), $fn )) {
							/**
							 * linux type server
							 */

							$sub = trim( preg_replace( sprintf( '@^%s\.@', $fldr), '', $fn ), '/ ');
							$a['subfolders'][] = $sub;
							// sys::logger( sprintf('%s => %s : %s', $fn, $sub, __METHOD__));

						}
						else {
							if ( $append ) {
								if ( preg_match( "@^inbox@i", $a["name"] )) {
									array_unshift( $ret, $a );

								}
								else {
									$ret[] = $a;

								}

								$append = false;

							}
							$fldr = $fn;
							$a = ['name' => $fn];
							$append = true;

						}

					}

				}

				if ( $append ) {
					$ret[] = $a;
					$append = false;

				}

				// sys::dump( $ret);

			}
			else {
				$this->errors[] = "imap_list failed: " . imap_last_error();

			}

			$this->_client->close();

		}

		return ( $ret );

	}

	protected static function _jMap( $fldr, $parent = null, &$a) {
		$obj = function( $txt, $map) {
			return (object)[
				'name' => $txt,
				'map' => $map,
				'fullname' => str_replace( ';', self::$delimiter, $map),
				'type' => 0,
				'delimiter' => self::$delimiter
			];

		};

		if ( $parent) {
			$o = $obj( $fldr['name'], implode([$parent->map, self::$delimiter, $fldr['name']]));
		}
		else {
			$o = $obj( $fldr['name'], $fldr['name']);

		}

		if ( isset( $fldr['subfolders'])) {
			$o->subFolders = [];
			foreach ( $fldr['subfolders'] as $f) {
				// sys::logger( sprintf( '==> %s : %s', $f, __METHOD__));
				// sys::dump( $f);
				self::_jMap( ['name' => $f], $o, $o->subFolders);

			}

		}

		$a[] = $o;

	}

	protected function _allToJson( $fldrs) {

		// sys::dump( $fldrs);

		$dX = [];
		foreach ( $fldrs as $f) {
			// sys::logger( sprintf( '%s : %s', $f['name'], __METHOD__));
			self::_jMap( $f, null, $dX);

		}

		return ( $dX);

	}

	public function create( string $folder, string $parent = '') : bool {
		$ret = false;

		if ( trim( $folder)) {
      $a = [];
      if ( $parent) {
        if ( '.' == self::$delimiter) {
          $a[] = trim( \str_replace( '/', '.', $parent), '. /');

        }
        elseif ( '/' == self::$delimiter) {
          $a[] = trim( \str_replace( '.', '/', $parent), '. /');

        }

      }

      $a[] = trim( $folder);
      $fldr = implode( self::$delimiter, $a);

      if ( $this->_client->open( false)) {
        if ( $this->_client->createmailbox( $fldr)) {
          $this->_client->subscribe( $fldr);
          $ret = true;

        }
        else {
          $errors = sprintf( 'create mailbox failed : %s', imap_last_error());
          sys::logger( sprintf('%s : %s', $error, __METHOD__));

          $this->errors[] = $error;

        }

        $this->_client->close();

      }

    }

		return ( $ret );

	}

	public function delete( string $folder) : bool {
		$ret = false;

		if ( $this->_client->open( false)) {
			$fldr = trim( \str_replace( '/', self::$delimiter, $folder), '. /');
			if ( $this->_client->deletemailbox( $fldr)) {
				$ret = true;

			}
			else {
				$error = sprintf( 'delete mailbox failed : %s', imap_last_error());
				$this->errors[] = $error;
				sys::logger( sprintf( '%s : %s => %s : %s', $error, $folder, $fldr, __METHOD__));

			}

			$this->_client->close();

		}

		return ( $ret );

	}

	function getByPath( $path) {
		if ( $fldrs = $this->getAll('json')) {
			foreach ( $fldrs as $fldr) {
				if ( $path == $fldr->map) {
					return ( $fldr);

				}

			}

		}

		return ( false);

	}

	function getAll( $format = '') {
		$res = $this->_getAll();
		return ( $format == 'json' ? $this->_allToJson( $res) : $res);

	}

}

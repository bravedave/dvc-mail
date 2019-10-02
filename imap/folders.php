<?php
/*
	David Bray
	BrayWorth Pty Ltd
	e. david@brayworth.com.au

	This work is licensed under a Creative Commons Attribution 4.0 International Public License.
		http://creativecommons.org/licenses/by/4.0/

	*/
namespace dvc\imap;
use sys;

class folders {
	protected $_client;
	public $errors = [];

	const delimiter = '.';

	function __construct( $creds = null) {
		$this->_client = client::instance( $creds);

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
						sys::logger( sprintf('=> %s : %s', $fn, __METHOD__));
						// sys::logger( "=>".$fn . " => @^" . $fldr . "(.|/)@" );
						if ( $fldr && preg_match( sprintf( '@^%s(.|/)@', $fldr), $fn )) {
							//~ error_log( "==>".$fn );
							$a['subfolders'][] = preg_replace( '@^'.$fldr.'(.|/)@', '', $fn );

						}
						else {
							//~ error_log( $fldr . ":" . $fn );
							if ( $append ) {
								if ( preg_match( "@inbox@i", $a["name"] )) {
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
				'fullname' => str_replace( ';', self::delimiter, $map),
				'type' => 0,
				'delimiter' => self::delimiter
			];

		};

		if ( $parent) {
			$o = $obj( $fldr['name'], implode([$parent->map, self::delimiter, $fldr['name']]));
		}
		else {
			$o = $obj( $fldr['name'], $fldr['name']);

		}

		if ( isset( $fldr['subfolders'])) {
			$o->subFolders = [];
			foreach ( $fldr['subfolders'] as $f) {
				// sys::dump( $f);
				self::_jMap( ['name' => $f], $o, $o->subFolders);

			}

		}

		sys::logger( sprintf('%s => %s : %s :: %s',
			$fldr['name'],
			$o->name, $o->fullname, __METHOD__));


		$a[] = $o;

	}

	protected function _allToJson( $fldrs) {

		// sys::dump( $fldrs);

		$dX = [];
		foreach ( $fldrs as $f) {
			self::_jMap( $f, null, $dX);

		}

		return ( $dX);

	}

	public function create( string $folder, string $parent) : bool {
		$ret = false;

		$a = [];
		if ( $parent) {
			$a[] = trim( \str_replace( '/', '.', $parent), '. /');

		}

		$a[] = $folder;
		$fldr = implode( '.', $a);

		if ( $this->_client->open( false)) {
			if ( $this->_client->createmailbox( $fldr)) {
				$this->_client->subscribe( $fldr);
				$ret = true;

			}
			else {
				$this->errors[] = sprintf( 'create mailbox failed : %s : %s', imap_last_error(), __METHOD__);

			}

			$this->_client->close();

		}

		return ( $ret );

	}

	public function delete( string $folder) : bool {
		$ret = false;

		if ( $this->_client->open( false)) {
			if ( $this->_client->deletemailbox( trim( \str_replace( '/', '.', $folder), '. /'))) {
				$ret = true;

			}
			else {
				$this->errors[] = sprintf( 'delete mailbox failed : %s : %s', imap_last_error(), __METHOD__);

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


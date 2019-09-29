<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 * 		http://creativecommons.org/licenses/by/4.0/
 *
 * */

namespace dvc\mail;

// use dvc\mail\credentials;

// use bCrypt;
// use Json;
// use Response;
// use strings;
// use sys;
// use url;

class controller extends \Controller {
	protected function getView( $viewName = 'index', $controller = null ) {
		$view = sprintf( '%s/views/%s.php', __DIR__, $viewName );		// php
		if ( file_exists( $view))
			return ( $view);

		return parent::getView( $viewName, $controller);

	}

}

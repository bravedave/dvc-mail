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

namespace dvc\mail\pages;
use dvc\pages\_page;

class minimal extends _page {
    function __construct( $title = '' ) {
        parent::__construct( $title);

        $this->css = [];
        $this->scripts = [];
        $this->meta = [];

    }

    public function pagefooter() {
		$this->_pagefooter();
		return ( $this);	// chain

	}

}

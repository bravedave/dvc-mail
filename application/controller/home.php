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

class home extends dvc\mail\controller {
    protected function _index() {
		$this->render([
			'title' => $this->title = $this->label,
			'primary' => 'blank',
			'secondary' =>'index'

		]);

	}

	protected function before() {
		parent::before();

        /**
         * in the development environment this
         * establishes a local account
         *
         * use this area to establish an account
         *
         */
		$this->creds = currentUser::exchangeAuth();

	}

	protected function postHandler() {
		$action = $this->getPost('action');

		if ( 'save-settings' == $action) {
			$a = (object)[
				'mode' => $this->getPost('mode'),

			];

			$config = dvc\mail\config::config();
			if ( file_exists( $config)) unlink( $config);

			file_put_contents( $config, json_encode( $a, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			//~ sys::dump( $a);

			Response::redirect( strings::url( $this->route));

		}
		else {
			parent::postHandler();

		}

	}

	public function settings() {
		$this->render([
			'title' => $this->title = 'Global Settings',
			'primary' => 'settings',
			'secondary' => ['index']

		]);

	}

}

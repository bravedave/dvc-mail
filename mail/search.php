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

namespace dvc\mail;
use sys;

class search {
	protected $options = null;
	protected $creds = null;
    protected $_messages = [];

    function __construct( credentials $creds, array $options) {
        $this->creds = $creds;
        $this->options = $options;

    }

    function messages() {
        return $this->_messages;

    }

	function search( array $params = []) : array {

		$options = array_merge([
			'folder' => 'default',
			'term' => '',
			'body' => 'no',

		], $params);

		$inbox = inbox::instance( $this->creds);
		$messages = (array)$inbox->search( $options);
		// sys::dump( $messages);

		$a = [];
		foreach ( $messages as $message)
			$a[] = $message->asArray();

		return $a;
		// return $messages;

	}

    function searchall( object $fldr) {
        // sys::logger( sprintf('%s : %s (%d) :: %s',
        //     $fldr->fullname,
        //     $this->options['term'],
        //     count( $this->messages()),
        //     __METHOD__)

        // );

        $msgs = $this->search([
            'folder' => $fldr->fullname,
            'term' => $this->options['term'],

        ]);

        foreach ( $msgs as $msg) {
            if ( count( $this->_messages) >= $this->options['max-results']) break;
            $this->_messages[] = $msg;

        }

        // sys::logger( sprintf('%s : %s %d (%d) :: %s',
        //     $fldr->fullname,
        //     $this->options['term'],
        //     count( $msgs),
        //     count( $this->messages()),
        //     __METHOD__)

        // );

        if ( count( $this->messages()) < $this->options['max-results']) {
            if ( isset( $fldr->subFolders)) {
                foreach( $fldr->subFolders as $folder) {
                    if ( count( $this->messages()) >= $this->options['max-results']) break;
                    $this->searchall( $folder);

                }

            }

        }

    }

}

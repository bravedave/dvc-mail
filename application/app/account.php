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

abstract class account {
	static $SERVER = '';
	static $USERNAME = '';
	static $PASSWORD = '';
	static $ENABLED = false;

}

account::$SERVER = dvc\ews\account::$SERVER;
account::$USERNAME = dvc\ews\account::$USERNAME;
account::$PASSWORD = dvc\ews\account::$PASSWORD;
account::$ENABLED = dvc\ews\account::$ENABLED;

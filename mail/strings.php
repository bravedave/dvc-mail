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

class strings extends \strings {
    function splitEmails( string $strOfEmails) : array {

        /*

        noting that there is a missing , between the first two emails and thne test survives
        so the email can be delimie by
            - space
            - comma
            - semicolon

        $test = 'dave@domain.tld "Bray, David" <david@brayworth.com.au>, "Bray, Dave" <david@brayworth.com.au>; davbray@domain.tld';
        Response::text_headers();
        print_r( \dvc\mail\strings::splitEmails());

        Result:
            Array
            (
                [0] => dave@domain.tld
                [1] => "Bray, David" <david@brayworth.com.au>
                [2] => "Bray, Dave" <david@brayworth.com.au>
                [2] => David Bray <david@brayworth.com.au>
                [3] => davbray@domain.tld
            )

        */

        $matches = [];
        /**
         * choice of two patterns
         *  a. (("[^"]*?"|[^,<]*?)?\s?<[^>]*>
         *      this actually matchs two patterns
         *      either - not the email is enclosed by <> (angle brackets)
         *      i. an unquoted name followed by <email> - but commas can't be present
         *      or
         *      ii. a quoted string name followed by <email>, ther may be commas in the string
         *
         *  b. \b[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}\b
         *      i. a valid email address
         */
        preg_match_all( '/(("[^"]*?"|[^,<]*?)?\s?<[^>]*>|\b[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}\b)?/i', $strOfEmails, $matches);
        $r = [];
        foreach( $matches[0] as $match) {
            if ( trim( $match)) $r[] = trim( $match);

        }

        return $r;

    }

}
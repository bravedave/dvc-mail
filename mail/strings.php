<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace dvc\mail;

abstract class strings extends \strings {
  static function htmlentities(string $html): string {
    return htmlentities($html, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8", false);
  }

  static function htmlspecialchars(string $html): string {
    return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8", false);
  }

  static function splitEmails(string $strOfEmails): array {

    /*

        noting that there is a missing , between the first two emails and the test survives
        so the email can be delimited by
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
    preg_match_all('/(("[^"]*?"|[^,<]*?)?\s?<[^>]*>|\b[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}\b)?/i', $strOfEmails, $matches);
    $r = [];
    foreach ($matches[0] as $match) {
      if (trim($match)) $r[] = trim($match);
    }

    return $r;
  }
}

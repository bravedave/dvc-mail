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
use bravedave\dvc\EmailAddress;
use sys;

class MimeMessage {
  protected $msg;
  protected $original;
	protected $_headers = [];

  protected function _getMessage( $msg) {
    /**
     * Return the body as a string (the MAILPARSE_EXTRACT parameter
     * acts just as it does in extract_headers method.
     */
    try {
      \sys::logger( sprintf('<%s> %s', 'ooh ...', __METHOD__));
      $body = $msg->extract_body( MAILPARSE_EXTRACT_RETURN);
      return htmlentities( $body);

    }
    catch ( \Exception $e) {
      // \sys::logger( sprintf('<%s> %s', 'ouch ...', __METHOD__));

      // sys::dump( $this->original);
      return $this->original;

    }

  }

  public function __construct( string $var) {
      $this->original = $var;
      $this->msg = new \MimeMessage( "var", $var);

  }

	public function getHeader( string $header) : string {
		if ( !$this->_headers) {
			$this->_headers = explode( PHP_EOL, $this->getHeaders());

		}

		if ( $this->_headers) {
			foreach ( $this->_headers as $_header) {
				$_strpos = strpos( $_header, ':');
				if ( false !== $_strpos) {
					$k = substr($_header, 0, ($_strpos));
					$v = substr($_header, ($_strpos + 1));
					//~ \sys::logger( sprintf( '<%s => %s> : %s', $k, $v, __METHOD__));

					if ( strtolower( $header) == strtolower( $k)) {
						return trim( (string)$v);

					}

				}

			}

		}

		return '';

	}

  public function getHeaders() {
      return $this->msg->extract_headers( MAILPARSE_EXTRACT_RETURN);

  }

  public function getMessage() {
      $n = $this->msg->get_child_count();

      if ($n == 0) {
          return $this->_getMessage( $this->msg);

      }
      else {
          // Recurse and show children of that part
          $parts = '';
          for ($i = 0; $i < $n; $i++) {
              $parts .= "child $i\n" . $this->_getMessage( $this->msg->get_child($i));

          }

          return $parts;

      }

  }

  // protected function xgetMessage() {
  //     /* parse the message and return a mime message resource */

  //     /**
  //      * return an array of message parts
  //      * - this consists of the names of the parts only
  //      **/
  //     $struct = mailparse_msg_get_structure( $this->msg);

  //     $tbl = "<table>\n";
  //     /* print a choice of sections */
  //     foreach( $struct as $st)	{
  //         $tbl .= "<tr>\n";
  //         $tbl .= "<td>$st</td>\n";

  //         /* get a handle on the message resource for a subsection */
  //         $section = mailparse_msg_get_part( $this->msg, $st);

  //         /* get content-type, encoding and header information for that section */
  //         $info = mailparse_msg_get_part_data( $section);
  //         // print_r($info);
  //         // echo "\n";

  //         $tbl .= "<td>" . $info["content-type"] . "</td>\n";
  //         $tbl .= "<td>" . $info["content-disposition"] . "</td>\n";
  //         $tbl .= "<td>" . $info["disposition-filename"] . "</td>\n";
  //         $tbl .= "<td>" . $info["charset"] . "</td>\n";
  //         $tbl .= "</tr>";

  //         $sec = mailparse_msg_get_part($mime, $showpart);
  //         ob_start();
  //         /**
  //          * extract the part from the message
  //          * file and dump it to the output buffer
  //          **/
  //         mailparse_msg_extract_part_file($sec, $filename);
  //         $contents = ob_get_contents();
  //         ob_end_clean();

  //         /* quote the message for safe display in a browser */
  //         $tbl .= "<tr><td>" . nl2br(htmlentities( $contents)) . "</td></tr>";

  //     }
  //     $tbl .= "</table>";
  //     return $tbl;

  // }

  // Little function to display things
  public function display_part_info( $caption) {
      echo "Message part: $caption\n";

      /**
       * The data member corresponds to the information
       * available from the mailparse_msg_get_part_data function.
       * You can access a particular header like this:
       * $subject = $this->msg->data["headers"]["subject"];
       */
      var_dump($this->msg->data);

      echo "The headers are:\n";
      /**
       * Display the headers (in raw format) to the browser output.
       * You can also use:
       *   $this->msg->extract_headers(MAILPARSE_EXTRACT_STREAM, $fp);
       *     to write the headers to the supplied stream at it's current
       *     position.
       *
       *   $var = $msgpart->extract_headers(MAILPARSE_EXTRACT_RETURN);
       *     to return the headers in a variable.
       */
      $this->msg->extract_headers( MAILPARSE_EXTRACT_OUTPUT);

      /**
       * Display the body if this part is intended to be displayed:
       */
      $n = $this->msg->get_child_count();

      if ($n == 0) {
          /**
           * Return the body as a string (the MAILPARSE_EXTRACT parameter
           * acts just as it does in extract_headers method.
           */
          $body = $this->msg->extract_body(MAILPARSE_EXTRACT_RETURN);
          echo htmlentities($body);

          /**
           * This function tells you about any uuencoded attachments
           * that are present in this part.
           */
          $uue = $this->msg->enum_uue();
          if ($uue !== false) {
              var_dump($uue);
              foreach($uue as $index => $data) {
                  /**
                   * $data => array("filename" => "original filename",
                   *                "filesize" => "size of extracted file",
                   *                );
                   */

                  printf("UUE[%d] %s (%d bytes)\n",
                      $index, $data["filename"],
                      $data["filesize"]);

                  /* Display the extracted part to the output. */
                  $this->msg->extract_uue($index, MAILPARSE_EXTRACT_OUTPUT);

              }

          }

      }
      else {
          // Recurse and show children of that part
          for ($i = 0; $i < $n; $i++) {
              $part =& $this->msg->get_child($i);
              $this->display_part_info("$caption child $i", $part);

          }

      }

  }

	public function SaveToFile( \PHPMailer $message, $msgStore, array $files = []) {
        /**
         * this message can be restored with
         *
         * $msg = dvc\mail\inbox::ReadFromFile( $path);
         *
         */

		$debug = false;
		//~ $debug = true;

		if ( !is_dir( $msgStore)) {
			mkdir( $msgStore, 0777);
			chmod( $msgStore, 0777);

		}

		if ( !is_writable($msgStore)) throw new \dvc\Exceptions\DirNotWritable( $msgStore);

		$attachmentPath = sprintf( '%s/attachments', $msgStore);
		if ( !file_exists( $attachmentPath)) {
			mkdir( $attachmentPath, 0777);
			chmod( $attachmentPath, 0777);

		}

		if ( !is_writable( $attachmentPath)) throw new \dvc\Exceptions\DirNotWritable( $attachmentPath);

		$from = $this->getHeader( 'From');
		$em = new EmailAddress( $from);

		$j = [
			'fromName' => $em->name,
			'fromEmail' => $em->email,
			'time' => $this->getHeader( 'date'),
			'Body' => $message->Body,
			'Folder' => 'Sent',
			'From' => $from,
			'MessageID' => $this->getHeader( 'message-id'),
			'Recieved' => '',
			'Subject' => $this->getHeader( 'subject'),
			'To' => $this->getHeader( 'to'),

		];

		$file = sprintf( '%s/msg.json', $msgStore);
		if ( file_exists( $file)) {
			if ( $debug) \sys::logger( sprintf( 'msg exists : %s :: %s', $file, __METHOD__));

		}
		else {
			file_put_contents( $file, json_encode( $j, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
			if ( $debug) \sys::logger( sprintf( 'save msg : %s :: %s', $file, __METHOD__));

		}

		foreach ( $files as $file) {
			$_file = sprintf( '%s/%s', $attachmentPath, $file->Name);
			if ( $debug) \sys::logger( sprintf( '<%s> : %s', $_file, __METHOD__));
			if ( file_exists( $_file)) {
				if ( $debug) \sys::logger( sprintf( 'attachment exists : %s :: %s', $_file, __METHOD__));

			}
			else {
				if ( !link( $file->Path, $_file)) {
					copy( $file->Path, $_file);	// takes up more space

				}

				if ( $debug) \sys::logger( sprintf( 'saved attachment : %s :: %s', $_file, __METHOD__));

			}

		}

		// Make sure the destination directory exists and is writeable.

	}

}

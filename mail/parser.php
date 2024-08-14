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

class parser extends \PhpMimeMailParser\Parser {
  public function asMessage() : message {

    $msg = new message;

    $msg->Received = $this->getHeader('date');
    $msg->Body = $this->getMessageBody('htmlEmbedded');
    $msg->From = $this->getHeader('from');
    $msg->MessageID = $this->getHeader('message-id');
    $msg->Subject = $this->getHeader('subject');
    $msg->To = $this->getHeader('to');
    $attachments = $this->getAttachments(false);
    foreach ($attachments as $attachment ) {
      // \sys::dump( $attachment);

      $attach = new attachment;
      $attach->Name = $attachment->getFilename();
      $attach->ContentId = $attachment->getContentID();
      $attach->Content = $attachment->getContent();

      $filename = strings::safe_file_name($attach->Name);
      if ($filename) {
        $attach->Name = $filename;
      }

      $msg->attachments[] = $attach;

    }

    return $msg;

  }
}

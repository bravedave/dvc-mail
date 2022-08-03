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

use dvc\imap\config;
use Sabre\VObject;

$debug = false;
// $debug = true;
// print \config::$PAGE_TEMPLATE;
$msg = $this->data->message;
// \sys::dump( $msg);
if ($debug) \sys::logger(sprintf('<start> %s', __METHOD__));

/**
 * process the safe html before listing the attachments
 * inline images are removed from the
 * attachments in this process
 */

$msgHtml = '';
if ('text' == strtolower($msg->BodyType)) {
  $encoding = mb_detect_encoding($msg->Body, config::mb_detect_encoding_array);

  if ($debug) \sys::logger(sprintf('<encoding : %s> %s', $encoding, __METHOD__));

  if ('utf-8' == strtolower($encoding)) {
    // \sys::logger( sprintf('<%s> %s', $encoding, __METHOD__));
    // $f = sprintf('%s/temp-0-utf8-message.txt', \config::dataPath());
    // if ( \file_exists($f)) unlink( $f);
    // \file_put_contents( $f, $msg->Body);

    $_msg = mb_convert_encoding($msg->Body, 'HTML-ENTITIES', 'UTF-8');

    // $f = sprintf('%s/temp-1-utf8-message.txt', \config::dataPath());
    // if ( \file_exists($f)) unlink( $f);
    // \file_put_contents( $f, $_msg);

  } elseif ('ascii' == strtolower($encoding)) {
    $_msg = mb_convert_encoding($msg->Body, 'HTML-ENTITIES', 'ASCII');
    // $_msg = $msg->Body;
    // \sys::logger( sprintf('<%s> %s', 'no conversion', __METHOD__));
    // $f = sprintf('%s/temp-4-message.txt', \config::dataPath());
    // if ( \file_exists($f)) unlink( $f);
    // \file_put_contents( $f, $_msg);

  } elseif (in_array($encoding, config::mb_detect_encoding_array)) {
    $_msg = mb_convert_encoding($msg->Body, 'HTML-ENTITIES', $encoding);
    // $_msg = $msg->Body;
    // \sys::logger( sprintf('<%s> %s', 'no conversion', __METHOD__));
    // $f = sprintf('%s/temp-4-message.txt', \config::dataPath());
    // if ( \file_exists($f)) unlink( $f);
    // \file_put_contents( $f, $_msg);

  } elseif (!$encoding) {
    /**
     * this list will grow I suspect, could probably just pull them from php
     * https://www.php.net/manual/en/mbstring.supported-encodings.php
     * forum : #7311 Mail - issue reading email
     * forum : #7276 Mail - Mail not reading
     */

    $otherEncodings = [
      'iso-8859-1'

    ];

    if (in_array(strtolower($msg->CharSet), $otherEncodings)) {
      $_msg = mb_convert_encoding($msg->Body, 'HTML-ENTITIES', strtoupper($msg->CharSet));
      if ($debug) \sys::logger(sprintf('<charset : %s> %s', $msg->CharSet, __METHOD__));
    } else {

      // there is no encoding
      $_msg = $msg->Body;
      if ($debug) \sys::logger(sprintf('<encoding : %s> %s', 'none', __METHOD__));
      // sys::dump( $msg);

    }
  } else {
    $_msg = sprintf("Encoding: %s\n%s", $encoding, $msg->Body);
    // $f = sprintf('%s/temp-3-message.txt', \config::dataPath());
    // if ( \file_exists($f)) unlink( $f);
    // \file_put_contents( $f, $_msg);

  }

  // $msgHtml = sprintf( "<pre>%s</pre>", htmlentities( $_msg));
  // $_msg = htmlspecialchars_decode( $_msg);
  $msgHtml = sprintf("<pre>%s</pre>", htmlspecialchars($_msg, $flags = ENT_COMPAT, $encoding = null, $double_encode = false));
  // die($msgHtml);
  // $msgHtml = sprintf( "<pre>%s</pre>", $_msg);
  // $msgHtml = str_replace( "\n", '<br />', $_msg);


} elseif ($msg->hasMso()) {
  $msgHtml = sprintf(
    '<style>p { margin: 0; }</style> %s %s',
    $msg->getMso(),
    preg_replace([
      '@class="WordSection1"@'
    ], '', $msg->safehtml())

  );

  if ($debug) \sys::logger(sprintf('<hasMso> %s', __METHOD__));
} elseif ($msg->hasBuggyMso()) {
  $msgHtml = sprintf(
    '<style>p { margin: 0; }</style> %s',
    preg_replace([
      '@class="WordSection1"@'
    ], '', $msg->safehtml())

  );

  if ($debug) \sys::logger(sprintf('<hasBuggyMso> %s', __METHOD__));
} else {
  $msgHtml = $msg->safehtml();
  if ($debug) \sys::logger(sprintf('<else> %s', __METHOD__));
  // $msgHtml = $msg->Body;
  // \sys::logger( sprintf('<%s> %s', strlen( $msgHtml), __METHOD__));


}


// unset( $this->data->message);
// $msg->attachments = [];
// $msg->Body = '';
// sys::dump( $msg);
// sys::dump( $this->data->default_folders);
$colStyle = 'width: 5rem; font-size: small;';
?>
<style>
  html,
  body {
    font-family: sans-serif;
  }

  ::-webkit-scrollbar {
    width: .5em;
  }

  ::-webkit-scrollbar-track {
    -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
    box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
  }

  ::-webkit-scrollbar-thumb {
    background-color: darkgrey;
    outline: 1px solid slategrey;
    border-bottom: 1px solid #ddd;
  }

  .grid-container {
    display: grid;
    grid-template-columns: auto;
    background-color: #efefef;
    margin: 0;
  }

  .grid-item {
    padding: 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .mail-text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;

  }

  pre {
    display: block;
    font-size: .875rem;
    line-height: 1.3em;
    word-break: normal;
    word-wrap: break-word;
    white-space: pre-wrap;
    font-family: Monaco, Menlo, Consolas, 'Courier New', monospace;

  }

  p.MsoNormal {
    margin: 0
  }

  div[message] {
    padding: 8px;
    margin: 0 0 1rem;
  }

  div[message]>div[data-x_type="body"] {
    margin: -8px;
    padding: 8px;
  }

  b,
  strong {
    font-weight: bold;
  }
</style>

<div class="grid-container">
  <div class="grid-item">
    <span style="float: right;"><?= strings::asLocalDate($msg->Recieved, $time = true) ?></span>
    <span style="display: none;" data-role="time"><?= $msg->Recieved ?></span>
    <?php
    if ($this->data->default_folders['Sent'] == $msg->Folder) {
      $_to = array_map(function ($v) {
        return sprintf(
          '<strong data-role="from" data-email="%s">%s</strong>',
          htmlentities($v),
          htmlentities($v),

        );
      }, explode(',', $msg->To));

      printf(
        '<div style="float: left;"><small>to&nbsp;</small>%s</div>',
        implode(', ', $_to)

      );
      // printf( '<div style="float: left;"><small>to&nbsp;</small><strong data-role="from" data-email="%s">%s</strong></div>',
      //   htmlentities( $msg->To),
      //   htmlentities( $msg->To)
      // );

    } else {
      if ($msg->From && $msg->From != $msg->fromEmail) {
        printf(
          '<div style="float: left;"><strong data-role="from" data-email="%s">%s</strong></div>',
          htmlentities($msg->From),
          htmlentities($msg->From)
        );
      } else {
        printf(
          '<div style="float: left;"><strong data-role="from" data-email="%s">%s</strong></div>',
          htmlentities($msg->fromEmail),
          $msg->fromEmail
        );
      }
    }
    ?>
  </div><!-- div class="grid-item" -->

  <?php

  if ($this->data->default_folders['Sent'] != $msg->Folder) {

    if ($msg->ReplyTo) {
      printf(
        '<div class="grid-item mail-text-truncate">
            <small label>reply to&nbsp;</small>
            <strong data-role="reply-to" data-email="%s">%s</strong>
          </div>',
        htmlspecialchars($msg->ReplyTo),
        htmlentities($msg->ReplyTo)
      );
    }
  ?>

    <div class="grid-item mail-text-truncate" data-role="recipients">
      <small label>to&nbsp;</small>
      <?php
      // $tos = explode( ',', $msg->To);
      $tos = strings::splitEmails($msg->To);
      if (($ito = count($tos)) > 1) {
        $uid = strings::rand();
        printf(
          '<span style="font-size: small;" data-role="to" data-email="%s">%s</span>',
          htmlspecialchars($tos[0]),
          htmlentities($tos[0])
        );

        printf('&nbsp;<a href="#" data-role="extra-recipients" data-target="%s">+%d more</a>', $uid, $ito - 1);
        array_shift($tos);
        $_tos = array_map(function ($to) {
          return sprintf(
            '<span data-role="to" data-email="%s">%s</span>',
            htmlspecialchars($to),
            htmlentities($to)
          );
        }, $tos);

        printf(
          '<span style="display: none; font-size: small;" id="%s">, %s</span>',
          $uid,
          implode(', ', $_tos)
        );
      } else {
        printf(
          '<span data-role="to" data-email="%s">%s</span>',
          htmlspecialchars($msg->To),
          htmlentities($msg->To)
        );
      } ?>

    </div>

  <?php
  }   // if ( $this->data->default_folders['Sent'] == $msg->Folder)

  if ($msg->CC) {    ?>
    <div class="grid-item" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
      <small>cc&nbsp;</small>
      <?php
      // printf( '<!-- --[%s]-- -->', $msg->CC);

      $ccs = strings::splitEmails($msg->CC);
      if (($icc = count($ccs)) > 1) {
        $uid = strings::rand();
        printf(
          '<span data-role="cc" style="font-size: small" data-email="%s">%s</span>',
          htmlentities($ccs[0]),
          htmlentities($ccs[0])
        );
        printf('&nbsp;<a href="#" data-role="extra-recipients" data-target="%s">+%d more</a>', $uid, $icc - 1);
        array_shift($ccs);
        $_ccs = [];
        foreach ($ccs as $cc) {
          $_ccs[] = sprintf(
            '<span data-role="cc" data-email="%s">%s</span>',
            htmlentities($cc),
            htmlentities($cc)
          );
        }
        printf('<span style="display: none; font-size: small;" id="%s">, %s</span>', $uid, implode(', ', $_ccs));
      } else {
        printf(
          '<span data-role="cc" data-email="%s">%s</span>',
          htmlentities($msg->CC),
          htmlentities($msg->CC)
        );
      } ?>

    </div>

  <?php
  }   // if ( $msg->CC)
  ?>

  <div class="grid-item" data-role="subject" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlentities($msg->Subject) ?>">
    <?= $msg->Subject ?>

  </div>

</div><!-- div class="grid-container" -->

<?php
if ($iMsgCount = count($msg->attachments)) {
  $splitAt = $iMsgCount > 0 ? ceil($iMsgCount / 2) : 0; ?>

  <table style="width: 100%;">
    <tbody>
      <tr>
        <td style="padding: 0; vertical-align: top;">
          <table style="width: 100%;" cellpadding="2">
            <tbody data-role="attachment-toolbar">
              <?php
              $iMsg = 0;
              foreach ($msg->attachments as $key => $attachment) {
                if ($iMsg++ == $splitAt) {
                  print '</tbody>
                      </table>
                    </td>
                    <td style="padding: 0; vertical-align: top;">
                      <table style="width: 100%;" cellpadding="2">
                        <tbody data-role="attachment-toolbar">';
                }

                if ('object' == gettype($attachment)) {
                  $path = [
                    sprintf('%s/file', $this->route),
                    sprintf('?uid=%s', $msg->Uid),
                    sprintf('&folder=%s', urlencode($msg->Folder)),
                    sprintf('&item=%s', urlencode($attachment->ContentId)),

                  ];

                  if ($this->data->user_id) {
                    $path[] = sprintf('&user_id=%d', $this->data->user_id);
                  }

                  $finfo = new \finfo(FILEINFO_MIME);
                  $mimetype = $finfo->buffer($attachment->Content);

                  // target="_blank"
                  printf(
                    '<tr><td><a
                    href="%s"
                    data-rel="attachment"
                    data-id="%s"
                    data-mimetype="%s"
                    style="font-size: small"
                    >%s</a></td></tr>',
                    strings::url(implode($path)),
                    $attachment->ContentId,
                    $mimetype,
                    $attachment->Name

                  );
                } else {
                  if (preg_match('@^BEGIN:VCALENDAR@', $attachment)) {
                    $vcalendar = VObject\Reader::read($attachment, VObject\Reader::OPTION_IGNORE_INVALID_LINES);
                    /**
                     * BEGIN:VCALENDAR
                     * PRODID:-//Google Inc//Google Calendar 70.9054//EN
                     * VERSION:2.0
                     * CALSCALE:GREGORIAN
                     * METHOD:REQUEST
                     * BEGIN:VEVENT
                     * DTSTART:20191031T060000Z
                     * DTEND:20191031T070000Z
                     * DTSTAMP:20191030T072504Z
                     * ORGANIZER;CNڶid Bray:mailto:david@brayworth.com.au
                     * UID:4giii922352nqurabqeutmj0li@google.com
                     * ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT¬CEPTED;RSVP=TRUE
                     *  ;CNڶid Bray;X-NUM-GUESTS=0:mailto:david@brayworth.com.au
                     * ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP TRUE;CNڶidb@darcy.com.au;X-NUM-GUESTS=0:mailto:davidb@darcy.com.au
                     * X-MICROSOFT-CDO-OWNERAPPTID:618106913
                     * CREATED:20191030T072501Z
                     * DESCRIPTION:-::~:~::~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~
                     *  :~:~:~:~:~:~:~:~::~:~::-\nPlease do not edit this section of the descriptio
                     *  n.\n\nThis event has a video call.\nJoin: https://meet.google.com/moe-azdx-
                     *  how\n+61 2 9051 6857 PIN: 993870766#\nView more phone numbers: https://tel.
                     *  meet/moe-azdx-how?pin`56668846446&hs=7\n\nView your event at https://www.
                     *  google.com/calendar/event?action=VIEW&eid=NGdpaWk5MjIzNTJucXVyYWJxZXV0bWowb
                     *  GkgZGF2aWRiQGRhcmN5LmNvbS5hdQ&tok=MjIjZGF2aWRAYnJheXdvcnRoLmNvbS5hdTczMzAxN
                     *  GVjZjBiZTRlYzg4MzIwZTQzMGE5NDY2ZGM1NTVjMTk1N2Q&ctz=Australia%2FBrisbane&hl en&es=1.\n-::~:~::~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~:~
                     *  :~:~:~:~:~:~:~::~:~::-
                     * LAST-MODIFIED:20191030T072501Z
                     * LOCATION:
                     * SEQUENCE:0
                     * STATUS:CONFIRMED
                     * SUMMARY:Meeting
                     * TRANSP:OPAQUE
                     * END:VEVENT
                     * END:VCALENDAR
                     * BEGIN:VCALENDAR
                     */

                    $start = strtotime($vcalendar->VEVENT->DTSTART);
                    $end = strtotime($vcalendar->VEVENT->DTEND);

                    if (date('Y-m-d') == date('Y-m-d')) {
                      $end = preg_replace('/m$/', '', date(\config::$TIME_FORMAT, $end));
                    } else {
                      $end = preg_replace('/m$/', '', date(\config::$DATETIME_FORMAT, $end));
                    }

                    $start = preg_replace('/m$/', '', date(\config::$DATETIME_FORMAT, $start));

                    printf(
                      '<tr><td><div
                            target="_blank"
                            data-rel="appointment"
                            data-summary=%s
                            data-description=%s
                            data-location=%s
                            data-start=%s
                            data-end=%s
                            >%s : %s - %s</div></td></tr>%s',
                      json_encode((string)$vcalendar->VEVENT->SUMMARY),
                      json_encode((string)$vcalendar->VEVENT->DESCRIPTION, JSON_UNESCAPED_SLASHES),
                      json_encode((string)$vcalendar->VEVENT->LOCATION, JSON_UNESCAPED_SLASHES),
                      json_encode((string)$vcalendar->VEVENT->DTSTART, JSON_UNESCAPED_SLASHES),
                      json_encode((string)$vcalendar->VEVENT->DTEND, JSON_UNESCAPED_SLASHES),
                      htmlentities($vcalendar->VEVENT->SUMMARY),
                      $start,
                      $end,
                      PHP_EOL
                    );
                  } else {
                    printf('<tr><td>%s</td></tr>', $attachment);
                  }
                }
              }   // foreach ( $msg->attachments as $key => $attachment)
              ?>

            </tbody>

          </table>

        </td>

      </tr>

    </tbody>

  </table>

<?php
}   // if ( count( $msg->attachments))

printf('<div message>%s</div>', $msgHtml);

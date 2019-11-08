<?php
/**
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * This work is licensed under a Creative Commons Attribution 4.0 International Public License.
 *      http://creativecommons.org/licenses/by/4.0/
 ** */

// print \config::$PAGE_TEMPLATE;
$msg = $this->data->message;
// unset( $this->data->message);
// $msg->attachments = [];
// $msg->Body = '';
// sys::dump( $msg);
// sys::dump( $this->data->default_folders);
$colStyle = 'width: 5rem; font-size: small;';
?>
<style>
html, body { font-family: sans-serif; }
::-webkit-scrollbar {
    width: .5em;
}

::-webkit-scrollbar-track {
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
}

::-webkit-scrollbar-thumb {
	background-color: darkgrey;
	outline: 1px solid slategrey;
}
</style>
<table style="width: 100%; font-family: sans-serif; border-bottom: 1px solid silver;" cellpadding="2">
    <tbody>
        <tr>
            <td>
                <span style="float: right;"><?= strings::asLocalDate( $msg->Recieved, $time = true) ?></span>
                <span style="display: none;" data-role="time"><?= $msg->Recieved ?></span>
                <?php
                $_style = 'overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';
                if ( $this->data->default_folders['Sent'] == $msg->Folder) {
                    printf( '<div style="%s"><small>to&nbsp;</small><strong data-role="from" data-email=%s>%s</strong></div>',
                        $_style,
                        json_encode( $msg->To),
                        htmlentities( $msg->To)
                    );

                }
                else {
                    if ( $msg->From && $msg->From != $msg->fromEmail) {
                        printf( '<div style="%s"><strong data-role="from" data-email=%s>%s</strong></div>',
                            $_style,
                            json_encode( $msg->From),
                            htmlentities( $msg->From)
                        );

                    }
                    else {
                        printf( '<div style="%s"><strong data-role="from" data-email=%s>%s</strong></div>',
                            $_style,
                            json_encode( $msg->fromEmail),
                            $msg->fromEmail
                        );

                    }

                }
                ?>

            </td>

        </tr>

<?php   if ( $this->data->default_folders['Sent'] != $msg->Folder) {    ?>
        <tr>
            <td>
                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <small>to&nbsp;</small>
                    <?php
                    $tos = explode( ',', $msg->To);
                    if ( ( $ito = count( $tos)) > 1) {
                        $uid = strings::rand();
                        print htmlentities( $tos[0]);
                        printf( '&nbsp;<a href="#" data-role="extra-recipients" data-target="%s">+%d more</a>', $uid, $ito-1);
                        array_shift( $tos);
                        $_tos = [];
                        foreach( $tos as $to) {
                            $_tos[] = htmlentities( $to);

                        }

                        printf( '<span style="display: none;" id="%s">, %s</a>', $uid, implode( ', ', $_tos));

                    }
                    else {
                        print htmlentities( $msg->To);

                    } ?>

                </div>

            </td>

        </tr>

<?php   }   // if ( $this->data->default_folders['Sent'] == $msg->Folder)

        if ( $msg->CC) {    ?>
        <tr>
            <td>
                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <small>cc&nbsp;</small>
                    <?php
                    $ccs = explode( ',', $msg->CC);
                    if ( ( $icc = count( $ccs)) > 1) {
                        $uid = strings::rand();
                        printf( '<strong data-role="cc" data-email=%s>%s</strong>',
                            json_encode( $ccs[0]),
                            htmlentities( $ccs[0])
                        );
                        printf( '&nbsp;<a href="#" data-role="extra-recipients" data-target="%s">+%d more</a>', $uid, $icc-1);
                        array_shift( $ccs);
                        $_ccs = [];
                        foreach( $ccs as $cc) {
                            $_ccs[] = sprintf( '<strong data-role="cc" data-email=%s>%s</strong>',
                                json_encode( $cc),
                                htmlentities( $cc)
                            );

                        }
                        printf( '<span style="display: none;" id="%s">, %s</a>', $uid, implode( ', ', $_ccs));

                    }
                    else {
                        printf( '<strong data-role="cc" data-email=%s>%s</strong>',
                            json_encode( $msg->CC),
                            htmlentities( $msg->CC)
                        );

                    } ?>

                </div>

            </td>

        </tr>

<?php   }   // if ( $msg->CC)   ?>

        <tr>
            <td>
                <div data-role="subject" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <?= $msg->Subject ?>

                </div>

            </td>

        </tr>

    </tbody>

</table>
<?php
    // unset( $msg->src);
    // foreach ( $msg->attachments as $attachment) {
    //     unset( $attachment->Content);

    // }
    // sys::dump( $msg);
    // sys::dump( $msg->attachments);
    if ( 'text' == strtolower( $msg->BodyType)) {
        printf( '<div message style="max-width: 100%%; overflow-x: auto;"><pre>%s</pre></div>', $msg->Body);

    }
    elseif ( $msg->hasMso()) {
        printf( '<div message style="max-width: 100%%; overflow-x: auto;">%s %s %s</div>',
            '<style>p { margin: 0; }</style>',
            $msg->getMso(),
            $msg->safehtml());

    }
    else {
        printf( '<div message style="width: 100%%; overflow-x: auto;">%s</div>', $msg->safehtml());

    }

    // unset( $msg->src);
    if ( count( $msg->attachments)) {   ?>
<table style="width: 100%; font-family: sans-serif; border-top: 1px solid silver; margin-top: 1rem;" cellpadding="2">
    <tbody>
    <?php
    foreach ( $msg->attachments as $key => $attachment) {
        if ( 'object' == gettype( $attachment)) {
            $path = sprintf('%s/file?uid=%s&folder=%s&item=%s',
                $this->route,
                $msg->Uid,
                urlencode($msg->Folder),
                urlencode($attachment->ContentId)
            );

            $finfo = new \finfo(FILEINFO_MIME);
            $mimetype = $finfo->buffer( $attachment->Content);

            printf('<tr><td><a
                href="%s"
                target="_blank"
                data-rel="attachment"
                data-id="%s"
                data-mimetype="%s"
                >%s</a></td></tr>',
                strings::url( $path),
                $attachment->ContentId,
                $mimetype,
                $attachment->Name
            );

        }
        else {
            if ( preg_match( '@^BEGIN:VCALENDAR@', $attachment)) {
                $vcalendar = Sabre\VObject\Reader::read($attachment);
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

                $start = strtotime( $vcalendar->VEVENT->DTSTART);
                $end = strtotime( $vcalendar->VEVENT->DTEND);

                if ( date('Y-m-d') == date( 'Y-m-d')) {
                    $end = preg_replace( '/m$/','',date( \config::$TIME_FORMAT, $end));

                }
                else {
                    $end = preg_replace( '/m$/','',date( \config::$DATETIME_FORMAT, $end));

                }

                $start = preg_replace( '/m$/','',date( \config::$DATETIME_FORMAT, $start));

                printf('<tr><td><div
                    target="_blank"
                    data-rel="appointment"
                    data-summary=%s
                    data-description=%s
                    data-location=%s
                    data-start=%s
                    data-end=%s
                    >%s : %s - %s</div></td></tr>%s',
                    json_encode( (string)$vcalendar->VEVENT->SUMMARY),
                    json_encode( (string)$vcalendar->VEVENT->DESCRIPTION, JSON_UNESCAPED_SLASHES),
                    json_encode( (string)$vcalendar->VEVENT->LOCATION, JSON_UNESCAPED_SLASHES),
                    json_encode( (string)$vcalendar->VEVENT->DTSTART, JSON_UNESCAPED_SLASHES),
                    json_encode( (string)$vcalendar->VEVENT->DTEND, JSON_UNESCAPED_SLASHES),
                    htmlentities( $vcalendar->VEVENT->SUMMARY),
                    $start, $end,
                    PHP_EOL
                );

            }
            else {
                printf('<tr><td>%s</td></tr>', $attachment);

            }

        }

    } ?>
    </tbody>

</table>


    <?php
    }

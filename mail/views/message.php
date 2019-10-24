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
<table style="width: 100%; font-family: sans-serif; border-bottom: 1px solid silver;" cellpadding="2">
    <tbody>
        <tr>
            <td>
                <span style="float: right;"><?= strings::asLocalDate( $msg->Recieved, $time = true) ?></span>
                <span style="display: none;" data-role="time"><?= $msg->Recieved ?></span>
                <?php
                if ( $this->data->default_folders['Sent'] == $msg->Folder) {
                    printf( '<small>to&nbsp;</small><strong data-role="from">%s</strong>', $msg->To);

                }
                else {
                    if ( $msg->From && $msg->From != $msg->fromEmail) {
                        printf( '<strong data-role="from">%s <%s></strong>', htmlentities( $msg->From), $msg->fromEmail);

                    }
                    else {
                        print $msg->fromEmail;

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
                        printf( '<div data-role="email-address" data-address=%s></div>', htmlentities( $tos[0]), json_encode( $tos[0]));
                        printf( '&nbsp;<a href="#" data-role="extra-recipients" data-target="%s">+%d more</a>', $uid, $ito-1);
                        array_shift( $tos);
                        $_tos = [];
                        foreach( $tos as $to) {
                            $_tos[] = sprintf( '<div data-role="email-address" data-address=%s></div>', htmlentities( $tos[0]), json_encode( $to));

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
                        print htmlentities( $ccs[0]);
                        printf( '&nbsp;<a href="#" data-role="extra-recipients" data-target="%s">+%d more</a>', $uid, $icc-1);
                        array_shift( $ccs);
                        $_ccs = [];
                        foreach( $ccs as $cc) {
                            $_ccs[] = htmlentities( $cc);

                        }
                        printf( '<span style="display: none;" id="%s">, %s</a>', $uid, implode( ', ', $_ccs));

                    }
                    else {
                        print htmlentities( $msg->CC);

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
        printf( '<div message><pre>%s</pre></div>', $msg->Body);

    }
    elseif ( $msg->hasMso()) {
        printf( '<div message style="max-width: 100%%">%s %s</div>',
            $msg->getMso(),
            $msg->safehtml());

    }
    else {
        printf( '<div message style="max-width: 100%%">%s</div>', $msg->safehtml());

    }

    // unset( $msg->src);
    if ( count( $msg->attachments)) {
        print '<ul>';
        foreach ( $msg->attachments as $key => $attachment) {
            if ( 'object' == gettype( $attachment)) {
                $path = sprintf('%s/file?uid=%s&folder=%s&item=%s',
                        $this->route,
                        $msg->Uid,
                        urlencode($msg->Folder),
                        urlencode($attachment->ContentId)
                    );
                printf('<li><a
                    href="%s"
                    target="_blank"
                    data-rel="attachment"
                    data-id="%s"
                    >%s</a></li>',
                    strings::url( $path),
                    $attachment->ContentId,
                    $attachment->Name
                );

            }
            else {
                printf('<li>%s</li>', $attachment);

            }

        }

        print '</ul>';

    }

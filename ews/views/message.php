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

// print \config::$PAGE_TEMPLATE;
$msg = $this->data->message;
$colStyle = 'width: 5rem; font-size: small;';
?>
<table style="width: 100%; font-family: sans-serif; border-bottom: 1px solid silver;" cellpadding="2">
    <tbody>
        <tr>
            <td>
                <span style="float: right;"><?= strings::asLocalDate( $msg->Recieved, $time = true) ?></span>
                <span style="display: none;" data-role="time"><?= $msg->Recieved ?></span>
                <strong data-role="from"><?php
                    if ( $msg->From && $msg->From != $msg->fromEmail) {
                        printf( '%s <%s>', htmlentities( $msg->From), $msg->fromEmail);

                    }
                    else {
                        print $msg->fromEmail;

                    }
                    ?></strong>

            </td>

        </tr>

        <tr>
            <td>
                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <small>to&nbsp;</small>
                    <?= $msg->To ?>

                </div>

            </td>

        </tr>

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
    else {
        printf( '<div message style="max-width: 100%%">%s</div>', $msg->safehtml());

    }

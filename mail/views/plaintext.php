<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

$msg = $this->data->message;

if ( $this->data->default_folders['Sent'] == $msg->Folder) {
    printf( "To      : %s\n", $msg->To);

}
else {
    if ( $msg->From && $msg->From != $msg->fromEmail) {
        printf( "From    : %s\n", $msg->From);

    }
    else {
        printf( "From    : %s\n", $msg->fromEmail);

    }

}

if ( $this->data->default_folders['Sent'] != $msg->Folder) {

    // $tos = explode( ',', $msg->To);
    $tos = dvc\mail\strings::splitEmails( $msg->To);
    if ( ( $ito = count( $tos)) > 1) {
        printf( "To      : %s\n", $tos[0]);
        array_shift( $tos);
        foreach( $tos as $to) {
            printf( "To      : %s\n", $to);

        }

    }
    else {
        printf( "To      : %s\n", $msg->To);

    }

}   // if ( $this->data->default_folders['Sent'] == $msg->Folder)

if ( $msg->CC) {
    $ccs = dvc\mail\strings::splitEmails( $msg->CC);
    if ( ( $icc = count( $ccs)) > 1) {
        printf( "cc   : %s\n", $ccs[0]);
        array_shift( $ccs);
        foreach( $ccs as $cc) {
            printf( "cc      : %s\n", $cc);

        }

    }
    else {
        printf( "cc      : %s\n", $msg->CC);

    }

}   // if ( $msg->CC)

$encoding = mb_detect_encoding($msg->Body);
printf( "Date    : %s\n", strings::asLocalDate( $msg->Recieved, $time = true));
if ( 'utf-8' == strtolower( $encoding)) {
    $_msg = mb_convert_encoding( $msg->Body, 'utf-8', 'ASCII');

}
else {
    printf( "Encoding: %s\n", $encoding);
    $_msg = $msg->Body;

}

printf( "Subject : %s\n", $msg->Subject);
print "-------------------------------------------------------------\n";

print $_msg;

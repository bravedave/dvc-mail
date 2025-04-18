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

use cms\strings;
use sys;

class message {

  public $attachmentIDs = [];
  public $attachments = [];
  public $answered = 'no';
  public $flagged = 'no';
  public $forwarded = 'no';
  public $fromName = '';
  public $fromEmail = '';
  public $seen = 'no';
  public $tags = '';
  public $time = '';

  public $SpamStatus = '';
  public $SpamScore = '';
  public $SpamDetail = [];

  public $BodyType = '';
  public $Body = '';
  public $Text = '';
  public $Folder = '';
  public $From = '';
  public $ReplyTo = '';
  public $ItemId = '';
  public $MessageID = '';
  public $Received = '';
  public $Subject = '';
  public $To = '';
  public $CC = '';    // imap
  public $BCC = '';    // imap
  public $Uid = '';

  public $MSGNo = '';    // imap
  public $CharSet = '';  // imap
  public $in_reply_to = '';  // imap
  public $references = '';  // imap
  public $cids = '';  // imap
  public $headers;
  public $comments = '';

  protected function getHtmlHeader() {
    $search = array(
      '@.*<head[^>]*?>@si',    // before the head element
      '@</head>.*@si',      // after head element
      '@<meta[^>]*?>@si',      // strip meta tags
    );

    return (preg_replace($search, '', $this->Body));
  }

  public function asArray() {

    return [
      'answered' => $this->answered,
      'flagged' => $this->flagged,
      'forwarded' => $this->forwarded,
      'folder' => $this->Folder,
      'from' => $this->From,
      'fromEmail' => $this->fromEmail,
      'messageid' => $this->MessageID,
      'received' => $this->Received,
      'seen' => $this->seen,
      'subject' => $this->Subject,
      'to' => $this->To,
      'uid' => $this->Uid,
    ];
  }

  public function asJson() {
    $input = (object)[
      'attachmentIDs' => $this->attachmentIDs,
      'attachments' => $this->attachments,
      'answered' => $this->answered,
      'flagged' => $this->flagged,
      'forwarded' => $this->forwarded,
      'fromName' => $this->fromName,
      'fromEmail' => $this->fromEmail,
      'seen' => $this->seen,
      'tags' => $this->tags,
      'time' => $this->time,
      'BodyType' => $this->BodyType,
      'Body' => $this->Body,
      'Folder' => $this->Folder,
      'From' => $this->From,
      'ItemId' => $this->ItemId,
      'MessageID' => $this->MessageID,
      'Received' => $this->Received,
      'Subject' => $this->Subject,
      'To' => $this->To,
      'CC' => $this->CC,
      'BCC' => $this->BCC,
      'Uid' => $this->Uid,
      'MSGNo' => $this->MSGNo,
      'CharSet' => $this->CharSet,
      'in_reply_to' => $this->in_reply_to,
      'references' => $this->references,
      'cids' => $this->cids

    ];

    $ret = json_encode($input, JSON_PRETTY_PRINT);

    if (!$ret) {
      // foreach ($input as $k => $v) {
      //   \sys::logger(sprintf('<%s => %s> %s', $k, print_r($v,true), __METHOD__));
      // }

      \sys::logger(sprintf('<%s> %s', json_last_error_msg(), __METHOD__));
    }

    return $ret;
  }

  public function fromJson($json) {
    if ($o = \json_decode($json)) {
      $this->attachmentIDs = (array)$o->attachmentIDs;
      $this->attachments = (array)$o->attachments;
      $this->answered = (string)$o->answered;
      $this->flagged = (string)$o->flagged;
      $this->forwarded = (string)$o->forwarded;
      $this->fromName = (string)$o->fromName;
      $this->fromEmail = (string)$o->fromEmail;
      $this->seen = (string)$o->seen;
      $this->tags = (string)$o->tags;
      $this->time = (string)$o->time;
      $this->BodyType = (string)$o->BodyType;
      $this->Body = (string)$o->Body;
      $this->Folder = (string)$o->Folder;
      $this->From = (string)$o->From;
      $this->ItemId = (string)$o->ItemId;
      $this->MessageID = (string)$o->MessageID;
      $this->Received = (string)$o->Received;
      $this->Subject = (string)$o->Subject;
      $this->To = (string)$o->To;
      $this->CC = (string)$o->CC;
      $this->BCC = (string)$o->BCC;
      $this->Uid = (string)$o->Uid;
      $this->MSGNo = (string)$o->MSGNo;
      $this->CharSet = (string)$o->CharSet;
      $this->in_reply_to = (string)$o->in_reply_to;
      $this->references = (string)$o->references;
      $this->cids = (string)$o->cids;
    } else {
      \sys::logger(sprintf('<%s> %s', 'unable to decode', __METHOD__));
    }
  }

  public function getMso() {
    $header = trim($this->getHtmlHeader());
    if (preg_match('@^<!--\[if \!mso\]>@', $header)) {
      if (strings::endswith($header, '<![endif]-->')) {
        return $header;
      }
    }

    return '';
  }

  public function hasBuggyMso() {
    if ($this->Body) {
      return strpos($this->Body, '"MsoNormal"') !== false;
    }

    return false;
  }

  public function hasMso() {
    $header = trim($this->getHtmlHeader());
    if (preg_match('@^<!--\[if \!mso\]>@', $header)) {
      if (strings::endswith($header, '<![endif]-->')) {
        return true;
      }
    }

    return false;
  }

  public function safehtml() {
    /**
     * to anyone who comes after me
     * anytime you attend this file increment this counter by 1
     * to represent the number of hours wasted here
     *
     * 132 hours
     */
    $debug = false;
    // $debug = true;

    if (!$this->Body) {
      $this->comments = sprintf('no html : %s %s', strlen($this->Body), __METHOD__);
      return;
    }

    if ($debug) {
      $f = sprintf('%s/temp-0-start.html', \config::dataPath());
      if (\file_exists($f)) unlink($f);
      \file_put_contents($f, $this->Body);
    }

    $decodecs = [
      '@(’|‘|' . chr(145) . '|' . chr(146) . ')@',
      '@(“|”)@',
      '@<!DOCTYPE[^>]*>@'
    ];
    $decodeca = [
      '&#39;',
      '&#34;',
      ''
    ];

    /* and possibly these as well */
    //~ UPDATE wp_posts SET post_content = REPLACE(post_content, '–', '–');
    //~ UPDATE wp_posts SET post_content = REPLACE(post_content, '—', '—');
    //~ UPDATE wp_posts SET post_content = REPLACE(post_content, '-', '-');
    //~ UPDATE wp_posts SET post_content = REPLACE(post_content, '…', '…');

    $_string = $this->Body;

    /**
     * probably expand this section ... 28/04/2021
     */
    if ($debug) \sys::logger(sprintf('<CharSet : %s> %s', $this->CharSet, __METHOD__));
    if ('ks_c_5601-1987' == $this->CharSet) {
      $_string = iconv('EUC-KR', 'utf-8', $_string);
    } elseif (in_array($this->CharSet, mb_list_encodings())) {
      if (!in_array($this->CharSet, ['UTF-8'])) {
        $_string = iconv($this->CharSet, 'utf-8', $_string);
        if ($debug) {
          \sys::logger(sprintf('<converted : %s to utf8> %s', $this->CharSet, __METHOD__));
        }
      } elseif ($debug) {
        \sys::logger(sprintf('<not converting : %s> %s', $this->CharSet, __METHOD__));
      }
    }

    $_string = preg_replace($decodecs, $decodeca, $_string);
    if ($debug) {
      $f = sprintf('%s/temp-00-start.html', \config::dataPath());
      if (\file_exists($f)) unlink($f);
      \file_put_contents($f, $_string);
    }
    $encoding = mb_detect_encoding($_string);
    if ($encoding) {
      if (!\in_array(strtolower($encoding), ['ascii', 'utf-8'])) {
        sys::logger(sprintf('%s : %s', $encoding, __METHOD__));
      }
      if (strtolower($encoding) != 'utf-8') {
        $_string = mb_convert_encoding($_string, 'utf-8', $encoding);
        if ($debug) \sys::logger(sprintf('<converted : %s to utf8> %s', $encoding, __METHOD__));
      }
      $_string = mb_convert_encoding($_string, 'html-entities', 'utf-8');
      if ($debug) \sys::logger(sprintf('<converted : utf8 to html-entities> %s', __METHOD__));
    } else {
      if ($debug) sys::logger(sprintf('no encoding on string :: %s', __METHOD__));
    }

    // sys::dump( $this);
    // die( $_string . '<br />die...');

    /**
     * rationale: some elements are destroyed by libxml, preserve them using a placeholder
     *
     * $funnyBreak : array of elements to preserve
     * [
     *  [the element, placeholder, and the final replacement]
     * ]
     */
    $funnyBreak = [
      ['&rsquo;', '&#8217;', '&rsquo;'],
      ['’', '__quote__', '&rsquo;'],
      ['&nbsp;', '__hardspace__', '&nbsp;'],
      [chr(150), '-', '-'],
      ['<![if !supportLists]>', '__supportlists__', '<![if !supportLists]>'],
      ['<![endif]>', '__endif__', '<![endif]>'],
    ];
    array_walk($funnyBreak, function ($v) use (&$_string) {
      $_string = str_replace($v[0], $v[1], $_string);
    });

    // $_string = str_replace('&rsquo;', '&#8217;', $_string);
    // // $_string = str_replace( '&rsquo;', '’', $_string);
    // $_string = str_replace('&nbsp;', '__hardspace__', $_string);
    // $_string = str_replace(chr(150), '-', $_string);
    // $_string = str_replace('<![if !supportLists]>', '__supportlists__', $_string);
    // $_string = str_replace('<![endif]>', '__endif__', $_string);
    // // $_string = str_replace( 'style="mso-fareast-language:EN-US"', '', $_string);
    // // $_string = str_replace( '<o:p>', '<div style="p">', $_string);
    // // $_string = str_replace( '</o:p>', '</div style="p">', $_string);
    // // $_string = str_replace( '<o:', '<div namespace="o" ', $_string);
    // // $_string = str_replace( '</o:', '</div namespace="o" ', $_string);

    if ($debug) {
      $f = sprintf('%s/temp-1-start.html', \config::dataPath());
      if (\file_exists($f)) unlink($f);
      \file_put_contents($f, $_string);
      // $_string = \file_get_contents( $f);

    }

    // die( $_string . '<br />die...');

    $doc = new \DOMDocument;
    // ini_set ('error_reporting', "5");
    libxml_use_internal_errors(true);
    $doc->loadHTML($_string, LIBXML_NOWARNING);
    // $doc->loadHTML( $_string, LIBXML_NOWARNING);
    libxml_clear_errors();
    // ini_set ('error_reporting', "6143");
    if ($debug) {
      $f = sprintf('%s/temp-2-just-after.html', \config::dataPath());
      if (\file_exists($f)) unlink($f);
      $doc->saveHTMLfile($f);
      // print $doc->saveHTML();
      // print '<hr />';
      // print $_string;
      // die();

    }

    $unsets = [];

    if ($debug) \sys::logger("processing ...");
    foreach ($doc->getElementsByTagName('body') as $el) {

      /** @var DOMElement $el */
      if ($el->hasAttribute('bgcolor')) {
        $bgcolor = $el->getAttribute('bgcolor');
        $el->removeAttribute('bgcolor');

        $css = 'background-color: ' . $bgcolor . ';';
        if ($el->hasAttribute('style')) {
          $css .= $el->getAttribute('style');
        }

        $el->setAttribute('style', $css);
      }
    }

    foreach ($doc->getElementsByTagName('a') as $img) {

      /** @var DOMElement $img */
      $img->setAttribute('target', '_blank');
    }

    foreach ($doc->getElementsByTagName('link') as $link) {

      /** @var DOMElement $link */
      if ($link->hasAttributes())
      {
        $href = $link->getAttribute('href');
        if ($href) {

          $link->removeAttribute('href');
          $link->setAttribute('data-safe-href', $href);
        }
      }
    }

    foreach ($doc->getElementsByTagName('a') as $anchor) {

      /** @var DOMElement $anchor */
      if ($anchor->hasAttributes()) {

        $href = $anchor->getAttribute('href');
        if ($href) {

          if (preg_match('@^mailto:@', $href)) {
            $anchor->removeAttribute('href');
            $anchor->setAttribute('data-role', 'email-link');
            $anchor->setAttribute('data-email', preg_replace('@^mailto:@', '', $href));
          }
        }
      }
    }

    /**
     * seems to be a memory bug in DOMDocument SetAttribute
     * cidContent replaces images using str_replace to avoid
     *
     * */

    $cidContent = [];

    foreach ($doc->getElementsByTagName('img') as $img) {

      /** @var DOMElement $img */
      if ($img->hasAttributes()) {

        $src = $img->getAttribute('src');
        if ($src) {
          // if ( $debug) \sys::logger( "processing .... :::" . count( $this->attachments));

          if (preg_match('@^data:image@', $src)) continue;

          $img->removeAttribute('src');
          $img->setAttribute('data-safe-src', $src);
          $img->setAttribute('src', \dvc\icon::base64_data(\dvc\icon::image));

          if ($debug) \sys::logger(sprintf('<processing attachments for inlinement> %s', __METHOD__));

          // $_attachments = [];
          foreach ($this->attachments as $key => $data) {
            if (!isset($data->Name)) continue;
            if (!isset($data->ContentId)) continue;
            $name = $data->Name;

            if (
              $src == $name ||
              $src == $data->ContentId ||
              (strpos($src, "cid:$name") !== false) ||
              (strpos($src, "cid:$data->ContentId") !== false)
            ) {

              // \sys::dump( $data);
              if ($debug) \sys::logger(sprintf('<%s == %s> <attachment> <processing> %s', $src, $name, $data->ContentId, __METHOD__));

              if (preg_match("@.gif$@i", $name)) {
                $_uid = strings::rand();
                $cidContent[] = (object)[
                  'refer' => $_uid,
                  'content' => 'data:image/gif;base64,' . base64_encode($data->Content),
                  'cache' => preg_replace('@[^a-zA-Z0-9]@', '_', implode([trim($this->MessageID, '<> '), $data->ContentId]))

                ];

                $img->setAttribute('src', $_uid);
                $img->setAttribute('data-name', $data->Name);
                $img->setAttribute('data-contentid', $data->ContentId);
                $img->removeAttribute('data-safe-src');
                if ($debug) \sys::logger(sprintf('process gif ..... %s, %s : %s', $src, $name, __METHOD__));
              } elseif (preg_match("@.jpe?g$@i", $name)) {
                $_uid = strings::rand();
                $cidContent[] = (object)[
                  'refer' => $_uid,
                  'content' => 'data:image/jpeg;base64,' . base64_encode($data->Content),
                  'cache' => preg_replace('@[^a-zA-Z0-9]@', '_', implode([trim($this->MessageID, '<> '), $data->ContentId]))

                ];

                $img->setAttribute('src', $_uid);
                $img->setAttribute('data-name', $data->Name);
                $img->setAttribute('data-contentid', $data->ContentId);
                $img->removeAttribute('data-safe-src');
                if ($debug) \sys::logger(sprintf('process jpg ..... %s, %s : %s', $src, $name, __METHOD__));
              } elseif (preg_match("@.png$@i", $name)) {
                $_uid = strings::rand();
                $cidContent[] = (object)[
                  'refer' => $_uid,
                  'content' => 'data:image/png;base64,' . base64_encode($data->Content),
                  'cache' => preg_replace('@[^a-zA-Z0-9]@', '_', implode([trim($this->MessageID, '<> '), $data->ContentId]))

                ];

                $img->setAttribute('src', $_uid);
                $img->setAttribute('data-name', $data->Name);
                $img->setAttribute('data-contentid', $data->ContentId);
                $img->removeAttribute('data-safe-src');
                if ($debug) \sys::logger(sprintf('process png ..... %s, %s : %s', $src, $name, __METHOD__));
              } else {

                $finfo = new \finfo(FILEINFO_MIME);
                $mimetype = $finfo->buffer($data->Content);
                if (preg_match("@image/gif@i", $mimetype)) {
                  $_uid = strings::rand();
                  $cidContent[] = (object)[
                    'refer' => $_uid,
                    'content' => 'data:image/gif;base64,' . base64_encode($data->Content),
                    'cache' => preg_replace('@[^a-zA-Z0-9]@', '_', implode([trim($this->MessageID, '<> '), $data->ContentId]))


                  ];

                  $img->setAttribute('src', $_uid);
                  $img->setAttribute('data-name', $data->Name);
                  $img->setAttribute('data-contentid', $data->ContentId);
                  $img->removeAttribute('data-safe-src');
                  if ($debug) \sys::logger(sprintf('processing %s ..... %s, %s : %s', $mimetype, $src, $name, __METHOD__));
                } elseif (preg_match("@image/jpe?g@i", $mimetype)) {
                  $_uid = strings::rand();
                  $cidContent[] = (object)[
                    'refer' => $_uid,
                    'content' => 'data:image/jpeg;base64,' . base64_encode($data->Content),
                    'cache' => preg_replace('@[^a-zA-Z0-9]@', '_', implode([trim($this->MessageID, '<> '), $data->ContentId]))

                  ];

                  $img->setAttribute('src', $_uid);
                  $img->setAttribute('data-name', $data->Name);
                  $img->setAttribute('data-contentid', $data->ContentId);
                  $img->removeAttribute('data-safe-src');
                  if ($debug) \sys::logger(sprintf('processing %s ..... %s, %s : %s', $mimetype, $src, $name, __METHOD__));
                } elseif (preg_match("@image/png@i", $mimetype)) {
                  $_uid = strings::rand();
                  $cidContent[] = (object)[
                    'refer' => $_uid,
                    'content' => 'data:image/png;base64,' . base64_encode($data->Content),
                    'cache' => preg_replace('@[^a-zA-Z0-9]@', '_', implode([trim($this->MessageID, '<> '), $data->ContentId]))

                  ];

                  $img->setAttribute('src', $_uid);
                  $img->setAttribute('data-name', $data->Name);
                  $img->setAttribute('data-contentid', $data->ContentId);
                  $img->removeAttribute('data-safe-src');
                  if ($debug) \sys::logger(sprintf('processing %s ..... %s, %s : %s', $mimetype, $src, $name, __METHOD__));
                } elseif ($debug) {
                  \sys::logger($mimetype);
                  \sys::logger("what about : $src, $name");
                }
              }

              $unsets[] = $key;
              if ($debug) \sys::logger(sprintf('unsetting %s : %s', $key, __METHOD__));
            } else {
              // if ( $debug) \sys::logger( sprintf('<%s:%s> <not processing attachment> %s', $name, $data->ContentId, __METHOD__));

            }
          }

          reset($this->attachments);
        }
      }
    }

    if ($debug) \sys::logger(sprintf('attachments %d : %s ', count($this->attachments), __METHOD__));
    foreach ($unsets as $u) {
      if ($debug) \sys::logger(sprintf('unset %s : %s', $u, __METHOD__));
      if (isset($this->attachments[$u])) {
        if ($debug) \sys::logger(sprintf('unset %s : %s : attachment ', $u, __METHOD__));
        unset($this->attachments[$u]);
      }

      if (isset($this->cids[$u])) {
        if ($debug) \sys::logger(sprintf('<unset cid : %s> %s', $u, __METHOD__));
        unset($this->cids[$u]);
      }
    }

    if ($debug) \sys::logger(sprintf('... attachments %d : %s ', count($this->attachments), __METHOD__));

    $frameQ = [];
    foreach ($doc->getElementsByTagName('iframe') as $frame) {
      $frameQ[] = $frame;
    }

    foreach ($frameQ as $frame) {
      $frame->parentNode->removeChild($frame);
      // \sys::logger( sprintf('<%s> %s', 'remove iframe', __METHOD__));

    }

    // $html = $doc->saveHTML();
    $tmpfile = \tempnam(\config::dataPath(), 'msg_');
    $doc->saveHTMLfile($tmpfile);
    $html = \file_get_contents($tmpfile);
    unlink($tmpfile);

    if ($debug) {
      $f = sprintf('%s/temp-2-middle-0.html', \config::dataPath());
      if (\file_exists($f)) unlink($f);
      \file_put_contents($f, $html);
    }

    foreach ($cidContent as $cid) {
      $html = \str_replace($cid->refer, $cid->content, $html);
      // $cache = implode([ \dvc\imap\config::IMAP_CACHE(), $cid->cache]);
      // \sys::logger( sprintf('<%s> %s', $cache, __METHOD__));


    }

    if ($debug) {
      $f = sprintf('%s/temp-2-middle-1.html', \config::dataPath());
      if (\file_exists($f)) unlink($f);
      \file_put_contents($f, $html);
    }

    array_walk($funnyBreak, function ($v) use (&$html) {
      $html = str_replace($v[1], $v[2], $html);
    });


    // // $html = str_replace( '<div style="p">', '<o:p>', $html);
    // // $html = str_replace( '</div style="p">', '</o:p>', $html);
    // // $html = str_replace( '<div namespace="o" ', '<o:', $html);
    // // $html = str_replace( '</div namespace="o" ', '</o:', $html);
    // // sys::logger( sprintf('%s : %s', mb_detect_encoding($html), __METHOD__));
    // $html = preg_replace(
    //   [
    //     sprintf('@%s@', chr(146)),
    //     '@__hardspace__@',
    //     '@’@',
    //     '@__supportlists__@',
    //     '@__endif__@',
    //   ],
    //   [
    //     '&rsquo;',
    //     '&nbsp;',
    //     '&rsquo;',
    //     '<![if !supportLists]>',
    //     '<![endif]>',
    //   ],
    //   $html
    // );
    // // sys::logger( sprintf('%s : %s', mb_detect_encoding($html), __METHOD__));
    // // $html = str_replace( chr(146), '&rsquo;', $html);
    // // $html = str_replace( chr(160), '&nbsp;', $html);

    if ($debug) {
      $f = sprintf('%s/temp-3-middle.html', \config::dataPath());
      if (\file_exists($f)) unlink($f);
      \file_put_contents($f, $html);
    }

    $encoding = mb_detect_encoding($html);
    if ($encoding) {
      if (!\in_array(strtolower($encoding), ['ascii', 'utf-8'])) {
        sys::logger(sprintf('%s : %s', $encoding, __METHOD__));
      }
      $html = mb_convert_encoding($html, 'html-entities', $encoding);
    } else {
      if ($debug) sys::logger(sprintf('no encoding on string :: %s', __METHOD__));
    }

    $_html = \strings::htmlSanitize($html);

    if ($debug) {
      $f = sprintf('%s/temp-4-late.html', \config::dataPath());
      if (\file_exists($f)) unlink($f);
      \file_put_contents($f, $_html);
    }

    if ($this->hasMso()) {
      // experimental empty <p></p>
      $_html = preg_replace('@<p></p>@', '', $_html);
      // sys::logger( sprintf('%s : %s', 'remove empty <p></p> tags', __METHOD__));
    }

    return $_html;
  }
}

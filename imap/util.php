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

use bravedave\dvc\logger;
use currentUser;

abstract class util {
  protected static function funnies(string $string): string {
    $s = [];
    $r = [];

    $s[] = '’';
    $r[] = '\'';

    $s[] = '…';
    $r[] = '...';

    $s[] = chr(150);
    $r[] = '-';

    return str_replace($s, $r, $string);
  }

  public static function decodeMimeStr(string $string, $charset = 'UTF-8'): string {
    $debug = false;
    // $debug = true;
    // $debug = currentUser::isDavid();

    $unsupportedEncodings = [
      'ks_c_5601-1987'
    ];

    $newString = '';
    if ($debug) logger::debug(sprintf('<%s> %s', $string, logger::caller()));
    if ($elements = imap_mime_header_decode($string)) {

      if ($debug) logger::debug(sprintf('<there is a %s element> %s', gettype($elements), logger::caller()));
      if ($debug) logger::debug(sprintf('<there are %s element/s> %s', count($elements), logger::caller()));
      for ($i = 0; $i < count($elements); $i++) {

        if ('default' == $elements[$i]->charset) $elements[$i]->charset = 'iso-8859-1';

        if ($debug) logger::debug(sprintf('<%s> %s', $elements[$i]->charset, logger::caller()));


        /**
         * Add checking to see if conversion is required
         * but:
         *  it may still rewquire work to add the //IGNORE flag, just that caused
         *  an error when converting utf-8 to utf-8, so elected to just go
         *  with checking at this stage - 3/3/2021
         *
         * possible more info:
         *  * https://stackoverflow.com/questions/26092388/iconv-detected-an-incomplete-multibyte-character-in-input-string
         *  * https://www.php.net/manual/en/function.iconv.php
         *
         * the thunderbird encodings are referenced here
         *  * https://github.com/php-mime-mail-parser/php-mime-mail-parser/issues/26
         *
         */
        if ('ks_c_5601-1987' == $elements[$i]->charset) $elements[$i]->charset = 'EUC-KR';  // thunderbird

        if ($debug) logger::debug(sprintf('<?%s:%s?> %s', $elements[$i]->charset, $charset, __METHOD__));
        if (strtolower($elements[$i]->charset) == strtolower($charset)) {

          if ($debug) logger::debug(sprintf('<no conversion> <%s:%s> %s', $elements[$i]->charset, $charset, __METHOD__));
          $newString .= $elements[$i]->text;
        } elseif (in_array($elements[$i]->charset, $unsupportedEncodings)) {

          logger::info(sprintf('<unsupported encoding> <%s:%s> %s', $elements[$i]->charset, $charset, __METHOD__));
          $newString .= $elements[$i]->text;
          // $newString .= iconv($elements[$i]->charset, $charset, $elements[$i]->text);

        } elseif ('windows-874' == $elements[$i]->charset) {

          $newString .= self::funnies(self::decodeWin874($elements[$i]->text));

          if ($debug) {
            logger::debug(sprintf(
              '<%s encoding> <%s> <%s> %s',
              $elements[$i]->charset,
              $newString,
              $charset,
              __METHOD__
            ));
          }

          $funnyText = substr($newString, 12, 1);
        } else {

          if ($debug) logger::debug(sprintf('<%s:%s> %s', $elements[$i]->charset, $charset, __METHOD__));
          try {

            $newString .= iconv($elements[$i]->charset, $charset, $elements[$i]->text);
          } catch (\Throwable $th) {

            logger::info(sprintf('<%s> %s', $th->getMessage(), __METHOD__));
            logger::info(sprintf('<%s => %s> %s', $elements[$i]->charset, $charset, __METHOD__));
            logger::dump($elements[$i]->text, __METHOD__);
          }
        }
      }
    } else {

      if (str_starts_with($string, '=?UTF-8?')) {

        $newString = iconv_mime_decode($string);
        if ($debug) logger::debug(sprintf('<converted : %s> %s', $newString, __METHOD__));
      } else {

        $newString = $string;
      }
    }

    if ($debug) logger::debug(sprintf('<exit : %s> %s', $newString, __METHOD__));
    return $newString;
  }

  public static function decodeWin874(string $string): string {
    /**
     * http://www.unicode.org/Public/MAPPINGS/VENDORS/MICSFT/WINDOWS/CP874.TXT
     */
    $map = [
      chr(0x00) =>  "\u{0000}", //	#NULL
      chr(0x01) =>  "\u{0001}", //	#START OF HEADING
      chr(0x02) =>  "\u{0002}", //	#START OF TEXT
      chr(0x03) =>  "\u{0003}", //	#END OF TEXT
      chr(0x04) =>  "\u{0004}", //	#END OF TRANSMISSION
      chr(0x05) =>  "\u{0005}", //	#ENQUIRY
      chr(0x06) =>  "\u{0006}", //	#ACKNOWLEDGE
      chr(0x07) =>  "\u{0007}", //	#BELL
      chr(0x08) =>  "\u{0008}", //	#BACKSPACE
      chr(0x09) =>  "\u{0009}", //	#HORIZONTAL TABULATION
      chr(0x0A) =>  "\u{000A}", //	#LINE FEED
      chr(0x0B) =>  "\u{000B}", //	#VERTICAL TABULATION
      chr(0x0C) =>  "\u{000C}", //	#FORM FEED
      chr(0x0D) =>  "\u{000D}", //	#CARRIAGE RETURN
      chr(0x0E) =>  "\u{000E}", //	#SHIFT OUT
      chr(0x0F) =>  "\u{000F}", //	#SHIFT IN
      chr(0x10) =>  "\u{0010}", //	#DATA LINK ESCAPE
      chr(0x11) =>  "\u{0011}", //	#DEVICE CONTROL ONE
      chr(0x12) =>  "\u{0012}", //	#DEVICE CONTROL TWO
      chr(0x13) =>  "\u{0013}", //	#DEVICE CONTROL THREE
      chr(0x14) =>  "\u{0014}", //	#DEVICE CONTROL FOUR
      chr(0x15) =>  "\u{0015}", //	#NEGATIVE ACKNOWLEDGE
      chr(0x16) =>  "\u{0016}", //	#SYNCHRONOUS IDLE
      chr(0x17) =>  "\u{0017}", //	#END OF TRANSMISSION BLOCK
      chr(0x18) =>  "\u{0018}", //	#CANCEL
      chr(0x19) =>  "\u{0019}", //	#END OF MEDIUM
      chr(0x1A) =>  "\u{001A}", //	#SUBSTITUTE
      chr(0x1B) =>  "\u{001B}", //	#ESCAPE
      chr(0x1C) =>  "\u{001C}", //	#FILE SEPARATOR
      chr(0x1D) =>  "\u{001D}", //	#GROUP SEPARATOR
      chr(0x1E) =>  "\u{001E}", //	#RECORD SEPARATOR
      chr(0x1F) =>  "\u{001F}", //	#UNIT SEPARATOR
      chr(0x20) =>  "\u{0020}", //	#SPACE
      chr(0x21) =>  "\u{0021}", //	#EXCLAMATION MARK
      chr(0x22) =>  "\u{0022}", //	#QUOTATION MARK
      chr(0x23) =>  "\u{0023}", //	#NUMBER SIGN
      chr(0x24) =>  "\u{0024}", //	#DOLLAR SIGN
      chr(0x25) =>  "\u{0025}", //	#PERCENT SIGN
      chr(0x26) =>  "\u{0026}", //	#AMPERSAND
      chr(0x27) =>  "\u{0027}", //	#APOSTROPHE
      chr(0x28) =>  "\u{0028}", //	#LEFT PARENTHESIS
      chr(0x29) =>  "\u{0029}", //	#RIGHT PARENTHESIS
      chr(0x2A) =>  "\u{002A}", //	#ASTERISK
      chr(0x2B) =>  "\u{002B}", //	#PLUS SIGN
      chr(0x2C) =>  "\u{002C}", //	#COMMA
      chr(0x2D) =>  "\u{002D}", //	#HYPHEN-MINUS
      chr(0x2E) =>  "\u{002E}", //	#FULL STOP
      chr(0x2F) =>  "\u{002F}", //	#SOLIDUS
      chr(0x30) =>  "\u{0030}", //	#DIGIT ZERO
      chr(0x31) =>  "\u{0031}", //	#DIGIT ONE
      chr(0x32) =>  "\u{0032}", //	#DIGIT TWO
      chr(0x33) =>  "\u{0033}", //	#DIGIT THREE
      chr(0x34) =>  "\u{0034}", //	#DIGIT FOUR
      chr(0x35) =>  "\u{0035}", //	#DIGIT FIVE
      chr(0x36) =>  "\u{0036}", //	#DIGIT SIX
      chr(0x37) =>  "\u{0037}", //	#DIGIT SEVEN
      chr(0x38) =>  "\u{0038}", //	#DIGIT EIGHT
      chr(0x39) =>  "\u{0039}", //	#DIGIT NINE
      chr(0x3A) =>  "\u{003A}", //	#COLON
      chr(0x3B) =>  "\u{003B}", //	#SEMICOLON
      chr(0x3C) =>  "\u{003C}", //	#LESS-THAN SIGN
      chr(0x3D) =>  "\u{003D}", //	#EQUALS SIGN
      chr(0x3E) =>  "\u{003E}", //	#GREATER-THAN SIGN
      chr(0x3F) =>  "\u{003F}", //	#QUESTION MARK
      chr(0x40) =>  "\u{0040}", //	#COMMERCIAL AT
      chr(0x41) =>  "\u{0041}", //	#LATIN CAPITAL LETTER A
      chr(0x42) =>  "\u{0042}", //	#LATIN CAPITAL LETTER B
      chr(0x43) =>  "\u{0043}", //	#LATIN CAPITAL LETTER C
      chr(0x44) =>  "\u{0044}", //	#LATIN CAPITAL LETTER D
      chr(0x45) =>  "\u{0045}", //	#LATIN CAPITAL LETTER E
      chr(0x46) =>  "\u{0046}", //	#LATIN CAPITAL LETTER F
      chr(0x47) =>  "\u{0047}", //	#LATIN CAPITAL LETTER G
      chr(0x48) =>  "\u{0048}", //	#LATIN CAPITAL LETTER H
      chr(0x49) =>  "\u{0049}", //	#LATIN CAPITAL LETTER I
      chr(0x4A) =>  "\u{004A}", //	#LATIN CAPITAL LETTER J
      chr(0x4B) =>  "\u{004B}", //	#LATIN CAPITAL LETTER K
      chr(0x4C) =>  "\u{004C}", //	#LATIN CAPITAL LETTER L
      chr(0x4D) =>  "\u{004D}", //	#LATIN CAPITAL LETTER M
      chr(0x4E) =>  "\u{004E}", //	#LATIN CAPITAL LETTER N
      chr(0x4F) =>  "\u{004F}", //	#LATIN CAPITAL LETTER O
      chr(0x50) =>  "\u{0050}", //	#LATIN CAPITAL LETTER P
      chr(0x51) =>  "\u{0051}", //	#LATIN CAPITAL LETTER Q
      chr(0x52) =>  "\u{0052}", //	#LATIN CAPITAL LETTER R
      chr(0x53) =>  "\u{0053}", //	#LATIN CAPITAL LETTER S
      chr(0x54) =>  "\u{0054}", //	#LATIN CAPITAL LETTER T
      chr(0x55) =>  "\u{0055}", //	#LATIN CAPITAL LETTER U
      chr(0x56) =>  "\u{0056}", //	#LATIN CAPITAL LETTER V
      chr(0x57) =>  "\u{0057}", //	#LATIN CAPITAL LETTER W
      chr(0x58) =>  "\u{0058}", //	#LATIN CAPITAL LETTER X
      chr(0x59) =>  "\u{0059}", //	#LATIN CAPITAL LETTER Y
      chr(0x5A) =>  "\u{005A}", //	#LATIN CAPITAL LETTER Z
      chr(0x5B) =>  "\u{005B}", //	#LEFT SQUARE BRACKET
      chr(0x5C) =>  "\u{005C}", //	#REVERSE SOLIDUS
      chr(0x5D) =>  "\u{005D}", //	#RIGHT SQUARE BRACKET
      chr(0x5E) =>  "\u{005E}", //	#CIRCUMFLEX ACCENT
      chr(0x5F) =>  "\u{005F}", //	#LOW LINE
      chr(0x60) =>  "\u{0060}", //	#GRAVE ACCENT
      chr(0x61) =>  "\u{0061}", //	#LATIN SMALL LETTER A
      chr(0x62) =>  "\u{0062}", //	#LATIN SMALL LETTER B
      chr(0x63) =>  "\u{0063}", //	#LATIN SMALL LETTER C
      chr(0x64) =>  "\u{0064}", //	#LATIN SMALL LETTER D
      chr(0x65) =>  "\u{0065}", //	#LATIN SMALL LETTER E
      chr(0x66) =>  "\u{0066}", //	#LATIN SMALL LETTER F
      chr(0x67) =>  "\u{0067}", //	#LATIN SMALL LETTER G
      chr(0x68) =>  "\u{0068}", //	#LATIN SMALL LETTER H
      chr(0x69) =>  "\u{0069}", //	#LATIN SMALL LETTER I
      chr(0x6A) =>  "\u{006A}", //	#LATIN SMALL LETTER J
      chr(0x6B) =>  "\u{006B}", //	#LATIN SMALL LETTER K
      chr(0x6C) =>  "\u{006C}", //	#LATIN SMALL LETTER L
      chr(0x6D) =>  "\u{006D}", //	#LATIN SMALL LETTER M
      chr(0x6E) =>  "\u{006E}", //	#LATIN SMALL LETTER N
      chr(0x6F) =>  "\u{006F}", //	#LATIN SMALL LETTER O
      chr(0x70) =>  "\u{0070}", //	#LATIN SMALL LETTER P
      chr(0x71) =>  "\u{0071}", //	#LATIN SMALL LETTER Q
      chr(0x72) =>  "\u{0072}", //	#LATIN SMALL LETTER R
      chr(0x73) =>  "\u{0073}", //	#LATIN SMALL LETTER S
      chr(0x74) =>  "\u{0074}", //	#LATIN SMALL LETTER T
      chr(0x75) =>  "\u{0075}", //	#LATIN SMALL LETTER U
      chr(0x76) =>  "\u{0076}", //	#LATIN SMALL LETTER V
      chr(0x77) =>  "\u{0077}", //	#LATIN SMALL LETTER W
      chr(0x78) =>  "\u{0078}", //	#LATIN SMALL LETTER X
      chr(0x79) =>  "\u{0079}", //	#LATIN SMALL LETTER Y
      chr(0x7A) =>  "\u{007A}", //	#LATIN SMALL LETTER Z
      chr(0x7B) =>  "\u{007B}", //	#LEFT CURLY BRACKET
      chr(0x7C) =>  "\u{007C}", //	#VERTICAL LINE
      chr(0x7D) =>  "\u{007D}", //	#RIGHT CURLY BRACKET
      chr(0x7E) =>  "\u{007E}", //	#TILDE
      chr(0x7F) =>  "\u{007F}", //	#DELETE
      chr(0x80) =>  "\u{20AC}", //	#EURO SIGN
      chr(0x85) =>  "\u{2026}", //	#HORIZONTAL ELLIPSIS
      chr(0x91) =>  "\u{2018}", //	#LEFT SINGLE QUOTATION MARK
      chr(0x92) =>  "\u{2019}", //	#RIGHT SINGLE QUOTATION MARK
      chr(0x93) =>  "\u{201C}", //	#LEFT DOUBLE QUOTATION MARK
      chr(0x94) =>  "\u{201D}", //	#RIGHT DOUBLE QUOTATION MARK
      chr(0x95) =>  "\u{2022}", //	#BULLET
      chr(0x96) =>  "\u{2013}", //	#EN DASH
      chr(0x97) =>  "\u{2014}", //	#EM DASH
      chr(0xA0) =>  "\u{00A0}", //	#NO-BREAK SPACE
      chr(0xA1) =>  "\u{0E01}", //	#THAI CHARACTER KO KAI
      chr(0xA2) =>  "\u{0E02}", //	#THAI CHARACTER KHO KHAI
      chr(0xA3) =>  "\u{0E03}", //	#THAI CHARACTER KHO KHUAT
      chr(0xA4) =>  "\u{0E04}", //	#THAI CHARACTER KHO KHWAI
      chr(0xA5) =>  "\u{0E05}", //	#THAI CHARACTER KHO KHON
      chr(0xA6) =>  "\u{0E06}", //	#THAI CHARACTER KHO RAKHANG
      chr(0xA7) =>  "\u{0E07}", //	#THAI CHARACTER NGO NGU
      chr(0xA8) =>  "\u{0E08}", //	#THAI CHARACTER CHO CHAN
      chr(0xA9) =>  "\u{0E09}", //	#THAI CHARACTER CHO CHING
      chr(0xAA) =>  "\u{0E0A}", //	#THAI CHARACTER CHO CHANG
      chr(0xAB) =>  "\u{0E0B}", //	#THAI CHARACTER SO SO
      chr(0xAC) =>  "\u{0E0C}", //	#THAI CHARACTER CHO CHOE
      chr(0xAD) =>  "\u{0E0D}", //	#THAI CHARACTER YO YING
      chr(0xAE) =>  "\u{0E0E}", //	#THAI CHARACTER DO CHADA
      chr(0xAF) =>  "\u{0E0F}", //	#THAI CHARACTER TO PATAK
      chr(0xB0) =>  "\u{0E10}", //	#THAI CHARACTER THO THAN
      chr(0xB1) =>  "\u{0E11}", //	#THAI CHARACTER THO NANGMONTHO
      chr(0xB2) =>  "\u{0E12}", //	#THAI CHARACTER THO PHUTHAO
      chr(0xB3) =>  "\u{0E13}", //	#THAI CHARACTER NO NEN
      chr(0xB4) =>  "\u{0E14}", //	#THAI CHARACTER DO DEK
      chr(0xB5) =>  "\u{0E15}", //	#THAI CHARACTER TO TAO
      chr(0xB6) =>  "\u{0E16}", //	#THAI CHARACTER THO THUNG
      chr(0xB7) =>  "\u{0E17}", //	#THAI CHARACTER THO THAHAN
      chr(0xB8) =>  "\u{0E18}", //	#THAI CHARACTER THO THONG
      chr(0xB9) =>  "\u{0E19}", //	#THAI CHARACTER NO NU
      chr(0xBA) =>  "\u{0E1A}", //	#THAI CHARACTER BO BAIMAI
      chr(0xBB) =>  "\u{0E1B}", //	#THAI CHARACTER PO PLA
      chr(0xBC) =>  "\u{0E1C}", //	#THAI CHARACTER PHO PHUNG
      chr(0xBD) =>  "\u{0E1D}", //	#THAI CHARACTER FO FA
      chr(0xBE) =>  "\u{0E1E}", //	#THAI CHARACTER PHO PHAN
      chr(0xBF) =>  "\u{0E1F}", //	#THAI CHARACTER FO FAN
      chr(0xC0) =>  "\u{0E20}", //	#THAI CHARACTER PHO SAMPHAO
      chr(0xC1) =>  "\u{0E21}", //	#THAI CHARACTER MO MA
      chr(0xC2) =>  "\u{0E22}", //	#THAI CHARACTER YO YAK
      chr(0xC3) =>  "\u{0E23}", //	#THAI CHARACTER RO RUA
      chr(0xC4) =>  "\u{0E24}", //	#THAI CHARACTER RU
      chr(0xC5) =>  "\u{0E25}", //	#THAI CHARACTER LO LING
      chr(0xC6) =>  "\u{0E26}", //	#THAI CHARACTER LU
      chr(0xC7) =>  "\u{0E27}", //	#THAI CHARACTER WO WAEN
      chr(0xC8) =>  "\u{0E28}", //	#THAI CHARACTER SO SALA
      chr(0xC9) =>  "\u{0E29}", //	#THAI CHARACTER SO RUSI
      chr(0xCA) =>  "\u{0E2A}", //	#THAI CHARACTER SO SUA
      chr(0xCB) =>  "\u{0E2B}", //	#THAI CHARACTER HO HIP
      chr(0xCC) =>  "\u{0E2C}", //	#THAI CHARACTER LO CHULA
      chr(0xCD) =>  "\u{0E2D}", //	#THAI CHARACTER O ANG
      chr(0xCE) =>  "\u{0E2E}", //	#THAI CHARACTER HO NOKHUK
      chr(0xCF) =>  "\u{0E2F}", //	#THAI CHARACTER PAIYANNOI
      chr(0xD0) =>  "\u{0E30}", //	#THAI CHARACTER SARA A
      chr(0xD1) =>  "\u{0E31}", //	#THAI CHARACTER MAI HAN-AKAT
      chr(0xD2) =>  "\u{0E32}", //	#THAI CHARACTER SARA AA
      chr(0xD3) =>  "\u{0E33}", //	#THAI CHARACTER SARA AM
      chr(0xD4) =>  "\u{0E34}", //	#THAI CHARACTER SARA I
      chr(0xD5) =>  "\u{0E35}", //	#THAI CHARACTER SARA II
      chr(0xD6) =>  "\u{0E36}", //	#THAI CHARACTER SARA UE
      chr(0xD7) =>  "\u{0E37}", //	#THAI CHARACTER SARA UEE
      chr(0xD8) =>  "\u{0E38}", //	#THAI CHARACTER SARA U
      chr(0xD9) =>  "\u{0E39}", //	#THAI CHARACTER SARA UU
      chr(0xDA) =>  "\u{0E3A}", //	#THAI CHARACTER PHINTHU
      chr(0xDF) =>  "\u{0E3F}", //	#THAI CURRENCY SYMBOL BAHT
      chr(0xE0) =>  "\u{0E40}", //	#THAI CHARACTER SARA E
      chr(0xE1) =>  "\u{0E41}", //	#THAI CHARACTER SARA AE
      chr(0xE2) =>  "\u{0E42}", //	#THAI CHARACTER SARA O
      chr(0xE3) =>  "\u{0E43}", //	#THAI CHARACTER SARA AI MAIMUAN
      chr(0xE4) =>  "\u{0E44}", //	#THAI CHARACTER SARA AI MAIMALAI
      chr(0xE5) =>  "\u{0E45}", //	#THAI CHARACTER LAKKHANGYAO
      chr(0xE6) =>  "\u{0E46}", //	#THAI CHARACTER MAIYAMOK
      chr(0xE7) =>  "\u{0E47}", //	#THAI CHARACTER MAITAIKHU
      chr(0xE8) =>  "\u{0E48}", //	#THAI CHARACTER MAI EK
      chr(0xE9) =>  "\u{0E49}", //	#THAI CHARACTER MAI THO
      chr(0xEA) =>  "\u{0E4A}", //	#THAI CHARACTER MAI TRI
      chr(0xEB) =>  "\u{0E4B}", //	#THAI CHARACTER MAI CHATTAWA
      chr(0xEC) =>  "\u{0E4C}", //	#THAI CHARACTER THANTHAKHAT
      chr(0xED) =>  "\u{0E4D}", //	#THAI CHARACTER NIKHAHIT
      chr(0xEE) =>  "\u{0E4E}", //	#THAI CHARACTER YAMAKKAN
      chr(0xEF) =>  "\u{0E4F}", //	#THAI CHARACTER FONGMAN
      chr(0xF0) =>  "\u{0E50}", //	#THAI DIGIT ZERO
      chr(0xF1) =>  "\u{0E51}", //	#THAI DIGIT ONE
      chr(0xF2) =>  "\u{0E52}", //	#THAI DIGIT TWO
      chr(0xF3) =>  "\u{0E53}", //	#THAI DIGIT THREE
      chr(0xF4) =>  "\u{0E54}", //	#THAI DIGIT FOUR
      chr(0xF5) =>  "\u{0E55}", //	#THAI DIGIT FIVE
      chr(0xF6) =>  "\u{0E56}", //	#THAI DIGIT SIX
      chr(0xF7) =>  "\u{0E57}", //	#THAI DIGIT SEVEN
      chr(0xF8) =>  "\u{0E58}", //	#THAI DIGIT EIGHT
      chr(0xF9) =>  "\u{0E59}", //	#THAI DIGIT NINE
      chr(0xFA) =>  "\u{0E5A}", //	#THAI CHARACTER ANGKHANKHU
      chr(0xFB) =>  "\u{0E5B}", //	#THAI CHARACTER KHOMUT
    ];

    return mb_convert_encoding(strtr($string, $map), 'UTF-8');
  }
}

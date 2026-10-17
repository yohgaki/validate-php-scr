--TEST--
validate() STRING character encodings (mb_check_encoding()) and "transfer_encoding"
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
error_reporting=-1
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

// Print pass / fail and the first error message. Inputs are written with
// "\x.." escapes and never printed, so this file has no raw 8-bit bytes.
function t($title, $input, $flags, $options = array())
{
    $spec = array(VALIDATE_STRING, $flags, $options + array('min' => 0, 'max' => 1024));
    $ctx = null;
    $copy = $input;
    $r = validate($ctx, $copy, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
    if ($ctx->getStatus()) {
        echo $title, ': pass', ($r === $input ? '' : ' (VALUE CHANGED)'), "\n";
        return;
    }
    $errs = validate_get_system_errors($ctx);
    echo $title, ': fail - ', $errs['error']['ROOT'][0]['message'], "\n";
}

function check($title, $spec)
{
    $ctx = null;
    $un = null;
    $ok = validate_spec($spec, $un, $ctx);
    $errs = validate_get_system_errors($ctx);
    echo $title, ': ', $ok ? 'valid' : 'invalid';
    foreach (array('error', 'warning') as $t) {
        foreach ($errs[$t] as $e) {
            echo ' / ', strtoupper($t), ': ', $e['message'];
        }
    }
    echo "\n";
}

$mb = VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB;

echo "**** UTF-8 (default) ****\n";
t('valid', "a\xE3\x81\x82", $mb);
t('truncated', "a\xE3\x81", $mb);
t('lone continuation byte', "a\x80", $mb);
t('overlong', "\xC0\xAF", $mb);
t('surrogate', "\xED\xA0\x80", $mb);
t('beyond U+10FFFF', "\xF4\x90\x80\x80", $mb);
t('5-byte form', "\xF8\x88\x80\x80\x80", $mb);
t('without _MB, ASCII only', "a\xE3\x81\x82", VALIDATE_STRING_ALNUM);

echo "\n**** other encodings ****\n";
// SJIS 0x835C (U+30BD): the second byte is '\' in ASCII, but chars are
// checked after the conversion to UTF-8.
t('SJIS', "\x83\x5C\x83\x41", $mb, array('encoding' => 'SJIS'));
t('Shift_JIS (MIME name)', "\x83\x5C", $mb, array('encoding' => 'Shift_JIS'));
t('SJIS truncated', "\x83", $mb, array('encoding' => 'SJIS'));
t('SJIS + disallowed ASCII', "\x83\x5C<", $mb, array('encoding' => 'SJIS'));
t('SJIS U+3000 (RFC 3454 C.1.2)', "\x81\x40", $mb, array('encoding' => 'SJIS'));
t('SJIS "unicode" option', "\x82\xA0", $mb, array('encoding' => 'SJIS', 'unicode' => array(0x3042 => false)));
t('EUC-JP', "\xA4\xA2", $mb, array('encoding' => 'EUC-JP'));
t('EUC-JP bad second byte', "\xA4\x41", $mb, array('encoding' => 'EUC-JP'));
t('ISO-2022-JP', "\x1B\$B\x24\x22\x1B(B", $mb, array('encoding' => 'ISO-2022-JP'));
t('ISO-2022-JP 8-bit bytes', "\xA4\xA2", $mb, array('encoding' => 'ISO-2022-JP'));
t('UTF-16BE', "\x30\x42\x00\x61", $mb, array('encoding' => 'UTF-16BE'));
t('UTF-16BE lone surrogate', "\xD8\x00", $mb, array('encoding' => 'UTF-16BE'));
t('UTF-16BE odd length', "\x30", $mb, array('encoding' => 'UTF-16BE'));
t('EUC-KR', "\xB0\xA1", $mb, array('encoding' => 'EUC-KR'));
t('BIG5', "\xA4\x40", $mb, array('encoding' => 'BIG5'));
t('ISO-8859-1', "caf\xE9", $mb, array('encoding' => 'ISO-8859-1'));
t('ISO-8859-1 C1 control 0x85', "a\x85", $mb, array('encoding' => 'ISO-8859-1'));
t('unknown encoding (no spec check)', "a", $mb, array('encoding' => 'FOO'));
t('transfer encoding as "encoding"', "a", $mb, array('encoding' => 'Quoted-Printable'));

echo "\n**** transfer_encoding: quoted-printable ****\n";
$qp = array('transfer_encoding' => 'quoted-printable');
t('valid', "=E3=81=82abc", $mb, $qp);
t('soft line break', str_repeat('a', 75)."=\r\nb", $mb, $qp);
t('hard line break needs CRLF flags', "a\r\nb", $mb, $qp);
t('hard line break', "a\r\nb", $mb | VALIDATE_STRING_CRLF, $qp);
t('line of 77 chars', str_repeat('a', 77), $mb, $qp);
t('bad hex', "=ZZ", $mb, $qp);
t('lowercase hex', "=e3=81=82", $mb, $qp);
t('soft line break at the end', "abc=", $mb, $qp);
t('SPACE at the end of a line', "a \r\nb", $mb | VALIDATE_STRING_SPACE | VALIDATE_STRING_CRLF, $qp);
t('SPACE before a soft line break', "a =\r\nb", $mb | VALIDATE_STRING_SPACE, $qp);
t('bare LF', "a\nb", $mb | VALIDATE_STRING_LF, $qp);
t('raw 8-bit byte', "\xE3\x81\x82", $mb, $qp);
t('decoded invalid UTF-8', "=FF", $mb, $qp);
t('decoded control char', "a=00", $mb, $qp);
t('decoded ISO-2022-JP', "=1B\$B\$\"=1B(B", $mb, $qp + array('encoding' => 'ISO-2022-JP'));
t('decoded non-ASCII without _MB', "=E3=81=82", VALIDATE_STRING_ALNUM, $qp);

echo "\n**** transfer_encoding: percent ****\n";
$pe = array('transfer_encoding' => 'percent');
t('valid', "%E3%81%82a-_.~", $mb | VALIDATE_STRING_SYMBOL, $pe);
t('+ is a space', "a+b", $mb | VALIDATE_STRING_SPACE, $pe);
t('+ is a space, SPACE not allowed', "a+b", $mb, $pe);
t('raw SPACE', "a b", $mb | VALIDATE_STRING_SPACE, $pe);
t('bad escape', "%G0", $mb, $pe);
t('decoded invalid UTF-8', "%FF%FE", $mb, $pe);
t('decoded U+202E', "%E2%80%AE", $mb, $pe);

echo "\n**** transfer_encoding: base64 / base64url ****\n";
$b64 = array('transfer_encoding' => 'base64');
t('valid', base64_encode("\xE3\x81\x82\xE3\x81\x84"), $mb, $b64);
t('no padding', rtrim(base64_encode("ab"), '='), $mb, $b64);
t('non-zero unused bits', "QR==", $mb, $b64);
t('bad char', "QQ=!", $mb, $b64);
t('decoded invalid UTF-8', base64_encode("\xFF"), $mb, $b64);
t('decoded NUL', base64_encode("a\x00"), $mb, $b64);
t('base64url', strtr(rtrim(base64_encode("\xE3\x81\xBE?"), '='), '+/', '-_'), $mb | VALIDATE_STRING_SYMBOL,
  array('transfer_encoding' => 'base64url'));
t('base64url with "+"', "ab+/", $mb, array('transfer_encoding' => 'base64url'));

echo "\n**** transfer_encoding: html ****\n";
$he = array('transfer_encoding' => 'html');
t('valid', "&lt;&#x3042;&#12354;&gt;&amp;", $mb | VALIDATE_STRING_SYMBOL, $he);
t('raw <', "<", $mb | VALIDATE_STRING_SYMBOL, $he);
t('unterminated reference', "&amp", $mb | VALIDATE_STRING_SYMBOL, $he);
t('SJIS text with references', "\x83\x5C&lt;", $mb | VALIDATE_STRING_SYMBOL, $he + array('encoding' => 'SJIS'));

echo "\n**** spec check ****\n";
$mm = array('min' => 0, 'max' => 10);
check('encoding SJIS', array(VALIDATE_STRING, $mb, $mm + array('encoding' => 'SJIS')));
check('unknown encoding', array(VALIDATE_STRING, $mb, $mm + array('encoding' => 'FOO')));
check('Quoted-Printable as encoding', array(VALIDATE_STRING, $mb, $mm + array('encoding' => 'Quoted-Printable')));
check('qprint as encoding', array(VALIDATE_STRING, $mb, $mm + array('encoding' => 'qprint')));
check('8bit as encoding', array(VALIDATE_STRING, $mb, $mm + array('encoding' => '8bit')));
check('transfer_encoding html', array(VALIDATE_STRING, $mb, $mm + array('transfer_encoding' => 'html')));
check('unknown transfer_encoding', array(VALIDATE_STRING, $mb, $mm + array('transfer_encoding' => 'qp')));
check('transfer_encoding with _BINARY', array(VALIDATE_STRING, VALIDATE_STRING_BINARY, $mm + array('transfer_encoding' => 'base64')));
check('transfer_encoding on INT', array(VALIDATE_INT, 0, $mm + array('transfer_encoding' => 'base64')));
?>
--EXPECT--
**** UTF-8 (default) ****
valid: pass
truncated: fail - VALIDATE_STRING: Invalid UTF-8 encoding.
lone continuation byte: fail - VALIDATE_STRING: Invalid UTF-8 encoding.
overlong: fail - VALIDATE_STRING: Invalid UTF-8 encoding.
surrogate: fail - VALIDATE_STRING: Invalid UTF-8 encoding.
beyond U+10FFFF: fail - VALIDATE_STRING: Invalid UTF-8 encoding.
5-byte form: fail - VALIDATE_STRING: Invalid UTF-8 encoding.
without _MB, ASCII only: fail - VALIDATE_STRING: Multibyte char detected.

**** other encodings ****
SJIS: pass
Shift_JIS (MIME name): pass
SJIS truncated: fail - VALIDATE_STRING: Invalid SJIS encoding.
SJIS + disallowed ASCII: fail - VALIDATE_STRING: Illegal char detected. ord: "60" chr: "<"
SJIS U+3000 (RFC 3454 C.1.2): fail - VALIDATE_STRING: Unicode CNTRL char detected. code point: U+3000
SJIS "unicode" option: fail - VALIDATE_STRING: Detected invalid unicode defined by $option["unicode"]. code point: U+3042
EUC-JP: pass
EUC-JP bad second byte: fail - VALIDATE_STRING: Invalid EUC-JP encoding.
ISO-2022-JP: pass
ISO-2022-JP 8-bit bytes: fail - VALIDATE_STRING: Invalid ISO-2022-JP encoding.
UTF-16BE: pass
UTF-16BE lone surrogate: fail - VALIDATE_STRING: Invalid UTF-16BE encoding.
UTF-16BE odd length: fail - VALIDATE_STRING: Invalid UTF-16BE encoding.
EUC-KR: pass
BIG5: pass
ISO-8859-1: pass
ISO-8859-1 C1 control 0x85: fail - VALIDATE_STRING: Unicode CNTRL char detected. code point: U+0085
unknown encoding (no spec check): fail - VALIDATE_STRING: Unsupported encoding "FOO".
transfer encoding as "encoding": fail - VALIDATE_STRING: Unsupported encoding "Quoted-Printable".

**** transfer_encoding: quoted-printable ****
valid: pass
soft line break: pass
hard line break needs CRLF flags: fail - VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"
hard line break: pass
line of 77 chars: fail - VALIDATE_STRING: Invalid quoted-printable encoding.
bad hex: fail - VALIDATE_STRING: Invalid quoted-printable encoding.
lowercase hex: fail - VALIDATE_STRING: Invalid quoted-printable encoding.
soft line break at the end: fail - VALIDATE_STRING: Invalid quoted-printable encoding.
SPACE at the end of a line: fail - VALIDATE_STRING: Invalid quoted-printable encoding.
SPACE before a soft line break: pass
bare LF: fail - VALIDATE_STRING: Invalid quoted-printable encoding.
raw 8-bit byte: fail - VALIDATE_STRING: Invalid quoted-printable encoding.
decoded invalid UTF-8: fail - VALIDATE_STRING: Invalid UTF-8 encoding.
decoded control char: fail - VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"
decoded ISO-2022-JP: pass
decoded non-ASCII without _MB: fail - VALIDATE_STRING: Multibyte char detected.

**** transfer_encoding: percent ****
valid: pass
+ is a space: pass
+ is a space, SPACE not allowed: fail - VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "
raw SPACE: fail - VALIDATE_STRING: Invalid percent encoding.
bad escape: fail - VALIDATE_STRING: Invalid percent encoding.
decoded invalid UTF-8: fail - VALIDATE_STRING: Invalid UTF-8 encoding.
decoded U+202E: fail - VALIDATE_STRING: Unicode CNTRL char detected. code point: U+202E

**** transfer_encoding: base64 / base64url ****
valid: pass
no padding: pass
non-zero unused bits: fail - VALIDATE_STRING: Invalid base64 encoding.
bad char: fail - VALIDATE_STRING: Invalid base64 encoding.
decoded invalid UTF-8: fail - VALIDATE_STRING: Invalid UTF-8 encoding.
decoded NUL: fail - VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"
base64url: pass
base64url with "+": fail - VALIDATE_STRING: Invalid base64url encoding.

**** transfer_encoding: html ****
valid: pass
raw <: fail - VALIDATE_STRING: Invalid html encoding.
unterminated reference: fail - VALIDATE_STRING: Invalid html encoding.
SJIS text with references: pass

**** spec check ****
encoding SJIS: valid
unknown encoding: invalid / ERROR: VALIDATE_STRING "encoding" option must be a character encoding mbstring knows (use "transfer_encoding" for quoted-printable, base64, ...).
Quoted-Printable as encoding: invalid / ERROR: VALIDATE_STRING "encoding" option must be a character encoding mbstring knows (use "transfer_encoding" for quoted-printable, base64, ...).
qprint as encoding: invalid / ERROR: VALIDATE_STRING "encoding" option must be a character encoding mbstring knows (use "transfer_encoding" for quoted-printable, base64, ...).
8bit as encoding: invalid / ERROR: VALIDATE_STRING "encoding" option must be a character encoding mbstring knows (use "transfer_encoding" for quoted-printable, base64, ...).
transfer_encoding html: valid
unknown transfer_encoding: invalid / ERROR: VALIDATE_STRING "transfer_encoding" option must be "quoted-printable", "percent", "base64", "base64url" or "html".
transfer_encoding with _BINARY: invalid / ERROR: VALIDATE_STRING has "transfer_encoding" option, but the VALIDATE_STRING_BINARY flag skips every check.
transfer_encoding on INT: valid / WARNING: VALIDATE_INT has "transfer_encoding" option that has no effect. It needs a string validator.

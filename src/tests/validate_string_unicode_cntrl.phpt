--TEST--
validate() STRING RFC 3454 prohibited unicode code points and "unicode" option map
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

// Encode a code point as UTF-8 without mbstring/intl dependency.
function cp2utf8($cp)
{
    if ($cp < 0x80) {
        return chr($cp);
    } elseif ($cp < 0x800) {
        return chr(0xC0 | ($cp >> 6)) . chr(0x80 | ($cp & 0x3F));
    } elseif ($cp < 0x10000) {
        return chr(0xE0 | ($cp >> 12)) . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
    }
    return chr(0xF0 | ($cp >> 18)) . chr(0x80 | (($cp >> 12) & 0x3F))
         . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
}

// One representative per RFC 3454 appendix C branch in validateStringUnicodeCntrl().
$prohibited = array(
    // C.1.2 non-ASCII space characters
    0x00A0, 0x1680, 0x2000, 0x2001, 0x2002, 0x2003, 0x2004, 0x2005, 0x2006,
    0x2007, 0x2008, 0x2009, 0x200A, 0x200B, 0x202F, 0x205F, 0x3000,
    // C.2.2 non-ASCII control characters
    0x0080, 0x009F, 0x06DD, 0x070F, 0x180E, 0x200C, 0x200D, 0x2028, 0x2029,
    0x2060, 0x2061, 0x2062, 0x2063, 0x206A, 0xFEFF, 0xFFF9, 0x1D173,
    // C.3 private use
    0xE000, 0xF8FF, 0xF0000, 0x100000,
    // C.4 non-character code points
    0xFDD0, 0xFFFE, 0x1FFFE, 0x2FFFE, 0x3FFFE, 0x4FFFE, 0x5FFFE, 0x6FFFE,
    0x7FFFE, 0x8FFFE, 0x9FFFE, 0xAFFFE, 0xBFFFE, 0xCFFFE, 0xDFFFE, 0xEFFFE,
    0xFFFFE, 0x10FFFE,
    // C.6 (0xFFF9-0xFFFC already match C.2.2 range; 0xFFFD is reachable)
    0xFFFD,
    // C.7 inappropriate for canonical representation
    0x2FF0,
    // C.8 change display properties / deprecated
    0x0340, 0x0341, 0x200E, 0x200F, 0x202A, 0x202B, 0x202C, 0x202D, 0x202E,
    // C.9 tagging characters
    0xE0001, 0xE0020, 0xE007F,
);

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION;
$spec = array(VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB, array('min'=>0, 'max'=>10));

echo "**** prohibited code points are rejected ****\n";
$ng = 0;
foreach ($prohibited as $cp) {
    $ctx = validate_init();
    $copy = 'a'.cp2utf8($cp).'b';
    $result = validate($ctx, $copy, $spec, $func_opts);
    if ($result === null && $ctx->getStatus() === false) {
        $ng++;
    } else {
        printf("cp U+%04X was NOT rejected\n", $cp);
    }
}
printf("rejected: %d/%d\n", $ng, count($prohibited));

echo "\n**** allowed multibyte passes ****\n";
$ctx = validate_init();
$copy = 'a'.cp2utf8(0x3042).'b'; // HIRAGANA A
var_dump(validate($ctx, $copy, $spec, $func_opts) === $copy, $ctx->getStatus());

echo "\n**** VALIDATE_STRING_RFC3454_C allows prohibited chars ****\n";
$spec_c = array(VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB | VALIDATE_STRING_RFC3454_C,
           array('min'=>0, 'max'=>10));
$ctx = validate_init();
$copy = 'a'.cp2utf8(0x00A0).'b';
var_dump(validate($ctx, $copy, $spec_c, $func_opts) === $copy, $ctx->getStatus());

echo "\n**** \"unicode\" option map overrides ****\n";
// Explicitly allowed prohibited char
$spec_u = array(VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB,
           array('min'=>0, 'max'=>10, 'unicode'=>array(0x00A0=>true)));
$ctx = validate_init();
$copy = 'a'.cp2utf8(0x00A0).'b';
var_dump(validate($ctx, $copy, $spec_u, $func_opts) === $copy, $ctx->getStatus());

// Explicitly disallowed normal char
$spec_u = array(VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB,
           array('min'=>0, 'max'=>10, 'unicode'=>array(0x3042=>false)));
$ctx = validate_init();
$copy = 'a'.cp2utf8(0x3042).'b';
var_dump(validate($ctx, $copy, $spec_u, $func_opts), $ctx->getStatus());

echo "\n**** non-UTF-8 encoding skips the unicode cntrl check ****\n";
$spec_e = array(VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB,
           array('min'=>0, 'max'=>10, 'encoding'=>'ISO-8859-1'));
$ctx = validate_init();
$copy = "caf\xE9"; // latin-1 e-acute; would be a broken byte in UTF-8
var_dump(validate($ctx, $copy, $spec_e, $func_opts) === $copy, $ctx->getStatus());

echo "\n**** UTF-8 surrogate / out-of-range sequences fail encoding check ****\n";
$ctx = validate_init();
$copy = "a\xED\xA0\x80b"; // U+D800 surrogate encoded in UTF-8
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
$ctx = validate_init();
$copy = "a\xF4\x90\x80\x80b"; // 0x110000 (> U+10FFFF)
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

?>
--EXPECT--
**** prohibited code points are rejected ****
rejected: 70/70

**** allowed multibyte passes ****
bool(true)
bool(true)

**** VALIDATE_STRING_RFC3454_C allows prohibited chars ****
bool(true)
bool(true)

**** "unicode" option map overrides ****
bool(true)
bool(true)
NULL
bool(false)

**** non-UTF-8 encoding skips the unicode cntrl check ****
bool(true)
bool(true)

**** UTF-8 surrogate / out-of-range sequences fail encoding check ****
NULL
bool(false)
NULL
bool(false)

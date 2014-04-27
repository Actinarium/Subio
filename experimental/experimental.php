<?php
/**
 * @author Actine <actine@actinarium.com>
 * Date: 25.04.14
 * Time: 0:46
 */

use Actinarium\Subio\AssSplitter;

include "../vendor/autoload.php";

$data = file_get_contents("sample.ass");

for ($i = 0; $i < 20; $i++) {
    $t = microtime(true);
    $result = AssSplitter::parse($data);
    $t2 = microtime(true);
    var_dump($t2 - $t);
}

$d = $result->fonts[0];

//file_put_contents('output.ttf', $d->getBinaryData());

$data = "12345678";

$pointer = 0;
$lenLimit = strlen($data) - 3;
$output = '';
while ($pointer <= $lenLimit) {
    $i = ord($data[$pointer++]) << 16
        | ord($data[$pointer++]) << 8
        | ord($data[$pointer++]);
    $output .= chr(($i >> 18 & 0x3F) + 0x21)
        . chr(($i >> 12 & 0x3F) + 0x21)
        . chr(($i >> 6 & 0x3F) + 0x21)
        . chr(($i & 0x3F) + 0x21);
}
if ($pointer == $lenLimit + 2) {
    // one hanging byte
    $i = ord($data[$pointer]) << 4;
    $output .= chr(($i >> 6 & 0x3F) + 0x21)
        . chr(($i & 0x3F) + 0x21);
} elseif ($pointer == $lenLimit + 1) {
    // two hanging bytes
    $i = ord($data[$pointer++]) << 10
        | ord($data[$pointer]) << 2;
    $output .= chr(($i >> 12 & 0x3F) + 0x21)
        . chr(($i >> 6 & 0x3F) + 0x21)
        . chr(($i & 0x3F) + 0x21);
}

$data = $output;

$pointer = 0;
$lenLimit = strlen($data) - 4;
$output = '';
while ($pointer <= $lenLimit) {
    $i = (ord($data[$pointer++]) - 0x21) << 18
        | (ord($data[$pointer++]) - 0x21) << 12
        | (ord($data[$pointer++]) - 0x21) << 6
        | (ord($data[$pointer++]) - 0x21);
    $output .= chr($i >> 16)
        . chr($i >> 8)
        . chr($i);
}
if ($pointer == $lenLimit + 2) {
    // two hanging chars - one output byte
    $i = (ord($data[$pointer++]) - 0x21) << 6
        | (ord($data[$pointer++]) - 0x21);
    $output .= chr($i >> 4);
} elseif ($pointer == $lenLimit + 1) {
    // three hanging chars - two output bytes
    $i = (ord($data[$pointer++]) - 0x21) << 12
        | (ord($data[$pointer++]) - 0x21) << 6
        | (ord($data[$pointer++]) - 0x21);
    $output .= chr($i >> 10)
        . chr($i >> 2);
}

//var_dump($result);

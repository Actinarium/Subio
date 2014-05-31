<?php
/**
 * @author Actine <actine@actinarium.com>
 * Date: 25.04.14
 * Time: 0:46
 */

use Actinarium\Subio\AssSplitter;
use Actinarium\Subio\AssWriter;

include "../vendor/autoload.php";

$data = file_get_contents("sample.ass");

$result = AssSplitter::parse($data);
$text = AssWriter::write($result);

file_put_contents("sample-written.ass", $text);

echo 1;

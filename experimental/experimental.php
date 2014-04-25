<?php
/**
 * @author Actine <actine@actinarium.com>
 * Date: 25.04.14
 * Time: 0:46
 */

use Actinarium\Subio\AssSplitter;

include "../vendor/autoload.php";

$data = file_get_contents("sample.ass");

$result = AssSplitter::parse($data);

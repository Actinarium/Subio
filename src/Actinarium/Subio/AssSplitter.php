<?php
/**
 * @author Actine <actine@actinarium.com>
 * Date: 25.04.14
 * Time: 0:14
 */

namespace Actinarium\Subio;


use Actinarium\Subio\Exception\UnexpectedDataException;

abstract class AssSplitter {

    public static function parse($data)
    {
        if (empty($data)) {
            throw new UnexpectedDataException("Provided data is empty");
        }

        // trim BOM from data if present:
        $data = preg_replace("@\xEF\xBB\xBF|\xFE\xFF|\xFF\xFE@", '', $data);

        // split data into lines
        $lines = preg_split("/\r\n|\n|\r/", $data);

        // initialize pointer and output object
        $linesCount = count($lines);
        $currentLine = 0;
        

        // read [Script Info] section first

    }

} 

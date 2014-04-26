<?php
/**
 * @author Actine <actine@actinarium.com>
 * Date: 25.04.14
 * Time: 0:14
 */

namespace Actinarium\Subio;


use Actinarium\Subio\DataBlock;
use Actinarium\Subio\Exception\UnexpectedDataException;

abstract class AssSplitter
{

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
        /** @var int $pointer Current line pointer */
        $pointer = 0;
        $matches = array();
        $styleFmtParsingRegex = null;
        $eventFmtParsingRegex = null;

        $output = new AssSubtitles();

        // read [Script Info] section first
        if ($lines[$pointer] !== "[Script Info]") {
            throw new UnexpectedDataException("First line of file should be [Script Info]");
        }
        // todo: push section line to linked list
        while ($lines[++$pointer][0] !== '[') {
            if ($lines[$pointer][0] === ';') {
                $output->comments[] = $lines[$pointer];
                // todo: push comment line to linked list
            } elseif (preg_match('@^([\w\d]+[\w\d\s]+?)\s*:\s*(.*)@u', $lines[$pointer], $matches)) {
                $output->properties[$matches[1]] = $matches[2];
                // todo: push property line to linked list
            } elseif (empty($lines[$pointer])) {
                // todo: push empty line to linked list
            } else {
                throw new UnexpectedDataException("Malformed line found in Script Info section");
            }
        }

        // Now parse the rest of the file (using state machine)
        $state = 0;
        $previousPointer = $pointer - 1;
        while ($pointer < $linesCount) {

            // loop prevention: if pointer hasn't moved during one iteration, this means we are stuck in a cycle
            if ($previousPointer == $pointer) {
                throw new \RuntimeException("Looped execution detected. Please raise a bug");
            }
            $previousPointer = $pointer;

            // skip empty line
            if (empty($lines[$pointer])) {
                $pointer++;
                // todo: push empty line to linked list
                continue;
            }

            // check for section line
            if ($lines[$pointer][0] === '[') {
                if ($lines[$pointer] === '[V4+ Styles]'
                    || $lines[$pointer] === '[v4+ Styles]'
                    || $lines[$pointer] === '[v4 Styles+]'
                    || $lines[$pointer] === '[v4 Styles]'
                    || $lines[$pointer] === '[Styles]'
                ) {
                    $state = 1;
                } elseif ($lines[$pointer] === '[Events]') {
                    $state = 3;
                } elseif ($lines[$pointer] === '[Fonts]') {
                    $state = 5;
                } elseif ($lines[$pointer] === '[Graphics]') {
                    $state = 6;
                } else {
                    throw new UnexpectedDataException("Unknown section header found");
                }
                // todo: push section line to linked list
                $pointer++;
            }

            // check for comment line
            while ($state < 5 && ($lines[$pointer][0] === ';' || $lines[$pointer][0] === '!')) {
                $output->comments[] = $lines[$pointer];
                // todo: push comment line to linked list
                $pointer++;
            }

            // if format string is expected, parse it
            if ($state === 1 || $state === 3) {
                $fmtParts = preg_split('@(?<=^Format)\s*:\s*|(?<!Format)\s*,\s*@u', $lines[$pointer]);
                if ($fmtParts[0] !== 'Format') {
                    throw new UnexpectedDataException("Format string expected to start with 'Format:'");
                }
                // out of captured format parameters, build a regex to parse following lines
                $formatParsingRegex = '\s*:\s*';
                // process last one separately
                for ($i = 1, $lastIndex = count($fmtParts) - 1; $i < $lastIndex; $i++) {
                    if (preg_match('@[A-Za-z]+@', $fmtParts[$i])) {
                        $formatParsingRegex .= "(?P<{$fmtParts[$i]}>[^,]*),";
                    } else {
                        throw new UnexpectedDataException("Format string parameters contain illegal characters");
                    }
                }
                if (preg_match('@[A-Za-z]+@', $fmtParts[$lastIndex])) {
                    $formatParsingRegex .= "(?P<{$fmtParts[$lastIndex]}>.*$)@u";
                } else {
                    throw new UnexpectedDataException("Format string parameters contain illegal characters");
                }

                if ($state === 1) {
                    $output->styleFormat = $lines[$pointer];
                    $styleFmtParsingRegex = '@^Style' . $formatParsingRegex;
                } else {
                    // here we should also capture event type
                    $output->eventFormat = $lines[$pointer];
                    $eventFmtParsingRegex = '@^(?P<eventType>\w+)' . $formatParsingRegex;
                }
                // todo: push format line to linked list
                $state++;
                $pointer++;

                // restart from top, in case there are comments or empty lines or a new section right after format line
                continue;
            }

            // parse styles (we already checked for empty line and comment above)
            if ($state === 2) {
                if (preg_match($styleFmtParsingRegex, $lines[$pointer], $matches)) {
                    self::filterMatchesArray($matches);
                    $output->styles[] = $matches;
                    // todo: push style line to linked list
                    $pointer++;
                    continue;
                } else {
                    throw new UnexpectedDataException("Style definition doesn't match style format");
                }
            }

            // parse events (we already checked for empty line and comment above)
            if ($state === 4) {
                if (preg_match($eventFmtParsingRegex, $lines[$pointer], $matches)) {
                    self::filterMatchesArray($matches);
                    $output->events[] = $matches;
                    // todo: push event line to linked list
                    $pointer++;
                    continue;
                } else {
                    throw new UnexpectedDataException("Event definition doesn't match event format");
                }
            }

            // parse fonts (several at once, if not split by newlines or such)
            if ($state === 5) {
                while (preg_match('@^(?:fontname)\s*:\s*(.*)$@u', $lines[$pointer], $matches)) {
                    $block = new DataBlock();
                    $block->setName($matches[1]);
                    $pointer++;
                    while (preg_match('@^[\x21-\x60]{1,80}$@', $lines[$pointer])) {
                        $block->appendEncodedData($lines[$pointer]);
                        $pointer++;
                    }
                    $output->fonts[] = $block;
                    // todo: push font block to linked list
                }
                continue;
            }

            // parse graphics (several at once, if not split by newlines or such)
            if ($state === 6) {
                while (preg_match('@^(?:filename)\s*:\s*(.*)$@u', $lines[$pointer], $matches)) {
                    $block = new DataBlock();
                    $block->setName($matches[1]);
                    $pointer++;
                    while (preg_match('@[\x21-\x60]{1,80}@', $lines[$pointer])) {
                        $block->appendEncodedData($lines[$pointer]);
                        $pointer++;
                    }
                    $output->graphics[] = $block;
                    // todo: push graphics block to linked list
                }
                continue;
            }

            // if we got there, that means we encountered illegal line
            throw new UnexpectedDataException("Illegal line encountered");
        }

        return $output;
    }

    private static function filterMatchesArray(array &$matches)
    {
        foreach ($matches as $key => &$value) {
            if (is_int($key)) {
                unset($matches[$key]);
            }
        }
    }
}

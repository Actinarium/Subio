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

        $output = new AssSubtitles();
        $matches = array();

        // detect and trim BOM bytes
        if (preg_match("@^(?:\xEF\xBB\xBF|\xFE\xFF|\xFF\xFE)+@", $data, $matches) != 0)  {
            $output->bom = $matches[0];
            $data = substr($data, strlen($matches[0]));
        }
        // detect used line separator and split
        if (preg_match("/\r\n|\n|\r/", $data, $matches)) {
            $output->newline = $matches[0];
        } else {
            $output->newline = "\n";
        }
        $lines = explode($matches[0], $data);

        // initialize pointer and output object
        $linesCount = count($lines);
        /** @var int $pointer Current line pointer */
        $pointer = 0;
        $styleFmtParsingRegex = null;
        $eventFmtParsingRegex = null;

        // read [Script Info] section first
        if ($lines[$pointer] !== "[Script Info]") {
            throw new UnexpectedDataException("First line of file should be [Script Info]");
        }
        // todo: push section line to linked list
        while ($lines[++$pointer][0] !== '[') {
            if ($lines[$pointer][0] === ';') {
                $output->comments[] = trim(substr($lines[$pointer], 1));
                // todo: push comment line to linked list
            } elseif (preg_match('@^([\w\d]+[\w\d\s]+?)\s*:\s*(.*)@u', $lines[$pointer], $matches)) {
                $output->properties[$matches[1]] = $matches[2];
                // todo: push property line to linked list
            } elseif (empty($lines[$pointer])) {
                // todo: push empty line to linked list
            } else {
                throw new UnexpectedDataException(sprintf("Malformed line found in Script Info section: line %s", $pointer + 1));
            }
        }

        // Now parse the rest of the file (using state machine)
        $state = 0;
        $previousPointer = $pointer - 1;
        while ($pointer < $linesCount) {

            // loop prevention: if pointer hasn't moved during one iteration, this means we are stuck in a cycle
            // and also it means there was malformed line that wasn't picked by any of conditionals
            if ($previousPointer == $pointer) {
                throw new UnexpectedDataException(sprintf("Malformed line encountered: line %s", $pointer + 1));
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
                    $output->stylesHeader = $lines[$pointer];
                    $state = 1;
                } elseif ($lines[$pointer] === '[Events]') {
                    $state = 3;
                } elseif ($lines[$pointer] === '[Fonts]') {
                    $state = 5;
                } elseif ($lines[$pointer] === '[Graphics]') {
                    $state = 6;
                } else {
                    throw new UnexpectedDataException(sprintf("Unknown section header found: line %s", $pointer + 1));
                }
                // todo: push section line to linked list
                $pointer++;
            }

            // check for comment line
            while ($state < 5 && ($lines[$pointer][0] === ';' || $lines[$pointer][0] === '!')) {
                $output->comments[] = trim(substr($lines[$pointer], 1));
                // todo: push comment line to linked list
                $pointer++;
            }

            // if format string is expected, parse it
            if ($state === 1 || $state === 3) {
                $fmtParts = preg_split('@(?<=^Format)\s*:\s*|(?<!Format)\s*,\s*@u', $lines[$pointer]);
                if ($fmtParts[0] !== 'Format') {
                    throw new UnexpectedDataException(sprintf("Format string expected to start with 'Format:', line %s", $pointer + 1));
                }
                // out of captured format parameters, build a regex to parse following lines
                $formatParsingRegex = '\s*:\s*';
                // process last one separately
                array_shift($fmtParts);
                for ($i = 0, $lastIndex = count($fmtParts) - 1; $i < $lastIndex; $i++) {
                    if (preg_match('@[A-Za-z]+@', $fmtParts[$i])) {
                        $formatParsingRegex .= "(?P<{$fmtParts[$i]}>[^,]*),";
                    } else {
                        throw new UnexpectedDataException(sprintf("Format string parameters contain illegal characters: line %s", $pointer + 1));
                    }
                }
                if ($state === 1) {
                    if (preg_match('@[A-Za-z]+@', $fmtParts[$lastIndex])) {
                        $formatParsingRegex .= "(?P<{$fmtParts[$lastIndex]}>[^,]*)$@u";
                    } else {
                        throw new UnexpectedDataException(sprintf("Format string parameters contain illegal characters: line %s", $pointer + 1));
                    }
                } else {
                    // last section must be Text for Event format
                    if ($fmtParts[$lastIndex] === 'Text') {
                        $formatParsingRegex .= "(?P<{$fmtParts[$lastIndex]}>.*)$@u";
                    } else {
                        throw new UnexpectedDataException(sprintf("Last section of Event format must be 'Text': line %s", $pointer + 1));
                    }
                }

                if ($state === 1) {
                    $output->styleFormat = $lines[$pointer];
                    $output->styleFormatItems = $fmtParts;
                    $styleFmtParsingRegex = '@^Style' . $formatParsingRegex;
                } else {
                    // here we should also capture event type
                    $output->eventFormat = $lines[$pointer];
                    $output->eventFormatItems = $fmtParts;
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
                    throw new UnexpectedDataException(sprintf("Style definition doesn't match style format: line %s", $pointer + 1));
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
                    throw new UnexpectedDataException(sprintf("Event definition doesn't match event format: line %s", $pointer + 1));
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
            throw new UnexpectedDataException(sprintf("Malformed line encountered: line %s", $pointer + 1));
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

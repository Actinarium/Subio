<?php
/**
 * @author Actine <actine@actinarium.com>
 * Date: 01.06.14
 * Time: 1:12
 */

namespace Actinarium\Subio;


abstract class AssWriter
{
    public static function write(AssSubtitles $ass)
    {
        // newline
        $nl = $ass->newline;

        // add BOM
        $output = $ass->bom;

        // add Script Info
        $output .= "[Script Info]$nl";
        foreach ($ass->comments as $comment) {
            $output .= "; $comment$nl";
        }
        foreach ($ass->properties as $name => $value) {
            $output .= "$name: $value$nl";
        }

        // add newline, Styles header and format
        $output .= "$nl{$ass->stylesHeader}$nl{$ass->styleFormat}$nl";
        foreach ($ass->styles as $style) {
            $tmp = array();
            foreach ($ass->styleFormatItems as $key) {
                $tmp[] = $style[$key];
            }
            $output .= "Style: " . implode(',', $tmp) . $nl;
        }

        // add newline, Events header and format
        $output .= "{$nl}[Events]$nl{$ass->eventFormat}$nl";
        foreach ($ass->events as $event) {
            $tmp = array();
            foreach ($ass->eventFormatItems as $key) {
                $tmp[] = $event[$key];
            }
            $output .= $event['eventType'] . ": " . implode(',', $tmp) . $nl;
        }

        if (!empty($ass->fonts)) {
            // add newline, Fonts header and blocks
            $output .= "{$nl}[Fonts]{$nl}";
            /** @var DataBlock $font */
            foreach ($ass->fonts as $font) {
                $output .= "fontname: {$font->getName()}$nl";
                $output .= $font->getEncodedDataFormatted($nl);
            }
        }

        if (!empty($ass->graphics)) {
            // add newline, Graphics header and blocks
            $output .= "{$nl}[Graphics]{$nl}";
            /** @var DataBlock $graphics */
            foreach ($ass->graphics as $graphics) {
                $output .= "filename: {$graphics->getName()}$nl";
                $output .= $graphics->getEncodedDataFormatted($nl);
            }
        }

        return $output;
    }
} 

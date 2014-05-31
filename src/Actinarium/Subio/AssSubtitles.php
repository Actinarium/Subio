<?php
/**
 * @author Actine <actine@actinarium.com>
 * Date: 26.04.14
 * Time: 1:36
 */

namespace Actinarium\Subio;


class AssSubtitles {

    public $comments = array();
    public $properties = array();
    public $styles = array();
    public $events = array();
    public $fonts = array();
    public $graphics = array();

    public $styleFormat;
    public $styleFormatItems;
    public $eventFormat;
    public $eventFormatItems;

    public $bom;
    public $newline;
    public $stylesHeader;
}

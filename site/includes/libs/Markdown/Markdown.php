<?php

require_once "ParseDown.php";

class Markdown {

    const FILE_LOCATION = "/../../../resources/text/";

    /**
     * @param $file
     * @return string
     */
    public function render($file)
    {
        $location = __DIR__ . self::FILE_LOCATION . $file . ".txt";
        if(!file_exists($location)) return $file . ".txt" . " does not exists";

        $file = file_get_contents($location);

        $parsedown = new Parsedown;
        $render = $parsedown->text($file);

        return $render;
    }

}
<?php

namespace League\Tactician\Bundle\Tests;

class EchoText
{
    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }
}

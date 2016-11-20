<?php

use PHPUnit\Framework\TestCase;
use tbollmeier\webappfound\routing\RouteParser;


class RouteParserTest extends TestCase
{

    function testParseSuccess()
    {
        $code = <<<CODE
(controller Home :default
    (actions 
        index []
        show []))

(controller TodoItem
    (actions 
        index []))
CODE;

        $parser = new RouteParser();

        $ast = $parser->parseString($code);

        $this->assertNotEquals(false, $ast);

        $children = $ast->getChildren();
        $this->assertEquals(2, count($children));

        echo $ast->toXml();

    }

}
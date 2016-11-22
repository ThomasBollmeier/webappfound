<?php

use PHPUnit\Framework\TestCase;
use tbollmeier\webappfound\routing\RouteParser;


class RouteParserTest extends TestCase
{

    function testParseSuccess()
    {
        $code = <<<CODE
(controller HomeController
    (actions 
        index [get / :default]
        show [get /]))

(controller TodoController
    (actions 
        index [get /my-todos]
        show [get /my-todos/:item_id(int)]
        create [post /my-todos]
        change [put /my-todos/:item_id(int)]
        destroy [delete /my-todos/:item_id(int)]))
CODE;

        $parser = new RouteParser();

        $ast = $parser->parseString($code);

        $this->assertNotEquals(false, $ast);

        $children = $ast->getChildren();
        $this->assertEquals(2, count($children));

        $element = new SimpleXMLElement($ast->toXml());
        print_r($element->asXML());

    }

}
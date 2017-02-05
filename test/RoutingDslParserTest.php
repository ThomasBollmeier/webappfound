<?php

use PHPUnit\Framework\TestCase;
use tbollmeier\webappfound\routing\RoutingDslParser;


class RouteParserTest extends TestCase
{

    function testParseSuccess()
    {
        $code = <<<CODE
;; Define route -> action mapping
        
(controller HomeController
    (actions 
        index [get / :default] ;; <-- set as default
        show [get /]))

(controller TodoController
    (actions 
        index [get /my-todos]
        show [get /my-todos/:item_id(int)]
        create [post /my-todos]
        change [put /my-todos/:item_id(int)]
        delete [delete /my-todos/:item_id(int)]))
CODE;

        $parser = new RoutingDslParser();

        $ast = $parser->parseString($code);

        if ($ast === false) {
            print($parser->error());
        }

        $this->assertNotEquals(false, $ast);

        //$children = $ast->getChildren();
        //$this->assertEquals(2, count($children));

        $element = new SimpleXMLElement($ast->toXml());
        print_r($element->asXML());

    }

}
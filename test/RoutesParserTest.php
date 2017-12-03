<?php

use PHPUnit\Framework\TestCase;
use tbollmeier\parsian\input\StringCharInput;
use tbollmeier\webappfound\routing\RoutesParser;


class RoutesParserTest extends TestCase
{
    public function testParsing()
    {
        $code = <<<CODE
% Define route -> action mapping
        
controller TodosController
    actions
        index <- get /my-todos
        create <- get /my-todos/new
        get <- get /my-todos/<todo_id:int> 
    end
end

default action TodosController#index

CODE;

        $parser = new RoutesParser();

        $lexer = $parser->getLexer();
        $tokenIn = $lexer->createTokenInput(new StringCharInput($code));

        while ($tokenIn->hasMoreTokens()) {
            $token = $tokenIn->nextToken();
            echo $token . PHP_EOL;
        }

        $ast = $parser->parseString($code);

        if ($ast !== false) {
            print($ast->toXml());
        } else {
            print($parser->error());
        }

    }
}


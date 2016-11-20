<?php
/*
   Copyright 2016 Thomas Bollmeier <entwickler@tbollmeier.de>

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

namespace tbollmeier\webappfound\routing;

use Symfony\Component\Yaml\Tests\A;
use tbollmeier\parsian as parsian;


class RouteParser
{

    public function parseFile($filePath)
    {
        return $this->parse(new parsian\FileCharInput($filePath));
    }

    public function parseString($code)
    {
        return $this->parse(new parsian\StringCharInput($code));
    }

    private function parse($charIn)
    {
        $root = new parsian\Ast("routing");

        $tokenIn = $this->createLexer()->createTokenInput($charIn);
        $parser = new parsian\Parser($tokenIn);
        $parser->openTokenInput();

        while ($ctrlAst = $this->controller($parser)) {
            $root->addChild($ctrlAst);
        }

        $parser->closeTokenInput();

        return $root;
    }

    private function controller(parsian\Parser $parser)
    {
        try {

            $ast = new parsian\Ast("controller");

            $parser->consumeExpected("PAR_OPEN", "CONTROLLER");

            $tokens = $parser->consumeExpected("ID");
            $name = new parsian\Ast("name", $tokens[0]->getContent());
            $ast->addChild($name);

            if ($parser->checkFor(":DEFAULT")) {
                $ast->addChild(new parsian\Ast("default"));
                $parser->consume();
            }

            $ast->addChild($this->actions($parser));

            $parser->consumeExpected("PAR_CLOSE");

            return $ast;

        } catch (\Exception $error) {

            return false;
        }
    }

    private function actions(parsian\Parser $parser)
    {
        $ast = new parsian\Ast("actions");

        $parser->consumeExpected("PAR_OPEN", "ACTIONS");

        while ($parser->checkFor("ID")) {
            $action = $this->action($parser);
            $ast->addChild($action);
        }

        $parser->consumeExpected("PAR_CLOSE");

        return $ast;
    }

    private function action(parsian\Parser $parser)
    {
        $ast = new parsian\Ast("action");
        $tokens = $parser->consumeExpected("ID");
        $ast->addChild(new parsian\Ast("name", $tokens[0]->getContent()));

        $parser->consumeExpected("SQB_OPEN");
        $parser->consumeExpected("SQB_CLOSE");

        return $ast;
    }

    private function createLexer()
    {
        $lexer = new parsian\Lexer();

        $lexer->addCommentType(";", "\n");

        $lexer->addSymbol("(", "PAR_OPEN");
        $lexer->addSymbol(")", "PAR_CLOSE");
        $lexer->addSymbol("[", "SQB_OPEN");
        $lexer->addSymbol("]", "SQB_CLOSE");
        $lexer->addSymbol("/", "SLASH");

        $lexer->addKeyword("controller");
        $lexer->addKeyword("actions");
        $lexer->addKeyword(":default");
        $lexer->addKeyword("get");
        $lexer->addKeyword("post");
        $lexer->addKeyword("delete");
        $lexer->addKeyword("string");
        $lexer->addKeyword("int");

        $lexer->addTerminal("/[a-zA-z_][a-zA-z_0-9]*/", "ID");

        return $lexer;
    }

}
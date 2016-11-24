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

use tbollmeier\parsian as parsian;


class RouteParser
{
    const PAR_OPEN = "PAR_OPEN";
    const PAR_CLOSE = "PAR_CLOSE";
    const SQB_OPEN = "SQB_OPEN";
    const SQB_CLOSE = "SQB_CLOSE";
    const SLASH = "SLASH";
    const COLON = "COLON";
    const CONTROLLER = "CONTROLLER";
    const ACTIONS = "ACTIONS";
    const DEFAULT = "DEFAULT";
    const GET = "GET";
    const POST = "POST";
    const PUT = "PUT";
    const DELETE = "DELETE";
    const STRING = "STRING";
    const INT = "INT";
    const ID ="ID";
    const URL_PART = "URL_PART";

    public function parseFile(string $filePath) : parsian\Ast
    {
        return $this->parse(new parsian\FileCharInput($filePath));
    }

    public function parseString(string $code) : parsian\Ast
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

            $parser->consumeExpected(self::PAR_OPEN, self::CONTROLLER);

            $tokens = $parser->consumeExpected("ID");
            $name = new parsian\Ast("name", $tokens[0]->getContent());
            $ast->addChild($name);

            $ast->addChild($this->actions($parser));

            $parser->consumeExpected(self::PAR_CLOSE);

            return $ast;

        } catch (\Exception $error) {

            return false;
        }
    }

    private function actions(parsian\Parser $parser)
    {
        $ast = new parsian\Ast("actions");

        $parser->consumeExpected(self::PAR_OPEN, self::ACTIONS);

        while ($parser->checkFor(self::ID)) {
            $action = $this->action($parser);
            $ast->addChild($action);
        }

        $parser->consumeExpected(self::PAR_CLOSE);

        return $ast;
    }

    private function action(parsian\Parser $parser)
    {
        $ast = new parsian\Ast("action");
        $tokens = $parser->consumeExpected(self::ID);
        $ast->addChild(new parsian\Ast("name", $tokens[0]->getContent()));

        $this->route($parser, $ast);

        return $ast;
    }

    private function route(parsian\Parser $parser, parsian\Ast $ast)
    {
        $parser->consumeExpected(self::SQB_OPEN);

        $tokens = $parser->consumeExpected([self::GET, self::POST, self::PUT, self::DELETE]);
        $ast->addChild(new parsian\Ast("method", $tokens[0]->getContent()));

        $this->url($parser, $ast);

        if ($parser->checkFor(self::COLON, self::DEFAULT)) {
            $parser->consumeMany(2);
            $ast->addChild(new parsian\Ast("default"));
        }

        $parser->consumeExpected(self::SQB_CLOSE);
    }

    private function url(parsian\Parser $parser, parsian\Ast $ast)
    {
        $url = new parsian\Ast("url");
        $ast->addChild($url);

        if ($parser->checkFor(self::SLASH)) {
            $url->addChild(new parsian\Ast("absolute"));
            $parser->consume();
        }
        $expected = [self::ID, self::URL_PART, self::COLON];

        while ($tokens = $parser->checkFor($expected)) {

            $token = $tokens[0];
            $ttype = $token->getType();

            if ($ttype == self::SLASH) {
                $parser->consume();
                $expected = [self::ID, self::URL_PART, self::COLON];
            } elseif ($ttype == self::ID || $ttype == self::URL_PART) {
                $parser->consume();
                $url->addChild(new parsian\Ast("url_part", $token->getContent()));
                $expected = [self::SLASH];
            } else { // Colon can start parameter or default keyword
                if ($parser->checkFor(self::DEFAULT)) {
                    break; // <-- url is parsed
                }
                $parser->consume();
                $url->addChild($this->param($parser));
                $expected = [self::SLASH];
            }

        }

    }

    private function param(parsian\Parser $parser)
    {
        $ast = new parsian\Ast("param");

        $tokens = $parser->consumeExpected([self::ID, self::URL_PART]);
        $ast->addChild(new parsian\Ast("name", $tokens[0]->getContent()));

        if ($parser->checkFor(self::PAR_OPEN)) {
            $parser->consume();
            $tokens = $parser->consumeExpected([self::STRING, self::INT], self::PAR_CLOSE);
            $ast->addChild(new parsian\Ast("type", $tokens[0]->getContent()));
        } else {
            $ast->addChild(new parsian\Ast("type", "string"));
        }

        return $ast;
    }

    private function createLexer()
    {
        $lexer = new parsian\Lexer();

        $lexer->addCommentType(";", "\n");

        $lexer->addSymbol("(", self::PAR_OPEN);
        $lexer->addSymbol(")", self::PAR_CLOSE);
        $lexer->addSymbol("[", self::SQB_OPEN);
        $lexer->addSymbol("]", self::SQB_CLOSE);
        $lexer->addSymbol("/", self::SLASH);
        $lexer->addSymbol(":", self::COLON);

        $lexer->addKeyword("controller");
        $lexer->addKeyword("actions");
        $lexer->addKeyword("default");
        $lexer->addKeyword("get");
        $lexer->addKeyword("post");
        $lexer->addKeyword("put");
        $lexer->addKeyword("delete");
        $lexer->addKeyword("string");
        $lexer->addKeyword("int");

        $lexer->addTerminal("/[a-zA-z_][a-zA-z_0-9]*/", self::ID);
        $lexer->addTerminal("#[^/]*#", self::URL_PART);

        return $lexer;
    }

}
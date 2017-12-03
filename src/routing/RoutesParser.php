<?php
/*
   Copyright 2017 Thomas Bollmeier <entwickler@tbollmeier.de>

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

use tbollmeier\parsian\grammar\Grammar;
use tbollmeier\parsian\output\Ast;


class RoutesParser extends RoutesBaseParser
{

    public function __construct()
    {
        parent::__construct();

        $this->getLexer()->enableMultipleTypesPerToken();
        $this->configGrammar($this->getGrammar());

    }

    private function configGrammar(Grammar $g)
    {
        $g->setCustomTermAst("GET", function (Ast $ast) {
            return new Ast("get");
        });

        $g->setCustomTermAst("POST", function (Ast $ast) {
            return new Ast("post");
        });

        $g->setCustomTermAst("PUT", function (Ast $ast) {
            return new Ast("put");
        });

        $g->setCustomTermAst("DELETE", function (Ast $ast) {
            return new Ast("delete");
        });

        $g->setCustomTermAst("PATH_SEGMENT", function (Ast $ast) {
            return new Ast("path_segment", $ast->getText());
        });

        $g->setCustomTermAst("INT", function (Ast $ast) {
            return new Ast("int");
        });

        $g->setCustomRuleAst("param", function (Ast $ast) {

            $ret = new Ast("param");

            $name = ($ast->getChildrenById("name")[0])->getText();
            $ret->addChild(new Ast("name", $name));

            $type = $ast->getChildrenById("ty");
            if (!empty($type)) {
                $type = $type[0];
                $ret->addChild(new Ast("type", $type->getName()));
            } else {
                $ret->addChild(new Ast("type", "string"));
            }

            return $ret;
        });


    }

}
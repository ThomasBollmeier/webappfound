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

use tbollmeier\parsian as parsian;
use tbollmeier\parsian\output\Ast;


class RoutingDslParser extends parsian\Parser
{
    public function __construct()
    {
        parent::__construct();

        $this->configLexer();
        $this->configGrammar();

        $this->setCustomRules();
    }

    private function setCustomRules()
    {
        $g = $this->getGrammar();

        $g->setCustomRuleAst("url_segment", function(Ast $ast) {
            if (!empty($ast->getChildrenById("segm_part"))) {
                $text = "";
                foreach ($ast->getChildren() as $child) {
                    $text .= $child->getText();
                }
                $res = new Ast("path_segment", $text);
            } else {
                $children = $ast->getChildren();
                $paramName = substr($children[0]->getText(), 1);
                $res = new Ast("param");
                $res->setAttr("name", $paramName);
                if (count($children) > 1) {
                    // => type info has been given
                    $res->setAttr("type", "int"); // currently only integer type supported
                }
            }

            return $res;
        });

        $g->setCustomRuleAst("url", function (Ast $ast) {
            $res = new Ast("url");
            foreach ($ast->getChildren() as $child) {
                if ($child->getName() !== "terminal") {
                    $res->addChild($child);
                }
            }

            return $res;
        });

        $g->setCustomRuleAst("method", function (Ast $ast) {

            $text = strtoupper($ast->getChildren()[0]->getText());

            return new Ast("method", $text);
        });

        $g->setCustomRuleAst("action", function (Ast $ast) {

            $res = new Ast("action");
            foreach ($ast->getChildren() as $child) {
                if ($child->getId() === "action") {
                    $res->setAttr("name", $child->getText());
                    continue;
                }
                switch ($child->getName()) {
                    case "method":
                        $res->setAttr("method", $child->getText());
                        break;
                    case "url":
                        $res->addChild($child);
                        break;
                    case "terminal":
                        if ($child->getAttr("type") == ":DEFAULT") {
                            $res->setAttr("default", "true");
                        }
                }
            }
            return $res;
        });

        $g->setCustomRuleAst("actions", function (Ast $ast) {
            $res = new Ast("actions");
            foreach ($ast->getChildren() as $child) {
                if ($child->getName() === "action") {
                    $res->addChild($child);
                }
            }

            return $res;
        });

        $g->setCustomRuleAst("controller", function (Ast $ast) {
            $res = new Ast("controller");
            foreach ($ast->getChildren() as $child) {
                if ($child->getId() === "ctrl") {
                    $res->setAttr("name", $child->getText());
                    continue;
                }
                if ($child->getName() === "actions") {
                    $res->addChild($child);
                }
            }

            return $res;
        });

    }

    private function configLexer()
    {

        $lexer = $this->getLexer();

        $lexer->addCommentType(";;", "\n");


        $lexer->addSymbol("(", "LPAREN");
        $lexer->addSymbol(")", "RPAREN");
        $lexer->addSymbol("[", "LSQBR");
        $lexer->addSymbol("]", "RSQBR");
        $lexer->addSymbol("/", "SLASH");
        $lexer->addSymbol("-", "HYPHEN");

        $lexer->addTerminal("/[_a-zA-Z][_a-zA-Z0-9]*/", "ID");
        $lexer->addTerminal("/:[a-zA-Z][_a-zA-Z0-9]*/", "URL_PARAM");

        $lexer->addKeyword("controller");
        $lexer->addKeyword("actions");
        $lexer->addKeyword(":default");
        $lexer->addKeyword("get");
        $lexer->addKeyword("post");
        $lexer->addKeyword("put");
        $lexer->addKeyword("delete");
        $lexer->addKeyword("int");

    }

    private function configGrammar()
    {

        $grammar = $this->getGrammar();

        $grammar->rule("routing_dsl",
            $grammar->oneOrMore($grammar->ruleRef("controller")),
            true);
        $grammar->rule("controller",
            $this->seq_1(),
            false);
        $grammar->rule("actions",
            $this->seq_2(),
            false);
        $grammar->rule("action",
            $this->seq_3(),
            false);
        $grammar->rule("method",
            $this->alt_2(),
            false);
        $grammar->rule("url",
            $this->alt_3(),
            false);
        $grammar->rule("url_segment",
            $this->alt_4(),
            false);
        $grammar->rule("type_info",
            $this->seq_8(),
            false);

    }

    private function alt_1()
    {
        $grammar = $this->getGrammar();

        return $grammar->alt()
            ->add($grammar->term("ID", "action"))
            ->add($grammar->ruleRef("method", "action"));
    }

    private function alt_2()
    {
        $grammar = $this->getGrammar();

        return $grammar->alt()
            ->add($grammar->term("GET"))
            ->add($grammar->term("POST"))
            ->add($grammar->term("PUT"))
            ->add($grammar->term("DELETE"));
    }

    private function alt_3()
    {
        $grammar = $this->getGrammar();

        return $grammar->alt()
            ->add($grammar->oneOrMore($this->seq_4()))
            ->add($grammar->term("SLASH"));
    }

    private function alt_4()
    {
        $grammar = $this->getGrammar();

        return $grammar->alt()
            ->add($this->seq_5())
            ->add($this->seq_7());
    }


    private function seq_1()
    {
        $grammar = $this->getGrammar();

        return $grammar->seq()
            ->add($grammar->term("LPAREN"))
            ->add($grammar->term("CONTROLLER"))
            ->add($grammar->term("ID", "ctrl"))
            ->add($grammar->ruleRef("actions"))
            ->add($grammar->term("RPAREN"));
    }

    private function seq_2()
    {
        $grammar = $this->getGrammar();

        return $grammar->seq()
            ->add($grammar->term("LPAREN"))
            ->add($grammar->term("ACTIONS"))
            ->add($grammar->oneOrMore($grammar->ruleRef("action")))
            ->add($grammar->term("RPAREN"));
    }

    private function seq_3()
    {
        $grammar = $this->getGrammar();

        return $grammar->seq()
            ->add($this->alt_1())
            ->add($grammar->term("LSQBR"))
            ->add($grammar->ruleRef("method"))
            ->add($grammar->ruleRef("url"))
            ->add($grammar->opt($grammar->term(":DEFAULT")))
            ->add($grammar->term("RSQBR"));
    }

    private function seq_4()
    {
        $grammar = $this->getGrammar();

        return $grammar->seq()
            ->add($grammar->term("SLASH"))
            ->add($grammar->ruleRef("url_segment"));
    }

    private function seq_5()
    {
        $grammar = $this->getGrammar();

        return $grammar->seq()
            ->add($grammar->term("ID", "segm_part"))
            ->add($grammar->many($this->seq_6()));
    }

    private function seq_6()
    {
        $grammar = $this->getGrammar();

        return $grammar->seq()
            ->add($grammar->term("HYPHEN"))
            ->add($grammar->term("ID", "segm_part"));
    }

    private function seq_7()
    {
        $grammar = $this->getGrammar();

        return $grammar->seq()
            ->add($grammar->term("URL_PARAM"))
            ->add($grammar->opt($grammar->ruleRef("type_info")));
    }

    private function seq_8()
    {
        $grammar = $this->getGrammar();

        return $grammar->seq()
            ->add($grammar->term("LPAREN"))
            ->add($grammar->term("INT"))
            ->add($grammar->term("RPAREN"));
    }

}

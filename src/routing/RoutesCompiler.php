<?php
/*
   Copyright 2019 Thomas Bollmeier <developer@thomas-bollmeier.de>

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

use tbollmeier\parsian\output\Ast;

class RoutesCompiler
{

    /**
     * Compile router data from routing DSL
     *
     * @param string $filePathOrCode
     * @return RouterData
     * @throws \Exception
     */
    public function compile(string $filePathOrCode) : RouterData
    {
        $parser = new RoutesParser();

        if (file_exists($filePathOrCode)) {
            $ast = $parser->parseFile($filePathOrCode);
        } else {
            $ast = $parser->parseString($filePathOrCode);
        }

        if ($ast === false) {
            throw new \Exception($parser->error());
        }

        $ret = new RouterData();
        $ret->controllers = [];
        $ret->defaultAction = new DefaultActionData();

        foreach ($ast->getChildren() as $child) {
            switch ($child->getName()) {
                case "controller":
                    $ret->controllers[] = $this->compileController($child);
                    break;
                case "default_action":
                    $ret->defaultAction = $this->compileDefaultAction($child);
                    break;
            }
        }

        return $ret;
    }

    private function compileController(Ast $controller) : ControllerData
    {
        $ret = new ControllerData();

        list($name, $actions) = $controller->getChildren();

        $ret->name = $name->getText();
        $ret->actions = $this->compileActions($actions);

        return $ret;
    }

    private function compileActions(Ast $actions) : array
    {
        return array_map(function ($action) {
            return $this->compileAction($action);
        }, $actions->getChildren());
    }

    private function compileDefaultAction(Ast $defaultAction) : DefaultActionData
    {
        $ret = new DefaultActionData();

        list($controller, $action) = $defaultAction->getChildren();
        $ret->controllerName = $controller->getText();
        $ret->actionName = $action->getText();

        return $ret;
    }

    private function compileAction(Ast $action) : ActionData
    {
        $ret = new ActionData();

        list($name, $method, $url) = $action->getChildren();

        $ret->name = $name->getText();
        $ret->httpMethod = strtoupper($method->getName());

        list($ret->pattern, $ret->paramNames) = $this->compileUrl($url);

        return $ret;
    }

    private function compileUrl(Ast $url)
    {
        $pattern = "";
        $params = [];

        foreach ($url->getChildren() as $child) {
            switch ($child->getName()) {
                case "path_segment":
                    if (!empty($pattern)) {
                        $pattern .= "\\/";
                    }
                    $pattern .= $child->getText();
                    break;
                case "param":
                    $this->compileParam($child, $pattern, $params);
                    break;
            }
        }

        return [$pattern, $params];
    }

    private function compileParam(Ast $param, &$pattern, &$params)
    {
        list($name, $type) = $param->getChildren();

        $params[] = $name->getText();

        if (!empty($pattern)) {
            $pattern .= "\\/";
        }

        if ($type->getText() == "int") {
            $pattern .= "(\\d+)";
        } else {
            $pattern .= "([^\\/]+)";
        }

    }

}

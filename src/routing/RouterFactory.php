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

class RouterFactory
{
    /**
     * Create a router instance from the routing DSL
     * @param $filePathOrCode string file path or code(string)
     * @param $baseUrl string base URL in pattern matching
     * @param $controllerNS string controller namespace
     * @return Router
     * @throws \Exception
     */
    public function createFromDSL(
        string $filePathOrCode,
        string $baseUrl = "",
        string $controllerNS = "") : Router
    {
        $compiler = new RoutesCompiler();

        $routerData = $compiler->compile($filePathOrCode);

        $router = new Router([
            "controllerNS" => $controllerNS,
            "baseUrl" => $baseUrl,
            "defaultCtrlAction" => $routerData->getDefaultAction()
        ]);

        $router->setupHandlers($routerData);

        return $router;
    }

}

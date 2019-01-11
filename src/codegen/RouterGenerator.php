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

namespace tbollmeier\webappfound\codegen;


use tbollmeier\webappfound\routing\RoutesCompiler;
use tbollmeier\webappfound\templating\TemplateFactory;

class RouterGenerator
{
    private $template;

    public function __construct()
    {
        $factory = new TemplateFactory(__DIR__);
        $this->template = $factory->createTemplate("RouterClass.template.php");
    }

    /**
     * Generate a Router from the routing DSL
     *
     * @param string $className
     * @param string $filePathOrCode
     * @param GeneratorOptions|null $genOptions
     * @return string
     * @throws \Exception
     */
    public function generateFromDSL(
        string $className,
        string $filePathOrCode,
        GeneratorOptions $genOptions = null) : string
    {
        $options = $genOptions ?: new GeneratorOptions();

        $compiler = new RoutesCompiler();
        $routerData = $compiler->compile($filePathOrCode);

        $options->defaultCtrlAction = $routerData->getDefaultAction();

        return $this->template->getContent([
            "className" => $className,
            "baseRouterAlias" => $options->baseRouterAlias,
            "namespace" => $options->namespace,
            "defaultCtrlAction" => $options->defaultCtrlAction,
            "routerData" => $routerData
        ]);
    }
}
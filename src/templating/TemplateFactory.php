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

namespace tbollmeier\webappfound\templating;

/**
 * Class TemplateFactory
 * @package tbollmeier\webappfound\templating
 *
 */
class TemplateFactory
{
    private $templateDir;

    public function __construct($templateDir = "")
    {
        $this->templateDir = $templateDir;
    }

    public function createTemplate(string $templateName) : Template
    {
        $path = "";

        if (!empty($this->templateDir)) {
            $path = $this->templateDir . DIRECTORY_SEPARATOR;
        }

        $path .= $templateName;

        return new Template($path);

    }

}
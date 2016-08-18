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

namespace tbollmeier\webappfound;


class Template
{

    const TEMPLATE_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'template';

    private $template;

    public function __construct($template)
    {
        $this->template = $template;
    }

    public function getHtml($data=[])
    {
        $path = self::TEMPLATE_DIR . DIRECTORY_SEPARATOR . $this->template;
        extract($data);

        ob_start();
        require($path);
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

}
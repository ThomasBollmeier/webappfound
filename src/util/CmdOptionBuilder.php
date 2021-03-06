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

namespace tbollmeier\webappfound\util;


class CmdOptionBuilder
{
    private $argParser;
    private $short;
    private $long;
    private $valueMode;
    
    public function __construct(ArgumentParser $argParser)
    {
        $this->argParser = $argParser;
        $this->short = "";
        $this->long = "";
        $this->valueMode = CmdOption::NO_VALUE;       
    }
    
    public function short(string $short)
    {
        $this->short = $short;
        return $this;
    }
    
    public function long(string $long)
    {
        $this->long = $long;
        return $this;
    }
    
    public function valueRequired()
    {
        $this->valueMode = CmdOption::VALUE_REQUIRED;
        return $this;
    }
    
    public function valueOptional()
    {
        $this->valueMode = CmdOption::VALUE_OPTIONAL;
        return $this;
    }
    
    public function add()
    {
        $this->argParser->addOption(
            new CmdOption($this->short, $this->long, $this->valueMode));
        
        return $this->argParser;
    }
    
}


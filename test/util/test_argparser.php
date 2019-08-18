<?php
/*
 Copyright 2019 Thomas Bollmeier <entwickler@tbollmeier.de>
 
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

require_once '../../vendor/autoload.php';

use tbollmeier\webappfound\util\ArgumentParser;


$argParser = new ArgumentParser();

$argParser->newOption()
    ->short("o")
    ->long("output")
    ->valueOptional()
    ->add();

$argParser->newOption()
    ->short("v")
    ->add();

$argParser->newOption()
    ->short("n")
    ->long("name")
    ->valueRequired()
    ->add();

list($args, $options) = $argParser->parse();

var_dump($args);

var_dump($options);

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

spl_autoload_register(function ($qualifiedName) {

    $nameParts = explode("\\", $qualifiedName);

    if ((count($nameParts) < 2 ||
        $nameParts[0] !== "tbollmeier" ||
        $nameParts[1] !== "webappfound")) {

        return;

    }

    array_shift($nameParts);
    array_shift($nameParts);

    $path = array_merge([__DIR__, "..", "src"], $nameParts);
    $path = implode(DIRECTORY_SEPARATOR, $path) . ".php";
    $path = realpath($path);

    require $path;

});
    

use tbollmeier\webappfound\util\ArgumentParser;
use tbollmeier\webappfound\codegen\GeneratorOptions;
use tbollmeier\webappfound\codegen\RouterGenerator;


function makeArgParser()
{
    $argParser = new ArgumentParser();
    
    $argParser->newOption()
        ->long("namespace")
        ->valueRequired()
        ->add();
        
    $argParser->newOption()
        ->short("n")
        ->long("name")
        ->valueRequired()
        ->add();
        
    $argParser->newOption()
        ->short("b")
        ->long("base-alias")
        ->valueRequired()
        ->add();
        
    $argParser->newOption()
        ->short("o")
        ->long("output")
        ->valueRequired()
        ->add();
        
    $argParser->newOption()
        ->long("help")
        ->add();
    
    return $argParser;
}

function getOptValue($options, $shortName, $longName, $default)
{
    if (array_key_exists($shortName, $options)) {
        return $options[$shortName];
    } elseif (array_key_exists($longName, $options)) {
        return $options[$longName];
    } else {
        return $default;
    }
}

function showHelp() 
{
    $help = <<<HELP
Syntax: <path_to_php> router_generate.php [options] <controller_config_file>

Generates a router from a controller (route) configuration file.

Available options:

    --namespace=<namespace>:
        PHP namespace of the generated router class

    -n <routerClass>, --name=<routerClass>:
        Name of generated router class

    -b <baseRouterAlias>, --base-alias=<baseRouterAlias>:
        Alias name of base router class from webappfound

    -o <file>, --output=<file>:
        Save output to file (instead of stdout)

    --help:
        show this info

HELP;
    
    print($help);
}

function generateRouter(
    $controllerConfigFile,
    $namespace, 
    $routerName, 
    $baseAlias, 
    $outputFile)
{
    $generator = new RouterGenerator();
    
    $options = new GeneratorOptions();
    $options->namespace = $namespace;
    $options->baseRouterAlias = $baseAlias;
    
    $classCode = $generator->generateFromDSL(
        $routerName, 
        $controllerConfigFile, 
        $options);
    
    if (!empty($outputFile)) {
        file_put_contents($outputFile, $classCode);
    } else {
        echo $classCode. "\n";
    }
}

// ===== main =====

list($args, $options) = makeArgParser()->parse();

if (array_key_exists("help", $options)) {
    showHelp();
    exit(0);
}

if (count($args) != 1) {
    showHelp();
    exit(1);
}

$controllerConfigFile = $args[0];

$namespace = getOptValue($options, "", "namespace", "");
$routerName = getOptValue($options, "n", "name", "Router");
$baseAlias = getOptValue($options, "b", "base-alias", "");
$outputFile = getOptValue($options, "o", "output", "");

generateRouter($controllerConfigFile, $namespace, 
    $routerName, $baseAlias, $outputFile);

<?php

namespace tbollmeier\webappfound\codegen;

use PHPUnit\Framework\TestCase;
require_once 'TestController.php';

class RouterGeneratorTest extends TestCase
{
    private $generator;

    protected function setUp()
    {
        $this->generator = new RouterGenerator();
    }

    protected function tearDown()
    {
        $this->generator = null;
    }

    public function testGenerateFromDSL()
    {
        $code = <<<ROUTING_DSL
% A simple controller

controller TestController
    actions 
        index <- get /my-todos
        show <- get /my-todos/<id:int>
        new <- get /my-todos/new
        create <- post /my-todos
        edit <- get /my-todos/<id:int>/edit
        update <- put /my-todos/<id:int>
        delete <- delete /my-todos/<id:int>
    end
end

default action TestController#page404
ROUTING_DSL;

        $options = new GeneratorOptions();

        $className = "TestRouter";

        $classCode = $this->generator->generateFromDSL(
            $className,
            $code,
            $options
        );

        //echo $classCode;

        $filePath = __DIR__. DIRECTORY_SEPARATOR . $className . ".php";
        file_put_contents($filePath, $classCode);
        require_once $filePath;

        $class = new \ReflectionClass($className);
        $router = $class->newInstance();

        $router->route("GET", "my-todos");
        $this->assertEquals("index", \TestController::$callInfo->action);

        $router->route("POST", "my-todos");
        $this->assertEquals("create", \TestController::$callInfo->action);

        $router->route("GET", "my-todos/42/edit");
        $this->assertEquals("edit", \TestController::$callInfo->action);
        $this->assertEquals("42", \TestController::$callInfo->urlParams["id"]);

        $router->route("GET", "nonexisting/todos");
        $this->assertEquals("page404", \TestController::$callInfo->action);

        $router->route("DELETE", "my-todos/4711");
        $this->assertEquals("delete", \TestController::$callInfo->action);
        $this->assertEquals("4711", \TestController::$callInfo->urlParams["id"]);

        $router->route("DELETE", "my-todos/abc");
        $this->assertEquals("page404", \TestController::$callInfo->action);

    }

}

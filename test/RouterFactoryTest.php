<?php

namespace tbollmeier\webappfound\routing;

use PHPUnit\Framework\TestCase;
require_once 'TestController.php';

class RouterFactoryTest extends TestCase
{
    private $factory;

    protected function setUp()
    {
        $this->factory = new RouterFactory();
    }

    protected function tearDown()
    {
        $this->factory = null;
    }

    function testCreateFromDSL()
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

default action TestController#index
ROUTING_DSL;

        $router = $this->factory->createFromDSL($code);

        $router->route("GET", "my-todos");
        $this->assertEquals("index", \TestController::$callInfo->action);

        $router->route("POST", "my-todos");
        $this->assertEquals("create", \TestController::$callInfo->action);

        $router->route("GET", "my-todos/42/edit");
        $this->assertEquals("edit", \TestController::$callInfo->action);
        $this->assertEquals("42", \TestController::$callInfo->urlParams["id"]);

        $router->route("GET", "nonexisting/todos");
        $this->assertEquals("index", \TestController::$callInfo->action);

        $router->route("DELETE", "my-todos/4711");
        $this->assertEquals("delete", \TestController::$callInfo->action);
        $this->assertEquals("4711", \TestController::$callInfo->urlParams["id"]);

        $router->route("DELETE", "my-todos/abc");
        $this->assertEquals("index", \TestController::$callInfo->action);

    }

}

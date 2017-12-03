<?php

use PHPUnit\Framework\TestCase;
use tbollmeier\webappfound\routing\Router;

require_once 'TestController.php';


class RouterTest extends TestCase
{

    function testRegisterActionsFromDSL()
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
        destroy <- delete /my-todos/<id:int>
    end
end

default action TestController#index

ROUTING_DSL;

        $router = new Router([]);

        $router->registerActionsFromDSL($code);

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
        $this->assertEquals("destroy", \TestController::$callInfo->action);
        $this->assertEquals("4711", \TestController::$callInfo->urlParams["id"]);

        $router->route("DELETE", "my-todos/abc");
        $this->assertEquals("index", \TestController::$callInfo->action);

    }

}
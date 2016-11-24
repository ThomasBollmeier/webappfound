<?php

use PHPUnit\Framework\TestCase;
use tbollmeier\webappfound\routing\Router;

require_once 'TestController.php';


class RouterTest extends TestCase
{

    function testRegisterActionsFromDSL()
    {
        $code = <<<ROUTING_DSL
;; A simple controller

(controller TestController
    (actions 
        index [get /todos :default]
        show [get /todos/:id(int)]
        new [get /todos/new]
        create [post /todos]
        edit [get /todos/:id(int)/edit]
        update [put /todos/:id(int)]
        destroy [delete /todos/:id(int)]))

ROUTING_DSL;

        $router = new Router([]);

        $router->registerActionsFromDSL($code);

        $router->route("GET", "todos");
        $this->assertEquals("index", \TestController::$callInfo->action);

        $router->route("POST", "todos");
        $this->assertEquals("create", \TestController::$callInfo->action);

        $router->route("GET", "todos/42/edit");
        $this->assertEquals("edit", \TestController::$callInfo->action);
        $this->assertEquals("42", \TestController::$callInfo->urlParams["id"]);

        $router->route("GET", "nonexisting/todos");
        $this->assertEquals("index", \TestController::$callInfo->action);

    }

}
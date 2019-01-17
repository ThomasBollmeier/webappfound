<?php

use tbollmeier\webappfound\http\Request;
use tbollmeier\webappfound\http\Response;

class TestController
{
    public static $callInfo = null;

    private function handle(string $action,
                            Request $req,
                            Response $res)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = $action;
        self::$callInfo->urlParams = $req->getUrlParams();
        //$res->send();
    }

    public function index(Request $req, Response $res)
    {
        $this->handle('index', $req, $res);
    }

    public function page404(Request $req, Response $res)
    {
        $this->handle('page404', $req, $res);
    }

    public function show(Request $req, Response $res)
    {
        $this->handle('show', $req, $res);
    }

    public function showItem(Request $req, Response $res)
    {
        $this->handle('showItem', $req, $res);
    }

    public function new(Request $req, Response $res)
    {
        $this->handle('new', $req, $res);
    }

    public function create(Request $req, Response $res)
    {
        $this->handle('create', $req, $res);
    }

    public function edit(Request $req, Response $res)
    {
        $this->handle('edit', $req, $res);
    }

    public function update(Request $req, Response $res)
    {
        $this->handle('update', $req, $res);
    }

    public function destroy(Request $req, Response $res)
    {
        $this->handle('destroy', $req, $res);
    }

    public function delete(Request $req, Response $res)
    {
        $this->handle('delete', $req, $res);
    }

}
<?php

use tbollmeier\webappfound\http\Request;

class TestController
{
    public static $callInfo = null;

    public function index(Request $req)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'index';
        self::$callInfo->urlParams = $req->getUrlParams();
    }

    public function page404(Request $req)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'page404';
        self::$callInfo->urlParams = $req->getUrlParams();
    }

    public function show(Request $req)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'show';
        self::$callInfo->urlParams = $req->getUrlParams();
    }

    public function showItem(Request $req)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'showItem';
        self::$callInfo->urlParams = $req->getUrlParams();
    }

    public function new(Request $req)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'new';
        self::$callInfo->urlParams = $req->getUrlParams();
    }

    public function create(Request $req)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'create';
        self::$callInfo->urlParams = $req->getUrlParams();
    }

    public function edit(Request $req)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'edit';
        self::$callInfo->urlParams = $req->getUrlParams();
    }

    public function update(Request $req)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'update';
        self::$callInfo->urlParams = $req->getUrlParams();
    }

    public function destroy(Request $req)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'destroy';
        self::$callInfo->urlParams = $req->getUrlParams();
    }

    public function delete(Request $req)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'delete';
        self::$callInfo->urlParams = $req->getUrlParams();
    }

}
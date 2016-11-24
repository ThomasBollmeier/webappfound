<?php

class TestController
{
    public static $callInfo = null;

    public function index($urlParams)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'index';
        self::$callInfo->urlParams = $urlParams;
    }

    public function show($urlParams)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'show';
        self::$callInfo->urlParams = $urlParams;
    }

    public function new($urlParams)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'new';
        self::$callInfo->urlParams = $urlParams;
    }

    public function create($urlParams)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'create';
        self::$callInfo->urlParams = $urlParams;
    }

    public function edit($urlParams)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'edit';
        self::$callInfo->urlParams = $urlParams;
    }

    public function update($urlParams)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'update';
        self::$callInfo->urlParams = $urlParams;
    }

    public function destroy($urlParams)
    {
        self::$callInfo = new stdClass();
        self::$callInfo->action = 'destroy';
        self::$callInfo->urlParams = $urlParams;
    }

}
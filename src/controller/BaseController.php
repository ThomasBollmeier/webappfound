<?php
/*
   Copyright 2018 Thomas Bollmeier <developer@tbollmeier.org>

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
namespace tbollmeier\webappfound\controller;


abstract class BaseController
{
    private $body = false;
    private $headers = false;
    private $getAllHeadersAvail = false;

    protected function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    protected function getRequestHeader(string $key)
    {
        if ($this->headers === false) {
            $this->readHeaders();
        }

        if (!$this->getAllHeadersAvail) {
            $key = str_replace("-", "_", $key);
            $key = strtoupper($key);
        }

        return array_key_exists($key, $this->headers) ?
            $this->headers[$key] :
            null;
    }

    protected function getRequestBody()
    {
        if ($this->body === false) {
            $this->body = file_get_contents("php://input");
        }

        return $this->body;
    }

    private function readHeaders()
    {
        if (function_exists("getallheaders")) {
            $this->headers = getallheaders();
            $this->getAllHeadersAvail = true;
        } else {
            $this->headers = [];
            foreach ($_SERVER as $key => $value) {
                if (preg_match("/^HTTP_(.*)/", $key, $matches)) {
                    $this->headers[$matches[1]] = $value;
                }
            }
        }
    }

}
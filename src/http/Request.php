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

namespace tbollmeier\webappfound\http;


class Request
{
    private $method;
    private $url;
    private $urlParams;
    private $queryParams;
    private $body;
    private $headers;
    private $getAllHeadersAvail;

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getUrlParams()
    {
        return $this->urlParams;
    }

    /**
     * @return mixed
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getRequestHeader(string $key)
    {

        if (!$this->getAllHeadersAvail) {
            $key = str_replace("-", "_", $key);
            $key = strtoupper($key);
        }

        return array_key_exists($key, $this->headers) ?
            $this->headers[$key] :
            null;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    public static function create(
        string $method,
        string $url,
        array $urlParams,
        array $queryParams) : Request
    {
        list($headers, $getAllHeadersAvail) = self::readHeaders();
        $body = file_get_contents("php://input");

        return new Request(
            $method,
            $url,
            $urlParams,
            $queryParams,
            $headers,
            $getAllHeadersAvail,
            $body);
    }

    private function __construct(
        string $method,
        string $url,
        array $urlParams,
        array $queryParams,
        array $headers,
        bool $getAllHeadersAvail,
        string $body)
    {
        $this->method = $method;
        $this->url = $url;
        $this->urlParams = $urlParams;
        $this->queryParams = $queryParams;
        $this->headers = $headers;
        $this->getAllHeadersAvail = $getAllHeadersAvail;
        $this->body = $body;
    }

    private static function readHeaders()
    {
        $getAllHeadersAvail = false;

        if (function_exists("getallheaders")) {
            $headers = getallheaders();
            $getAllHeadersAvail = true;
        } else {
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (preg_match("/^HTTP_(.*)/", $key, $matches)) {
                    $headers[$matches[1]] = $value;
                }
            }
        }

        return [$headers, $getAllHeadersAvail];
    }

}
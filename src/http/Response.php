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


class Response
{
    private $responseCode;
    private $body;
    private $headers;

    public function __construct()
    {
        $this->responseCode = 200;
        $this->headers = [];
        $this->body = "";
    }

    /**
     * @param int $responseCode
     * @return Response
     */
    public function setResponseCode(int $responseCode)
    {
        $this->responseCode = $responseCode;
        return $this;
    }

    /**
     * @param string $body
     * @return Response
     */
    public function setBody(string $body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setHeader(string $key, string $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function send()
    {
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        http_response_code($this->responseCode);

        echo $this->body;
    }


}
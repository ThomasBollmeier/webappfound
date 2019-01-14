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

namespace tbollmeier\webappfound\auth;


class JsonWebToken
{
    public static function encode(array $payload, string $secretKey) : string
    {
        $header = [
            "alg" => "HS256",
            "typ" => "JWT"
        ];
        $header64 = base64_encode(json_encode($header));
        $payload64 = base64_encode(json_encode($payload));

        $signature = self::calcSignature(
            $header64,
            $payload64,
            $secretKey);
        $signature64 = base64_encode($signature);

        return "$header64.$payload64.$signature64";
    }

    public static function decode(string $token, string $secretKey)
    {
        list ($header64, $payload64, $signature64) = explode(".", $token);

        $signature = base64_decode($signature64);

        $expectedSignature = self::calcSignature(
            $header64,
            $payload64,
            $secretKey);

        if ($signature === $expectedSignature) {
            $payload = base64_decode($payload64);
            return json_decode($payload, true);
        } else {
            return null;
        }

    }

    private static function calcSignature($header64, $payload64, $secretKey) : string
    {
        return hash_hmac(
            "sha256",
            $header64 . "." . $payload64,
            $secretKey);
    }

}
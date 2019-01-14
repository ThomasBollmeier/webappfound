<?php

namespace tbollmeier\webappfound\auth;

use PHPUnit\Framework\TestCase;
require_once 'TestController.php';

class JsonWebTokenTest extends TestCase
{
    public function testEncodeDecode()
    {
        $data = [
            "username" => "drbolle",
            "email" => "developer@thomas-bollmeier.de"
        ];
        $secret = "geheim";

        $token = JsonWebToken::encode($data, $secret);

        $decoded = JsonWebToken::decode($token, $secret);
        $this->assertEquals($data, $decoded);

        $decodedError = JsonWebToken::decode($token, "nicht geheim");
        $this->assertNull($decodedError);

    }

}

<?php
/*
 Copyright 2020 Thomas Bollmeier <developer@thomas-bollmeier.de>

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

namespace tbollmeier\webappfound\db;


class SqlType
{
    const BOOL = 1;
    const INT = 2;
    const FLOAT = 3;
    const VARCHAR = 4;
    const DATE = 5;
    const TIME = 6;
    const DATETIME = 7;

    private $typeCode;

    protected function __construct(int $typeCode)
    {
        $this->typeCode = $typeCode;
    }

    public function getTypeCode() : int
    {
        return $this->typeCode;
    }

    public static function makeBool() : SqlType
    {
        return new SqlType(SqlType::BOOL);
    }

    public static function makeInt()
    {
        return new SqlType(SqlType::INT);
    }

    public static function makeFloat(int $digits, int $decimals)
    {
        return new SqlTypeFloat($digits, $decimals);
    }

    public static function makeVarChar(int $length = 0)
    {
        return new SqlTypeVarChar($length);
    }

    public static function makeDate()
    {
        return new SqlType(SqlType::DATE);
    }

    public static function makeTime()
    {
        return new SqlType(SqlType::TIME);
    }

    public static function makeDateTime()
    {
        return new SqlType(SqlType::DATETIME);
    }

}

class SqlTypeFloat extends SqlType
{
    private $digits;
    private $decimals;

    public function __construct(int $digits, int $decimals)
    {
        parent::__construct(SqlType::FLOAT);

        $this->digits = $digits;
        $this->decimals = $decimals;
    }

    /**
     * @return int
     */
    public function getDigits(): int
    {
        return $this->digits;
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return $this->decimals;
    }

}

class SqlTypeVarChar extends SqlType
{
    private $length;

    public function __construct(int $length)
    {
        parent::__construct(SqlType::VARCHAR);

        $this->length = $length;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }
}


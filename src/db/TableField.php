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


class TableField
{
    private $name;

    private $sqlType;
    private $nullable;
    private $autoIncrement;

    public function __construct(string $name,
                                SqlType $sqlType,
                                bool $nullable = true,
                                bool $autoIncrement = false)
    {
        $this->name = $name;
        $this->sqlType = $sqlType;
        $this->nullable = $nullable;
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return SqlType
     */
    public function getSqlType(): SqlType
    {
        return $this->sqlType;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * @return bool
     */
    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }
}
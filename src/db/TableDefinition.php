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


class TableDefinition
{
    private $name;
    private $keyFields;
    private $dataFields;

    public function __construct($name)
    {
        $this->name = $name;
        $this->keyFields = [];
        $this->dataFields = [];
    }

    public function addKeyField(TableField $field)
    {
        $this->keyFields[] = $field;
    }

    public function addDataField(TableField $field)
    {
        $this->dataFields[] = $field;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getKeyFields(): array
    {
        return $this->keyFields;
    }

    /**
     * @return array
     */
    public function getDataFields(): array
    {
        return $this->dataFields;
    }

}
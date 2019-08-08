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

namespace tbollmeier\webappfound\db;


class ActiveRecordMeta
{
    private $tableName;
    private $fields;
    private $assocs;
    private $sqlBuilder;
    
    public function __construct(SqlBuilder $sqlBuilder = null)
    {
        $this->tableName = "";
        $this->sqlBuilder = $sqlBuilder ?? new SqlBuilder();
        $this->fields = [];
        $this->assocs = [];
    }
    
    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }
    
    public function setTableName(string $tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @return multitype:
     */
    public function getFields()
    {
        return $this->fields;
    }
    
    public function addField(FieldMetaInfo $field) 
    {
        $this->fields[] = $field;
    }

    /**
     * @return multitype:
     */
    public function getAssocs()
    {
        return $this->assocs;
    }

    /**
     * @return \tbollmeier\webappfound\db\SqlBuilder
     */
    public function getSqlBuilder()
    {
        return $this->sqlBuilder;
    }

    
    
    
}


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


abstract class EntityDefinition
{
    private $tableName;
    private $fields;
    private $assocs;
    private $sqlBuilder;
    
    public function __construct($tableName, SqlBuilder $sqlBuilder = null)
    {
        $this->tableName = $tableName;
        $this->fields = [];
        $this->assocs = [];
        $this->sqlBuilder = $sqlBuilder ?? new SqlBuilder();
    } 
    
    public function getTableName()
    {
        return $this->tableName;
    }
    
    public function newField($name)
    {
        return new FieldDefinition($this, $name);
    }
    
    function addField(FieldDefinition $field)
    {
        $this->fields[$field->getName()] = $field;
        return $this;
    }
    
    public function newAssociation($targetClass)
    {
        return new AssociationDefinition($this, $targetClass);
    }
    
    function addAssociation(AssociationDefinition $assoc)
    {
        $this->assocs[$assoc->getName()] = $assoc;
        return $this;
    }
    
}


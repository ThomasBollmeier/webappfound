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
    
    public function getSqlBuilder()
    {
        return $this->sqlBuilder;
    }
    
    public function isField($name) 
    {
        return array_key_exists($name, $this->fields);
    }
    
    public function getField($name)
    {
        return $this->fields[$name];
    }
    
    public function isAssociation($name)
    {
        return array_key_exists($name, $this->assocs);
    }
    
    public function getAssociation($name)
    {
        return $this->assocs[$name];
    }
    
    public function getAssociations()
    {
        return array_values($this->assocs);
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
    
    public function query($options = [])
    {
        if ($options instanceof QueryOptions) {
            $options = $options->toArray();
        }
        
        $params = $options['params'] ?? [];
        $sql = $this->sqlBuilder->createSelectCommand(
            $this->tableName,
            $options);
        
        return $this->queryCustom($sql, $params);
    }
    
    public function queryCustom($sql, $params = [])
    {
        $objects = [];
        
        $stmt = Connector::getDbConnection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        while ($row) {
            $obj = $this->createEntity($row['id']);
            $obj->setRowData($row);
            $objects[] = $obj;
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        $stmt->closeCursor();
        
        return $objects;
    }
    
    public function createEntity($id=Entity::INDEX_NOT_IN_DB)
    {
        return new Entity($this, $id);
    }
    
}


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


class Entity
{
    const INDEX_NOT_IN_DB = -1;
    
    private $entityDef;
    private $id;
    private $state;
    
    public function __construct(EntityDefinition $entityDef, $id=self::INDEX_NOT_IN_DB)
    {
        $this->entityDef = $entityDef;
        $this->id = intval($id);
        
        $this->state = new \stdClass();
        $this->state->row = [];
        $this->state->assocs = [];
        $this->state->loaded = false;
        $this->state->assocsLoaded = false;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function isInDb()
    {
        if ($this->id != self::INDEX_NOT_IN_DB) {
            
            $builder = $this->entityDef->getSqlBuilder();
            
            $sql = $builder->createSelectCommand(
                $this->entityDef->getTableName(),
                [
                    'fields' => ["id"],
                    'filter' => "id = :id"
                ]);
            
            $stmt = Connector::getDbConnection()->prepare($sql);
            $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
            
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            return $result !== false;
            
        } else {
            return false;
        }
    }
    
    public function setRowData($row)
    {
        $this->state->row = $row;
        if ($this->id != self::INDEX_NOT_IN_DB) {
            $this->state->loaded = true;
        }
    }
    
    public function __get($name)
    {
        if (!$this->state->loaded) {
            $this->load();
        }
        
        if ($this->entityDef->isField($name)) {
            
            // It's a field
            $field = $this->entityDef->getField($name);
            $dbName = $field->getDbAlias();
            $dbValue = $this->state->row[$dbName] ?? null;
            if ($dbValue !== null) {
                $convFromDb = $field->getConvFromDb();
                if ($convFromDb === null) {
                    return $dbValue;
                } else {
                    return $convFromDb($dbValue);
                }
            } else {
                return null;
            }
            
            
        } elseif ($this->entityDef->isAssociation($name)) {
            
            if (!$this->state->assocsLoaded) {
                $this->loadAssociations();
            }
            
            // It's an association
            return $this->state->assocs[$name];
            
        } else {
            
            return null;
            
        }
        
    }
    
    public function __set($name, $value)
    {
        if ($this->entityDef->isField($name)) {
            
            if (!$this->_state->loaded) {
                $this->load();
            }
            
            // It's a property
            $field = $this->entityDef->getField($name);
            $dbName = $field->getDbAlias();
            $convToDb = $field->getConvToDb();
            $this->state->row[$dbName] = $convToDb === null ?
                $value : $convToDb($value);
            
        } elseif ($this->entityDef->isAssociation($name)) {
            
            $assoc = $this->entityDef->getAssociation($name);
            if ($assoc->isReadOnly()) {
                return;
            }
            
            if (!$this->state->assocsLoaded) {
                $this->loadAssociations();
            }
            
            $this->state->assocs[$name] = $value;
            
        }
    }
    
    private function load()
    {
        $this->state->loaded = true;
        
        if ($this->id == self::INDEX_NOT_IN_DB) {
            return;
        }
        
        $sql = $this->entityDef->getSqlBuilder()->createSelectCommand(
            $this->entityDef->getTableName(),
            ['filter' => 'id = :id']);
        $stmt = Connector::getDbConnection()->prepare($sql);
        $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $this->setRowData($row);
        }
        
        $stmt->closeCursor();
        
        $this->loadAssociations();
        
    }
    
    private function loadAssociations()
    {
        if ($this->state->assocsLoaded) {
            return; // nothing to do
        }
        
        if ($this->isInDb()) {
            $associations = $this->entityDef->getAssociations();
            foreach ($associations as $association) {
                $this->loadAssociation($association);
            }
        }
        
        $this->state->assocsLoaded = true;
    }
    
    private function loadAssociation(AssociationDefinition $association)
    {
        $this->state->assocs[$association->getName()] = $this->readAssocObjects($association);
    }
    
    private function readAssocObjects(AssociationDefinition $association)
    {
        // TODO
    }
    
}


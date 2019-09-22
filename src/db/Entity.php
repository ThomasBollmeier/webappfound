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
        
        $this->initState();
    }
    
    private function initState()
    {
        $this->state = new \stdClass();
        $this->state->row = [];
        $this->state->assocs = [];
        $this->state->loaded = false;
        $this->state->assocsLoaded = false;
        
        foreach ($this->entityDef->getFields() as $field) {
            $this->state->row[$field->getDbAlias()] = $field->getInitialDbValue();
        }
        
        foreach ($this->entityDef->getAssociations() as $association) {
            $this->state->assocs[$association->getName()] = [];
        }
        
    }

    public function getId()
    {
        return $this->id;
    }
    
    public function isInDb()
    {
        if ($this->id != self::INDEX_NOT_IN_DB) {
            
            $sql = $this->sqlBuilder()->createSelectCommand(
                $this->entityDef->getTableName(),
                [
                    'fields' => ["id"],
                    'filter' => "id = :id"
                ]);
            
            $stmt = $this->dbConn()->prepare($sql);
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
            
            return $this->state->assocs[$name];
            
        } else {
            
            return null;
            
        }
        
    }
    
    public function __set($name, $value)
    {
        if ($this->entityDef->isField($name)) {
            
            if (!$this->state->loaded) {
                $this->load();
            }
            
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
 
    public function isAssociated(string $assocName, Entity $object)
    {
        if (!$this->state->assocsLoaded) {
            $this->loadAssociations();
        }

        $assocObjects = $this->state->assocs[$assocName];
        
        foreach($assocObjects as $assocObject) {
            if ($assocObject->getId() === $object->getId()) {
                return true;
            }
        }
     
        return false;
    }
 
    public function associate(string $assocName, Entity $object)
    {
        if (!$this->state->assocsLoaded) {
            $this->loadAssociations();
        }

        $assocObjects = $this->state->assocs[$assocName];
        $assocObjects[] = $object;
        $this->state->assocs[$assocName] = $assocObjects;
    }

    public function dissociate(string $assocName, Entity $object)
    {
        if (!$this->state->assocsLoaded) {
            $this->loadAssociations();
        }

        $newAssocObjects = array_filter($this->state->assocs[$assocName], function ($obj) use ($object) {
            return $obj->getId() != $object->getId();
        }); 
        $this->state->assocs[$assocName] = $newAssocObjects;
    }

    public function save()
    {
        $isNew = !$this->isInDb();
        
        $sql = $isNew ?
        $this->createPreparedInsert() :
        $this->createPreparedUpdate();
        
        $stmt = $this->dbConn()->prepare($sql);
        
        $names = [];
        $types = [];
        $numCols = 0;
        foreach ($this->entityDef->getFields() as $field) {
            $names[] = $field->getDbAlias();
            $types[] = $field->getPdoType();
            $numCols++;
        }
        
        for ($col = 0; $col < $numCols; $col++) {
            $name = $names[$col];
            $stmt->bindParam(
                ':' . $name,
                $this->state->row[$name],
                $types[$col]);
        }
        if ($this->id != self::INDEX_NOT_IN_DB) {
            $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        }
        
        if (!$stmt->execute()) {
            throw new \PDOException($stmt->errorInfo()[2]);
        }
        
        if ($isNew) {
            $this->id = $this->dbConn()->lastInsertId();
        }
        
        $this->saveAssociations();
    }
    
    public function delete()
    {
        if (!$this->isInDb()) {
            return;
        }
        
        $sql = $this->sqlBuilder()->createDeleteCommand(
            $this->entityDef->getTableName());
        
        $stmt = $this->dbConn()->prepare($sql);
        $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        $stmt->execute();

        $this->state->row = [];
        
        $this->deleteAssociations();
        
        $this->id = self::INDEX_NOT_IN_DB;
    }
    
    private function dbConn()
    {
        return Environment::getInstance()->dbConn;
    }

    private function sqlBuilder()
    {
        return Environment::getInstance()->sqlBuilder;
    }
    
    private function load()
    {
        $this->state->loaded = true;
        
        if ($this->id == self::INDEX_NOT_IN_DB) {
            return;
        }
        
        $sql =$this->sqlBuilder()->createSelectCommand(
            $this->entityDef->getTableName(),
            ['filter' => 'id = :id']);
        $stmt = $this->dbConn()->prepare($sql);
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
        $linkTable = $association->getLinkTable();
        $sourceId = $association->getSourceIdField();
        $targetId = $association->getTargetIdField();
        $targetEntityDef = $association->getTargetEntityDef();
        
        $sql = $this->sqlBuilder()->createSelectCommand(
            $linkTable,
            [
                'fields' => [$targetId],
                'filter' => $sourceId . ' = :id'
            ]);
        $stmt = $this->dbConn()->prepare($sql);
        $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $objects = [];
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        while ($row) {
            $objects[] = $targetEntityDef->createEntity($row[$targetId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        $stmt->closeCursor();
        
        return $objects;
        
    }

    private function saveAssociations()
    {
        $associations = $this->entityDef->getAssociations();
        
        foreach ($associations as $association) {
            $this->saveAssociation($association);
        }   
    }
    
    private function saveAssociation(AssociationDefinition $association)
    {
        if ($association->isReadonly()) {
            // Read-only associations must not be saved
            return;
        }
        
        $linkTable = $association->getLinkTable();
        $sourceId = $association->getSourceIdField();
        $targetId = $association->getTargetIdField();
        $targetEntityDef = $association->getTargetEntityDef();
        $isComposition = $association->isComposition();
        $onDeleteCallback = $association->getOnDeleteCallback();
        
        $existingObjects = $this->readAssocObjects($association);
        $existingIds = [];
        foreach ($existingObjects as $obj) {
            $existingIds[$obj->getId()] = true;
        }
        
        $assocObjs = $this->state->assocs[$association->getName()];
        
        foreach ($assocObjs as $obj) {
            $objId = $obj->getId();
            if (array_key_exists($objId, $existingIds)) {
                // Delete orphaned links:
                if (!$obj->isInDb()) {
                    $this->deleteLink($linkTable, $sourceId, $targetId, $objId);
                }
                unset($existingIds[$objId]);
            } else {
                
                // New association object...
                // If the associated objects does not exist yet it must be
                // saved now:
                if (!$obj->isInDb()) {
                    $obj->save();
                }
                
                // Insert new link
                $sql = $this->sqlBuilder()->createInsertCommand(
                    $linkTable,
                    [$sourceId, $targetId]);
                
                $stmt = $this->dbConn()->prepare($sql);
                $objId = $obj->getId();
                $stmt->bindParam(':'.$sourceId, $this->id, \PDO::PARAM_INT);
                $stmt->bindParam(':'.$targetId, $objId, \PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        
        // Delete unused links:
        foreach (array_keys($existingIds) as $objId) {
            $this->deleteLink($linkTable, $sourceId, $targetId, $objId);
            if ($isComposition) {
                $obj = $targetEntityDef->createEntity($objId);
                $obj->delete();
            } elseif ($onDeleteCallback !== null) {
                $obj = $targetEntityDef->createEntity($objId);
                call_user_func($onDeleteCallback, $obj);
            }
        }
        
    }
    
    private function deleteLink($linkTable,
        $sourceId,
        $targetId,
        $objId)
    {
        $sql = $this->sqlBuilder()->createDeleteCommand(
            $linkTable,
            "$sourceId = :source_id AND $targetId = :target_id");
        
        $stmt = $this->dbConn()->prepare($sql);
        $stmt->bindParam(':source_id', $this->id, \PDO::PARAM_INT);
        $stmt->bindParam(':target_id', $objId, \PDO::PARAM_INT);
        $stmt->execute();
    }
    
    private function deleteAssociations()
    {
        foreach ($this->entityDef->getAssociations() as $association) {
            $this->state->assocs[$association->getName()] = [];
            $this->saveAssociation($association);
        }
    }
        
    private function createPreparedInsert()
    {
        $names = [];
        foreach ($this->entityDef->getFields() as $field) {
            $names[] = $field->getDbAlias();
        }
        
        return $this->sqlBuilder()->createInsertCommand(
            $this->entityDef->getTableName(), $names);
    }
    
    private function createPreparedUpdate()
    {
        $names = [];
        foreach ($this->entityDef->getFields() as $field) {
            $names[] = $field->getDbAlias();
        }
        
        return $this->sqlBuilder()->createUpdateCommand(
            $this->entityDef->getTableName(), $names);
    }
    
}


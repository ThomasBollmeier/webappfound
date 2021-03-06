<?php
/*
   Copyright 2016-2019 Thomas Bollmeier <developer@thomas-bollmeier.de>

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


abstract class ActiveRecord
{
    const INDEX_NOT_IN_DB = -1;
    // Association properties:
    const ASSOC_TARGET_CLASS = "targetClass";
    const ASSOC_IS_COMPOSITION = "isComposition";
    const ASSOC_LINK_TABLE = "linkTable";
    const ASSOC_SOURCE_ID_FIELD = "sourceIdField";
    const ASSOC_TARGET_ID_FIELD = "targetIdField";
    const ASSOC_ON_DELETE_CALLBACK = "onDeleteCallback";
    const ASSOC_READ_ONLY = "readonly";
   
    protected static $dbConn;
    protected $id;
    protected $_meta;
    protected $_state;

    public static function setDbConnection(\PDO $dbConn)
    {
        self::$dbConn = $dbConn;
    }

    public static function query($options = [])
    {
        if ($options instanceof QueryOptions) {
            $options = $options->toArray();
        }
        
        $params = $options['params'] ?? [];
        $model = new static();
        $sql = $model->_meta->sqlBuilder->createSelectCommand(
            $model->_meta->tableName,
            $options);

        return self::queryCustom($sql, $params);
    }

    public static function queryCustom($sql, $params = [])
    {
        $objects = [];

        $stmt = self::$dbConn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        while ($row) {
            $obj = new static($row['id']);
            $obj->setRowData($row);
            $objects[] = $obj;
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        $stmt->closeCursor();

        return $objects;
    }

    public function __construct(int $id = self::INDEX_NOT_IN_DB,
                                $sqlBuilder = null)
    {
        $this->id = intval($id);
        $this->_meta = new \stdClass();
        $this->_meta->tableName = '';
        $this->_meta->fields = [];
        $this->_meta->assocs = [];
        $this->_meta->sqlBuilder = $sqlBuilder ?? new SqlBuilder();

        $this->_state = new \stdClass();
        $this->_state->row = [];
        $this->_state->assocs = [];
        $this->_state->loaded = false;
        $this->_state->assocsLoaded = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isInDb()
    {
        if ($this->id != self::INDEX_NOT_IN_DB) {

            $builder = $this->_meta->sqlBuilder;

            $sql = $builder->createSelectCommand(
                $this->_meta->tableName,
                [
                    'fields' => ["id"],
                    'filter' => "id = :id"
                ]
            );
            $stmt = self::$dbConn->prepare($sql);
            $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);

            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $result !== false;

        } else {
            return false;
        }
    }

    protected function defineTable($tableName)
    {
        $this->_meta->tableName = $tableName;
    }

    protected function defineField($name, $options = [])
    {
        $dbName = $options['dbAlias'] ?? $name;
        $pdoType = $options['pdoType'] ?? \PDO::PARAM_STR;
        /*
         * convToDb and convFromDb are callback functions that
         * are used to implement a custom data conversion while
         * reading from or writing to the database.
         *
         * function convToDb($value) : $dbValue
         * function convFromDb($dbValue) : $value
         */
        $convToDb = $options['convToDb'] ?? null;
        $convFromDb = $options['convFromDb'] ?? null;
        $this->_meta->fields[$name] = [$dbName, $pdoType, $convToDb, $convFromDb];

        $this->_state->row[$dbName] = null;
    }

    protected function defineAssoc($assocName,
                                   $targetClass,
                                   $isComposition = false,
                                   $linkData = [])
    {
        $linkTable = $linkData[self::ASSOC_LINK_TABLE] ?? '';
        $sourceIdField = $linkData[self::ASSOC_SOURCE_ID_FIELD] ?? 'source_id';
        $targetIdField = $linkData[self::ASSOC_TARGET_ID_FIELD] ?? 'target_id';
        $onDeleteCallback = $linkData[self::ASSOC_ON_DELETE_CALLBACK] ?? null;
        $readOnly = $linkData[self::ASSOC_READ_ONLY] ?? false;

        $this->_meta->assocs[$assocName] = [
            self::ASSOC_TARGET_CLASS => $targetClass,
            self::ASSOC_IS_COMPOSITION => $isComposition,
            self::ASSOC_LINK_TABLE => $linkTable,
            self::ASSOC_SOURCE_ID_FIELD => $sourceIdField,
            self::ASSOC_TARGET_ID_FIELD => $targetIdField,
            self::ASSOC_ON_DELETE_CALLBACK => $onDeleteCallback,
            self::ASSOC_READ_ONLY => $readOnly
        ];

        $this->_state->assocs[$assocName] = [];
    }

    protected function setRowData($row)
    {
        $this->_state->row = $row;
        if ($this->id != self::INDEX_NOT_IN_DB) {
            $this->_state->loaded = true;
        }
    }

    public function __get($name)
    {
        if (!$this->_state->loaded) {
            $this->load();
        }

        if (isset($this->_meta->fields[$name])) {

            // It's a property
            $field = $this->_meta->fields[$name];
            $dbName = $field[0];
            $dbValue = $this->_state->row[$dbName] ?? null;
            if ($dbValue !== null) {
                $convFromDb = $field[3];
                if ($convFromDb === null) {
                    return $dbValue;
                } else {
                    return $convFromDb($dbValue);
                }
            } else {
                return null;
            }


        } elseif ($this->_meta->assocs[$name]) {

            if (!$this->_state->assocsLoaded) {
                $this->loadAssociations();
            }

            // It's an association
            return $this->_state->assocs[$name];

        } else {

            return null;

        }

    }

    public function __set($name, $value)
    {

        if (isset($this->_meta->fields[$name])) {

            if (!$this->_state->loaded) {
                $this->load();
            }

            // It's a property
            $field = $this->_meta->fields[$name];
            $dbName = $field[0];
            $convToDb = $field[2];
            $this->_state->row[$dbName] = $convToDb === null ?
                $value : $convToDb($value);

        } elseif (isset($this->_meta->assocs[$name])) {
           
            $assocData = $this->_meta->assocs[$name];
            if ($assocData[self::ASSOC_READ_ONLY]) {
               return;
            }

            if (!$this->_state->assocsLoaded) {
                $this->loadAssociations();
            }

            // It's an association
            $this->_state->assocs[$name] = $value;

        }
    }

    public function load()
    {
        $this->_state->loaded = true;

        if ($this->id == self::INDEX_NOT_IN_DB) {
            return;
        }

        $sql = $this->_meta->sqlBuilder->createSelectCommand(
            $this->_meta->tableName,
            ['filter' => 'id = :id']);
        $stmt = self::$dbConn->prepare($sql);
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
        if ($this->_state->assocsLoaded) {
            return; // nothing to do
        }

        if ($this->isInDb()) {
            $assocNames = array_keys($this->_meta->assocs);
            foreach ($assocNames as $assocName) {
                $this->loadAssociation($assocName);
            }
        }

        $this->_state->assocsLoaded = true;
    }

    private function loadAssociation($assocName)
    {
        $this->_state->assocs[$assocName] = $this->readAssocObjects($assocName);
    }

    private function readAssocObjects($assocName)
    {
        $linkData = $this->_meta->assocs[$assocName];
        $linkTable = $linkData[self::ASSOC_LINK_TABLE];
        $sourceId = $linkData[self::ASSOC_SOURCE_ID_FIELD];
        $targetId = $linkData[self::ASSOC_TARGET_ID_FIELD];
        $targetClass = $linkData[self::ASSOC_TARGET_CLASS];

        $sql = $this->_meta->sqlBuilder->createSelectCommand(
            $linkTable,
            [
                'fields' => [$targetId],
                'filter' => $sourceId . ' = :id'
            ]);
        $stmt = self::$dbConn->prepare($sql);
        $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        $stmt->execute();

        $objects = [];

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        while ($row) {
            $objects[] = new $targetClass($row[$targetId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        $stmt->closeCursor();

        return $objects;
    }

    public function save()
    {
        $isNew = !$this->isInDb();

        $sql = $isNew ?
            $this->createPreparedInsert() :
            $this->createPreparedUpdate();

        $stmt = self::$dbConn->prepare($sql);
        $names = $this->getColumnInfo([$this, 'getColName']);
        $types = $this->getColumnInfo([$this, 'getColType']);
        $numCols = count($names);
        for ($col = 0; $col < $numCols; $col++) {
            $name = $names[$col];
            $stmt->bindParam(
                ':' . $name,
                $this->_state->row[$name],
                $types[$col]);
        }
        if ($this->id != self::INDEX_NOT_IN_DB) {
            $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        }

        if (!$stmt->execute()) {
            throw new \PDOException($stmt->errorInfo()[2]);
        }

        if ($isNew) {
            $this->id = self::$dbConn->lastInsertId();
        }

        $this->saveAssociations();
    }

    private function saveAssociations()
    {
        $assocNames = array_keys($this->_meta->assocs);
        foreach ($assocNames as $assocName) {
            $this->saveAssociation($assocName);
        }
    }

    private function saveAssociation($assocName)
    {
        $linkData = $this->_meta->assocs[$assocName];
       
        if ($linkData[self::ASSOC_READ_ONLY]) {
           // Read-only associations must not be saved
           return;
        }
       
        $linkTable = $linkData[self::ASSOC_LINK_TABLE];
        $sourceId = $linkData[self::ASSOC_SOURCE_ID_FIELD];
        $targetId = $linkData[self::ASSOC_TARGET_ID_FIELD];
        $targetClass = $linkData[self::ASSOC_TARGET_CLASS];
        $isComposition = $linkData[self::ASSOC_IS_COMPOSITION];
        $onDeleteCallback = $linkData[self::ASSOC_ON_DELETE_CALLBACK];

        $existingObjects = $this->readAssocObjects($assocName);
        $existingIds = [];
        foreach ($existingObjects as $obj) {
            $existingIds[$obj->getId()] = true;
        }

        $assocObjs = $this->_state->assocs[$assocName];

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
                $builder = $this->_meta->sqlBuilder;
                $sql = $builder->createInsertCommand(
                    $linkTable,
                    [$sourceId, $targetId]);

                $stmt = self::$dbConn->prepare($sql);
                $objId = $obj->getId();
                $stmt->bindParam(':'.$sourceId, $this->id, \PDO::PARAM_INT);
                $stmt->bindParam(':'.$targetId, $objId, \PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        // Delete unused links:
        foreach ($existingIds as $objId => $val) {
            $this->deleteLink($linkTable, $sourceId, $targetId, $objId);
            if ($isComposition) {
                $obj = new $targetClass($objId);
                $obj->delete();
            } elseif ($onDeleteCallback !== null) {
                $obj = new $targetClass($objId);
                call_user_func($onDeleteCallback, $obj);
            }
        }

    }

    private function deleteLink($linkTable,
                                $sourceId,
                                $targetId,
                                $objId)
    {
        $builder = $this->_meta->sqlBuilder;
        $sql = $builder->createDeleteCommand(
            $linkTable,
            "$sourceId = :source_id AND $targetId = :target_id"
        );
        $stmt = self::$dbConn->prepare($sql);
        $stmt->bindParam(':source_id', $this->id, \PDO::PARAM_INT);
        $stmt->bindParam(':target_id', $objId, \PDO::PARAM_INT);
        $stmt->execute();
    }

    private function deleteAssociations()
    {
        $assocNames = array_keys($this->_meta->assocs);
        foreach ($assocNames as $assocName) {
            $this->_state->assocs[$assocName] = [];
            $this->saveAssociation($assocName);
        }

    }

    public function delete()
    {
        if (!$this->isInDb()) {
            return;
        }

        $sql = $this->_meta->sqlBuilder->createDeleteCommand(
            $this->_meta->tableName);
        $stmt = self::$dbConn->prepare($sql);

        $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);

        $stmt->execute();

        $this->_state->row = [];

        $this->deleteAssociations();

        $this->id = self::INDEX_NOT_IN_DB;
    }

    private function createPreparedInsert()
    {
        $names = $this->getColumnInfo([$this, 'getColName']);
        return $this->_meta->sqlBuilder->createInsertCommand(
            $this->_meta->tableName, $names);
    }

    private function createPreparedUpdate()
    {
        $names = $this->getColumnInfo([$this, 'getColName']);
        return $this->_meta->sqlBuilder->createUpdateCommand(
            $this->_meta->tableName, $names);
    }

    private function getColumnInfo($columnDataFn)
    {
        $info = [];
        foreach ($this->_meta->fields as $name => $data) {
            if ($this->$name === null) {
                continue;
            }
            $info[] = $columnDataFn != null ? $columnDataFn($data) : $data;
        }
        return $info;
    }

    private function getColName($col)
    {
        return $col[0];
    }

    private function getColType($col)
    {
        return $col[1];
    }

}

<?php
/*
   Copyright 2016 Thomas Bollmeier <entwickler@tbollmeier.de>

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

    private static $dbConn;
    protected $id;
    protected $_meta;
    protected $_state;

    public static function setDbConnection(\PDO $dbConn)
    {
        self::$dbConn = $dbConn;
    }

    public static function query($options = [])
    {
        $objects = [];
        $params = $options['params'] ?? [];
        $model = new static();
        $sql = $model->_meta->sqlBuilder->createSelectCommand(
            $model->_meta->tableName,
            $options);
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
        $this->_state->loaded = false;
        $this->_state->assocsLoaded = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isInDb()
    {
        return $this->id != self::INDEX_NOT_IN_DB; // todo: SELECT...
    }

    protected function defineTable($tableName)
    {
        $this->_meta->tableName = $tableName;
    }

    protected function defineField($name, $options = [])
    {
        $dbName = $options['dbAlias'] ?? $name;
        $pdoType = $options['pdoType'] ?? \PDO::PARAM_STR;
        $this->_meta->fields[$name] = [$dbName, $pdoType];

        $this->_state->row[$dbName] = null;
    }

    protected function defineAssoc($assocName,
                                   $targetClass,
                                   $isComposition = false,
                                   $linkData = [])
    {
        $linkTable = $linkData['linkTable'] ?? ''; // TODO name
        $sourceIdField = $linkData['sourceIdField'] ?? 'source_id'; // TODO name
        $targetIdField = $linkData['targetIdField'] ?? 'target_id'; // TODO name

        $this->_meta->assocs[$assocName] = [
            'targetClass' => $targetClass,
            'isComposition' => $isComposition,
            'linkTable' => $linkTable,
            'sourceIdField' => $sourceIdField,
            'targetIdField' => $targetIdField
        ];

        $loaded = false;
        $objects = [];
        $this->_state->assocs[$assocName] = [$loaded, $objects];
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
            return $this->_state->row[$dbName] ?? null;

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

            // It's a property
            $field = $this->_meta->fields[$name];
            $dbName = $field[0];
            $this->_state->row[$dbName] = $value;

        } elseif ($this->_meta->assocs[$name]) {

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
        $assocNames = array_keys($this->_meta->assocs);
        foreach ($assocNames as $assocName) {
            $this->loadAssociation($assocName);
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
        $linkTable = $linkData['linkTable'];
        $sourceId = $linkData['sourceIdField'];
        $targetId = $linkData['targetIdField'];
        $targetClass = $linkData['targetClass'];

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
        $types = $this->getColumnInfo([$this, 'getColName']);
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

        $stmt->execute();

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
        $linkTable = $linkData['linkTable'];
        $sourceId = $linkData['sourceIdField'];
        $targetId = $linkData['targetIdField'];
        $targetClass = $linkData['targetClass'];

        $existingObjects = $this->readAssocObjects($assocName);
        $existingIds = [];
        foreach ($existingObjects as $obj) {
            $existingIds[$obj->getId()] = true;
        }

    }

    public function delete()
    {
        if ($this->id == self::INDEX_NOT_IN_DB) {
            return;
        }
        $sql = $this->_meta->sqlBuilder->createDeleteCommand(
            $this->_meta->tableName);
        $stmt = self::$dbConn->prepare($sql);

        $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);

        $stmt->execute();

        $this->_state->row = [];
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
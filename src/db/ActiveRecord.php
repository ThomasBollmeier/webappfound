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
    protected $_state;

    public static function setDbConnection(\PDO $dbConn)
    {
        self::$dbConn = $dbConn;
    }

    public static function query($options=[])
    {
        $objects = [];

        $params = $options['params'] ?? [];

        $model = new static();
        $sql = $model->_state->sqlBuilder->createSelectCommand(
            $model->_state->tableName,
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

        $this->_state = new \stdClass();
        $this->_state->tableName = '';
        $this->_state->row = [];
        $this->_state->fields = [];
        $this->_state->sqlBuilder = $sqlBuilder ?? new SqlBuilder();
        $this->_state->loaded = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isInDb()
    {
        return $this->id != self::INDEX_NOT_IN_DB;
    }

    protected function setTableName($tableName)
    {
        $this->_state->tableName = $tableName;
    }

    protected function setDbField($name, $options=[])
    {
        $dbName = $options['dbAlias'] ?? $name;
        $pdoType = $options['pdoType'] ?? \PDO::PARAM_STR;
        $this->_state->fields[$name] = [$dbName, $pdoType];
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

        $field = $this->_state->fields[$name] ?? [$name, \PDO::PARAM_STR];
        $dbName = $field[0];

        if (isset($this->_state->row[$dbName])) {
            return $this->_state->row[$dbName];
        } else {
            return null;
        }
    }

    public function __set($name, $value)
    {
        $field = $this->_state->fields[$name] ?? [$name, \PDO::PARAM_STR];
        $dbName = $field[0];

        $this->_state->row[$dbName] = $value;
    }

    public function load()
    {
        $this->_state->loaded = true;

        if ($this->id == self::INDEX_NOT_IN_DB) {
            return;
        }

        $sql = $this->_state->sqlBuilder->createSelectCommand(
            $this->_state->tableName,
            ['filter' => 'id = :id']);
        $stmt = self::$dbConn->prepare($sql);
        $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $this->setRowData($row);
        }
        $stmt->closeCursor();

    }

    public function save()
    {
        $sql = $this->id == self::INDEX_NOT_IN_DB ?
            $this->createPreparedInsert() :
            $this->createPreparedUpdate();

        $stmt = self::$dbConn->prepare($sql);

        $names = $this->getColumnInfo(self::COLUMN_INFO_NAME);
        $types = $this->getColumnInfo(self::COLUMN_INFO_TYPE);
        $numCols = count($names);
        for ($col=0; $col<$numCols; $col++) {
            $name = $names[$col];
            $stmt->bindParam(
                ':'.$name,
                $this->_state->row[$name],
                $types[$col]);
        }
        if ($this->id != self::INDEX_NOT_IN_DB) {
            $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        }

        $stmt->execute();

        if ($this->id == self::INDEX_NOT_IN_DB) {
            $this->id = self::$dbConn->lastInsertId();
        }

    }

    public function delete()
    {
        if ($this->id == self::INDEX_NOT_IN_DB) {
            return;
        }

        $sql = $this->_state->sqlBuilder->createDeleteCommand(
            $this->_state->tableName);
        $stmt = self::$dbConn->prepare($sql);
        $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        $stmt->execute();

        $this->_state->row = [];
        $this->id = self::INDEX_NOT_IN_DB;

    }

    private function createPreparedInsert()
    {
        $names = $this->getColumnInfo(self::COLUMN_INFO_NAME);

        return $this->_state->sqlBuilder->createInsertCommand(
            $this->_state->tableName, $names);
    }

    private function createPreparedUpdate()
    {
        $names = $this->getColumnInfo(self::COLUMN_INFO_NAME);

        return $this->_state->sqlBuilder->createUpdateCommand(
            $this->_state->tableName, $names);
    }

    const COLUMN_INFO_NAME = 1;
    const COLUMN_INFO_TYPE = 2;

    private function getColumnInfo($infoType) {

        $info = [];

        foreach ($this->_state->fields as $name => $data) {
            $colName = $data[0];
            if ($this->$name === null) {
                continue;
            }
            switch ($infoType) {
                case self::COLUMN_INFO_NAME:
                    $info[] = $colName;
                    break;
                case self::COLUMN_INFO_TYPE:
                    $info[] = $data[1];
                    break;
            }
        }

        return $info;

    }

}
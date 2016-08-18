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

namespace tbollmeier\webappfound;

abstract class Model
{
    const INDEX_NOT_IN_DB = -1;

    protected $tableName;
    protected $fields;
    protected $id;
    protected $row;

    public function __construct(int $id=-1)
    {
        $this->id = intval($id);
        $this->tableName = '';
        $this->row = [];
        $this->fields = [];
    }

    public function getId()
    {
        return $this->id;
    }

    protected function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    protected function setDbField($name, $options=[])
    {
        $dbName = isset($options['dbAlias']) ? $options['dbAlias'] : $name;
        $pdoType = isset($options['pdoType']) ? $options['pdoType'] : \PDO::PARAM_STR;
        $this->fields[$name] = [$dbName, $pdoType];
    }

    protected function setRowData($row)
    {
        $this->row = $row;
    }

    public function __get($name)
    {
        $field = isset($this->fields[$name]) ?
            $this->fields[$name] : [$name, \PDO::PARAM_STR];
        $dbName = $field[0];

        if (isset($this->row[$dbName])) {
            return $this->row[$dbName];
        } else {
            return null;
        }
    }

    public function __set($name, $value)
    {
        $field = isset($this->fields[$name]) ?
            $this->fields[$name] : [$name, \PDO::PARAM_STR];
        $dbName = $field[0];
        $this->row[$dbName] = $value;
    }

    public function load(\PDO $dbConn)
    {
        if ($this->id == self::INDEX_NOT_IN_DB) {
            return;
        }

        $sql = 'SELECT * FROM '.$this->tableName.' WHERE id = :id';
        $stmt = $dbConn->prepare($sql);
        $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $this->setRowData($row);
        }
        $stmt->closeCursor();
    }

    public function save(\PDO $dbConn)
    {
        $sql = $this->id == self::INDEX_NOT_IN_DB ?
            $this->createPreparedInsert() :
            $this->createPreparedUpdate();

        $stmt = $dbConn->prepare($sql);

        $names = $this->getColumnInfo(self::COLUMN_INFO_NAME);
        $types = $this->getColumnInfo(self::COLUMN_INFO_TYPE);
        $numCols = count($names);
        for ($col=0; $col<$numCols; $col++) {
            $name = $names[$col];
            $stmt->bindParam(
                ':'.$name,
                $this->row[$name],
                $types[$col]);
        }
        if ($this->id != self::INDEX_NOT_IN_DB) {
            $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        }

        $stmt->execute();

        if ($this->id == self::INDEX_NOT_IN_DB) {
            $this->id = $dbConn->lastInsertId();
        }

    }

    public function delete(\PDO $dbConn)
    {
        if ($this->id == self::INDEX_NOT_IN_DB) {
            return;
        }

        $sql = 'DELETE FROM '.$this->tableName.' WHERE id = :id';
        $stmt = $dbConn->prepare($sql);
        $stmt->bindParam(':id', $this->id, \PDO::PARAM_INT);
        $stmt->execute();

        $this->row = [];
        $this->id = self::INDEX_NOT_IN_DB;

    }

    private function createPreparedInsert()
    {
        $names = $this->getColumnInfo(self::COLUMN_INFO_NAME);
        $namesStr = implode(', ', $names);

        $params = array_map(function($name) {
            return ':'.$name;
        }, $names);
        $paramsStr = implode(', ', $params);

        $sql = 'INSERT INTO ' . $this->tableName . ' (';
        $sql .= $namesStr . ') VALUES (' . $paramsStr . ')';

        return $sql;
    }

    private function createPreparedUpdate()
    {
        $names = $this->getColumnInfo(self::COLUMN_INFO_NAME);

        $sql = 'UPDATE ' . $this->tableName . ' SET ';

        $numCols = count($names);
        for ($col = 0; $col<$numCols; $col++) {
            if ($col > 0) {
                $sql .= ', ';
            }
            $sql .= $names[$col] . ' = :' . $names[$col];
        }

        $sql .= ' WHERE id = :id';

        return $sql;

    }

    const COLUMN_INFO_NAME = 1;
    const COLUMN_INFO_VALUE = 2;
    const COLUMN_INFO_TYPE = 3;

    private function getColumnInfo($infoType) {

        $info = [];

        foreach ($this->fields as $name => $data) {
            $colName = $data[0];
            if ($this->$name === null) {
                continue;
            }
            switch ($infoType) {
                case self::COLUMN_INFO_NAME:
                    $info[] = $colName;
                    break;
                case self::COLUMN_INFO_VALUE:
                    $info[] = $this->$colName;
                    break;
                case self::COLUMN_INFO_TYPE:
                    $info[] = $data[1];
                    break;
            }
        }

        return $info;

    }

}
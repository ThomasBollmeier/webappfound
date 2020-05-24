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


use Exception;

class SqlBuilder implements ISqlBuilder
{
    public function createCreateTableCommand(TableDefinition $tableDef)
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . $tableDef->getName() . " (\n";
        foreach ($tableDef->getKeyFields() as $keyField) {
            $sql .= "\t" . $this->createFieldDefLine($keyField) . ",\n";
        }
        foreach ($tableDef->getDataFields() as $dataField) {
            $sql .= "\t" . $this->createFieldDefLine($dataField) . ",\n";
        }
        $sql .= "\t" . $this->createPrimaryKeyLine($tableDef) . "\n";
        $sql .= ")";

        return $sql;
    }

    private function createFieldDefLine(TableField $field)
    {
        $line = $field->getName() . " ";

        $sqlType = $field->getSqlType();

        switch ($sqlType->getTypeCode()) {
            case SqlType::BOOL:
                $line .= "TINYINT(1)";
                break;
            case SqlType::INT:
                $line .= "INTEGER";
                break;
            case SqlType::FLOAT:
                $digits = $sqlType->getDigits();
                $decimals = $sqlType->getDecimals();
                $line .= "DOUBLE PRECISION(" . $digits . "," . $decimals .")";
                break;
            case SqlType::VARCHAR:
                $length = $sqlType->getLength();
                if ($length != 0) {
                    $line .= "VARCHAR(" . $length . ")";
                } else {
                    $line .= "MEDIUMTEXT";
                }
                break;
            case SqlType::DATE:
                $line .= "DATE";
                break;
            case SqlType::TIME:
                $line .= "TIME";
                break;
            case SqlType::DATETIME:
                $line .= "DATETIME";
                break;
            default:
                throw new Exception("Unknown SQL Type");
        }

        if (!$field->isNullable()) {
            $line .= " NOT NULL";
        }

        if ($field->isAutoIncrement()) {
            $line .= " AUTO_INCREMENT";
        }

        return $line;
    }

    private function createPrimaryKeyLine(TableDefinition $tableDef)
    {
        $line = "PRIMARY KEY (";
        $first = true;
        foreach ($tableDef->getKeyFields() as $keyField) {
            if ($first) {
                $first = false;
            } else {
                $line .= ", ";
            }
            $line .= $keyField->getName();
        }
        $line .= ")";
        return $line;
    }

    public function createInsertCommand($tableName, $columnNames)
    {
        $namesStr = implode(', ', $columnNames);

        $params = array_map(function($name) {
            return ':'.$name;
        }, $columnNames);
        $paramsStr = implode(', ', $params);

        $sql = 'INSERT INTO ' . $tableName . ' (';
        $sql .= $namesStr . ') VALUES (' . $paramsStr . ')';

        return $sql;
    }

    public function createUpdateCommand($tableName, $columnNames)
    {
        $sql = 'UPDATE ' . $tableName . ' SET ';

        $numCols = count($columnNames);
        for ($col = 0; $col<$numCols; $col++) {
            if ($col > 0) {
                $sql .= ', ';
            }
            $sql .= $columnNames[$col] . ' = :' . $columnNames[$col];
        }

        $sql .= ' WHERE id = :id';

        return $sql;
    }

    public function createDeleteCommand($tableName, $where="id = :id")
    {
        return 'DELETE FROM '.$tableName.' WHERE '.$where;
    }

    public function createSelectCommand($tableName, $options)
    {
        $fields = $options['fields'] ?? [];
        $filter = $options['filter'] ?? '';
        $orderBy = $options['orderBy'] ?? '';

        $fieldsStr = empty($fields) ? '*' : implode(', ', $fields);
        $sql = 'SELECT ' . $fieldsStr . ' FROM ' . $tableName;
        if (!empty($filter)) {
            $sql .= ' WHERE ' . $filter;
        }
        if (!empty($orderBy)) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        
        if (array_key_exists("limit", $options)) {
            $limit = $options["limit"];
            $sql .= " LIMIT $limit ";
        }

        if (array_key_exists("offset", $options)) {
            $offset = $options["offset"];
            $sql .= " OFFSET $offset ";
        }
        
        return $sql;
    }
}
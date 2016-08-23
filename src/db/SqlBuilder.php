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

class SqlBuilder
{
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

    public function createDeleteCommand($tableName)
    {
        return 'DELETE FROM '.$tableName.' WHERE id = :id';
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

        return $sql;
    }
}
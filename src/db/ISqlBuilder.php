<?php

namespace tbollmeier\webappfound\db;

interface ISqlBuilder
{
    public function createCreateTableCommand(TableDefinition $tableDef);

    public function createInsertCommand($tableName, $columnNames);

    public function createUpdateCommand($tableName, $columnNames);

    public function createDeleteCommand($tableName, $where = "id = :id");

    public function createSelectCommand($tableName, $options);
}
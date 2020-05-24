<?php
/*
 Copyright 2020 Thomas Bollmeier <developer@thomas-bollmeier.de>

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


class EntityRelationshipModel
{
    private $entityDefs;

    public function __construct()
    {
        $this->entityDefs = [];
    }

    public function addEntityDef(EntityDefinition $entityDef)
    {
        $this->entityDefs[] = $entityDef;
        return $this;
    }

    /**
     * Return table definitions that represent the
     * model in the database
     */
    public function determineTableDefs()
    {
        $ret = [];

        foreach ($this->entityDefs as $entityDef) {

            $tableName = $entityDef->getTableName();
            $fields = $entityDef->getFields();
            $ret[$tableName] = $this->createTableDef($tableName, $fields);

            $assocDefs = $entityDef->getAssociations();

            foreach ($assocDefs as $assocDef) {
                $linkTableName = $assocDef->getLinkTable();
                if (array_key_exists($linkTableName, $ret)) {
                    continue;
                }
                $linkTable = $this->createLinkTableDef($assocDef);
                $ret[$linkTableName] = $linkTable;
            }

        }

        return $ret;
    }

    private function createTableDef($tableName, $fields) : TableDefinition
    {
        $ret = new TableDefinition($tableName);

        $keyField = new TableField(
            "id",
            SqlType::makeInt(),
            false,
            true);
        $ret->addKeyField($keyField);

        foreach ($fields as $field) {
            $dataField = new TableField(
                $field->getDbAlias(),
                $field->getSqlType(),
                false,
                false);
            $ret->addDataField($dataField);
        }
        return $ret;
    }

    private function createLinkTableDef(AssociationDefinition $assocDef)
    {
        $ret = new TableDefinition($assocDef->getLinkTable());

        $firstField = new TableField(
            $assocDef->getSourceIdField(),
            SqlType::makeInt(),
            false,
            false);
        $ret->addKeyField($firstField);

        $secondField = new TableField(
            $assocDef->getTargetIdField(),
            SqlType::makeInt(),
            false,
            false);
        $ret->addKeyField($secondField);

        return $ret;
    }

}
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


class FieldDefinition
{

    private $entityDef;
    private $name;
    private $dbAlias;
    private $pdoType;
    /*
     * convToDb and convFromDb are callback functions that
     * are used to implement a custom data conversion while
     * reading from or writing to the database.
     *
     * function convToDb($value) : $dbValue
     * function convFromDb($dbValue) : $value
     */
    private $convToDb;
    private $convFromDb;
    
    public function getName()
    {
        return $this->name;
    }

    public function getDbAlias()
    {
        return $this->dbAlias;
    }

    public function getPdoType()
    {
        return $this->pdoType;
    }

    public function getConvToDb()
    {
        return $this->convToDb;
    }

    public function getConvFromDb()
    {
        return $this->convFromDb;
    }

    public function setDbAlias($dbAlias)
    {
        $this->dbAlias = $dbAlias;
        return $this;
    }

    public function setPdoType($pdoType)
    {
        $this->pdoType = $pdoType;
        return $this;
    }

    public function setConvToDb($convToDb)
    {
        $this->convToDb = $convToDb;
        return $this;
    }

    public function setConvFromDb($convFromDb)
    {
        $this->convFromDb = $convFromDb;
        return $this;
    }
    
    public function add() 
    {
        return $this->entityDef->addField($this);
    }

    public function __construct(EntityDefinition $entityDef, string $name)
    {
        $this->entityDef = $entityDef;
        $this->name = $name;
        $this->dbAlias = $name;
        $this->pdoType = \PDO::PARAM_STR;
        $this->convToDb = null;
        $this->convFromDb = null;
    } 
    
}


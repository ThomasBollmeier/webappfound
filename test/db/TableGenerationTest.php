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

use tbollmeier\webappfound\db as db;

require_once "model.php";


class TableGenerationTest extends PHPUnit_Framework_TestCase
{
    private $model;

    public function setUp()
    {
        $this->connectionSetup();
        $this->model = new demo\Model();
        $this->dropTables(db\Environment::getInstance()->dbConn, $this->model);
    }

    public function tearDown()
    {
        //$this->dropTables($this->dbConn, $this->model);
        $this->model = null;
    }

    public function testTableGeneration() {

        $tableDefs = $this->model->determineTableDefs();
        $dbEnv = db\Environment::getInstance();

        foreach ($tableDefs as $tableDef) {
            $sql = $dbEnv->sqlBuilder->createCreateTableCommand($tableDef);
            $dbEnv->dbConn->exec($sql);
        }

        $personDef = $this->model->getEntityDef(\demo\Person::class);
        $hobbyDef = $this->model->getEntityDef(\demo\Hobby::class);

        $people = $personDef->query();
        $this->assertEmpty($people);

        $ego = $personDef->createEntity();
        $ego->name = "Bollmeier";
        $ego->firstName = "Thomas";
        $hobby = $hobbyDef->createEntity();
        $hobby->name = "Running";
        $ego->hobbies = [$hobby];
        $ego->save();

        $people = $personDef->query();
        $this->assertCount(1, $people);

        $hobbies = $hobbyDef->query();
        $this->assertCount(1, $hobbies);
    }

    private function connectionSetup()
    {
        $connector = new db\Connector();
        $dbConn = $connector->createConnection([
            'dbname' => 'waftest',
            'user' => 'waftester',
            'password' => 'test1234'
        ]);

        $this->assertNotNull($dbConn);
        $this->assertTrue($dbConn !== false);

        db\Environment::getInstance()->dbConn = $dbConn;
        db\Environment::getInstance()->sqlBuilder = new db\SqlBuilder();
    }

    private function dropTables(PDO $dbConn, db\EntityRelationshipModel $model)
    {
        $tableDefs = $model->determineTableDefs();

        foreach ($tableDefs as $tableDef) {
            $tableName = $tableDef->getName();
            $dbConn->exec("DROP TABLE $tableName");
        }

    }
}
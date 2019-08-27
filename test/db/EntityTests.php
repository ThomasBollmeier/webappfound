<?php
/*
   Copyright 2019 Thomas Bollmeier <entwickler@tbollmeier.de>

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


class Person extends db\EntityDefinition
{
    public function __construct()
    {
        parent::__construct("people");
        
        $this
            ->newField("name")->add()
            ->newField("firstName")
                ->setDbAlias("first_name")
                ->add()
            ->newAssociation("hobbies", Hobby::class)
                ->setIsComposition()
                ->setLinkTable("people_hobbies")
                ->setSourceIdField("person_id")
                ->setTargetIdField("hobby_id")
                ->add();
    }
}

class Hobby extends db\EntityDefinition
{
    public function __construct()
    {
        parent::__construct("hobbies");
        
        $this->newField("name")->add();
    }
}

class EntityTests extends PHPUnit_Framework_TestCase
{
    private $dbConn;
    private $personDef;
    private $hobbyDef;

    public function setUp()
    {
        $connector = new db\Connector();
        $this->dbConn = $connector->createConnection([
            'dbname' => 'waftest',
            'user' => 'waftester',
            'password' => 'test1234',
            'unix_socket' => '/opt/lampp/var/mysql/mysql.sock'
        ]);

        $this->assertNotNull($this->dbConn);
        $this->assertTrue($this->dbConn !== false);

        db\Environment::getInstance()->dbConn = $this->dbConn;
        
        $this->personDef = new Person();
        $this->hobbyDef = new Hobby();

        $this->initContent();

    }

    private function initContent()
    {
        $this->dbConn->exec("DELETE FROM people");
        $this->dbConn->exec("DELETE FROM hobbies");
        $this->dbConn->exec("DELETE FROM people_hobbies");

        $person = $this->personDef->createEntity();
        $person->firstName = "Herbert";
        $person->name = "Mustermann";

        $this->addHobby($person, "Laufen");
        $this->addHobby($person, "Programmieren");

        $person->save();
    }

    private function addHobby($person, $name)
    {
        $hobby = $this->hobbyDef->createEntity();
        $hobby->name = $name;
        $hobbies = $person->hobbies;
        $hobbies[] = $hobby;
        $person->hobbies = $hobbies;
    }

    public function tearDown()
    {
        if ($this->dbConn) {
            unset($this->dbConn);
        }
    }
    
    public function testInitialSave()
    {
        $person = $this->personDef->createEntity();
        
        $person->save();
        
        $id = $person->getId();
        $this->assertFalse($id == db\Entity::INDEX_NOT_IN_DB);       
    }

    public function testLoadAssociation() {

        $people = $this->personDef->query([
            'filter' => 'name = :name',
            'params' => [':name' => 'Mustermann']
        ]);
        $this->assertEquals(count($people), 1);

        $person = $people[0];

        $hobbyNames = array_map(function ($hobby) {
            return $hobby->name;
        }, $person->hobbies);
        sort($hobbyNames);

        $this->assertEquals(2, count($hobbyNames));
        $this->assertEquals($hobbyNames[0], 'Laufen');
        $this->assertEquals($hobbyNames[1], 'Programmieren');

    }

    public function testSaveAssociation()
    {

        $people = $this->personDef->query([
            'filter' => 'name = :name',
            'params' => [':name' => 'Mustermann']
        ]);
        $this->assertEquals(count($people), 1);

        $person = $people[0];

        $newHobby = $this->hobbyDef->createEntity();
        $newHobby->name = 'Literatur';
        $hobbies = $person->hobbies;
        $hobbies[] = $newHobby;
        $person->hobbies = $hobbies;

        $person->save();

        $people = $this->personDef->query([
            'filter' => 'name = :name',
            'params' => [':name' => 'Mustermann']
        ]);
        $this->assertEquals(count($people), 1);

        $person = $people[0];
        $hobbies = $person->hobbies;

        $this->assertEquals(count($hobbies), 3);

        $changedHobbies = [];
        foreach ($hobbies as $hobby) {
            if ($hobby->name == 'Literatur') {
                continue;
            }
            $changedHobbies[] = $hobby;
        }
        $person->hobbies = $changedHobbies;

        $person->save();

        $people = $this->personDef->query([
            'filter' => 'name = :name',
            'params' => [':name' => 'Mustermann']
        ]);
        $this->assertEquals(count($people), 1);

        $person = $people[0];
        $hobbies = $person->hobbies;

        $this->assertEquals(count($hobbies), 2);

    }

}
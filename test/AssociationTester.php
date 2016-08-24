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

use tbollmeier\webappfound\db as db;


class Person extends db\ActiveRecord
{
    public function __construct($id=self::INDEX_NOT_IN_DB)
    {
        parent::__construct($id);

        $this->defineTable('people');
        $this->defineField('name');
        $this->defineField('firstName', ['dbAlias' => 'first_name']);

        $this->defineAssoc('hobbies', 'Hobby', true, [
            'linkTable' => 'people_hobbies',
            'sourceIdField' => 'person_id',
            'targetIdField' => 'hobby_id'
        ]);
    }
}

class Hobby extends db\ActiveRecord
{
    public function __construct($id=self::INDEX_NOT_IN_DB)
    {
        parent::__construct($id);

        $this->defineTable('hobbies');
        $this->defineField('name');
    }
}

class AssociationTester extends PHPUnit_Framework_TestCase
{
    private $dbConn;

    public function setUp()
    {
        $connector = new db\Connector();
        $this->dbConn = $connector->createConnection([
            'dbname' => 'waftest',
            'user' => 'waftester',
            'password' => ''
        ]);

        $this->assertNotNull($this->dbConn);
        $this->assertTrue($this->dbConn !== false);

        db\ActiveRecord::setDbConnection($this->dbConn);

    }

    public function tearDown()
    {
        if ($this->dbConn) {
            unset($this->dbConn);
        }
    }

    public function testLoadAssociation() {

        $people = Person::query([
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

}
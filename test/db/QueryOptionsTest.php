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
use tbollmeier\webappfound\db\QueryOptions;

/**
 * QueryOptions test case.
 */
class QueryOptionsTest extends PHPUnit_Framework_TestCase
{

    /**
     *
     * @var QueryOptions
     */
    private $queryOptions;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->queryOptions = new QueryOptions();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->queryOptions = null;
        parent::tearDown();
    }

    /**
     * Tests QueryOptions->addField()
     */
    public function testAddField()
    {
        $this->queryOptions
            ->addField("testfield1")
            ->addField("testfield2");
        
        $this->assertEquals(2, count($this->queryOptions->fields));
    }

    /**
     * Tests QueryOptions->setFilter()
     */
    public function testSetFilter()
    {
        $filter = "name = :name";
        
        $this->queryOptions->setFilter($filter);
        $this->assertEquals($filter, $this->queryOptions->filter);
    }

    /**
     * Tests QueryOptions->setOrderBy()
     */
    public function testSetOrderBy()
    {
        $orderBy = "name ASCENDING";
        
        $this->queryOptions->setOrderBy($orderBy);
        $this->assertEquals($orderBy, $this->queryOptions->orderBy);
    }

    /**
     * Tests QueryOptions->addParam()
     */
    public function testAddParam()
    {
        $this->queryOptions->addParam("name", "Thomas");
        $this->assertEquals([":name" => "Thomas"], $this->queryOptions->params);
    }

    /**
     * Tests QueryOptions->toArray()
     */
    public function testToArray()
    {
        $this->queryOptions
            ->addField("firstName")
            ->addField("lastName")
            ->setFilter("birthday = :today")
            ->addParam("today", "01012019");
      
        $this->assertEquals([
            "fields" => ["firstName", "lastName"],
            "filter" => "birthday = :today",
            "params" => [":today" => "01012019"]], 
            $this->queryOptions->toArray());
    }
}


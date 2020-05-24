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

namespace demo;

use tbollmeier\webappfound\db as db;

class Person extends db\EntityDefinition
{
    public function __construct()
    {
        parent::__construct("people");

        $this
            ->newField("name")
                ->setSqlType(db\SqlType::makeVarChar(50))
                ->add()
            ->newField("firstName")
                ->setSqlType(db\SqlType::makeVarChar(50))
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

class Model extends db\EntityRelationshipModel
{
    public function __construct()
    {
        parent::__construct();
        $this->addEntityDef(new Person());
        $this->addEntityDef(new Hobby());
    }
}

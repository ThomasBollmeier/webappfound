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


class AssociationDefinition
{
    private $entityDef;
    private $name;
    private $targetEntityDef;
    private $isComposition;
    private $linkTable;
    private $sourceIdField;
    private $targetIdField;
    private $onDeleteCallback;
    private $readonly;
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getTargetEntityDef()
    {
        return new $this->targetEntityDef();
    }

    public function isComposition()
    {
        return $this->isComposition;
    }

    public function getLinkTable()
    {
        return $this->linkTable;
    }

    public function getSourceIdField()
    {
        return $this->sourceIdField;
    }

    public function getTargetIdField()
    {
        return $this->targetIdField;
    }

    public function getOnDeleteCallback()
    {
        return $this->onDeleteCallback;
    }

    public function isReadonly()
    {
        return $this->readonly;
    }

    public function setIsComposition($isComposition=true)
    {
        $this->isComposition = $isComposition;
        return $this;
    }

    public function setLinkTable($linkTable)
    {
        $this->linkTable = $linkTable;
        return $this;
    }

    public function setSourceIdField($sourceIdField)
    {
        $this->sourceIdField = $sourceIdField;
        return $this;
    }

    public function setTargetIdField($targetIdField)
    {
        $this->targetIdField = $targetIdField;
        return $this;
    }

    public function setOnDeleteCallback($onDeleteCallback)
    {
        $this->onDeleteCallback = $onDeleteCallback;
        return $this;
    }

    public function setReadonly($readonly)
    {
        $this->readonly = $readonly;
        return $this;
    }
    
    public function add() {
        return $this->entityDef->addAssociation($this);
    }

    public function __construct(EntityDefinition $entityDef, $name, $targetEntityDef)
    {
        $this->entityDef = $entityDef;
        $this->name = $name;
        $this->targetEntityDef = $targetEntityDef;
        $this->linkTable = "";
        $this->sourceIdField = "source_id";
        $this->targetIdField = "target_id";
        $this->onDeleteCallback = null;
        $this->readonly = false;
        $this->isComposition = false;
    }
    
}


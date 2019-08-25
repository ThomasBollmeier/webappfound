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

class Environment
{
    private static $single = null;
    
    public $dbConn; // Connection
    public $sqlBuilder; // SQL Dialect
    
    public static function getInstance()
    {
        if (self::$single === null) {
            self::$single = new Environment();
        }
        
        return self::$single;
    }
    
    private function __construct()
    {
        $this->dbConn = null;
        $this->sqlBuilder = new SqlBuilder();
    }
}


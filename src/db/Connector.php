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

namespace tbollmeier\webappfound\db;


class Connector
{
    /**
     * Create a connection from a configuration file:
     *
     * @param $configFile   string  path to config file
     * @return mixed                PDO connection instance or false
     */
    public function createConfigConnection($configFile)
    {
        $options = $this->parseConfigFile($configFile);

        return $this->createConnection($options);
    }

    public function createConnection($options)
    {
        try {

            $dsn = $this->composeDSN($options);
            $user = $options['user'];
            $password = $options['password'];

            return new \PDO($dsn, $user, $password);

        } catch (\Exception $error) {
            return false;
        }

    }

    private function parseConfigFile($configFile)
    {

        $content = file_get_contents($configFile);
        if ($content === false) {
            throw new \Exception("Cannot read file {$configFile}.");
        }

        $convertIntoAssoc = true;
        $options = json_decode($content, $convertIntoAssoc);

        $defaults = [
            'type' => 'mysql',
            'host' => 'localhost',
            'user' => 'root',
            'password' => ''
        ];

        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $options)) {
                $options[$key] = $value;
            }
        }

        return $options;
    }

    private function composeDSN($options)
    {
        $dsn = "";
        if ($options['type'] == 'mysql') {
            $dsn .= 'mysql:';
        } else {
            throw new \Exception("Unknown DB type {$options['type']}.");
        }
        
        $dsn .= 'host='.$options['host'];

        if (array_key_exists('port', $options)) {
            $dsn .= ";port=".$options['port'];
        }

        $dsn .= ';dbname='.$options['dbname'];

        if (array_key_exists('charset', $options)) {
            $dsn .= ";port=".$options['charset'];
        }

        return $dsn;
    }

}
<?php
/*
 Copyright2019 Thomas Bollmeier <developer@thomas-bollmeier.de>
 
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

namespace tbollmeier\webappfound\util;


class ArgumentParser
{
    private $options;
    
    public function __construct()
    {
        $this->options = [];
    }
    
    public function addOption(CmdOption $option)
    {
        $this->options[] = $option;
    }
    
    public function parse()
    {
        global $argv;
        
        list($shortOpts, $longOpts) = $this->buildOptions();

        $options = getopt($shortOpts, $longOpts);
        
        $args = $this->removeOptionsFromArgs($argv, $options);
        
        return [$args, $options];
    }
    
    private function buildOptions()
    {
        $shortOpts = "";
        $longOpts = [];
        
        foreach ($this->options as $option) {
            
            $valueMode = $option->getValueMode();
            
            switch ($valueMode) {
                case CmdOption::VALUE_REQUIRED:
                    $suffix = ":";
                    break;
                case CmdOption::VALUE_OPTIONAL:
                    $suffix = "::";
                    break;
                default:
                    $suffix = "";
                    break;
            }
            
            $shortOpt = $option->getShort();
            
            if (!empty($shortOpt)) {
                $shortOpts .= $shortOpt . $suffix;
            }
            
            $longOpt = $option->getLong();
            
            if (!empty($longOpt)) {
                $longOpts[] = $longOpt . $suffix;
            }
            
        }
        
        return [$shortOpts, $longOpts];
    }
    
    private function removeOptionsFromArgs($args, $options) 
    {
        $ret = [];
        
        $idx = 1;
        $idxMax = count($args) - 1;
        
        while ($idx <= $idxMax) {
            
            $arg = $args[$idx];
            
            if ($arg[0] !== "-") {
                $ret[] = $arg;
            } else {
                // Check whether option has a pending value as next item:
                if (strlen($arg) > 1 && $arg[1] == "-") {
                    // long options will always have their value directly attached
                } else {
                    // Find matching short option and check whether the value is already
                    // included
                    if ($this->hasShortOptionPendingValue($arg, $options)) {
                        $idx++; // skip next item as it represents the pending option value
                    }
                }
            }
            
            $idx++;
        }
        
        return $ret;
    }
    
    private function hasShortOptionPendingValue($arg, $usedOptions) 
    {
        foreach ($this->options as $option) {
            
            $short = $option->getShort();
            if (empty($short)) {
                continue; // no short option
            }
            
            if (!array_key_exists($short, $usedOptions)) {
                continue; // option not used in current call
            }
            
            $valueMode = $option->getValueMode();
            
            if ($valueMode == CmdOption::NO_VALUE) {
                continue; // no value
            } elseif ($valueMode == CmdOption::VALUE_OPTIONAL &&
                      $usedOptions[$short] === false) {
                continue;
            }
            
            
            $matches = [];
            if (preg_match("/\-" . $short . "(.*)/", $arg, $matches)) {
                if (strlen($matches[0]) == strlen($short) + 1) {
                    return true;
                }
            }
            
        }
        
        return false;
    }
}


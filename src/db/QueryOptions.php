<?php
namespace tbollmeier\webappfound\db;

class QueryOptions
{
    public $fields;
    public $filter;
    public $orderBy;
    public $params;
    
    public function __construct()
    {
        $this->fields = [];
        $this->filter = "";
        $this->orderBy = "";
        $this->params = [];
    }
    
    public function addField(string $field)
    {
        $this->fields[] = $field;
        return $this;
    }
    
    public function setFilter(string $filter)
    {
        $this->filter = $filter;
        return $this;
    }
    
    public function setOrderBy(string $orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }
    
    public function addParam(string $name, $value)
    {
        $this->params[":$name"] = $value;
        return $this;
    }
    
    public function toArray() 
    {
        $ret = [];
        
        if (count($this->fields) > 0) {
            $ret["fields"] = $this->fields;
        }
        
        if (!empty($this->filter)) {
            $ret["filter"] = $this->filter;
        }
        
        if (!empty($this->orderBy)) {
            $ret["orderBy"] = $this->orderBy;
        }
        
        if (count($this->params) > 0) {
            $ret["params"] = $this->params;
        }
        
        return $ret;
    }
    
}


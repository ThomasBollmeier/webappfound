<?php
namespace tbollmeier\webappfound\db;

class QueryOptions
{
    public $fields;
    public $filter;
    public $orderBy;
    public $limit;
    public $offset;
    public $params;
    
    public function __construct()
    {
        $this->fields = [];
        $this->filter = "";
        $this->orderBy = "";
        $this->limit = -1;
        $this->offset = -1;
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
    
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function setOffset(int $offset)
    {
        $this->offset = $offset;
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
        
        if ($this->limit >= 0) {
            $ret["limit"] = $this->limit;
        }
        
        if ($this->offset >= 0) {
            $ret["offset"] = $this->offset;
        }
        
        if (count($this->params) > 0) {
            $ret["params"] = $this->params;
        }
        
        return $ret;
    }
    
}


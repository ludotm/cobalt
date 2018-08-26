<?php

namespace Core;

use IteratorAggregate;
use ArrayIterator;

class ResultsSet implements IteratorAggregate
{
    protected $db;
    private $results;
    public $table;
    public $count;
    public $joinData;

    public function __construct($db, $table) 
    {
        $this->db = $db;
        $this->table = $table;
        $this->count = 0; 
    }

    public function setResults($results) 
    {
        $this->results = $results;
        $this->count = count($results);
    }

    public function get_entity($i=0) {
        $results = new ArrayIterator( $this->results );
        return $results[$i];
    }

    public function get_first() {
        return $this->get_entity(0);
    }

    public function getIterator() {
        return new ArrayIterator( $this->results );
    }

    public function count()
    {
        return $this->count;
    }

    public function toObject(array $array)
    {
        foreach ($array as $name => $value) {
            $this->set($name, $value);
        }
    }
    
    public function toArray()
    {
        $data = array();
        
        foreach ($this->results as $result) {
            $data []= $result;
        }
        return $data;
        //return array_merge($this->joinData, $this->data);
    }
}
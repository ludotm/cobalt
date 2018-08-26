<?php

namespace Core;

use Core\Service;

class Sql
{
	protected $type;
	protected $id;
	protected $vars;
	protected $table;
	protected $joins;
	protected $cols;
	protected $wheres;
	protected $values;
	protected $orders;
	protected $group;
	protected $offset;
	protected $limit;
	protected $one_result;
	protected $primary_key;

	public $sql;

	protected function reset() 
	{
		$this->type = null;
		$this->id = null;
		$this->vars = array();
		$this->table = null;
		$this->joins = array();
		$this->cols = null;
		$this->wheres = null;
		$this->values = null;
		$this->orders = null;
		$this->group = null;
		$this->offset = null;
		$this->limit = null;
		$this->one_result = false;
		$this->sql = '';
	}

	public function one_result($bool) {
		if ($bool) {
			$this->limit(1);
		}
		$this->one_result = $bool;
	}

	public function get_one_result() {
		return $this->one_result;
	}

	public function select($table) 
	{
		$this->reset();
		$this->type = 'SELECT';
		$this->table = $table;
		return $this;
	}

	public function select_one($table) 
	{
		$this->select($table); 
		$this->one_result(true);
		return $this;
	}

	public function insert($table) 
	{
		$this->reset();
		$this->type = 'INSERT';
		$this->table = $table;
		return $this;
	}

	public function update($table) 
	{
		$this->reset();
		$this->type = 'UPDATE';
		$this->table = $table;
		return $this;
	}

	public function delete($table) 
	{
		$this->reset();
		$this->type = 'DELETE';
		$this->table = $table;
		return $this;
	}

	public function join($table, $field, $field2='', $cols=array(), $type='LEFT') 
	{
		$this->joins []= array('table'=>$table, 'field1'=>$field,'field2'=>( $field2!='' ? $field2:$field ), 'cols'=>$cols, 'type'=>$type);
		return $this;
	}

	public function id($id, $primary_key=null) 
	{	
		$this->id = $id;
		$this->primary_key = !$primary_key ? 'id' : $primary_key;
		$this->one_result(true);
		return $this;
	}

	public function cols($cols) 
	{
		$this->cols = $cols;
		return $this;
	}

	public function where($wheres, $vars=array()) 
	{
		$this->wheres = $wheres;
		if (!empty($vars)) {
			foreach($vars as $key => $value) {
				$this->vars[':'.$key] = $value;
			}
		}
		return $this;
	}

	public function values($values) 
	{
		$this->values = $values;
		return $this;
	}

	public function order($field, $type='DESC') 
	{
		$this->orders []= array('field'=>$field, 'type'=>$type);
		return $this;
	}

	public function group($group) 
	{
		$this->group = $group;
		return $this;
	}

	public function offset($offset) 
	{
		$this->offset = $offset;
		return $this;
	}

	public function limit($limit) 
	{
		$this->limit = $limit;
		return $this;
	}

	protected function parseWheres() 
	{
		$sql = '';
		
		if ($this->id) {
			$sql .= 'WHERE '.$this->primary_key.'=:'.$this->primary_key.' ';
			$this->vars[':'.$this->primary_key] = $this->id;
		
		} else if (is_array($this->wheres) && !empty($this->wheres)) {
			
			foreach ($this->wheres as $key => $value) {
				$sql .= $sql == '' ? 'WHERE ' : 'AND ';
				$sql .= $key.'=:'.$key.' ';
				$this->vars[':'.$key] = $value;
			}

		} else if (!$this->wheres) {
			$sql = '';

		} else if (is_string($this->wheres) && trim($this->wheres) != '') {

			$sql = 'WHERE ' . $this->wheres;
		}
		return $sql;
	}
		
	public function execute() 
	{
		switch ($this->type) {
			
			case 'SELECT':

				$this->sql = 'SELECT ';

				if (!empty($this->cols)) {
					
					$sqlCols = array();

					foreach ($this->cols as $key=>$col) {
						
						if (is_int($key)) {
							$key = $col;
						}

						$temp = explode('-', $key);
						
						if (isset($temp[1])) {
							$sqlCols []=  strtoupper($temp[0]).'(t0.'.$temp[1].') ' . ($key!=$col ? 'AS '.$col : '');

						} else {
							$sqlCols []=  $temp[0]. ' ' . ($key!=$col ? 'AS '.$col : '');
						}
					}

					$this->sql .= implode(',', $sqlCols);

				} else {
					$this->sql .= 't0.*';
				}

				if (!empty($this->joins)) {
					foreach ($this->joins as $key => $join) {

						foreach ($join['cols'] as $k => $v) {

							if (is_int($k)) {
								$k = $v;
							}
							$this->sql .= ', t'.($key+1).'.'.$k.($k!=$v ? ' AS '.$v : '');
						}
					}
				}

				$this->sql .= ' FROM ' . $this->table . ' t0 ';

				if (!empty($this->joins)) {
					foreach ($this->joins as $key => $join) {
						$this->sql .= $join['type'] . ' JOIN ' . $join['table'] . ' AS t'.($key+1).' ON t'.$key.'.'.$join['field1'].'=t'.($key+1).'.'.$join['field2'].' ';
					}
				}

				$this->sql .= $this->parseWheres(); 

				if (!empty($this->orders)) {
					for ($i=0; $i<count($this->orders); $i++) {
						$this->orders[$i] = implode(' ',$this->orders[$i]) ;
					}
					$this->sql .= ' ORDER BY ' . implode(',',$this->orders) ;
				}

				$this->sql .= $this->group ? ' GROUP BY ' . $this->group : '';
				$this->sql .= $this->limit ? ' LIMIT ' . ($this->offset ? $this->offset . ',' : '' ) . $this->limit .' ' : '';
				
				return $this->query($this->sql, $this->vars, $this->table);
				break;


			case 'INSERT':

				$this->sql = 'INSERT INTO ' . $this->table;
				
				if (is_array($this->values)) {

					$cols = $values = '';

					foreach ($this->values as $col => $value) {
						$cols .= ($cols != '' ? ',' : '') . $col;
						$values .= ($values != '' ? ',' : '') .':'.$col;
					}
				}

				$this->sql .= ' ('.$cols.') VALUES ('.$values.')';



				return $this->query($this->sql, $this->values);
				break;


			case 'UPDATE':

				$this->sql = 'UPDATE ' . $this->table . ' SET ';
				
				if (is_array($this->values)) {

					$cols = '';

					foreach ($this->values as $col => $value) {
						$cols .= ($cols != '' ? ', ' : ' ') . $col.'=:'.$col.'' ;
					}
				}
				$this->sql .= $cols . ' ';
				$this->sql .= $this->parseWheres();
	
				return $this->query($this->sql, array_merge($this->vars, $this->values));
				break;


			case 'DELETE':

				$this->sql = 'DELETE FROM ' . $this->table . ' ';
				$this->sql .= $this->parseWheres();

				return $this->query($this->sql, $this->vars);
				break;
		}
	}
}
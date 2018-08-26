<?php

namespace Core;

use Core\Sql;
use Core\Entity;
use Core\Service;

class Db extends Sql
{
	use \Core\Singleton;

	protected $connexion;
	protected $databases;
	public $is_connected = false;

	protected function __construct() 
	{
		$request = Service::Request();

		if ($request->load_db) {

			$this->setDatabase();

			if (!$this->databases) {
				Service::redirect('/install');
			} else {
				$this->setConnexion();

				if (!$this->table_exists('_users')) {
					Service::redirect('/install');
				}
			}
		}
	}

	public function setDatabase() 
	{
		$this->databases = _get('config.db', null);
	}

	public function setConnexion($db='default') 
	{
		if (!array_key_exists($db, $this->databases['dbs'])) {
			Service::error('BDD inconnue dans la liste des BDD');
		}
		$this->connexion = new \PDO(
			"mysql:host=".$this->databases['host'].";dbname=".$this->databases['dbs'][$db], 
			$this->databases['user'], 
			$this->databases['password'],
			array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
		);
		//$this->connexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
		$this->connexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		//$this->connexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$this->is_connected = true;
	}

	public function query_one ($sql, $vars=array(), $table=null) {
		$this->one_result(true);
		return $this->query($sql, $vars, $table);
	}

	public function query_count ($sql, $vars=array(), $table=null) {
		$r = $this->query($sql, $vars, $table);
		return $r ? $r->count() : 0;
	}

	public function table_exists($table)
	{
		$query = $this->connexion->prepare('SHOW TABLES LIKE "'.$table.'"');
		$result = $query->execute();
	    return !$query->fetch() ? false : true ;
	}

	public function classic_query($sql) 
	{
		try{
			$query = $this->connexion->query($sql);

		} catch (\Exception $e) {
			$e->sql = $sql;
			throw $e;
		}
	}
	
	public function query ($sql, $vars=array(), $table=null) 
	{
		if (!$this->is_connected) {
			Service::error("Impossible d\'effectuer une requ&ecirc;te. La base de donn&eacute;e n'est pas connect&eacute;e");
		}

		$type = substr(trim($sql), 0, 3);

		switch ($type) {
			
			case "SEL":

				if (!empty($vars)) {

					try{
						$query = $this->connexion->prepare($sql);
						$query->execute($vars);

					} catch (\Exception $e) {
						$e->sql = $sql;
						$e->vars = $vars;
						throw $e;
					}
					
				} else {

					try{
						$query = $this->connexion->query($sql);

					} catch (\Exception $e) {
						$e->sql = $sql;
						throw $e;
					}
				}
				
				$results = $query->fetchAll(\PDO::FETCH_CLASS, $this->get_fetch_class($table) , array($table));
				$count_results = count($results);

				if ($count_results == 0) {
					$this->reset();
					return null;
				}
				if ($this->get_one_result()) {
					$this->one_result(false);
					$this->reset(); 
					return $results[0];

				} else {
					$resultsSet = new ResultsSet($this, $this->table);
					$resultsSet->setResults($results);
					$resultsSet->count = $count_results;
					$this->reset();
					return $resultsSet;
				}
				
				break;

			case "INS":
				$query = $this->connexion->prepare($sql);
				$query->execute($vars);
				$return = $this->connexion->lastInsertId();
				$this->reset(); 
				return $return;
				break;

			case "UPD":
			case "DEL":
				$query = $this->connexion->prepare($sql);
				$return = $query->execute($vars);
				$this->reset(); 
				return $return;
				break;
		}
	}

	protected function get_fetch_class ($table) 
	{
		if ($table) {
			$model = _model($table);
			
			if ($model) {
				if (isset($model['params']['entity'])) {
					if ($model['params']['entity'] && $model['params']['entity'] != '') {
						return $model['params']['entity'];
					}
				}
			}
		}
		return 'Core\Entity';
	}
}

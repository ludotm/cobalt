<?php

namespace Core;

use Core\Service;

class Entity
{
    protected $db;
    protected $table;
    protected $model;
    public $id;
    protected $primary_key;

    public function __construct($table=null) 
    {
        $this->db = Service::Db();

        if ($table) {
            $this->setModel($table);
        }
    }

    /* --------------------- SET VALUES OR GET ARRAY OF VALUES ----------------------------- */

    public function get($key)
    {
        return $this->{$key};
    }

    public function set($key, $value)
    {
        $this->{$key} = $value;
    }

    // si une requête ne contient qu'un résultat, renvoi une entité au lieu d'un resultSet, 
    // cette function permet de pouvoir faire appel à count() quel que soit le nombre de résultat
    public function count()  
    {
        return 1;
    }

    public function toArray()
    {
        return Tools::toArray($this);
    }

    public function get_table()
    {
        return $this->table;
    }
    /* --------------------- MODEL ----------------------------- */

    public function setModel ($modelOrArray) {

        if (is_array($modelOrArray)) {
            $this->table = null;
            $this->model = array(); 
            if (!array_key_exists('fields', $modelOrArray)) {
                $this->model['params'] = array();
                $this->model['fields'] = $modelOrArray;
            } else {
                $this->model = $modelOrArray;
            }
            
        } else {
            $model = _model($modelOrArray);

            if ($model) {
                $this->table = $modelOrArray;
                $this->primary_key = $model['params']['primary'];
                $this->model = $model;
            }
        }   

        return $this;
    }

    /* --------------------- BIND VALUES OR GET VALUES ----------------------------- */

    public function bind($data) 
    {
        if ($this->model) {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->model['fields'])) {
                    $this->{$key} = $value;

                } else if (array_key_exists('external_fields', $this->model)) {
                    
                    if (array_key_exists($key, $this->model['external_fields'])) {
                        $this->{$key} = $value;
                    }
                }
            }
        } else {
            Service::error("Aucun modèle n'a été affilié à cette entité, impossible d'injecter un tableau de valeur");
        }
        return $this;
    }
    public function bind_from_db($id=null) 
    {
        if ($this->table) {

            if (!$id) {
                if ($this->{$this->primary_key} != 0) {
                    $id = $this->{$this->primary_key};
                } else {
                    Service::error('Function bind_from_db, ID nulle ou absente');
                }
            }
            $values = $this->db->select($this->table)->id($id)->execute();
            $this->bind($values);
        }
    }

    public function get_data($type='all') {

        $data = array();
        
        if ($type == 'all' || $type == 'fields') {

            foreach ($this->model['fields'] as $key => $value) {

                if (isset($this->{$key})) {

                    $data[$key] = $this->{$key};
                }
            }
        }
        
        if ($type == 'all' || $type == 'external_fields') {
            if (array_key_exists('external_fields', $this->model)) {
                
                foreach ($this->model['external_fields'] as $key => $value) {
                    if (isset($this->{$key})) {
                        $data[$key] = $this->{$key};
                    }
                }
            }
        }

        return $data;
    }


    /* --------------------- GET SELECT / RADIO / CHAECKBOX VALUES ----------------------------- */


    public function get_external_values($name) 
    {
        if (isset($this->model['external_fields'][$name])) {

            if (array_key_exists('table', $this->model['external_fields'][$name])) {

                $keys = array_keys($this->model['external_fields'][$name]['table']);

                $self_primary_field = $this->model['params']['primary'];

                $link_table = $this->model['external_fields'][$name]['table'][0];
                $link_self_field = $this->model['external_fields'][$name]['table'][1];
                $link_target_field = $keys[2];

                $target_table = $this->model['external_fields'][$name]['table'][$link_target_field][0];
                $target_primary_field = $this->model['external_fields'][$name]['table'][$link_target_field][1];
                $target_name_field = $this->model['external_fields'][$name]['table'][$link_target_field][2];

                $results = $this->db->query('SELECT t1.* FROM '.$target_table.' t1 LEFT JOIN '.$link_table.' t2 ON t1.'.$target_primary_field.'=t2.'.$link_target_field.' WHERE t2.'.$link_self_field.'="'.$this->{$self_primary_field}.'" ');
                
                $values = array();

                if ($results) {
                    foreach ($results as $result) {
                        $values[$result->{$target_primary_field}] = $result->{$target_name_field};
                    }
                } 
                $this->{$name} = $values;
                return $values;
            }
        }
    }


    public function get_option_value($field) {
        if ($this->{$field} == 0 || $this->{$field} == '-') {
            return '-';
        }
        return $this->model['fields'][$field]['options'][$this->{$field}];
    } 

    public function get_options($field) {

        return $this->model['fields'][$field]['options'];
    }

    public function get_option_value_table($field, $table, $primary_field, $label_field) {

        $result = $this->db->select($table)->id($this->{$primary_field}, $primary_field)->execute();

        if (!$result) {
            return '';
        } else {
            return $result->{$label_field};
        }
    }

    /* --------------------- SET AUTOMATIC VALUES TO SPECIFIC FIELDS ----------------------------- */

    protected function save_automatic_fields() 
    {
        foreach ($this->model['fields'] as $key => $value) {
            
            switch ($this->model['fields'][$key]['type']) {

                case 'ID_BIG_USER':
                    if (array_key_exists('id_big_user', $_SESSION)) {
                        if (!property_exists($this, $key) || $this->{$key} === null) {
                            $this->{$key} = $_SESSION['id_big_user'];
                        }
                    }
                    break;

                case 'DATE_CREATE':
                    if (!$this->{$this->primary_key} || $this->{$this->primary_key} == '') {
                       
                        $this->{$key} = date('Y-m-d H:i:s');

                    } /* else if (array_key_exists('editable', $this->model['fields'][$key])) {
                        $this->{$key} = 
                    }*/
                    break;

                case 'DATE_UPDATE':
                    $this->{$key} = date('Y-m-d H:i:s');
                    break;

                case 'USER_CREATE':
                    if (!$this->{$this->primary_key} || $this->{$this->primary_key} == '') {
                        $session = Service::Session();
                        $this->{$key} = $session->get('id_user');
                    }
                    break;

                case 'USER_UPDATE':
                    $session = Service::Session();
                    $this->{$key} = $session->get('id_user');
                    break; 
            }
        }
    }

    /* --------------------- SAVE EXTERNAL FIELDS ----------------------------- */

    protected function save_external_fields() 
    {
        if (array_key_exists('external_fields', $this->model)) {

            $data = $this->get_data('external_fields');

            foreach ($this->model['external_fields'] as $key => $field) {
                if (array_key_exists($key, $data)) {

                    $table = $field['table'][0];
                    $link_self_field = $field['table'][1];
                    next($field['table']);
                    next($field['table']);
                    $link_target_field = key($field['table']);

                    $self_primary_key = $this->model['params']['primary'];


                    // comparaison des données anciennes et nouvelles : $old_values & $data[$key]

                    $old_values = $this->get_external_values($key);

                    foreach ($old_values as $old_value => $label) { // si des anciennes valeurs ne sont pas trouvées dans les nouvelles, on supprime
                        if(!in_array($old_value, $data[$key])) {
                            $this->db->query('DELETE FROM '.$table.' WHERE '.$link_self_field.'="'.$this->{$self_primary_key}.'" AND '.$link_target_field.'="'.$old_value.'" ');
                        }
                    }
                    foreach ($data[$key] as $value) {

                        if (!array_key_exists($value, $old_values)) { // si la valeur n'est pas déjà enregistrée
                            $this->db->insert($table)->values(array($link_self_field => $this->{$self_primary_key}, $link_target_field => $value))->execute();
                        }
                    }

                }
            }
        }
    }

    /* --------------------- SAVE / DELETE / DELETE CASCADE / RESTORE OR TRASH ----------------------------- */

	public function save() 
	{
        if ($this->table && $this->model) {

            $this->save_automatic_fields();
            $data = $this->get_data('fields');

            if ($this->id) {
                $success = $this->db->update($this->table)->values($data)->id($this->id, $this->primary_key)->execute();
                $this->save_external_fields();
                return $success;

            } else {
                $new_id = $this->db->insert($this->table)->values($data)->execute();
                $this->{$this->primary_key} = $new_id;
                $this->save_external_fields();
                return $new_id;
            }
        } else {
            Service::error("Aucun modèle n'a été affilié à cette entité, impossible de sauvegarder cette entité");
        }
	}

	public function delete() 
	{
        if ($this->id && $this->table) {

            $result = $this->db->delete($this->table)->id($this->id)->execute(); 
 
                if (array_key_exists('delete_cascade', $this->model['params'])) {
                    foreach ($this->model['params']['delete_cascade'] as $table => $field) {              
                        $this->delete_cascade($table, $field);
                    }
                }

            return $result;  
        }  else {
            Service::error("Aucun modèle n'a été affilié à cette entité, impossible de supprimer cette entité");
        }
	}

    public function delete_cascade($table, $field) 
    {
        if ($this->id) {
           
            $elements = $this->db->select($table)->where($field."=:field", array('field' => $this->id))->execute();
 
            if ($elements) {
                foreach ($elements as $element) {
                    $element->delete();
                }
            }
            return true;

        }  else {
            Service::error("Aucun modèle n'a été affilié à cette entité, impossible de supprimer en cascade");
        }
    }

    public function trash() 
    {
        if ($this->id && $this->table) {

            if (array_key_exists('deleted', $this->model['fields'])) {
                return $this->db->update($this->table)->values(array('deleted'=>1))->id($this->id, $this->primary_key)->execute();
            } else {
                Service::error("La table ".$this->table." ne contient pas de champs 'deleted', l'entité ne peut être mise à la corbeille");
            }
        }  else {
            Service::error("Aucun modèle n'a été affilié à cette entité, impossible de mettre cette entité à la corbeille");
        }
    }

    public function restore() 
    {
        if ($this->id && $this->table) {
            if (array_key_exists('deleted', $this->model['fields'])) {
                return $this->db->update($this->table)->values(array('deleted'=>0))->id($this->id, $this->primary_key)->execute();
            } else {
                Service::error("La table ".$this->table." ne contient pas de champs 'deleted', l'entité ne peut être restaurée depuis la corbeille");
            }
        }  else {
            Service::error("Aucun modèle n'a été affilié à cette entité, impossible de restaurer cette entité depuis la corbeille");
        }
    }

    /* --------------------- IMAGES & DOCUMENTS ----------------------------- */

    public function get_image($field) 
    {
        if (array_key_exists($field, $this->model['fields'])) {
            if ($this->model['fields'][$field]['type'] == 'IMAGE') {
                $this->{$field.'_entity'} = $this->db->select('_images')->id($this->{$field})->execute();
                return $this->{$field.'_entity'};
            } else {
                Service::error("function get_image: ce champ n'est pas de type IMAGE");
            }
        } else {
            Service::error("function get_image: champs introuvable dans le modèle");
        }
    }

    public function get_document($field) 
    {
        if (array_key_exists($field, $this->model['fields'])) {
            if ($this->model['fields'][$field]['type'] == 'FILE') {
                $this->{$field.'_entity'} = $this->db->select('_documents')->id($this->{$field})->execute();
                return $this->{$field.'_entity'};
            } else {
                Service::error("function get_image: ce champ n'est pas de type FILE");
            }
        } else {
            Service::error("function get_image: champs introuvable dans le modèle");
        }
    }

}
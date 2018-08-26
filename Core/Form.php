<?php 

namespace Core;

use Core\FormElement;
use Core\Tools;
use Core\Entity;

class Form extends HtmlTag
{
	public $table;
	public $model;
	public $entity_id;

	public $ajax;
	public $elements;
	public $template;
	protected $is_compiled;
	protected $js_validation;
	protected $validation_rules;
	protected $binded;
	protected $count_htlm_blocks;
	protected $displayed_fields;
	protected $file_manager;

	public function __construct($model='', $entity_id=0) 
	{
		$this->elements = array();
		$this->method = 'POST';
		$this->js_validation = true;
		$this->ajax = false;
		$this->binded = false;
		$this->is_compiled = false;
		$this->table = null;
		$this->validation_rules = array();
		$this->count_htlm_blocks = 0;
		$this->set_template ('bootstrap');

		$this->entity_id = $entity_id;

		if ($model!='') {
			$this->setModel($model);
		}

		$this->displayed_fields = array('ID', 'HIDDEN', 'VARCHAR', 'PASSWORD', 'EMAIL', 'URL', 
			'TEXT', 'SMS', 'INT', 'SELECT', 'SELECT_ID', 'CHECKBOX', 'RADIO', 'BOOL', 'TEXT_EDITOR', 'SEARCH' ,'DATE', 
			'DATETIME', 'COLOR', 'FILE', 'IMAGE', 'SUBMIT');
	}

	/* --------------------- TEMPLATES ----------------------------- */

	public function set_template ($template)
	{
		if (is_array($template)) {
			$this->template = $template;
		} else if (is_string($template)) {
			$this->{'template_'.$template}();
		}
		
		return $this;
	}

	protected function template_table () {
		$this->template = array(
			'header' => '<table class="form" border="0" width="100%">'."\n\t",
			'body' => '<tr><td>[label]</td><td>[element]</td></tr>'."\n\t",
			'footer' => '</table>'."\n\t"
		);
	}

	protected function template_bootstrap () {
		$this->template = array(
			'header' => ''."\n\t",
			'body' => '<div class="row"><div class="form-group"><label for="" class="col-md-3 control-label">[label]</label><div class="col-md-9">[element]</div><div class="clear"></div></div></div>'."\n\t",
			'footer' => ''."\n\t"
		);
	}

	protected function template_horizontal () {
		$this->template = array(
			'header' => '<div class="form-inline">'."\n\t",
			'body' => '<label for="" class="control-label">[label]</label>[element]'."\n\t",
			'footer' => '</div>'."\n\t"
		);
	}

	/* --------------------- ELEMENTS ----------------------------- */

	public function addElement ($type, $label, $name, $value=null)
	{
		switch ($type) {

			case 'FILE':
			case 'IMAGE':
				$this->enctype('multipart/form-data');
				break;
		}

		return $this->elements[$name] = new FormElement ($type, $label, $name, $value);
	}

	public function add_html_before ($name, $html)
	{
		$this->count_htlm_blocks++;
		$new_key = 'html_'.$this->count_htlm_blocks;
		$offset = array_search($name, array_keys($this->elements));

		$this->elements = array_merge(
            array_slice($this->elements, 0, $offset),
            array($new_key => $html),
            array_slice($this->elements, $offset, null)
        );

		return $this;
	}
	
	public function add_submit ($label="Envoyer")
	{
		return $this->elements['submit'] = new FormElement ('SUBMIT', '', $label);
	}

	public function get ($name)
	{
		return $this->elements[$name];
	}

	public function remove ($key)
	{
		if (is_array($key)) {
			foreach ($key as $element) {
				unset($this->elements[$element]);
			}
		} else {
			unset($this->elements[$key]);
		}

		return $this;
	}

	public function remove_all_except ($key)
	{
		if (is_array($key)) {
			foreach ($this->elements as $current_key => $element) {
				if (!in_array($current_key, $key) && $element->type != 'ID' && $element->type != 'ID_BIG_USER') {
					unset($this->elements[$current_key]);
				}
			}
		} else {
			foreach ($this->elements as $current_key => $element) {
				if ($current_key != $key && $element->type != 'ID' && $element->type != 'ID_BIG_USER') {
					unset($this->elements[$current_key]);
				}
			}
		}

		return $this;
	}
	
	public function disable()
	{
		foreach ($this->elements as $key => $element) {
			$this->elements[$key]->disable();
		}

		return $this;
	}

	/* --------------------- MODEL ----------------------------- */

	public function setModel ($modelOrArray) {

		if (is_array($modelOrArray)) {
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
				$this->model = $model;
			}
		}	
		return $this;
	}

	public function entity_id ($entity_id) {
		$this->entity_id = $entity_id;
		return $this;
	}

	protected function get_file_manager () {
		if (!$this->file_manager) {
			$this->file_manager = Service::FileManager();
		}
		return $this->file_manager;
	}

	/* --------------------- ATRIBUTES ALLIAS & DIVERS ----------------------------- */

	public function ajax ($transition='none') 
	{
		$this->ajax = $transition;
		return $this;
	}

	public function reset() 
	{
		foreach ($this->elements as $field => $element) {
			$this->elements[$field]->value = null;
		}
	}

	public function name ($name) {
		$this->attr('name', $name);
		return $this;
	}
	public function enctype ($enctype) {
		$this->attr('enctype', $enctype);
		return $this;
	}
	public function id ($id) {
		$this->attr('id', $id);
		return $this;
	}
	public function action ($action) {
		$this->attr('action', $action);
		return $this;
	}
	public function method ($method) {
		$this->attr('method', $method);
		return $this;
	}


	/* --------------------- BINDERS, GETTERS & FACTORIZATION ----------------------------- */

	// CREER TOUS LES CHAMPS A PARTIR DU MODEL
	public function factorize ($model=null)
	{
		if (!$this->model && $model) {
			$this->setModel($model);	
		}

		foreach ($this->model['fields'] as $field => $data) {

			$this->build_element_from_model ($field,  $data);
		}

		if (array_key_exists('external_fields', $this->model)) {

			foreach ($this->model['external_fields'] as $field => $data) {

				$this->build_element_from_model ($field,  $data);
				$this->get($field)->is_extrafield = true;
			}
		}
	}

	public function build_element_from_model ($field,  $data) {

		if (in_array($data['type'], $this->displayed_fields) && (array_key_exists('display', $data) ? $data['display'] : true)) {

			$element = $this->addElement($data['type'], (array_key_exists('label', $data) ? $data['label'] : '') , $field, (array_key_exists('default', $data) ? $data['default'] : null));

			if ($data['type'] == 'BOOL' && !array_key_exists('options', $data)) {
				$data['options'] = array(1=>'');
			}
			if ($data['type'] == 'ID') {
				$data['value'] = $this->entity_id;
			}
			if (array_key_exists('placeholder', $data)) {
				$element->placeholder($data['placeholder']);
			}
			if (array_key_exists('options', $data)) {
				$element->options($data['options']);
			}
			if (array_key_exists('empty_option', $data)) {
				$element->empty_options($data['empty_option']);
			}
			if (array_key_exists('required', $data) && $data['required']) {
				$element->required();
						
				if ($data['type'] == 'PASSWORD' && $this->entity_id != 0) {
					$element->required(false);
				}
			}
			if (array_key_exists('max', $data)) {
				if ($data['type'] == 'INT') {
					$element->attr('max', $data['max']);
				} else {
					$element->attr('maxlength', $data['max']);
				}
			}
			if (array_key_exists('min', $data)) {
						
				if ($data['type'] == 'INT') {
					$element->attr('min', $data['min']);
				} else {
					$element->attr('minlength', $data['min']);
				}

				if ($data['type'] == 'PASSWORD' && $this->entity_id != 0) {
					$element->remove_attr('minlength');
				}
			}
			if (array_key_exists('skin', $data)) {
				$element->skin($data['skin']);
			}
			if (array_key_exists('inline', $data)) {
				$element->inline($data['inline']);
			}
			if (array_key_exists('value', $data)) {
				$element->value = $data['value'];
			}
			if (array_key_exists('remote_validation', $data)) {
				$this->remote_vaidation ($field, $data['remote_validation']);
			}

			if (array_key_exists('confirmation', $data)) {
				$confirm_element = clone $element;
				$confirm_element->name = $field.'_confirmation'; 
				$confirm_element->label = $confirm_element->label != '' ? $confirm_element->label.' ('.translate('confirmation').')' : '';
				$confirm_element->placeholder = $confirm_element->placeholder != '' ? $confirm_element->placeholder.' ('.translate('confirmation').')' : '';
				$confirm_element->value = ''; 

				$this->elements[$field.'_confirmation'] = $confirm_element;
				$this->validation_same_field ($field, $field.'_confirmation');
			}

		} else if ($data['type'] == 'DATE_CREATE' || $data['type'] == 'DATE_UPDATE') {

			if (array_key_exists('display', $data)) {
				if ($data['display'] == true) {
					$element = $this->addElement('DATE', (array_key_exists('label', $data) ? $data['label'] : '') , $field, (array_key_exists('default', $data) ? $data['default'] : null));
				}
			}
		}
	}

	// INJECTE DES VALEURS AUX CHAMPS DU FORM A PARTIR D UN TABLEAU
	public function bind($data) 
	{
		foreach ($data as $key => $value) {
			if (array_key_exists($key, $this->elements)) {
				$this->elements[$key]->value = $value;	
			}
		}

		foreach ($this->elements as $key => $element) {
			
			if (!is_string($element)) {

				if ($element->type == 'CHECKBOX' || $element->type == 'BOOL') {
					
					if (!array_key_exists($key, $data) && $this->elements[$key]->value === null) {
						
						if (count($this->elements[$key]->options) > 1) {
							$this->elements[$key]->value = array();
						} else {
							$this->elements[$key]->value = 0;
						}
						
					}/* else {
						if (count($this->elements[$key]->options) > 1) {
							
							foreach ($this->elements[$key]->options as $val => $label) {
								if (!in_array($val, $data[$key])) {

								}
							}
						}
					}*/				
				} else if ($element->type == 'DATE' || $element->type == 'DATETIME') {

					if ($element->type == 'DATE') {
						$this->elements[$key]->value = explode(' ', $this->elements[$key]->value)[0];
					}

					$this->elements[$key]->value = implode('-',array_reverse(explode('/', $this->elements[$key]->value)));
					
					if ($element->type == 'DATETIME' && array_key_exists($key.'_hour', $data) && array_key_exists($key.'_minute', $data)) {
						$this->elements[$key]->value .= ' ' . $data->{$key.'_hour'} . ':' . $data->{$key.'_minute'} . ':00' ;
					}

				}
			}
		}
		$this->binded = true;
	}

	// RECUPERE LES VALEURS DE TOUS LES CHAMPS EN RETOURNANT UN TABLEAU
	public function get_data() {

		$data = array();
		
		foreach ($this->elements as $field => $element) {
			if (isset($this->elements[$field]->value)) {
				$data[$field] = $this->elements[$field]->value;
			} else {
				$data[$field] = null;
			}
		}
		return $data;
	}

	// RECUPERE L'ENTITE CORRESPONDANTE AU MODEL AVEC VALEURS DU FORMULAIRE SOUS FORME D OBJET 
	public function get_entity() {

		if (!$this->table) {
			Service::error("Function Form->get_entity(), aucune table n'a été définie pour ce formulaire");
		}

		$entity = new Entity($this->table);
		$entity->bind($this->get_data());

		return $entity;
	}

	/* --------------------- VALIDATION ----------------------------- */

	public function js_validation ($bool)
	{
		$this->js_validation = $bool;
		return $this;
	}

	public function js_validate() {

		if ($this->js_validation) {

			if (!$this->get_attr('id')) {
				$this->attr('id', 'form'.rand(1, 10000));
			}

			$rules = '';
			
			if (!empty($this->validation_rules)) {
				$rules = '{rules:{';  
				foreach ($this->validation_rules as $field => $rule) {
					$rules .= $field.':'.$rule.',';
				}
				$rules .= '}}';
			}

			return '<script>$(document).ready(function(){$("#'.$this->get_attr('id').'").validate('.$rules.');});</script>';
		}
	}

	public function validation_rules ($rule=array())
	{	
		if (is_array($rule)) {
			foreach ($rule as $key => $value) {
				$this->validation_rules[$key] = $value;
			}
		}
		return $this;
	}

	public function validation_same_field ($field1, $field2)
	{
		$this->validation_rules (array(
			$field2 => '{equalTo: "input[name='.$field1.']"}',
		));
		return $this;
	}

	public function remote_vaidation ($field, $message)
	{
		$this->elements[$field]->attr('data-msg-remote', $message);

		$this->validation_rules (array(
			$field => '{remote: {url:"'.REMOTE_VALIDATION_URL.'", type:"post", data:{id:"'.$this->entity_id.'", field:"'.$field.'", table:"'.$this->table.'"} }}',
		));
		return $this;
	}

	public function is_valid() {
		foreach ($this->elements as $element) {
			if (!is_string($element)) {
				if (!is_null($element->error)) {
					return false;
				}
			}
		}
		return true;
	}

	public function validate ($model=null)
	{
		if (!$this->model) {
			if ($model) {
				$this->setModel($model);
			} else {
				Service::error("Ce formulaire n'a pas de modèle, impossible de valider les données");
			}
		}

		if (!$this->binded) {
			Service::error("Ce formulaire doit être bindé avant de tenter de valider ses données");
		}

		foreach ($this->model['fields'] as $field => $data) {

			if ($field != 'params' && array_key_exists($field, $this->elements)) {
				
				// VALIDATIONS

				if (array_key_exists('required', $data)) { // && ($data['type'] == 'PASSWORD' && !$this->entity_id)

					if ($data['required']) {

						if ($this->elements[$field]->value == '' || !$this->elements[$field]->value) {
							$this->get($field)->error(translate('Ce champs est obligatoire'));
						}
						if ($data['type'] == 'PASSWORD' && $this->entity_id != 0) {
							$this->get($field)->error(null);
						}
					}
				}

				if (array_key_exists('max', $data)) {

					if (strlen($this->elements[$field]->value) > $data['max']) {
						$this->get($field)->error(translate('Ce champs contient trop de caractères'));
					}
				}

				if (array_key_exists('min', $data)) {

					if (strlen($this->elements[$field]->value) < $data['min']) {
						$this->get($field)->error(translate('Ce champs ne contient pas assez de caractères'));
					}
					if ($data['type'] == 'PASSWORD' && $this->entity_id != 0) {
						$this->get($field)->error(null);
					}
				}

				if (array_key_exists('confirmation', $data)) {

					if ($this->elements[$field]->value != $this->elements[$field.'_confirmation']->value) {
						$this->get($field)->error(translate('Les deux champs doivent correspondre.'));
					}
				}

				if (array_key_exists('remote_validation', $data) && $this->elements[$field]->value != '') {

					$db = Service::Db();
					$primary_key = _get('models.'.$this->table.'.params.primary');
					$result = $db->query_one('SELECT * FROM '.$this->table.' WHERE '.$field.'=:'.$field.' ', array($field => $this->elements[$field]->value));

					if ($result && $result->{$primary_key} != $this->entity_id) {
						$this->get($field)->error($data['remote_validation']);
					}
				}

				switch ($data['type']) {

					case 'EMAIL':
						if (!Tools::is_email($this->elements[$field]->value) && $this->elements[$field]->value != '') {
							$this->get($field)->error(translate('Ce champs doit être une adresse Email valide'));
						}	
						break;

					case 'URL':
						if (!Tools::is_url($this->elements[$field]->value) && $this->elements[$field]->value != '') {
							$this->get($field)->error(translate('Ce champs doit être une adresse Email valide'));
						}	
						break;

					case 'INT':

						if (!is_numeric($this->elements[$field]->value) && $this->elements[$field]->value != '') {
							$this->get($field)->error(translate('Ce champs doit être un nombre valide'));
						}	
						break;

					case 'DATE':
					case 'DATETIME':

						if (!strtotime($this->elements[$field]->value) && $this->elements[$field]->value != '0000-00-00' && $this->elements[$field]->value != '0000-00-00 00:00:00'  && $this->elements[$field]->value != '') {
								$this->get($field)->error('Ce champs doit correspondre à une date valide');
						}
						break;
				}

				// FILTRES

				if (array_key_exists('filter', $data)) {

					switch ($data['filter']) {

						case 'doublon':
							
							break;

						case 'delete_whitespace':

							$this->elements[$field]->value = str_replace(' ', '', $this->elements[$field]->value);
							break;

						case 'trim':

							$this->elements[$field]->value = trim($this->elements[$field]->value);
							break;

						case 'HtmlEntities':
							
							$this->elements[$field]->value = htmlentities($this->elements[$field]->value);
							break;
						
						case 'quote':
							
							break;

						case 'uppercase':

							$this->elements[$field]->value = strtoupper($this->elements[$field]->value);
							break;

						case 'lowercase':

							$this->elements[$field]->value = strtolower($this->elements[$field]->value);
							break;

						case 'ucfirst': // premier caractère en majuscule

							$this->elements[$field]->value = ucfirst($this->elements[$field]->value);
							break;
					}
				}
			}
		}

		// SI LE FORMULAIRE EST VALIDE, ON S'OCCUPE DES EVANTUELS UPLOAD DE FICHIER
		if ($this->is_valid()) {
			foreach ($this->model['fields'] as $field => $data) {
				if ($data['type'] == 'IMAGE' || $data['type'] == 'FILE') {

					if (!array_key_exists('file_type', $data)) {
						Service::error("file_type non précisé dans le modèle pour le champ ".$field);
					}
					
					$this->get_file_manager();	
					$file = $this->file_manager->upload($field, $data['file_type']);

					if (is_string($file)) {
						$this->get($field)->error($file);

					} else if ($file) {
						$file->save_and_format();
						$this->get($field)->value = $file->id;
					}
				}
			}
		}

		return $this->is_valid();
	}

	/* --------------------- COMPILE & DRAWING ----------------------------- */

	public function compile ()
	{
		if (!$this->is_compiled) {

			foreach ($this->elements as $element) {
				if (!is_string($element)) {
					$element->compile();
				}
			}
			$this->is_compiled = true;
		} 
	}
	
	public function open_tag ()
	{
		$this->compile();
		if (!$this->get_attr('id')) {
			$this->attr('id', 'form'.rand(1, 10000));
		}
		if (!$this->get_attr('method')) {
			$this->attr('method', 'POST');
		}

		return $this->js_validate().'<form '.$this->get_attrs().' '.(!$this->ajax ? '':'data-ajax-form="'.$this->ajax.'"').'>';
	}

	public function draw ($width=null)
	{	
		$this->compile();
		
		$html = '';

		if ($width) {
			$html .= '<div style="max-width:'.$width.';">';
		}

		$html .= $this->open_tag();
		$html .= $this->template['header'];

		foreach ($this->elements as $element) {
			if (is_string($element)) {
				$html .= $element;
			} else if ($element->type == 'HIDDEN') {
				$html .= str_replace(array('[label]', '[element]'), array('', $element->html), $this->template['body']);
			} else {
				$html .= str_replace(array('[label]', '[element]'), array($element->label, $element->html), $this->template['body']);
			}
		}
		$html .= $this->template['footer'];		
		$html .= '</form>';

		if ($width) {
			$html .= '</div>';
		}

		return $html;
	}
}

?>
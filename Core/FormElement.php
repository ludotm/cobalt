<?php 

namespace Core;

use Core\Service;

class FormElement extends HtmlTag
{
	public $type;
	public $name;
	public $label;
	public $error;
	public $options;
	public $emptyOption;
	public $value;
	public $required;
	public $skin;
	public $placeholder;
	public $html;
	public $is_extrafield;
	protected $db;
	
	public function __construct ($type, $label, $name, $value=null)
	{
		$this->type = $type;
		$this->label = $label;
		$this->name = $name;
		$this->value = $value;
		$this->required = false;
		$this->options = array();
		$this->skin = null;
		$this->inline = true;
		$this->emptyOption = array('','-');
		$this->placeholder = '';
		$this->error = null;
		$this->is_extrafield = false;

		if ($type == 'TEXT') {
			$this->attr('cols',50)->attr('rows',5);
		}
	}
	
	/* --------------------- PARAMS ------------------------------ */

	public function skin ($skin='') 
	{
		$this->skin = $skin;
		return $this;
	}

	public function required ($required=true) 
	{
		if ($required) {
			$this->attr('required', 'required');
		} else {
			$this->remove_attr('required');
		}
		return $this;
	}

	public function placeholder ($placeholder) 
	{
		$this->attr('placeholder', $placeholder);
		return $this;
	}

	public function inline ($bool) 
	{
		$this->inline = $bool;
		return $this;
	}

	public function label ($label) 
	{
		$this->label = $label;
		return $this;
	}

	public function disable () 
	{
		$this->attr('disabled', 'disabled');
		return $this;
	}

	public function error ($msg) 
	{
		$this->error = $msg;
		return $this;
	}
	
	/* --------------------- SET FIELD OPTIONS ------------------------------ */

	protected function getDb() {
		if (!$this->db) {
			$this->db = Service::Db();
		}
	}
	public function options_table ($table, $field1, $field2) 
	{
		$sql = "SELECT ".$field1.", ".$field2." FROM ".$table;
		return $this->options_query ($sql);
	}
	public function options_query ($query) 
	{
		$this->getDb();
		$options = $this->db->query($query);
		return $this->options ($options);
	}
	public function options ($options) 
	{
		if ($options) {
			foreach ($options as $key => $value) {

				if (is_object($value)) {
					$temp = $value->toArray();
					$this->options[array_values($temp)[0]] = array_values($temp)[1];
				} else {
					$this->options[$key] = $value;
				}
			}
		}
		return $this;
	}
	public function empty_options ($emptyOption) {
		$this->emptyOption = $emptyOption;
		return $this;
	}

	/* --------------------- SET GET VALUE & ALLIAS ------------------------------ */

	public function get_value() {
		return $this->value;
	}

	public function value($value) {
		$this->value = $value;
		return $this;
	}
	public function values ($value) {return $this->value($value);}
	public function set_value ($value) {return $this->value($value);}

	/* --------------------- GETTERS ------------------------------ */

	public function get_label() {
		return $this->label;
	}

	/* --------------------- COMPILE & DRAWING ------------------------------ */

	public function compile ()
	{
		switch ($this->type) {

			case 'HIDDEN' : 
			case 'ID' : 
				$this->html = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '" ' . $this->get_attrs() . '>';
				break;
			
			case 'VARCHAR' : 
				$this->html = '<input type="text" name="' . $this->name . '" value="' . $this->value . '" ' . $this->get_attrs() . '>';
				break;
			
			case 'EMAIL' : 
				$this->html = '<input type="email" name="' . $this->name . '" value="' . $this->value . '" ' . $this->get_attrs() . '>';
				break;
			
			case 'URL' : 
				$this->html = '<input type="url" name="' . $this->name . '" value="' . $this->value . '" ' . $this->get_attrs() . '>';
				break;

			case 'SEARCH' : 
				$this->html = '<input type="search" name="' . $this->name . '" value="' . $this->value . '" ' . $this->get_attrs() . '>';
				break;

			case 'COLOR' : 
				$this->html = '<input type="text" name="' . $this->name . '" value="' . $this->value . '" ' . $this->get_attrs() . '>';
				$this->html .= '<script>$(function(){$(\'input[name="'.$this->name.'"]\').minicolors();});</script>';
				break;

			case 'INT' : 
				$this->html = '<input type="number" name="' . $this->name . '" value="' . $this->value . '" ' . $this->get_attrs() . '>';
				break;

			case 'DATE' : 
				//$this->addClass('form-control');
				$this->value = $this->value == '0000-00-00' ? '' : $this->value;
				$date = implode('/',array_reverse(explode('-', $this->value)));

				$this->html = '';
				//$this->html = '<div class="input-group date">';
				$this->html .= '<input type="text" name="' . $this->name . '" value="' . $date . '" ' . $this->get_attrs() . '>';
				//$this->html .= '<span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span></div>';
				$this->html .= $this->launch_date_picker();
				break;

			case 'DATETIME' : 
				//$this->addClass('form-control');
				$this->value = $this->value == '' ? '0000-00-00 00:00:00' : $this->value ;

				$datetime = explode(' ', $this->value);
				$datetime[0] = $datetime[0] == '0000-00-00' ? '' : $datetime[0];
				$date = implode('/',array_reverse(explode('-', $datetime[0])));
				$time = explode(':', $datetime[1]);

				$this->html = '';
				//$this->html = '<div class="input-group date">';
				$this->html .= '<div class="datetime">';	
				$this->html .= '<input type="text" name="' . $this->name . '" value="' . $date . '" ' . $this->get_attrs() . '>';
				$this->html .= '<select name="' . $this->name . '_hour" ' . $this->get_attrs() . '>';
				
				for ($i=0; $i<24;$i++) {
					$temp_val = $i<10 ? '0'.$i : $i;
					$this->html .= '<option value="'.$temp_val.'" '.($temp_val == $time[0] ? 'SELECTED':'').'>'.$temp_val.'</option>';
				}
				
				$this->html .= '</select>';
				$this->html .= 'H';
				$this->html .= '<select name="' . $this->name . '_minute" ' . $this->get_attrs() . '>';
				
				for ($i=0; $i<60;$i+=5) {
					$temp_val = $i<10 ? '0'.$i : $i;
					$this->html .= '<option value="'.$temp_val.'" '.($temp_val == $time[1] ? 'SELECTED':'').'>'.$temp_val.'</option>';
				}
				$this->html .= '</select>';
				$this->html .= '</div>';
				//$this->html .= '<span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span></div>';
				$this->html .= $this->launch_date_picker();
				break;

			case 'PASSWORD' :
				$this->html = '<input type="password" name="' . $this->name . '" ' . $this->get_attrs() . '>';
				break;

			case 'IMAGE' : 
				$this->html = '';
				
				$this->html .= '<div class="upload-block">';

				if ($this->value != 0) {

					$this->html .= '<div class="pull-left upload-thumb">';

					$this->getDb();
					$image = $this->db->select("_images")->id($this->value)->execute();
					if (!$image) {
						$this->value = 0;
					} else {
						$this->html .= '<a href="'.$image->get_src_url().'" target="_blank">'.$image->show('media_manager_thumb').'</a>';
					}	
					$this->html .= '</div>';
				}
				
				$this->html .= '<div class="upload-button"><span>'.($this->value != 0 ? 'Modifier le fichier' : 'Séléctionner un fichier').'</span><br>';
				$this->html .= '<input type="text" disabled=disabled />';
				$this->html .= '<input type="hidden" name="' . $this->name . '" value="'.$this->value.'">';
				$this->html .= '<input type="file" onChange="javascript:$(this).parent().find(\'input[type=text]\').val($(this).val().substring(12));" name="' . $this->name . '_file" ' . $this->get_attrs() . '>';
				if ($this->value != 0) {
					$this->html .= '<button class="btn btn-danger" onClick="javascript:$(this).parent().parent().find(\'img\').remove();$(this).parent().find(\'input[type=hidden]\').val(0);$(this).parent().find(\'span\').html(\'Séléctionner un fichier\');$(this).remove();return false;">Supprimer le fichier</button>';
				}
				$this->html .= '<div class="clear"></div>';
				$this->html .= '</div></div>';
				break;

			case 'FILE' : 
				$this->html = '<input type="hidden" name="' . $this->name . '" value="'.$this->value.'">';
				$this->html = '<input type="file" name="' . $this->name . '_file" ' . $this->get_attrs() . '>';
				break;
			
			case 'SUBMIT' : 
				$this->html = '<input type="submit" value="' . $this->name . '" ' . $this->get_attrs() . '>';
				break;

			case 'TEXT' : 
				$this->html = '<textarea name="' . $this->name . '" ' . $this->get_attrs() . '>' . $this->value . '</textarea>';
				break;
			
			case 'SMS' : 
				if (!$this->get_attr('rows')) {
					$this->attr('rows', 6);
				}
				$this->html = '<textarea name="' . $this->name . '" ' . $this->get_attrs() . '>' . $this->value . '</textarea>';
				$this->html .= '<br><div id="counter_'.$this->name.'"></div>';
				$this->html .= '<script>sms_field("'.$this->name.'");</script>';
				break;

			case 'TEXT_EDITOR' : 
				$locale = Service::get_lang();
            	$this->html = '<div name="' . $this->name . '" ' . $this->get_attrs() . ' >' . $this->value . '</div>';
            	$this->html .= '<textarea name="' . $this->name . '" style="display:none;">' . $this->value . '</textarea>';
				$this->html .= '<script>$(\'div[name="'.$this->name.'"]\').summernote({
					minHeight: 200, 
					height:300, 
					lang: "'.strtolower($locale).'-'.strtoupper($locale).'", 

					onInit: function() {
						$(this).closest("form").on("submit", function(){ $(\'textarea[name="'.$this->name.'"]\').val($(\'div[name="'.$this->name.'"]\').code());});     
					},
					toolbar : [
						["style", ["style", "clear"]],
   						["style2", ["bold", "italic", "underline"]],
					    ["font", ["fontsize"]],
					    ["height", ["height"]],
					    ["color", ["color"]],
					    ["para", ["ul", "ol", "paragraph"]],
					    ["insert", ['.(isset($this->no_popup) ?'':'"link", "video", ').' "table", "hr" ]], 
					    ["command", ["fullscreen", "codeview"]],
					    ["command2", [ "undo", "redo"]],
					    
   					],
   					disableDragAndDrop: true,

			});</script>'; //, "picture"
				break;

			case 'SELECT' :
			case 'SELECT_ID' :  

				$this->html = '<select name="' . $this->name . '" ' . $this->get_attrs() . ' >';
				
				if (!empty($this->emptyOption)) {
					$this->html .= '<option value="'.$this->emptyOption[0].'">'.$this->emptyOption[1].'</option>';
				}

				foreach ($this->options as $value => $label) {
					$this->html .= '<option value="'.$value.'" '.($this->value == $value ? 'SELECTED':'').'>'.$label.'</option>';
				}
				$this->html .= '</select>';
				break;
			
			case 'CHECKBOX' : 
			case 'BOOL' : 
				$this->html = '';

				$element_values = $this->value;

				// converti les valeurs en tableau séquenciel si c'est un tableau associatif
				if (is_array($this->value)) {
					if (array_keys($this->value) !== range(0, count($this->value) - 1)) { // si c'est un tableau associatif
						$element_values = array();
						foreach($this->value as $value_key => $value_name) {
							$element_values []= $value_key;
						}
					}
				}

				$j = 1;
				$this->html = '<div class="checkbox-container '.($this->skin ? 'checkbox-'.$this->skin : '').'">';
				foreach ($this->options as $value => $label) {  

					$checked = (is_array($element_values) ? (in_array($value, $element_values) ? ' CHECKED':'') : ($element_values == $value ? ' CHECKED':''));
					$this->html .= '<input type="checkbox" id="' . $this->name .'_'. $j .'" name="' . $this->name . (count($this->options) > 1 ? '[]' : '') .'" value="' . $value . '" ' . $this->get_attrs() . $checked .' > ';
					$this->html .= '<label for="' . $this->name .'_'. $j .'">' . ($this->skin == 'switch' ? '<span class="ui"></span>' : '') . $label . '</label> ';
					if (!$this->inline) {
						$this->html .= '<br />';
					}
					$j++;
				}
				$this->html .= '</div>';
				break;

			
			case 'RADIO' :
				$this->html = '';
				$j = 1;
				$this->html = '<div class="radio-container '.($this->skin ? 'radio-'.$this->skin : '').'">';
				foreach ($this->options as $value => $label) {  
					$checked = $this->value == $value ? ' CHECKED':'';
					$this->html .= '<input type="radio" id="' . $this->name .'_'. $j .'" name="' . $this->name .'" value="' . $value . '" ' . $this->get_attrs() . $checked .' > <label for="' . $this->name .'_'. $j .'">' . $label . '</label> ';
					if (!$this->inline) {
						$this->html .= '<br />';
					}
					$j++;
				}
				$this->html .= '</div>';
				break;
		}

		$this->html .= "\n\t";

		if ($this->error) {
			$this->html .= '<label id="'.$this->name.'-error" class="error" for="'.$this->name.'">'.$this->error.'</label>';
		}
	}
	
	protected function launch_date_picker() {

		$default_dp_options = array(
			'format' => 'dd/mm/yyyy',
			'language' => 'fr',
			'autoclose' => 'true',
			//'startDate'=> '0', //>> interdit la séléction de jours passé
			//startDate: '-2m', //>> commence la séléction 2 mois avant le joru en cours
    		//endDate: '+2d', //>> interdit la séléction de jours futurs, au de la de 2 jours
		);
		$dp_options = array_merge($default_dp_options, $this->options);

		$dp_js_options = '{';
		foreach ($dp_options as $option => $value) {
			$dp_js_options .= ($dp_js_options != '{' ? ',' : '' ) .$option.':"'.$value.'"';
		}
		$dp_js_options .= '}';

		return '<script>if ($.fn.datepicker) {$(\'input[name="'.$this->name.'"]\').datepicker('.$dp_js_options.');}</script>';
	}

	public function draw ()
	{
		$this->compile();
		return $this->html;
	}
	
}

?>
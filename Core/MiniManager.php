<?php

namespace Core;

use Core\Service;

class MiniManager
{
    protected $db;
    protected $options;

    public function __construct($modelOrOptions) 
    {   
        /*
        options list :
        
        table
        fields
        url 
        
        zone (auto)
        controller (auto)           

        permission (optionnel) 
        
        selector (optionnel) > défini si les items peuvent être séléctionnés

        name (optionnel) default :table name
        width  (optionnel) default :600px
        primary_key (optionnel) default :id
        sql (optionnel) default : SELECT * FROM $table
        */

        $this->db = Service::Db();

        if (is_array($modelOrOptions)) {

            if (!array_key_exists('table', $modelOrOptions) || !array_key_exists('fields', $modelOrOptions) || !array_key_exists('zone', $modelOrOptions) || !array_key_exists('controller', $modelOrOptions)) {
                Service::error("Class MiniManager : les options 'zone', 'controller', 'table' et 'fields' sont obligatoires");
            }

            $this->options = $modelOrOptions;
        } else {
            Service::error('Class MiniManager : le doit être un array');
        }

        if (!array_key_exists('primary_key', $this->options)) {
            $this->set('primary_key', 'id');
        }
        if (!array_key_exists('width', $this->options)) {
            $this->set('width', '600px');
        }
        if (!array_key_exists('name', $this->options)) {
            $this->set('name', $this->get('table'));
        }
        if (!array_key_exists('sql', $this->options)) {
            $this->set('sql', 'SELECT * FROM '.$this->get('table'));
        }
        if (!array_key_exists('selector', $this->options)) {
            $this->set('selector', false);
        }
        $this->set('url', '/mini-manager/'.$this->get('zone').'/'.$this->get('controller').'/'.$this->get('name'));

        foreach ($this->get('fields') as $key => $field) {

            if ($field['type'] == "SELECT" || $field['type'] == "SELECT_ID") {
                if (!array_key_exists('options', $field) && array_key_exists('sql', $field)) {
                    $results = $this->db->query($field['sql']);
                    if ($results) {

                        $this->options['fields'][$key]['options'] = array();

                        foreach ($results as $result) {
                            
                            $myArray = get_object_vars($result);
                            $arrayKeys = array_keys($myArray);
                            $this->options['fields'][$key]['options'][$myArray[$arrayKeys[0]]] = $myArray[$arrayKeys[1]];
                        }
                    }
                }
            }
        }
    }

    public function get($option_key)
    {
        return array_key_exists($option_key, $this->options) ? $this->options[$option_key] : null;
    }

    public function set($option_key, $value)
    {
        $this->options[$option_key] = $value;
    }

    public function draw()
    {
        
        $items = $this->db->query($this->get('sql'));

        ob_start(); 
        ?>

        <div class="mini_manager" data-url="<?= $this->get('url') ?>" id="<?= $this->get('name') ?>_manager" style="width:<?= $this->get('width') ?>;">
            
            <form class="add-action">

                <?php
                    $hidden_fields = '';
                    $visible_fields = '';

                    $td_width = 'style="width:'.round(100/(count($this->get('fields')) + 1)) . '%"';

                    foreach($this->get('fields') as $key => $field) {
                        switch ($field['type']) {
                            
                            case 'HIDDEN':
                                $hidden_fields .= '<input type="hidden" name="'.$key.'" value="'.$field['value'].'">';
                                break;

                            case 'TEXT':
                                $visible_fields .= '<td '.$td_width.'><input type="text" name="'.$key.'" placeholder="'.(array_key_exists('placeholder', $field) ? $field['placeholder'] : translate('Ajouter un élément')).'"></td>';
                                break;

                            case 'SELECT':
                            case 'SELECT_ID':
                                $visible_fields .= '<td '.$td_width.'><select name="'.$key.'">';

                                foreach ($field['options'] as $option_key => $option_value) {
                                    $visible_fields .= '<option value="'.$option_key.'">'.$option_value.'</option>';    
                                }
                                $visible_fields .= '</select></td>';
                                break;
                        }
                    }
                ?>

                <?= $hidden_fields ?>
                <table border="0">
                    <?= $visible_fields ?>
                    <td <?= $td_width ?>><input type="submit" value="<?= translate('Ajouter') ?>" /></td>
                </table>
            </form>

            <table border="0" cellpadding="8" class="table list_items" width="100%">

            <tr <?= !$items ? '' : 'style="display:none;"' ?>><td colspan="<?= (count($this->get('fields'))+3) ?>"><?= translate('Aucun élément pour le moment'); ?></td></tr>
            <tr data-id="0" style="display:none;">
                    <?php 
                        foreach($this->get('fields') as $key => $field) {
                            switch ($field['type']) {
                                case 'HIDDEN': 
                                    echo '<td data-field="'.$key.'" data-type="hidden"><input type="hidden" name="'.$key.'" value="'.$field['value'].'"></td>';
                                    break;
                                case 'TEXT': 
                                    echo '<td data-field="'.$key.'" data-type="text"></td>';
                                    break;
                                case 'SELECT':
                                case 'SELECT_ID':
                                    echo '<td data-field="'.$key.'" data-type="select"></td>';
                                    break;
                            }
                        }
                    ?>
                    <td data-type="submit"><!--submit--></td>
                    <td style="width:50px;"><a href="#" class="edit-action"><i class="fa fa-pencil"></i></a></td>
                    <td style="width:50px;"><a href="#" data-confirm="<?= translate('Souhaitez-vous vraiment supprimer cet élément ?') ?>" class="delete-action"><i class="fa fa-close"></i></a></td>
            </tr>

            <?php if ($items) : ?>

                <?php foreach($items as $item) : ?>
                    
                    <tr data-id="<?= $item->{$this->get('primary_key')} ?>">   

                        <?php 
                        foreach($this->get('fields') as $key => $field) {
                            
                            switch ($field['type']) {

                                case 'HIDDEN': 
                                    echo '<td data-field="'.$key.'" data-type="hidden"><input type="hidden" name="'.$key.'" value="'.$item->{$key}.'"></td>';
                                    break;
                            
                                case 'TEXT': 
                                    echo '<td data-field="'.$key.'" data-type="text">'.$item->{$key}.'</td>';
                                    break;

                                case 'SELECT':
                                case 'SELECT_ID':
                                    echo '<td data-field="'.$key.'" data-type="select">'.(array_key_exists($item->{$key},$field['options']) ? $field['options'][$item->{$key}] : '-') .'</td>';
                                    break;
                            }
                        }
                        ?>

                        <td data-type="submit"><!--submit--></td>
                        <td style="width:50px;"><a href="#" class="edit-action"><i class="fa fa-pencil"></i></a></td>
                        <td style="width:50px;"><a href="#" data-confirm="<?= translate('Souhaitez-vous vraiment supprimer cet élément ?') ?>" class="delete-action"><i class="fa fa-close"></i></a></td>
                        
                        <?php if ($this->get('selector')) : ?>
                            <td style="width:50px;"><a href="#" class="select-action"><i class="fa fa-arrow-right"></i></a></td>
                        <?php endif; ?>
                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>
                </table>

                <script>$('#<?= $this->get('name') ?>_manager').MiniManager();</script>
        </div>

        <?php
        ob_end_flush();
    }

}
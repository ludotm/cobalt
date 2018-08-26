<?php

namespace Core\Com;

use \Core\Service;
use \Core\Api\Spothit;
use \Core\Entity;

class Sms extends Entity
{
    public $options=array();
    protected $options_keys=array();

    public function __construct($params=array()) 
    {
        parent::__construct('_sms');

        // options supplémentaires n'apparaissant pas dans la table _email
        $this->options_keys = array(
            'campain_name',
            'save',
            'tronque',
        );

        $this->options['save'] = 1;

        if (is_array($params) && !empty($params)) {
            $this->set_params($params);
        }
    }

    public function set_params($data)
    {
        if (array_key_exists('to', $data)) {
            $data['send_to'] = $data['to'];
            unset($data['to']);
        }
        if (array_key_exists('from', $data)) {
            $data['from_num'] = $data['from'];
            unset($data['from']);
        }

        if (array_key_exists('send_to', $data)) {
            if (is_array($data['send_to'])) {
                $data['send_to'] = implode(';',$data['send_to']);
            }
        }
        $this->bind($data);

        foreach ($data as $key => $value) {
            if (in_array($key, $this->options_keys)) {
                $this->options[$key] = $value;
            }
        }
    }

    /*
    CHAMPS DISPONIBLES

    // champs obligatoires
    - send_to / to * : destinataires (array, ou string séparés de ";")
    - message * : corps du message
    - from_num / from  : numéro de l'expediteur
    // non obligatoire mais conseillé            
    - provider : "spothit_premium" ou "spothit_lowcost"
    
    // options
    - id_big_user : int, précise sous quel big user le message doit etre enregistré, par défaut id_big_user session en cours
    - save : bool, pour savoir si le message doit etre enregistré en BDD ou non

    // spot hit uniquement
    - smslong : Si égal à "1", autorise l'envoi de SMS supérieur à 160 caractères (SMS Premium uniquement)
    - smslongnbr : Permet de vérifier la taille du SMS long envoyé. Vous devez envoyer le nombre de SMS concaténés comme valeur. Si notre compteur nous indique un nombre différent, votre message sera rejeté.
    - tronque : Si égal à "1", tronque automatiquement le message à 160 caractères.
    - encodage : si égal à "auto", conversion de votre message en UTF-8 (il est conseillé de convertir votre message en UTF-8 dans votre application cependant si votre message reste coupé après un caractère accentué, vous pouvez activer ce paramètre).
    - timestamp : (spothit uniquement) timestamp, date à laquelle le mail doit etre envoyé, null si immédiat
    - campain_name :  (spothit uniquement) Nom de la campagne ou identifiant permettant de distinguer le mail
  
    STATUTS 
    0 = En attente
    1 = Livré
    2 = Envoyé
    3 = En cours
    4 = Echec
    5 = Expiré       
    */


    /****************************************** SEND *************************************************/

    public function send()
    {
        if (property_exists($this, 'send_to') && property_exists($this, 'message')) {
            if ($this->send_to == '' || $this->message == '' ) {
                Service::error('Fonction send sms : paramètre obligatoire manquant');
            }
        } else {
            Service::error('Fonction send sms : paramètre obligatoire manquant');
        }
        
        $send_to = explode(';',$this->send_to);
        if (trim($send_to[(count($send_to)-1)]) == '') {
            unset($send_to[(count($send_to)-1)]);
        }

        // ID BIG USER
        if (!property_exists($this, 'id_big_user')) {
            $session = Service::Session();
            $this->id_big_user = $session->get('id_big_user');
        }

        if (!$this->provider || $this->provider == '') {
            // valeurs par défaut
            $this->provider = _config('default_providers.sms');
        }

        $this->provider = str_replace('-', '_', $this->provider);

        switch ($this->provider) {

            case 'spothit_lowcost':
            case 'spothit_premium':
                
                $smser = new Spothit($this->id_big_user);

                if ($this->provider == 'spothit_lowcost') {
                    $type = 'lowcost';
                } else if ($this->provider == 'spothit_premium') {
                    $type = 'premium';
                }

                $data = array(
                    'message' => $this->message, //utf8_encode($message),
                    'destinataires' => $send_to,
                    'expediteur' => $this->from_num,
                    'type' => $type,
                );
                if (property_exists($this, 'timestamp') && $this->timestamp != '') {$data['date'] = $this->timestamp;}
                if (array_key_exists('campain_name', $this->options)) {$data['nom'] = $this->options['campain_name'];}

                /* a traiter 
                - encodage
                */

                $data['smslongnbr'] = $this->get_sms_nb_concat($this->get_sms_nb_chars($this->message));

                if ($this->provider == 'spothit_lowcost') {
                    
                    if ($data['smslongnbr'] > 1) {
                        Service::error("Fonction send SMS, la taille du sms est trop longue pour le mde low cost");
                    }
                    $data['smslong'] = 0 ;
                
                } else {
                    $data['smslong'] = 1;
                }

                $response = $smser->send_sms($data);

                if ($this->options['save']) {

                    $this->id_common_provider = $response['id'];
                    $this->count_sms = count($send_to);
                    $this->sms_concat_size = $data['smslongnbr'];
                    $this->timestamp = 1;
                    $this->save();    
                }

                return true;
                break;
            
            default:
                Service::error('Aucun provider séléctionné');
                break;
        }
    }

    public function get_sms_nb_chars($message) 
    {
        $message = str_replace("\r\n","\n", $message);
        $total_chars = mb_strlen($message, "UTF-8"); 
        $total_chars += substr_count ( $message , '|');
        $total_chars += substr_count ( $message , '^');
        $total_chars += substr_count ( $message , '€');
        $total_chars += substr_count ( $message , '}');
        $total_chars += substr_count ( $message , '{');
        $total_chars += substr_count ( $message , '[');
        $total_chars += substr_count ( $message , '~');
        $total_chars += substr_count ( $message , ']');
        $total_chars += substr_count ( $message , '\\');
        
        return $total_chars;
    }
    
    public function get_sms_nb_concat($nb_chars) 
    {
        if (!is_numeric($nb_chars)) {
            $nb_chars = $this->get_sms_nb_chars($nb_chars);
        }
        if ($nb_chars>160) {
            return ceil($nb_chars/153);    
        } else {
            return 1;    
        }
    }
}
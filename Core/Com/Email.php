<?php

namespace Core\Com;

use \Core\Service;
use \Core\Api\Spothit;
use \Core\Com\Mailer\Mailer;
use \Core\Entity;

class Email extends Entity
{
    public $options=array();
    protected $options_keys=array();

    public function __construct($params=array()) 
    {
        parent::__construct('_emails');

        // options supplémentaires n'apparaissant pas dans la table _email
        $this->options_keys = array(
            'smtp',
            'is_html',
            'alt_message',
            'campain_name',
            'save',
            'unsuscribe_link',
            'online_link',
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
            $data['from_mail'] = $data['from'];
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
    - subject * : sujet du mail
    - message * : corps du mail
    - from_mail / from * : mail de l'expediteur  (votreentreprise@sh-mail.fr)
    // non obligatoire mais conseillé
    - from_name : nom de l'expediteur            
    - response_mail : email auquel répondre si besoin
    - provider : (optionnel) précise le provider utilisé, provider par défaut en config sinon
    
    
    // options
    - id_big_user : int, précise sous quel big user le message doit etre enregistré, par défaut id_big_user session en cours
    - id_template : template à utiliser 
    - id_image : Ajoute une image en haut du mail
    - save : bool, pour savoir si le message doit etre enregistré en BDD ou non
    - unsuscribe_link : bool, faire apparaitre ou non un lien de désinscription

    // spot hit uniquement
    - timestamp : (spothit uniquement) timestamp, date à laquelle le mail doit etre envoyé, null si immédiat
    - campain_name :  (spothit uniquement) Nom de la campagne ou identifiant permettant de distinguer le mail
    
    // self uniquement
    - cc : (self uniquement) array ou string séparés de ";"
    - cci : (self uniquement) array ou string séparés de ";"
    - attachments (self uniquement) : string '/var/html/image/1.jpg' ou pour pièces jointe smultiples array('/var/html/image/1.jpg', '/var/html/image/2.jpg'),
    - is_html : (self uniquement), bool, mail html ou texte
    - alt_message (self uniquement) : message alternatif si le destinataire ne peut pas lire les mails html
    - smtp (self uniquement) : array( // facultatif, faire apparaitre vide si besoin d'écraser le smtp par défaut
        'host' => 'smtp1.example.com;smtp2.example.com',
        'username' => 'test@test.com',
        'password' => 'blablabla', 
        'encryption' => 'tls', // Enable TLS encryption, `ssl` also accepted
        'port' => '583',
    ),
    
    STATUTS 
    0 = En attente
    2 = Envoyé
    3 = Cliqué
    4 = Erreur
    5 = Bloqué
    6 = Spam
    7 = Desabonné
    8 = Ouvert         
    */


    /****************************************** SEND *************************************************/

    public function send()
    {
        if (property_exists($this, 'from_mail') && property_exists($this, 'send_to') && property_exists($this, 'subject') && property_exists($this, 'message')) {
            if ($this->from_mail == '' || $this->send_to == '' || $this->subject == '' || $this->message == '' ) {
                Service::error('Fonction send mail : paramètre obligatoire manquant');
            }
        } else {
            Service::error('Fonction send mail : paramètre obligatoire manquant');
        }
        
        $send_to = explode(';',$this->send_to);
        if (trim($send_to[(count($send_to)-1)]) == '') {
            unset($send_to[(count($send_to)-1)]);
        }

        // ONLINE LINK
        if ($this->provider == 'self') {
            if (array_key_exists('online_link', $this->options)) {
                unset($this->options['online_link']);
            }
        }

        // TEMPLATE
        if (property_exists($this, 'id_template') && $this->id_template != 0) {
            $message = $this->render_mail($this->id_template, false);
        } else {
            $message = $this->message;
        }
        
        // ID BIG USER
        if (!property_exists($this, 'id_big_user')) {
            $session = Service::Session();
            $this->id_big_user = $session->get('id_big_user');
        }

        if (!$this->provider || $this->provider == '') {
            // valeurs par défaut
            $this->provider = _config('default_providers.mail');
        }

        switch ($this->provider) {

            case 'spothit':
                
                $mailer = new Spothit($this->id_big_user);

                $data = array(
                    'sujet' => $this->subject,
                    'message' => str_replace(array('[UNSUSCRIBE_LINK]', '[ONLINE_LINK]'), array('{DESINSCRIPTION}','{PERMALIEN}'), $message),
                    'destinataires' => $send_to,
                    'expediteur' => $this->from_mail,
                );
                if (property_exists($this, 'from_name') && $this->from_name != '') {$data['nom_expediteur'] = $this->from_name;}
                if (property_exists($this, 'response_mail') && $this->response_mail != '') {$data['email_reponse'] = $this->response_mail;}
                if (property_exists($this, 'timestamp') && $this->timestamp != '') {$data['date'] = $this->timestamp;}
                if (array_key_exists('campain_name', $this->options)) {$data['nom'] = $this->options['campain_name'];}

                // PAS D'ATTACHMENTS

                $response = $mailer->send_mail($data);

                if ($this->options['save']) {

                    $this->id_common_provider = $response['id'];
                    $this->count_mails = count($send_to);
                    $this->timestamp = 1;
                    $this->save();    
                }

                return true;
                break;

            case 'self':

                $data = array(
                    'subject' => $this->subject,
                    'from' => $this->from_mail,
                );
                if (property_exists($this, 'from_name') && $this->from_name != '') {$data['from_name'] = $this->from_name;}
                if (property_exists($this, 'cc') && $this->cc != '') {$data['cc'] = $this->cc;}
                if (property_exists($this, 'cci') && $this->cci != '') {$data['bcc'] = $this->cci;}
                if (property_exists($this, 'response_mail') && $this->response_mail != '') {$data['reply_to'] = $this->response_mail;}
                if (property_exists($this, 'attachments') && $this->attachments != '') {$data['attachments'] = $this->attachments;}
                if (array_key_exists('alt_content', $this->options)) {$data['alt_content'] = $this->options['alt_content'];}
                if (array_key_exists('is_html', $this->options)) {$data['is_html'] = $this->options['is_html'];} else {$data['is_html'] = 1;}
                if (array_key_exists('smtp', $this->options)) {$data['smtp'] = $this->options['smtp'];}

                $mailer = new Mailer();

                $errors = array();
                $to_send = $mail_sent = 0;

                if (!IS_LOCAL) {
                    foreach ($send_to as $email) {

                        $to_send++; 

                        $data['to'] = $email;
                        $data['content'] = str_replace('[UNSUSCRIBE_LINK]', $this->get_unsuscribe_link($email), $message);
                        $response = $mailer->send_mail($data);
                        
                        if (is_string($response)) {
                            $errors []= $email.' : '.$response;
                        } else {
                            $mail_sent++;
                        }
                    }
                }
                
                if ($this->options['save']) {

                    $this->id_provider = 0;
                    $this->count_mails = count($send_to);
                    $this->timestamp = time();
                    $this->save();    
                }
                if (!empty($errors)) {
                    return $errors;
                } else {
                    return true;
                }
                break;
            
            default:
                Service::error('Aucun provider séléctionné');
                break;
        }
    }

    /****************************************** RENDER TEMPLATE *************************************************/

    public function get_template_css($template, $element="body")
    {
        ob_start();
        ?>
        <?= $element ?> {
            font-size:<?= $template->font_size ?>px;
            color:<?= $template->font_color ?>;
        }
        <?= $element ?> a:link, <?= $element ?> a:visited {color:<?= $template->link_color ?>; text-decoration:none;}
        <?= $element ?> a:active, <?= $element ?> a:hover {color:<?= $template->link_color ?>; text-decoration:underline;}
        <?= $element ?> h1 {
            font-size:<?= $template->title_font_size ?>px;
            color:<?= $template->title_color ?>;
        }
        <?php 
        $css = ob_get_contents();
        ob_end_clean();

        return $css;
    }

    public function render_mail($id_template, $render=true, $element="body")
    {
        if ($id_template == 0) {
            exit("Aucun template n'a été séléctionné, aperçu non disponible.");
        }
        
        $template = $this->db->select('_emails_templates')->id($id_template)->execute();

        if (!$template) {
            exit("Aucun template n'a été séléctionné, aperçu non disponible.");
        }

        $vars = array();
        if (property_exists($this, 'subject')) {
            $vars['title'] = $this->subject;
        } else {
            $vars['title'] = '';
        }

        $vars['css'] = $this->get_template_css($template, $element);
        
        if ($template->id_image != 0) {
            $vars['banner'] = $template->get_image('id_image')->get_src_absolute_url();
        } else {
            $vars['banner'] = '';
        }

        
        if (property_exists($this, 'id_image') && $this->id_image != 0) {
            $vars['body_image'] = $this->get_image('id_image')->get_src_absolute_url();
        } else  {
            $vars['body_image'] = 0;
        }

        if (property_exists($this, 'message') && trim(strip_tags($this->message)) != '') {
            $vars['body'] = $this->message;
        } else {
            $vars['body'] = "<h1>Lorem ipsum</h1>\n\n
            Quam ob rem ut ii qui superiores sunt submittere se debent in amicitia, sic quodam modo inferiores extollere. Sunt enim quidam qui molestas amicitias faciunt, cum ipsi se contemni putant; quod non fere contingit nisi iis qui etiam contemnendos se arbitrantur; qui hac opinione non modo verbis sed etiam opere levandi sunt.\n\n
            Sed laeditur hic coetuum magnificus splendor levitate paucorum incondita, ubi nati sunt non reputantium, sed tamquam indulta licentia vitiis ad errores lapsorum ac lasciviam. ut enim Simonides lyricus docet, beate perfecta ratione vieturo ante alia patriam esse convenit gloriosam.\n\n
            <h1>Lorem ipsum</h1>\n\n
            Quam ob rem ut ii qui superiores sunt submittere se debent in amicitia, sic quodam modo inferiores extollere. Sunt enim quidam qui molestas amicitias faciunt, cum ipsi se contemni putant; quod non fere contingit nisi iis qui etiam contemnendos se arbitrantur; qui hac opinione non modo verbis sed etiam opere levandi sunt.\n\n";
        }

        $url =  PREFIX . 'Core' .DS. 'Com' .DS. 'layout_mail.phtml';

        if (array_key_exists('unsuscribe_link', $this->options) && $this->options['unsuscribe_link']) {
            $vars['unsuscribe_link'] = true;
        } else {
            $vars['unsuscribe_link'] = false;
        }
        if (array_key_exists('online_link', $this->options) && $this->options['online_link']) {
            $vars['online_link'] = true;
        } else {
            $vars['online_link'] = false;
        }

        if (is_array($vars)) {
            extract($vars); 
        }

        if ($render) {
            ob_start(); 
            require_once($url);
            ob_end_flush();

        } else {
            ob_start();
            require_once($url);
            $out = ob_get_contents();
            ob_end_clean();
            return $out; 
        }
    }

    /****************************************** UNSUSCRIBE *************************************************/

    public function get_unsuscribe_link ($mail) 
    {
        $crypto = Service::Crypto();
        $crypto->setSecret($this->get_unsuscribe_secret());
        $code = $crypto->encode($mail);

        return SITE_URL.'/unsuscribe_email/?mail='.$mail.'&code='.$code;
    }

    public function unsuscribe_validation ($mail, $code) 
    {
        $crypto = Service::Crypto();
        $crypto->setSecret($this->get_unsuscribe_secret());

        $decoded = $crypto->decode($code);
        
        return $mail == $decoded ? true : false;
    }

    protected function get_unsuscribe_secret() 
    {
        $this->unsuscribe_secret = 'Zd8iaL63';
        return $this->unsuscribe_secret;
    }

}
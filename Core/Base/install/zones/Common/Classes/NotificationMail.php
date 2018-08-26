<?php 

namespace Common\Classes;

use \Core\Service;

class NotificationMail
{

	static function admin_account_created ($email, $login, $password, $url_login) 
	{
		$url_login_text = self::url_plain_text($url_login);

		$subject = "WIFID - Création de vote compte";
		$message = "Bonjour, <br><br>
		Nous sommes ravis de votre inscription à notre service et nous vous remercions de la confiance que vous portez en Wifid !<br><br>
		
		Vous trouverez ci-dessous les informations personnelles vous permettant d'accéder à votre compte Wifid<br>
		URL d'accès : <a href='".$url_login."' target='_blank'>".$url_login_text."</a><br>
		Login : ".$login." <br>
		Mot de passe : ".$password." <br><br>

		Vous pouvez changer votre mot de passe à tout moment dans la rubrique Paramètres > Utilisateurs.<br><br>

		Conseils de sécurité importants :<br><br>
		1. Gardez toujours vos informations de compte en sécurité.<br>
		2. Ne divulguez jamais vos informations de connexion.<br>
		3. Si vous suspecter une utilisation frauduleuse de votre compte, modifier vos informations d'accès et contactez nous. <br><br>

		Sincères salutations.<br><br>
		L’équipe Wifid";

		return Service::send_mail(array(
			'to' => $email,
			'from_mail' => _config('contact.email_spothit'),
			'from_name' => 'Wifid',
			'response_mail' => _config('contact.email_contact'),
			'provider' => 'spothit',
			'id_big_user' => 0,
			'save' => 1,
			'unsuscribe_link' => false,
			'subject' => $subject,
			'message' => $message,
		));
	}

	static function date_trial ($email, $date_start, $date_end, $url_login) 
	{
		$url_login_text = self::url_plain_text($url_login);

		$subject = "WIFID - Votre période d'essai";
		$message = "Bonjour, <br><br>
		Votre période d'essai Wifid a été programmée du ".\Core\Tools::convert_date($date_start)." au ".\Core\Tools::convert_date($date_end).".<br><br>

		Durant cette période, vous avez accès à la version intégrale du logiciel Wifid excepté l'envoi d'emails Premium et de SMS. 
		L'envoi d'emails Basic est cependant disponible.<br><br>

		Si vous n'avez pas encore reçu vos identifiants de connexion, contactez-nous à l'adresse suivante : "._config('contact.email_contact')."
		<br><br>
		
		Accédez au logiciel en ligne grâce à l'adresse suivante : <a href='".$url_login."' target='_blank'>".$url_login_text."</a><br><br>

		N'hésitez pas à vous référer à notre guide d'utilisation présent dans votre espace perso et/ou à nous contacter pour toute information.<br><br>

		Sincères salutations.<br><br>
		L’équipe Wifid";

		return Service::send_mail(array(
			'to' => $email,
			'from_mail' => _config('contact.email_spothit'),
			'from_name' => 'Wifid',
			'response_mail' => _config('contact.email_contact'),
			'provider' => 'spothit',
			'id_big_user' => 0,
			'save' => 1,
			'unsuscribe_link' => false,
			'subject' => $subject,
			'message' => $message,
		));
	}

	static function url_plain_text ($url) 
	{
		return str_replace(array('https://','http://'),array('',''), $url);
		//return str_replace(':','&colon;', $url);
		//return str_replace(array('/',':'),array('&sol;','&colon;'), $url);
	}
}

?>
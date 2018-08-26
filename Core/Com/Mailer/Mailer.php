<?php 
namespace Core\Com\Mailer;

use Core\Service;

class Mailer
{
	use \Core\Singleton;
	
	protected $smtp;
	protected $unsuscribe_secret;

	public function __construct() 
	{
		/*
		dans la config globale, mailer > server smtp, language etc...
		*/
		//$mail->setLanguage('fr', '/optional/path/to/language/directory/');

		require_once  "PhpMailer/PHPMailerAutoload.php";

		$this->smtp = _config('mailer.smtp', array());

		if (!empty($this->smtp)) {
			$this->set_smtp($this->smtp['host'], $this->smtp['username'], $this->smtp['password'], $this->smtp['port'], $this->smtp['encryption']);
		}

		
	}

	/****************************************** SENDERS *************************************************/

	public function send_mail ($data) 
	{
		$mail = new \PHPMailer;
		$mail->CharSet = 'utf-8';
		//$mail->SMTPDebug = 3;                               // Enable verbose debug output

		/*
		EXEMPLE : 
		$data = array(
			'smtp' => array( // facultatif, faire apparaitre vide si besoin d'écraser le smtp par défaut
				'host' => 'smtp1.example.com;smtp2.example.com',
				'username' => 'test@test.com',
				'password' => 'blablabla', 
				'encryption' => 'tls', // Enable TLS encryption, `ssl` also accepted
				'port' => '583',
			),
			'from' => 'admin@wifid.com',
			'from_name' => 'Wifid',
			'to' => array('john.doe@gmail.com', 'John Doe'),
			'cc' => array('john.doe@gmail.com', 'john.doe@gmail.com', 'john.doe@gmail.com'),  // optionnel
			'bcc' => 'john.doe@gmail.com',  // optionnel
			'reply_to' => 'admin@wifid.com', // optionel, 'false' si on ne veux pas de reply
			'is_html' => true, // optionnel, true ou false, true par défaut 
			'subject' => 'Sujet du mail, 
			'content' => 'Contenu du mail',
			'alt_content' => 'Contenu alternatif du mail', // optionnel, pour les client mail ne lisant pas le html
			'attachments' => array('/var/html/image/1.jpg', '/var/html/image/2.jpg'), // optionnel, en cas de plusieurs pièces jointes
		);
		*/


		if (!empty($this->smtp)) {

			$mail->isSMTP();                                      // Set mailer to use SMTP
			$mail->Host = $this->smtp['host'];
			$mail->SMTPAuth = true;                               // Enable SMTP authentication
			$mail->Username = $this->smtp['username'];
			$mail->Password = $this->smtp['password'];
			$mail->SMTPSecure = $this->smtp['encryption'];
			$mail->Port = $this->smtp['port'];

		} else if (array_key_exists('smtp', $data)) {

			$mail->isSMTP();
			$mail->Host = $data['smtp']['host'];
			$mail->SMTPAuth = true;
			$mail->Username = $data['smtp']['username'];
			$mail->Password = $data['smtp']['password'];
			$mail->SMTPSecure = $data['smtp']['encryption'];
			$mail->Port = $data['smtp']['port'];
		}

		$mail->setFrom($data['from'], $data['from_name']);

		if (!is_array($data['to'])) {
			$data['to'] = explode(';',$data['to']);
		}
		for($i=0; $i<count($data['to']); $i++) {

			if (is_array($data['to'][$i])) {
				$mail->addAddress(trim($data['to'][$i][0]), $data['to'][$i][1]);
			} else {
				$mail->addAddress(trim($data['to'][$i]));	
			}
		}

		if (array_key_exists('cc', $data)) {
			if (!is_array($data['cc'])) {
				$data['cc'] = array($data['cc']);
			}
			for($i=0; $i<count($data['cc']); $i++) {
				$mail->addCC($data['cc'][$i]);
			}
		}
		
		if (array_key_exists('bcc', $data)) {
			if (!is_array($data['bcc'])) {
				$data['bcc'] = array($data['bcc']);
			}
			for($i=0; $i<count($data['bcc']); $i++) {
				$mail->addCC($data['bcc'][$i]);
			}
		}

		if (array_key_exists('reply_to', $data)) {
			if ($data['reply_to']) {
				$mail->addReplyTo($data['reply_to']);
			}		
		} else {
			$mail->addReplyTo($data['from'], $data['from_name']);
		}

		if (array_key_exists('attachments', $data)) {

			if (is_array($data['attachments'])) {

				for($i=0; $i<count($data['attachments']); $i++) {
					if (is_array($data['attachments'][$i])) {
						$mail->addAttachment($data['attachments'][$i][0], $data['attachments'][$i][1]);
					} else {
						$mail->addAttachment($data['attachments'][$i]);
					}
				}
			} else {
				$mail->addAttachment($data['attachments']);
			}
		}

		if (array_key_exists('is_html', $data)) {
			$mail->isHTML($data['is_html']);  
		} else {
			$mail->isHTML(true);  
		}

		$mail->Subject = $data['subject'];
		$mail->Body    = $data['content'];

		// pour ceux qu'on n'ont pas de cleint mail html
		if (array_key_exists('alt_content', $data)) {
			$mail->AltBody = $data['alt_content'];
		}

		if(!$mail->send()) {
		    return $mail->ErrorInfo;
		    //Service::error('Votre mail n\'a pas pu être envoyé' . $mail->ErrorInfo);
		} else {
		    return true;
		    //Service::flash('Votre mail a bien été envoyé');
		}
	}

	public function set_smtp($host, $username, $password, $port, $encryption='tls')
	{
		$this->smtp['host'] = $host; // 'smtp1.example.com;smtp2.example.com' // Specify main and backup SMTP servers
		$this->smtp['username'] = $username; // SMTP username
		$this->smtp['password'] = $password; // SMTP password
		$this->smtp['encryption'] = $encryption; // Enable TLS encryption, `ssl` also accepted
		$this->smtp['port'] = $port; // TCP port to connect to (ex: 587)
	}
	public function clear_smtp()
	{
		$this->smtp = array();
	}

}

?>
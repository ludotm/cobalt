<?php 

namespace Core\Base\Controllers;

use Core\Base\Controllers\BaseController;
use Core\Service;
use Core\Crypto;

class FilesController extends BaseController
{
	public function onDispatch()
	{
        
	}

    public function page_read()
    {
        $url = $this->request->fromRoute('u', false);
        $download = $this->request->fromRoute('d', false);

        $path = Service::secure_file_url_decode($url, $download);

        if ($path === false || !file_exists($path)) {
            Service::redirectError('404');
        }

        $this->read_file($path);
        exit();
    }

    protected function read_file ($path, $download = false){
        // Chemin du document
        $file = ROOT.'/'.$path;

        // Récupération du type mime
        $mime = $this->get_mime_type($file);

        // Envoi de l'en-tête adapté au type mime.
        header('Content-type: ' . $mime);

        // Il sera proposé au téléchargement au lieu de s'afficher.
        if ($download) {
            header('Content-Disposition: attachment; filename="'.$path.'"');
        }
        // La source du fichier est lue et envoyée au navigateur.
        readfile($file);
    }


    protected function get_mime_type($fichier = ''){
        if (empty ( $fichier)){
            exit ('Paramètre invalide');
        }
        if( function_exists('finfo_open') ){
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // Retourne le type MIME à l'extension mimetype.
            $retour = finfo_file($finfo, $fichier);
            finfo_close($finfo);
        } elseif(file_exists('mime.ini')){
            $retour = $tihs->typeMime($fichier);
        } else {
            $retour = mime_content_type ( $fichier );
        }
        return $retour;
    }

    protected function typeMime($nomFichier)
    /* Fonction grandement inspirée de :
     * http://www.asp-php.net/ressources/codes/PHP-Type+MIME+d%27un+fichier+a+partir+de+son+nom.aspx
     * retourne le type MIME à partir de l'extension de fichier contenu dans $nomFichier
     * Exemple : $nomFichier = "fichier.pdf" => type renvoyé : "application/pdf" */
    {
        // On détecte d'abord le navigateur, ça nous servira plus tard.
        if(preg_match("@Opera(/| )([0-9].[0-9]{1,2})@", $_SERVER['HTTP_USER_AGENT'], $resultats))
        $navigateur="Opera";
        elseif(preg_match("@MSIE ([0-9].[0-9]{1,2})@", $_SERVER['HTTP_USER_AGENT'], $resultats))
        $navigateur="Internet Explorer";
        else $navigateur="Mozilla";

        // On récupère la liste des extensions de fichiers et leurs types MIME associés.
        $mime=parse_ini_file("mime.ini");
        $extension=substr($nomFichier, strrpos($nomFichier, ".")+1);

        /* On affecte le type MIME si l'on a trouvé l'extension, sinon le type par défaut (un flux d'octets).
         Attention : Internet Explorer et Opera ne supportent pas le type MIME standard. */
        if(array_key_exists($extension, $mime)){
            $type=$mime[$extension];
        }
        else{
            $type=($navigateur!="Mozilla") ? 'application/octetstream' : 'application/octet-stream';
        }

        return $type;
    }

}
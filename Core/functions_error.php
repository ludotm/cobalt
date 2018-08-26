<?php

function ErrorManager($type, $message, $fichier, $ligne, $exception=null)
{
  switch ($type)
  {
    case E_ERROR:
    case E_PARSE:
    case E_CORE_ERROR:
    case E_CORE_WARNING:
    case E_COMPILE_ERROR:
    case E_COMPILE_WARNING:
    case E_USER_ERROR:
      $type_erreur = "Erreur fatale";
      $color = 'red';
      break;

    case E_WARNING:
    case E_USER_WARNING:
      $type_erreur = "Avertissement";
      //$type_erreur = "Erreur";
      $color = 'yellow';
      break;

    case E_NOTICE:
    case E_USER_NOTICE:
      $type_erreur = "Remarque";
      //$type_erreur = "Erreur";
      $color = 'yellow';
      break;

    case E_STRICT:
      $type_erreur = "Syntaxe Obsolète";
      $color = 'yellow';
      break;

    default:
      $type_erreur = "Erreur inconnue";
      $color = 'red';
      break;
  }
  
  $raw_message = $message;

  $debug = _get('config.debug');
  $is_superadmin = (isset($_SESSION['id_rank']) && $_SESSION['id_rank'] == 1) ? true : false;
  $current_ip = $_SERVER["REMOTE_ADDR"];

  $show_details = $debug['show_details']['public'];
  $show_backtrace = $debug['show_backtrace']['public'];

  if ($is_superadmin) {
    if (array_key_exists('superadmin', $debug['show_details'])) {
      $show_details = $debug['show_details']['superadmin'];
    }
    if (array_key_exists('superadmin', $debug['show_backtrace'])) {
      $show_backtrace = $debug['show_backtrace']['superadmin'];
    }
  }  
  if (array_key_exists('true_for_ip', $debug['show_details'])) {
    if ($current_ip == $debug['show_details']['true_for_ip'] ) {
      $show_details = true;
    }
  }
  if (array_key_exists('true_for_ip', $debug['show_backtrace'])) {
    if ($current_ip == $debug['show_backtrace']['true_for_ip']) {
      $show_backtrace = true;
    }
  }

  $style = '';
  $style .= 'font-family:helvetica, arial, sans-serif;';
  $style .= 'margin-top:10px; margin-bottom:10px; padding: 15px;';
  $style .= 'border-radius: 4px;';

  if ($color == 'red') {
    $style .= 'color:#a94442; background-color:#f2dede; border-color: #ebccd1;';
  } else if ($color == 'yellow') {
    $style .= 'color:#8a6d3b; background-color:#fcf8e3; border-color: #faebcc;';
  }

  if ($exception) {
    if (isset($exception->sql)) {
      if ($show_details) {
        $message .= '<br><br><span style="font-weight:bold;">Requ&ecirc;te SQL :</span> '.$exception->sql.'';
        $raw_message .= 'Requ&ecirc;te SQL :'.$exception->sql;

        if (isset($exception->vars)) {
          $message .= '<br><br><span style="font-weight:bold;">Variables SQL :</span>';
          $raw_message .= ' - Variables SQL :';
          foreach ($exception->vars as $key => $value) {
            $message .= '<br>'.$key.' : ';
            $raw_message .= ' - '.$key.' :'; 
            if (is_array($value)) {
              $message .= 'array()';
              $raw_message .= 'array()';
            } else if (is_object($value)) {
              $message .= 'object()';
              $raw_message .= 'object()';
            } else if (is_null($value)) {
              $message .= 'null';
              $raw_message .= 'null';
            } else {
              $message .= $value;
              $raw_message .= $value;
            }

          } 
        }
      } else {
        $message = 'Requ&ecirc;te SQL incorrecte';
        $raw_message = 'Requ&ecirc;te SQL incorrecte';
      } 
    }
  }


  $fullmessage = $show_details ? $message.'<br><br>' . $fichier . ' - ligne ' . $ligne : $message ;

  $erreur = '<div style="'.$style.'"> <span style="font-weight:bold;">' . $type_erreur.'</span> : ' . $fullmessage . '</div>';
  
  // Si c'est une requete AJAX
  if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    $show_details = true;

    $is_json_expected = false;
    foreach (headers_list() as $name => $value) 
    {
        if ($value == 'Content-Type: application/json') {
          header('Content-Type: text/plain');
          $is_json_expected = true;
        }
    }

    if ($show_details) {
      echo $type_erreur.' : '.$raw_message.' - '. $fichier . ' - ligne ' . $ligne ;
    } else {
      echo $type_erreur.' : '.$raw_message;
    }
    if ($is_json_expected) {
        //http_response_code(500);
        header('HTTP/1.0 500 Erreur serveur: '.$raw_message); 
    }
    if ($color == 'yellow' && $is_json_expected) { // si erreur de type warning et json 
      exit();
    }
    
  } else {
    echo $erreur;
    if ($show_backtrace) {
      echo get_debug_backtrace();
    }
  }

  // Enregistrement de l'erreur dans un fichier txt
  $big_user = isset($_SESSION['id_big_user']) ? $_SESSION['id_big_user'] : 0 ;
  $error_log_line = date('d / m / Y').' | '.date('H:i').' | '.($is_superadmin?'Superadmin':'Autre').' | '.$big_user.' | '.$type_erreur.' | ' . $message .' | '.$fichier.' | '.$ligne.PHP_EOL;
  LogErrorTxt($error_log_line);
  
}

function LogErrorTxt($line)
{
  $path = _config('error_logs_file');
  $max_lines = 300;
  
  if (!file_exists($path)) {
      $ptr = fopen($path, "w+");
  }
  $ptr = fopen($path, "r");
   $content = fread($ptr, filesize($path));
   fclose($ptr); /* On a plus besoin du pointeur */
   $content = explode(PHP_EOL, $content); /* PHP_EOL contient le saut à la ligne utilisé sur le serveur (\n linux, \r\n windows ou \r Macintosh */
    if (isset($content[$max_lines])) {
      unset($content[$max_lines]); /* On supprime la dernière ligne */
    }
   $content = array_values($content); /* Ré-indexe l'array */
   /* Puis on reconstruit le tout et on l'écrit */
   $content = implode(PHP_EOL, $content);
   $content = $line.$content;
   $ptr = fopen($path, "w");
   fwrite($ptr, $content);
   fclose($ptr);
}

function ExceptionManager($exception, $type=E_USER_ERROR)  
{
  $trace = $exception->getTrace();
  ErrorManager ($type, $exception->getMessage(), $trace[0]['file'], $trace[0]['line'], $exception);
}

function FatalErrorManager()
{
  if (is_array($e = error_get_last()))
  {
    $type = isset($e['type']) ? $e['type'] : 0;
    $message = isset($e['message']) ? $e['message'] : '';
    $fichier = isset($e['file']) ? $e['file'] : '';
    $ligne = isset($e['line']) ? $e['line'] : '';

    if ($type > 0) ErrorManager($type, $message, $fichier, $ligne);
  }
}

function get_debug_backtrace() 
{
    $dbs = debug_backtrace();
 
    $out = '<table border="0" width="100%" cellpadding="8" style="font-family:helvetica, arial, sans-serif; margin-bottom:10px;">';
    $out .= '<tr style="background-color:orange; color:white;"><td>Num</td><td>Fichier</td><td>Ligne</td><td>Classe</td><td>Fonction</td><td>Arguments</td></tr>';
    $i=1;
    $color = '#FAFAFA';

    foreach ($dbs as $db) {
      
      if (array_key_exists('function', $db) ) {
        if ($db['function'] == 'get_debug_backtrace' || $db['function'] == 'ErrorManager' || $db['function'] == 'FatalErrorManager' || $db['function'] == 'ExceptionManager') {
          continue;
        }
      }

      $color = $color == '#f0f0f0' ? '#FAFAFA' : '#f0f0f0' ;

      $out .= '<tr style="background-color:'.$color.';">';
      $out .= '<td>'.$i.'. </td>';
      $out .= array_key_exists('file', $db) ? '<td>'.$db['file'].'</td>' : '<td></td>';
      $out .= array_key_exists('line', $db) ? '<td>ligne '.$db['line'].'</td>' : '<td></td>';
      $out .= array_key_exists('class', $db) ? '<td>'.$db['class'].'</td>' : '<td></td>';
      $out .= array_key_exists('function', $db) ? '<td>'.$db['function'].'</td>' : '<td></td>';
      if (!empty($db['args'])) {
        $out .= '<td>';
            foreach($db['args'] as $arg) {
              if (is_string($arg) || is_int($arg)) {
                $out .= '"'.$arg.'", ';
              } else if (is_array($arg)) {
                $out .= 'array(), ';
              } else if (is_object($arg)) {
                $out .= 'object(), ';
              } else {
                $out .= 'unknown arg type, ';
              }
            }
        $out .= '</td>';
      } else {
        $out .= '<td>&nbsp;</td>';
      }
      $out .= '</tr>';
      $i++;
    }

    if ($i<2) {
      $out .= '<tr style="background-color:'.$color.';"><td colspan="6">No backtrace found</td></tr>';
    }

    $out .= '</table>';
    return $out;
}

error_reporting(0);

set_error_handler('ErrorManager');
set_exception_handler("ExceptionManager");
register_shutdown_function('FatalErrorManager');












?>
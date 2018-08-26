<?php

//$translate_language = "fr";


$TRANSLATOR = array(
  'active' => _get('config.language.active_translation'),
  'default_language' => _get('config.language.default_language'),
  'language' => _get('config.language.default_language'),
);

function switch_language($language) {
  
  global $TRANSLATOR;

  $TRANSLATOR['language'] = $language;
}



function translate($text)
{
  global $TRANSLATOR;
  
  return $text;
}


?>
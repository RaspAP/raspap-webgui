<?php

/**
* Sets locale information for i18n support
*
*/

if (!isset($_SESSION["locale"])) {
  $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
  switch ($lang){
    case "de":
      $locale = "de_DE.UTF-8";
      break;
    case "fr":
      $locale = "fr_FR.UTF-8";
      break;
    case "it":
      $locale = "it_IT.UTF-8";
      break;        
    default:
      $locale = "en_US.UTF-8";
      break;
  }
}

// debug
$locale = "fr_FR.UTF-8";
$_SESSION["locale"] = $locale;                                                                                                                                                                                          
// activate the locale setting                                                                                                                                                            
putenv("LANG=" . $_SESSION["locale"]);                                                                                                                                                                
setlocale(LC_ALL, $_SESSION["locale"]);                                                                                                                                                               
                                                                                                                                                                                          
bindtextdomain(LOCALE_DOMAIN, LOCALE_ROOT);                                                                                                                                                    
bind_textdomain_codeset(LOCALE_DOMAIN, 'UTF-8');                                                                                                                                                
                                                                                                                                                                                          
textdomain(LOCALE_DOMAIN);                                                                                                                                                                      
?>

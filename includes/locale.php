<?php

/**
* Sets locale information for i18n support
*
*/


/** 
* Rudimentary language detection via the browser.
* Accept-Language returns a list of weighted values with a quality (or 'q') parameter.
* A better method would parse the list of preferred languages and match this with 
* the languages supported by our platform.
*
* Refer to: https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
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
    case "es":
      $locale = "es_ES.UTF-8";
      break;       
    default:
      $locale = "en_US.UTF-8";
      break;
  }
}

// Uncomment for testing
// Note: the associated locale must be installed on the RPi 
// $locale = "fr_FR.UTF-8";
$_SESSION["locale"] = $locale;                                                                                                                                                                                          
// activate the locale setting                                                                                                                                                            
putenv("LANG=" . $_SESSION["locale"]);                                                                                                                                                                
setlocale(LC_ALL, $_SESSION["locale"]);                                                                                                                                                               
                                                                                                                                                                                          
bindtextdomain(LOCALE_DOMAIN, LOCALE_ROOT);                                                                                                                                                    
bind_textdomain_codeset(LOCALE_DOMAIN, 'UTF-8');                                                                                                                                                
                                                                                                                                                                                          
textdomain(LOCALE_DOMAIN);                                                                                                                                                                      
?>

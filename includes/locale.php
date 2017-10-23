<?php

/**
* Sets locale information for i18n support
*
*/

$lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

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
                                                                                                                                                                                          
// activate the locale setting                                                                                                                                                            
putenv("LANG=" . $locale);                                                                                                                                                                
setlocale(LC_ALL, $locale);                                                                                                                                                               
                                                                                                                                                                                          
$domain = "messages";                                                                                                                                                                     
$locale_root = "locale";                                                                                                                                              
bindtextdomain($domain, $locale_root);                                                                                                                                                    
bind_textdomain_codeset($domain, 'UTF-8');                                                                                                                                                
                                                                                                                                                                                          
textdomain($domain);                                                                                                                                                                      
                                                                                                                                                                                          
// debug                                                                                                                                                                                  
echo '<br>locale: ' . $locale . "<br>";                                                                                                                                                       
echo 'locale root: ' .$locale_root . "<br>";                                                                                                                                              
                                                                                                                                                                                          
$results = bindtextdomain($domain, $locale_root);                                                                                                                                         
echo 'new text domain is set: ' . $results. "<br>";                                                                                                                                       
                                                                                                                                                                                          
$results = textdomain($domain);                                                                                                                                                           
echo 'current message domain is set: ' . $results. "<br>";                                                                                                                                
?>

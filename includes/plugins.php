<?php

require_once 'includes/functions.php';
require_once 'config.php';

/**
 *
 */
function DisplayPlugins()
{
    $status = new \RaspAP\Messages\StatusMessage;
    $pluginInstaller = \RaspAP\Plugins\PluginInstaller::getInstance();
    $plugins = $pluginInstaller->getUserPlugins();
    $pluginsTable = $pluginInstaller->getHTMLPluginsTable($plugins);

    echo renderTemplate("plugins", compact(
        "status",
        "pluginsTable"
    ));

}

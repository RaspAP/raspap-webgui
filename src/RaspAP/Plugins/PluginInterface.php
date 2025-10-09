<?php

/**
 * Plugin Interface
 *
 * @description Basic plugin interface for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 * @see         
 */

declare(strict_types=1);

namespace RaspAP\Plugins;

use RaspAP\UI\Sidebar;

interface PluginInterface
{
    /**
     * Initialize the plugin
     * @param Sidebar $sidebar Sidebar instance for adding items
     */
    public function initialize(Sidebar $sidebar): void;

    /**
     * Render a template within the plugin's template directory
     * @param string $templateName
     * @param array $__data
     * @return string
     */
    public function renderTemplate(string $templateName, array $__data = []): string;
}


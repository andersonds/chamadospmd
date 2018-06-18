<?php

/**
 * Plugin Manager
 *
 *
 * Created: 2007-02-12
 * Last update: 2008-09-21
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @version 0.2.1
 */

define( 'PLUGINS_PLUGINMANAGER_VERSION', '0.4.0' );

function pluginmanager_get_info() {
    return array(
                 'name' => 'Plugin Manager',
                 'version' => PLUGINS_PLUGINMANAGER_VERSION,
                 'url' => 'http://deboutv.free.fr/mantis/plugin.php?plugin=PluginManager',
                 'check' => 'http://deboutv.free.fr/mantis/check.php?plugin=PluginManager',
                 'upgrade' => 'http://deboutv.free.fr/mantis/check.php?plugin=PluginManager&upgrade=1',
                 'check_unstable' => 'http://bugtracker.morinie.fr/plugins/check.php?plugin=PluginManager',
                 'upgrade_unstable' => 'http://bugtracker.morinie.fr/plugins/check.php?upgrade=1&plugin=PluginManager' );
}

?>

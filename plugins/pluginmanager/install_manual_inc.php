<?php

/**
 * This file is used to install the plugin manually.
 *
 * Created: 2007-02-12
 * Last update: 2008-08-23
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @package PluginManager
 * @version 0.1.3
 */

$t_result = array();
$t_result['info_pm'] = array( 'name' => 'Plugin Manager', 'version' => '0.1.0', 'url' => 'http://deboutv.free.fr/mantis/plugin.php?plugin=PluginManager', 'check' => 'http://deboutv.free.fr/mantis/check.php?plugin=PluginManager', 'upgrade' => 'http://deboutv.free.fr/mantis/check.php?plugin=PluginManager&upgrade=1' );
$t_result['info'] = array( 'name' => 'Plugin Manager', 'version' => '0.3.0' );

$t_result['check']['mantis']['min'] = '1.0.5';
$t_result['check']['php']['min'] = '4.0.7';

$t_result['plugin'] = 'pluginmanager';

?>

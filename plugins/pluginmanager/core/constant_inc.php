<?php

/**
 * This file is dedicated to all constants definition relative to
 * Plugin Manager.
 *
 * Created: 2007-05-26
 * Last update: 2008-08-23
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @package PluginManager
 * @version 0.1.3
 */

/** 
 * By default, the plugins version is not checked.
 * @access private
 */
define( 'PLUGINS_PLUGINMANAGER_CHECK_VERSION_DEFAULT', OFF );

/** 
 * By default, the plugins version is not checked.
 * @access private
 */
define( 'PLUGINS_PLUGINMANAGER_CHECK_UNSTABLE_DEFAULT', OFF );

/** 
 * By default, the plugins url will be displayed.
 * @access private
 */
define( 'PLUGINS_PLUGINMANAGER_DISPLAY_WEBSITE_DEFAULT', ON );

/** 
 * By default, configure a plugin requires ADMINISTRATOR
 * access level.
 * @access private
 */
define( 'PLUGINS_PLUGINMANAGER_CONFIGURE_THRESHOLD_DEFAULT', ADMINISTRATOR );

/** 
 * By default, install/upgrade/uninstall a plugin requires
 * ADMINISTRATOR access level.
 * @access private
 */
define( 'PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT', ADMINISTRATOR );

/** 
 * By default, the URL to check for the available list of plugin
 * is http://deboutv.free.fr/mantis/list_plugin.php
 * @access private
 */
define( 'PLUGINS_PLUGINMANAGER_URL_LIST_DEFAULT', 'http://deboutv.free.fr/mantis/list_plugin.php' );

/** 
 * Constant used to report a failure during a step
 * (of install/upgrade/uninstall).
 */
define( 'PLUGINS_PLUGINMANAGER_FAIL', 0 );

/** 
 * Constant used to report a warning during a step
 * (of install/upgrade/uninstall).
 */
define( 'PLUGINS_PLUGINMANAGER_WARNING', 1 );

/** 
 * Constant used to report an OK during a step
 * (of install/upgrade/uninstall).
 */
define( 'PLUGINS_PLUGINMANAGER_OK', 2 );

?>

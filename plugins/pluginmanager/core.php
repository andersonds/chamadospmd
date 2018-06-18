<?php

/**
 * Plugin Manager
 *
 *
 * Created: 2007-05-26
 * Last update: 2008-08-24
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @version 0.1.4
 */

if ( !$g_skip_open_db ) {

    if ( !function_exists( 'auth_is_user_authenticated' ) ) {
        global $g_default_mapping;
        
        require_once( dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'authentication_api.php' );
        require_once( dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'user_api.php' );
        require_once( dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'project_api.php' );
        require_once( dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'access_api.php' );
    }

    require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'constant_inc.php' );
    require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'function_api.php' );
    require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'backup_api.php' );
    require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'plugin_api.php' );
    require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'file_api.php' );
    require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'xml_api.php' );

    if ( pm_plugin_is_installed( 'pluginmanager' ) ) {
        $t_plugin_list = plugins_pluginmanager_get_plugin_list( false, true );
        $t_dir = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR;
        foreach( $t_plugin_list as $t_plugin ) {
            $t_core_file = $t_dir . $t_plugin['id'] . DIRECTORY_SEPARATOR . 'core.php';
            if ( file_exists( $t_core_file ) ) {
                require_once( $t_core_file );
            }
        }
    }

}

?>

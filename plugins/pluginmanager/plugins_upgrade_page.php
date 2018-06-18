<?php

/**
 * Plugin manager
 *
 *
 * Created: 2007-05-20
 * Last update: 2008-01-12
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @version 0.2.0
 */

require_once( 'core.php' );

$t_plugin = gpc_get_string( 'plugin' );
$t_info = pm_get_plugin_info( $t_plugin );
if ( !isset( $t_info['upgrade'] ) ) {
    if ( !isset( $t_info['url'] ) ) {
        header( 'Location: ' . config_get( 'path' ) . 'index.php' );
        exit();
    } else {
        header( 'Location: ' . $t_info['url'] );
        exit();
    }
} else {
    if ( isset( $t_info['check_unstable'] ) && config_get( 'plugins_pluginmanager_check_unstable', PLUGINS_PLUGINMANAGER_CHECK_UNSTABLE_DEFAULT ) == ON ) {
        $t_file = @fopen( $t_info['upgrade_unstable'], 'r' );
    } else {
        $t_file = @fopen( $t_info['upgrade'], 'r' );
    }
    if ( $t_file ) {
        $t_url = fscanf( $t_file, "%s\n" );
        $t_url = $t_url[0];
        fclose( $t_file );
        header( 'Location: ' . config_get( 'path' ) . 'plugins_install_page.php?url=' . urlencode( $t_url ) );
        exit();
    } else {
        if ( !isset( $t_info['url'] ) ) {
            header( 'Location: ' . config_get( 'path' ) . 'index.php' );
            exit();
        } else {
            header( 'Location: ' . $t_info['url'] );
            exit();
        }
    }
}

?>
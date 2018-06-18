<?php

/**
 * Plugin manager - Plugin Packager
 *
 *
 * Created: 2007-02-04
 * Last update: 2008-01-12
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @version 0.2.0
 */

require_once( 'core.php' );

$t_plugin = gpc_get_string( 'plugin', 'none' );

require_once( 'plugins' . DIRECTORY_SEPARATOR . 'pluginmanager' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'plugin_api.php' );

$t_user_id = auth_get_current_user_id();

$t_package_threshold = config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT );

if ( user_get_access_level( $t_user_id ) < $t_package_threshold ) {
    header( 'Location: ' . config_get( 'path' ) . 'plugins_page.php' );
    exit();
}

function plugins_pluginmanager_package_files( &$p_result, $p_file ) {
    global $t_plugin_directory;
    global $t_install_file;
    
    if ( is_dir( $p_file ) ) {
        $t_dir = opendir( $p_file );
        while( ( $t_file = readdir( $t_dir ) ) !== false ) {
            if ( $t_file != '.' && $t_file != '..' ) {
                plugins_pluginmanager_package_files( $p_result, $p_file . DIRECTORY_SEPARATOR . $t_file );
            }
        }
        closedir( $t_dir );        
    } else {
        if ( $t_plugin_directory . 'package_inc.php' != $p_file && $t_plugin_directory . 'install_manual_inc.php' != $p_file && $t_plugin_directory . 'file_package_inc.php' != $p_file ) {
            if ( filesize( $p_file ) == 0 ) {
                $p_result['files'][str_replace( DIRECTORY_SEPARATOR, '/', $p_file )] = base64_encode( '' );
            } else {
                $t_file = fopen( $p_file, 'r' );
                $p_result['files'][str_replace( DIRECTORY_SEPARATOR, '/', $p_file )] = base64_encode( fread( $t_file, filesize( $p_file ) ) );
                fclose( $t_file );
                if ( $t_plugin_directory . 'install_inc.php' == $p_file ) {
                    $t_install_file = true;
                }
            }
        }
    }
}

$t_plugin_directory = 'plugins' . DIRECTORY_SEPARATOR . $t_plugin . DIRECTORY_SEPARATOR;

include( $t_plugin_directory . 'package_inc.php' );

$t_result = array();

$t_result['plugin'] = $t_plugin;
$t_result['check'] = array();
$t_install_file = false;
if ( isset( $t_pluginmanager_checks ) && is_array( $t_pluginmanager_checks ) ) {
    $t_result['check'] = $t_pluginmanager_checks;
}
$t_result['files'] = array();
if ( isset( $t_pluginmanager_directories ) && is_array( $t_pluginmanager_directories ) ) {
    foreach( $t_pluginmanager_directories as $t_directory ) {
        if ( !ereg( '^..', $t_directory ) ) {
            $t_directory = pathinfo( $t_plugin_directory . $t_directory );
            plugins_pluginmanager_package_files( $t_result, $t_directory['dirname'] );
        }
    }
}
if ( isset( $t_pluginmanager_files ) && is_array( $t_pluginmanager_files ) ) {
    foreach( $t_pluginmanager_files as $t_file ) {
        $t_file = $t_plugin_directory . $t_file;
        plugins_pluginmanager_package_files( $t_result, $t_file );
    }
}
if ( isset( $t_pluginmanager_remove_files ) && is_array( $t_pluginmanager_remove_files ) && $t_pluginmanager_remove_files != array() ) {
    $t_result['remove'] = array();
    foreach( $t_pluginmanager_remove_files as $t_file ) {
        $t_result['remove'][] = $t_file;
    }
}
if ( !$t_install_file ) {
    plugins_pluginmanager_package_files( $t_result, $t_plugin_directory . 'install_inc.php' );
}

$t_info = pm_get_plugin_info( $t_plugin );

$t_result['info'] = $t_info;
$t_result['info_pm'] = pm_get_plugin_info( 'pluginmanager' );
if ( $t_plugin == 'pluginmanager' ) {
    $t_result['info_pm']['version'] = '0.1.0';
}

$t_result = serialize( $t_result );
$t_result = gzcompress( $t_result, 9 );

header( 'Pragma: public' );
header( 'Content-Description: File Transfer' );
header( 'Content-Type: application/octet-stream' );
header( 'Content-Transfer-Encoding: binary;' );
header( 'Content-Disposition: attachment; filename="' . $t_plugin . '-' . $t_info['version'] . '.mantis.pkg' );

echo $t_result;

?>

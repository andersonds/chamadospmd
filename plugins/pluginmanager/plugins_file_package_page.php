<?php

/**
 * Plugin manager - File Packager
 *
 *
 * Created: 2007-10-13
 * Last update: 2008-01-22
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @version 0.2.0
 */

require_once( 'core.php' );

$t_file = gpc_get_string( 'file', 'none' );

require_once( 'plugins' . DIRECTORY_SEPARATOR . 'pluginmanager' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'plugin_api.php' );

$t_user_id = auth_get_current_user_id();

$t_package_threshold = config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT );

$t_plugin = explode( '-', $t_file );

if ( user_get_access_level( $t_user_id ) < $t_package_threshold || count( $t_plugin ) != 2 || !preg_match( '/^([A-z0-9_]+)$/', $t_plugin[1] ) ) {
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
        if ( filesize( $p_file ) == 0 ) {
            $p_result['files'][str_replace( DIRECTORY_SEPARATOR, '/', $p_file )] = base64_encode( '' );
        } else {
            $t_file = fopen( $p_file, 'r' );
            $p_result['files'][str_replace( DIRECTORY_SEPARATOR, '/', $p_file )] = base64_encode( fread( $t_file, filesize( $p_file ) ) );
            fclose( $t_file );
        }
    }
}

$t_plugin_directory = 'plugins' . DIRECTORY_SEPARATOR . $t_plugin[0] . DIRECTORY_SEPARATOR;

include( $t_plugin_directory . 'file_package_inc.php' );

$t_result = array();

$i = 0;
while( $i < count( $t_pluginmanager_file_package ) && $t_pluginmanager_file_package[$i]['id'] != $t_plugin[1] ) {
    $i++;
}

if ( $t_pluginmanager_file_package[$i]['id'] == $t_plugin[1] ) {
    $t_plg = $t_pluginmanager_file_package[$i];
    $t_result['file_plugin'] = $t_plg['id'];
    $t_result['plugin_dir'] = $t_plugin[0];
    $t_result['check'] = array();
    $t_install_file = false;
    if ( isset( $t_plg['checks'] ) && is_array( $t_plg['checks'] ) ) {
        $t_result['check'] = $t_plg['checks'];
    }
    $t_result['files'] = array();
    if ( isset( $t_plg['directories'] ) && is_array( $t_plg['directories'] ) ) {
        foreach( $t_plg['directories'] as $t_directory ) {
            if ( !ereg( '^\.\.', $t_directory ) ) {
                $t_directory = pathinfo( $t_plugin_directory . $t_directory );
                plugins_pluginmanager_package_files( $t_result, $t_directory['dirname'] );
            }
        }
    }
    if ( isset( $t_plg['files'] ) && is_array( $t_plg['files'] ) ) {
        foreach( $t_plg['files'] as $t_file ) {
            $t_file = $t_plugin_directory . $t_file;
            plugins_pluginmanager_package_files( $t_result, $t_file );
        }
    }
    if ( isset( $t_plg['remove_files'] ) && is_array( $t_plg['remove_files'] ) && $t_plg['remove_files'] != array() ) {
        $t_result['remove'] = array();
        foreach( $t_plg['remove_files'] as $t_file ) {
            $t_result['remove'][] = $t_file;
        }
    }   
    $t_result['info'] = array( 'name' => $t_plg['name'], 'version' => $t_plg['version'] );
    $t_result['info_file_pm'] = pm_get_plugin_info( 'pluginmanager' );
}

$t_result = serialize( $t_result );
$t_result = gzcompress( $t_result, 9 );

header( 'Pragma: public' );
header( 'Content-Description: File Transfer' );
header( 'Content-Type: application/octet-stream' );
header( 'Content-Transfer-Encoding: binary;' );
header( 'Content-Disposition: attachment; filename="' . $t_plugin[0] . '-' . $t_plugin[1] . '-' . $t_plg['version'] . '.mantis.pkg' );

echo $t_result;

?>

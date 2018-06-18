<?php

/**
 * This file is dedicated to all functions used to manage the
 * default Mantis functions. 
 *
 * Created: 2007-05-26
 * Last update: 2008-09-21
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @package PluginManager
 * @version 0.2.0
 */

/**
 * This function is used by the install/upgrade script to overwrite an
 * existing function.
 * This function returns '' if the function has been overwritten.
 * If the function has already been overwritten, this function
 * returns the list of the plugins using the overwritten function. The
 * first plugin of the list is the plugin that actually handle the
 * overwrite. Installer must check if it can manage all the requirements
 * of all plugins in the list before retry another overwrite. If it is
 * possible, the installer must recall this function and pass the list
 * of plugins as $p_plugins.
 * If the overwrite fails, this function returns ****ERROR****.
 * 
 * @param string $p_function_name The name of the function to overwrite.
 * @param string $p_file The name of the file where the function is located.
 * The file _must_ be relative to the Mantis root directory. '/' must
 * be used as directory separator.
 * @param string $p_plugin The name of the plugin that overwrites the
 * function.
 * @param string $p_plugins The list of the plugin that have already
 * overwrite the function.
 * @return string
 */
function pm_function_add_overwrite( $p_function_name, $p_file, $p_plugin, $p_plugins = '' ) {
    global $g_file;
    
    $t_overwrite_function = 'plugins_' . $p_plugin . '_' . $p_function_name;
    $t_query = 'SELECT `plugins` FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' ) . ' WHERE `function`=\'' . db_prepare_string( $p_function_name ) . '\' AND `file`=\'' . db_prepare_string( $p_file ) . '\'';
    $t_result = db_query( $t_query );
    if ( db_num_rows( $t_result ) == 1 && $p_plugins == '' ) {
        $t_row = db_fetch_array( $t_result );
        if ( $t_row['plugins'] == '' ) {
            $t_query = 'UPDATE ' . config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' ) . ' SET `new_function`=\'' . db_prepare_string( $t_overwrite_function ) . '\', `plugins`=\'' . db_prepare_string( $p_plugin ) . '\' WHERE `function`=\'' . db_prepare_string( $p_function_name ) . '\' AND `plugins`=\'' . db_prepare_string( $p_plugins ) . '\' AND `file`=\'' . db_prepare_string( $p_file ) . '\'';
            db_query( $t_query );
        }
        return $t_row['plugins'];
    } else if ( db_num_rows( $t_result ) == 1 ) {
        $t_query = 'UPDATE ' . config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' ) . ' SET `new_function`=\'' . db_prepare_string( $t_overwrite_function ) . '\', `plugins`=\'' . db_prepare_string( $p_plugin . ',' . $p_plugins ) . '\' WHERE `function`=\'' . db_prepare_string( $p_function_name ) . '\' AND `plugins`=\'' . db_prepare_string( $p_plugins ) . '\' AND `file`=\'' . db_prepare_string( $p_file ) . '\'';
        db_query( $t_query );
        return '';
    }
    $t_query = 'INSERT INTO ' . config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' ) . ' ( `plugin`, `function`, `new_function`, `plugins`, `file` ) VALUES ( \'' . db_prepare_string( $p_plugin ) . '\', \'' . db_prepare_string( $p_function_name ) . '\', \'' . db_prepare_string( $t_overwrite_function ) . '\', \'' . db_prepare_string( $p_plugin ) . '\', \'' . db_prepare_string( $p_file ) . '\' )';
    $t_result = db_query( $t_query );
    if ( $t_result ) {
        $g_file = $p_file;
        $t_file = str_replace( '/', DIRECTORY_SEPARATOR, $p_file );
        if ( pm_backup_file( $p_file ) && is_writeable( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . $t_file ) ) {
            $t_handle = fopen( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . $t_file, 'r' );
            $t_file_content = fread( $t_handle, filesize( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . $t_file ) );
            fclose( $t_handle );
            if ( !preg_match( '/pm_old_' . $p_function_name . '/', $t_file_content ) ) {
                $t_new_file_content = preg_replace_callback( '/function[ \t\n]+(' . $p_function_name . ')[ \t\n]*\(/', 'plugins_pluginmanager_function_callback_replace', $t_file_content );
                if ( $t_new_file_content != $t_file_content ) {
                    $t_handle = fopen( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . $t_file, 'w' );
                    fwrite( $t_handle, $t_new_file_content );
                    fclose( $t_handle );
                    return '';
                }
            } else {
		return '';
	    }
            pm_backup_remove_file( $p_file );
        }
        $t_query = 'DELETE FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' ) . ' WHERE `function`=\'' . db_prepare_string( $p_function_name ) . '\'';
        db_query( $t_query );
    }
    return '****ERROR****';
}

/**
 * This function is used by the install/upgrade script to remove an
 * overwritten function.
 * 
 * @param string $p_function_name The name of the function to overwrite.
 * @param string $p_file The name of the file where the function is located.
 * The file _must_ be relative to the Mantis root directory. '/' must
 * be used as directory separator.
 * @param string $p_plugin The name of the plugin that overwrites the
 * function.
 * @return string
 */
function pm_function_remove_overwrite( $p_function_name, $p_file, $p_plugin ) {
    $t_query = 'SELECT `plugins` FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' ) . ' WHERE `function`=\'' . db_prepare_string( $p_function_name ) . '\' AND `file`=\'' . db_prepare_string( $p_file ) . '\'';
    $t_result = db_query( $t_query );
    $t_row = db_fetch_array( $t_result );
    $t_plugins = explode( ',', $t_row['plugins'] );
    for( $i=0; $i<count( $t_plugins ); $i++ ) {
        if ( $t_plugins[$i] == $p_plugin ) {
            if ( $i == 0 && isset( $t_plugins[1] ) ) {
                $t_overwrite_function = 'plugins_' . $t_plugins[1] . '_' . $p_function_name;
            } else if ( $i == 0 && !isset( $t_plugins[1] ) ) {
                $t_overwrite_function = 'pm_old_' . $p_function_name;
            } else {
                $t_overwrite_function = 'plugins_' . $t_plugins[0] . '_' . $p_function_name;
            }
            unset( $t_plugins[$i] );
        }
    }
    $t_plugins = implode( ',', $t_plugins );
    $t_query = 'UPDATE ' . config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' ) . ' SET `new_function`=\'' . db_prepare_string( $t_overwrite_function ) . '\', `plugins`=\'' . db_prepare_string( $t_plugins ) . '\' WHERE `function`=\'' . db_prepare_string( $p_function_name ) . '\' AND `file`=\'' . db_prepare_string( $p_file ) . '\'';
    db_query( $t_query );
    return '';
}

/** 
 * @access private
 */
function plugins_pluginmanager_function_overwrite( $p_function_name, $p_arguments, $p_file ) {
    $t_function_name = 'pm_old_' . $p_function_name;
    $t_function_name_old = $t_function_name;
    $t_query = 'SELECT `new_function` FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' ) . ' WHERE `function`=\'' . db_prepare_string( $p_function_name ) . '\' AND `file`=\'' . db_prepare_string( $p_file ) . '\'';
    $t_result = db_query( $t_query );
    if ( db_num_rows( $t_result ) == 1 ) {
        $t_row = db_fetch_array( $t_result );
        /**
        * Prevent infinite loop if the new_function is the same as
        * the old one
        */
        if ( $t_row['new_function'] != $p_function_name ) {
            $t_function_name = $t_row['new_function'];
        }
    }
    if ( function_exists( $t_function_name ) ) {
        return call_user_func_array( $t_function_name, $p_arguments );
    } else {
        return call_user_func_array( $t_function_name_old, $p_arguments );
    }
}

/** 
 * @access private
 */
function plugins_pluginmanager_function_callback_replace( $p_matches ) {
    global $g_file;
    
    $t_result = $p_matches[0] . ') {';
    $t_result .= "\n\t\t" . '$t_arguments = func_get_args();';
    $t_result .= "\n\t\t" . 'return plugins_pluginmanager_function_overwrite( \'' . $p_matches[1] . '\', $t_arguments, \'' . $g_file . '\' );';
    $t_result .= "\n\t}\n\n\t" . 'function pm_old_';
    $t_result .= $p_matches[1] . '(';
    return $t_result;
}

/**
 * This function is used to repair the overwrite.
 * 
 * @return int
 * @access private
 */
function plugins_pluginmanager_function_repair_overwrite() {
    global $g_file;
    
    $t_query = 'SELECT `function`, `file` FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' );
    $t_result = db_query( $t_query );
    if ( db_num_rows( $t_result ) > 0 ) {
        while( $t_row = db_fetch_array( $t_result ) ) {
            $g_file = $t_row['file'];
            $t_file = str_replace( '/', DIRECTORY_SEPARATOR, $t_row['file'] );
            if ( pm_backup_file( $t_row['file'] ) && is_writeable( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . $t_file ) ) {
                $t_handle = fopen( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . $t_file, 'r' );
                $t_file_content = fread( $t_handle, filesize( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . $t_file ) );
                fclose( $t_handle );
                if ( !preg_match( '/pm_old_' . $t_row['function'] . '/', $t_file_content ) ) {
                    $t_new_file_content = preg_replace_callback( '/function[ \t\n]+(' . $t_row['function'] . ')[ \t\n]*\(/', 'plugins_pluginmanager_function_callback_replace', $t_file_content );
                    if ( $t_new_file_content != $t_file_content ) {
                        $t_handle = fopen( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . $t_file, 'w' );
                        fwrite( $t_handle, $t_new_file_content );
                        fclose( $t_handle );
                        return '';
                    }
                }
                pm_backup_remove_file( $t_row['file'] );
            }
        }
    }
}

?>

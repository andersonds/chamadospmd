<?php

/**
 * Plugin Manager
 *
 *
 * Created: 2007-02-13
 * Last update: 2008-01-22
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @version 0.1.4
 */

global $g_skip_open_db;

if ( !$g_skip_open_db ) {

    $t_lang = lang_get_current();

    require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'core.php' );

    $t_plugin_list = plugins_pluginmanager_get_plugin_list( false, true );
    $t_dir = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR;
    foreach( $t_plugin_list as $t_plugin ) {
        $t_lang_file = $t_dir . $t_plugin['id'] . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'strings_' . $t_lang . '.txt';
        if ( file_exists( $t_lang_file ) ) {
            require( $t_lang_file );
        } else {
            $t_lang_file = $t_dir . $t_plugin['id'] . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'strings_english.txt';
            if ( file_exists( $t_lang_file ) ) {
                require( $t_lang_file );
            }       
        }
    }

    if ( !eregi( 'utf-8', $s_charset ) ) {
        $t_vars = get_defined_vars();
        
        foreach ( array_keys( $t_vars ) as $t_var ) {
            if ( ereg( '^s_plugins_', $t_var ) ) {
                eval( '$' . $t_var . ' = utf8_decode( \'' . str_replace( "'", "\\'", $$t_var ) . '\' );' );
            }
        }
    }

}

?>

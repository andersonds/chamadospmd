<?php

/**
 * This file is dedicated to all plugin functions.
 *
 * Created: 2007-02-12
 * Last update: 2008-01-12
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @package PluginManager
 * @version 0.2.0
 * @access private
 */

/** 
 * @access private
 */
function plugins_pluginmanager_install_load_lang() {
    global $g_lang_strings;

    $t_lang = lang_get_current();
    $t_lang_path = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
    if ( file_exists( $t_lang_path . 'strings_' . $t_lang . '.txt' ) ) {
        include( $t_lang_path . 'strings_' . $t_lang . '.txt' );
    } else {
        if ( file_exists( $t_lang_path . 'strings_english.txt' ) ) {
            include( $t_lang_path . 'strings_english.txt' );
        }
    }
    $t_vars = get_defined_vars();
    foreach( array_keys( $t_vars ) as $t_var ) {
        $t_lang_var = ereg_replace( '^s_', '', $t_var );
        if ( $t_lang_var != $t_var || 'MANTIS_ERROR' == $t_var ) {
            $g_lang_strings[$t_lang][$t_lang_var] = $$t_var;
        }
    }
}

if ( !isset( $g_lang_strings[lang_get_current()]['plugins_pluginmanager_title'] ) ) {
    plugins_pluginmanager_install_load_lang();
}

?>

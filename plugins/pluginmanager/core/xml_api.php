<?php

/**
 * Plugin Manager plugin
 *
 *
 * Created: 2008-08-23
 * Last update: 2008-08-29
 *
 * @link http://deboutv.free.fr/mantis/
 * @author DEBOUT Vincent  <deboutv@free.fr>
 */

function plugins_pluginmanager_StartElement( $p_parser, $p_name, $p_attrs ) {
    global $t_current;
    global $t_mantis;
    global $t_plugin;
    
    if ( $p_name == 'MANTISPLUGIN' ) {
        if ( isset( $t_mantis ) ) {
            $t_error = true;
        }
        $t_mantis = array();
    }
    if ( $p_name == 'PLUGINS' && $t_current[count( $t_current ) - 1] == 'mantisplugin' ) {
        if ( !isset( $t_mantis['plugins'] ) ) {
            $t_mantis['plugins'] = array();
        }
    }
    if ( $p_name == 'PLUGIN' && $t_current[count( $t_current ) - 1] == 'plugins' ) {
        $t_plugin = array();
    }
    array_push( $t_current, strtolower( $p_name ) );
}

function plugins_pluginmanager_EndElement( $p_parser, $p_name ) {
    global $t_result;
    global $t_mantis;
    global $t_current;
    global $t_plugin;
    
    if ( $p_name == 'MANTISPLUGIN' ) {
        $t_result[] = $t_mantis;
        unset( $GLOBALS['t_mantis'] );
    }
    if ( $p_name == 'PLUGIN' ) {
        $t_mantis['plugins'][] = $t_plugin;
        unset( $GLOBALS['t_bug'] );
    }
    array_pop( $t_current );
}

function plugins_pluginmanager_CharacterData( $p_parser, $p_data ) {
    global $t_current;
    global $t_plugin;
    global $t_mantis;
    global $g_charset;
    global $t_attr;
    
    $t_data = html_entity_decode( $p_data, ENT_NOQUOTES );
    if ( $g_charset ) {
        $t_data = utf8_decode( $p_data );
    }
    switch( $t_current[count( $t_current ) - 2] ) {
      case 'mantisplugin':
        break;
      case 'plugin':
        $t_plugin[$t_current[count( $t_current ) - 1]] = $t_data;
        break;
      default:
        break;
    }
}

function plugins_pluginmanager_xml_parse( $p_content ) {
    global $t_result;
    global $t_current;
    global $t_error;
    global $g_charset;
    
    $t_result = array();
    $t_current = array();
    $t_error = false;
    if ( lang_get( 'charset' ) != 'utf-8' ) {
        $g_charset = true;
    } else {
        $g_charset = false;
    }
    $t_parser = xml_parser_create( '' );
    xml_set_element_handler( $t_parser, 'plugins_pluginmanager_StartElement', 'plugins_pluginmanager_EndElement' );
    xml_set_character_data_handler( $t_parser, 'plugins_pluginmanager_CharacterData' );
    if ( !xml_parse( $t_parser, $p_content, true ) ) {
        echo '<font color="red">Error</font>: ';
        echo xml_get_current_line_number( $t_parser ) . ':' . xml_get_current_column_number( $t_parser ) . ', ';
        echo xml_error_string( xml_get_error_code( $t_parser ) );
        echo '<br />' . "\n";
        return array();
    }
    xml_parser_free( $t_parser );
    if ( $t_error ) {
        echo '<font color="red">Error</font>: ';
        echo 'Format not supported';
        echo '<br />' . "\n";
        $t_result = array();
    }
    return $t_result;
}

if ( !function_exists( 'html_entity_decode' ) ) {
    function html_entity_decode( $p_data, $p_not_used ) {
        $t_result = str_replace( '&lt;', '<', $p_data );
        $t_result = str_replace( '&gt;', '>', $t_result );
        $t_result = str_replace( '&amp;', '&', $t_result );
        return $t_result;
    }
}

?>

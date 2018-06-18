<?php

/**
 * This file is used to install the plugin manually.
 *
 * Created: 2007-02-12
 * Last update: 2008-09-21
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @package PluginManager
 * @version 0.2.0
 */

if ( !ereg( 'plugins_install_page.php', $_SERVER['PHP_SELF'] ) && !ereg( 'plugins_uninstall_page.php', $_SERVER['PHP_SELF'] ) ) {
    header( 'Location: ../../plugins_page.php' );
    exit();
}

$t_pm_info = pm_get_plugin_info( 'pluginmanager' );

$t_plugins_plugin_manager_step_count = array();
$t_plugins_plugin_manager_step_count['install'] = 4;
$t_plugins_plugin_manager_step_count['upgrade'] = ( version_compare( $t_pm_info['version'], '0.1.2', '<' ) ) ? 4 : 2;
$t_plugins_plugin_manager_step_count['uninstall'] = 3;
$t_plugins_plugin_manager_step_count['mantis_upgrade'] = 0;
$t_plugins_plugin_manager_step_count['mantis_repair'] = ( version_compare( $t_pm_info['version'], '0.1.2', '<' ) ) ? 6 : 4;

$t_file_list = array( 'plugins_page.php', 'plugins_install_page.php', 'plugins_uninstall_page.php', 'plugins_package_page.php', 'plugins_file_package_page.php', 'plugins_upgrade_page.php', 'plugins_mantis_upgrade_page.php', 'plugins_list_page.php' );

function plugins_pluginmanager_install_description_plugin( $p_step ) {
    if ( $p_step == 1 ) {
        return 'Check functions';
    } else if ( $p_step == 2 ) {
        return 'SQL upgrade';
    } else if( $p_step == 3 ) {
        return 'Patching files';
    } else {
        return 'Finishing installation';
    }
}

function plugins_pluginmanager_upgrade_description_plugin( $p_step ) {
    if ( $p_step == 1 ) {
        return 'Moving files';
    } else if ( $p_step == 2 ) {
        return 'Change plugin link';
    } else if ( $p_step == 3 ) {
        return 'SQL upgrade';
    } else {
        return 'Patching files';
    }
}

function plugins_pluginmanager_uninstall_description_plugin( $p_step ) {
    if ( $p_step == 1 ) {
        return 'Moving installed files';
    } else if ( $p_step == 2 ) {
        return 'Remove menu link';
    } else {
        return 'Removing SQL tables';
    }
}

function plugins_pluginmanager_mantis_upgrade_description_plugin( $p_step ) {
    return '';
}

function plugins_pluginmanager_mantis_repair_description_plugin( $p_step ) {
    return '';
}

function plugins_pluginmanager_install_plugin( &$p_msg, $p_step ) {
    global $g_plugins_manager_core;
    global $g_plugins_manager_lang;
    global $g_db;
    $p_msg = '';

    if ( $p_step == 1 ) {
        if ( !function_exists( 'gzuncompress' ) ) {
            $p_msg = 'The pluginManger needs gzuncompress';
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
    } else if ( $p_step == 2 ) {
        if ( db_is_connected() ) {
            $t_dict = NewDataDictionary( $g_db );
            $t_table_name = config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' );
            $t_table_options = array( 'mysql' => 'TYPE=MyISAM', 'pgsql' => 'WITHOUT OIDS' );
            if ( !db_table_exists( $t_table_name ) ) {
                $t_table_fields = "`plugin` C(50) NOTNULL PRIMARY, `installed` I UNSIGNED DEFAULT '0'";
                $t_res = $t_dict->ExecuteSQLArray( $t_dict->CreateTableSQL( $t_table_name, $t_table_fields, $t_table_options ) );
                if ( $t_res != 2 ) {
                    return PLUGINS_PLUGINMANAGER_FAIL;
                }
            }
            $t_table_name = config_get( 'db_table_prefix' ) . '_plugins_pm_dependencies' . config_get( 'db_table_suffix' );
            if ( !db_table_exists( $t_table_name ) ) {
                $t_table_fields = "`plugin` C(50) NOTNULL PRIMARY, `dependencies` XL";
                $t_res = $t_dict->ExecuteSQLArray( $t_dict->CreateTableSQL( $t_table_name, $t_table_fields, $t_table_options ) );
                if ( $t_res != 2 ) {
                    return PLUGINS_PLUGINMANAGER_FAIL;
                }
            }
            $t_table_name = config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' );
            if ( !db_table_exists( $t_table_name ) ) {
                $t_table_fields = "`id` I UNSIGNED AUTOINCREMENT PRIMARY, `plugin` C(50) NOTNULL, `function` C(100) NOTNULL, `new_function` C(100) NOTNULL, `plugins` XL, `file` C(150) NOTNULL";
                $t_res = $t_dict->ExecuteSQLArray( $t_dict->CreateTableSQL( $t_table_name, $t_table_fields, $t_table_options ) );
                if ( $t_res != 2 ) {
                    return PLUGINS_PLUGINMANAGER_FAIL;
                }
            }
        } else {
            $p_msg = 'Database not connected';
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
    } else if ( $p_step == 3 ) {
        if ( !is_writeable( 'core.php' ) ) {
            $p_msg = 'core.php file is not writeable';
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
        if ( !pm_backup_file( 'core.php' ) ) {
            $p_msg = pm_backup_error();
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
        if ( !isset( $g_plugins_manager_core ) ) {
            $t_file = fopen( 'core.php', 'a' );
            fwrite( $t_file, '<?php' . "\n\t" . 'if ( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . \'plugins\' . DIRECTORY_SEPARATOR . \'pluginmanager\' . DIRECTORY_SEPARATOR . \'core.php\' ) ) {' );
            fwrite( $t_file, "\n\t\t" . 'require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . \'plugins\' . DIRECTORY_SEPARATOR . \'pluginmanager\' . DIRECTORY_SEPARATOR . \'core.php\' );' );
            fwrite( $t_file, "\n\t\t" . '$g_plugins_manager_core = ON;' );
            fwrite( $t_file, "\n\t}\n" . '?>' );
            fclose( $t_file );
        }
        if ( file_exists( 'custom_strings_inc.php' ) && !pm_backup_file( 'custom_strings_inc.php' ) ) {
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
        if ( !isset( $g_plugins_manager_lang ) ) {
            $t_file = fopen( 'custom_strings_inc.php', 'a' );
            fwrite( $t_file, '<?php' . "\n\t" . 'if ( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . \'plugins\' . DIRECTORY_SEPARATOR . \'pluginmanager\' . DIRECTORY_SEPARATOR . \'lang_inc.php\' ) ) {' );
            fwrite( $t_file, "\n\t\t" . 'require( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . \'plugins\' . DIRECTORY_SEPARATOR . \'pluginmanager\' . DIRECTORY_SEPARATOR . \'lang_inc.php\' );' );
            fwrite( $t_file, "\n\t\t" . 'global $g_plugins_manager_lang;' . "\n\t\t" . '$g_plugins_manager_lang = ON;' );
            fwrite( $t_file, "\n\t}\n" . '?>' );
            fclose( $t_file );
        }
    } else {
        $t_main_menu_custom_options = config_get( 'main_menu_custom_options', array() );
        $t_found = false;
        if ( isset( $t_main_menu_custom_options ) && is_array( $t_main_menu_custom_options ) ) {
            foreach( $t_main_menu_custom_options as $t_menu_link ) {
                if ( $t_menu_link[0] == 'plugins_pluginmanager_link' ) {
                    $t_found = true;
                }
            }
        } else {
            $t_main_menu_custom_options = array();
        }
        if ( !$t_found ) {
            $t_main_menu_custom_options[] = array( 'plugins_pluginmanager_link', VIEWER, 'plugins_page.php' );
        }
        config_set( 'main_menu_custom_options', $t_main_menu_custom_options );
    }
    return PLUGINS_PLUGINMANAGER_OK;
}

function plugins_pluginmanager_upgrade_plugin( &$p_msg, $p_step ) {
    global $t_file_list;
    global $g_db;
        
    $p_msg = '';

    $t_pm_info = pm_get_plugin_info( 'pluginmanager' );
    if ( $p_step == 1 ) {
        if ( $t_pm_info['version'] == '0.1.0' ) {
            $t_query = 'DELETE FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' ) . ' WHERE plugin=\'pluginmanager\'';
            db_query( $t_query );
        }
        $t_dirname = dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR;
        foreach( $t_file_list as $t_file ) {
            pm_backup_file( $t_file );
            if ( file_exists( $t_dirname . $t_file ) ) {
                unlink( $t_dirname . $t_file );
            }
            if ( !pm_file_copy( 'plugins' . DIRECTORY_SEPARATOR . 'pluginmanager' . DIRECTORY_SEPARATOR . $t_file, $t_file ) ) {
                return PLUGINS_PLUGINMANAGER_FAIL;
            }
        }
    } else if ( $p_step == 2 ) {
        $t_main_menu_custom_options = config_get( 'main_menu_custom_options', array() );
        for( $i=0; $i<count( $t_main_menu_custom_options ); $i++ ) {
            if ( $t_main_menu_custom_options[$i] == array( 'plugins_pluginmanager_link', ADMINISTRATOR, 'plugins_page.php' ) ) {
                $t_main_menu_custom_options[$i] = array( 'plugins_pluginmanager_link', VIEWER, 'plugins_page.php' );
            }
        }
        config_set( 'main_menu_custom_options', $t_main_menu_custom_options );
    } else if ( $p_step == 3 ) {
        if ( db_is_connected() ) {
            $t_table_name = config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' );
            $t_table_options = array( 'mysql' => 'TYPE=MyISAM', 'pgsql' => 'WITHOUT OIDS' );
            if ( !db_table_exists( $t_table_name ) ) {
                $t_table_fields = "`id` I UNSIGNED AUTOINCREMENT PRIMARY, `plugin` C(50) NOTNULL, `function` C(100) NOTNULL, `new_function` C(100) NOTNULL, `plugins` XL, `file` C(150) NOTNULL";
                $t_res = $t_dict->ExecuteSQLArray( $t_dict->CreateTableSQL( $t_table_name, $t_table_fields, $t_table_options ) );
                if ( $t_res != 2 ) {
                    return PLUGINS_PLUGINMANAGER_FAIL;
                }
            }
        } else {
            $p_msg = 'Database not connected';
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
    } else {
        pm_backup_remove_file( 'core.php' );
        if ( !is_writeable( 'core.php' ) || !pm_backup_file( 'core.php' ) ) {
            $p_msg = 'core.php file not writeable';
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
        $t_file = fopen( 'core.php', 'r' );
        $t_file_content = fread( $t_file, filesize( 'core.php' ) );
        fclose( $t_file );
        $t_pattern = "/(file_exists|require_once)[( \t\n]+\'plugins\' \. DIRECTORY_SEPARATOR/";
        $t_replacement = '${1}( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . \'plugins\' . DIRECTORY_SEPARATOR';
        $t_new_file_content = preg_replace( $t_pattern, $t_replacement, $t_file_content );
        if ( $t_new_file_content != $t_file_content ) {
            $t_file = fopen( 'core.php', 'w' );
            fwrite( $t_file, $t_new_file_content );
            fclose( $t_file );
        }
        pm_backup_remove_file( 'custom_strings_inc.php' );
        if ( file_exists( 'custom_strings_inc.php' ) && !pm_backup_file( 'custom_strings_inc.php' ) ) {
            $p_msg = 'custom_strings_inc.php file not writeable';
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
        $t_file = fopen( 'custom_strings_inc.php', 'r' );
        $t_file_content = fread( $t_file, filesize( 'custom_strings_inc.php' ) );
        fclose( $t_file );
        $t_new_file_content = preg_replace( $t_pattern, $t_replacement, $t_file_content );
        $t_pattern = "/require_once[( \t\n]+dirname/";
        $t_replacement = 'require( dirname';
        $t_new_file_content = preg_replace( $t_pattern, $t_replacement, $t_new_file_content );
        if ( $t_new_file_content != $t_file_content ) {
            $t_file = fopen( 'custom_strings_inc.php', 'w' );
            fwrite( $t_file, $t_new_file_content );
            fclose( $t_file );
        }
    }
    return PLUGINS_PLUGINMANAGER_OK;
}

function plugins_pluginmanager_install_undo_plugin( &$p_msg, $p_step ) {
    global $g_db;
    
    $p_msg = '';

    if ( $p_step == 1 ) {
    } else if ( $p_step == 2 ) {
        if ( db_is_connected() ) {
            $t_dict = NewDataDictionary( $g_db );
            $t_table_name = config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' );
            if ( !db_table_exists( $t_table_name ) ) {
                $t_res = $t_dict->ExecuteSQLArray( $t_dict->DropTableSQL( $t_table_name ) );
                if ( $t_res != 2 ) {
                    return PLUGINS_PLUGINMANAGER_FAIL;
                }
            }
            $t_table_name = config_get( 'db_table_prefix' ) . '_plugins_pm_dependencies' . config_get( 'db_table_suffix' );
            if ( !db_table_exists( $t_table_name ) ) {
                $t_res = $t_dict->ExecuteSQLArray( $t_dict->DropTableSQL( $t_table_name ) );
                if ( $t_res != 2 ) {
                    return PLUGINS_PLUGINMANAGER_FAIL;
                }
            }
            $t_table_name = config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' );
            if ( !db_table_exists( $t_table_name ) ) {
                $t_res = $t_dict->ExecuteSQLArray( $t_dict->DropTableSQL( $t_table_name ) );
                if ( $t_res != 2 ) {
                    return PLUGINS_PLUGINMANAGER_FAIL;
                }
            }
        }
    } else if ( $p_step == 2 ) {
        if ( !is_writeable( 'core.php' ) || !pm_backup_restore_file( 'core.php' ) ) {
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
        if ( file_exists( 'custom_strings_inc.php' ) && !pm_backup_restore_file( 'custom_strings_inc.php' ) ) {
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
    } else if ( $p_step == 3 ) {
        return PLUGINS_PLUGINMANAGER_OK;
    } else {
        $t_main_menu_custom_options = config_get( 'main_menu_custom_options', array() );
        for( $i=0; $i<count( $t_main_menu_custom_options ); $i++ ) {
            if ( $t_main_menu_custom_options[$i] == array( 'plugins_pluginmanager_link', VIEWER, 'plugins_page.php' ) ) {
                unset( $t_main_menu_custom_options[$i] );
            }
        }
        config_set( 'main_menu_custom_options', $t_main_menu_custom_options );
    }
    return PLUGINS_PLUGINMANAGER_OK;
}

function plugins_pluginmanager_upgrade_undo_plugin( &$p_msg, $p_step ) {
    $p_msg = '';

    if ( $p_step == 1 ) {
        pm_backup_restore_file( 'plugins_page.php' );
        pm_backup_restore_file( 'plugins_install_page.php' );
        pm_backup_restore_file( 'plugins_uninstall_page.php' );
        pm_backup_restore_file( 'plugins_package_page.php' );
        pm_backup_restore_file( 'plugins_file_package_page.php' );
        pm_backup_restore_file( 'plugins_upgrade_page.php' );
    } else if ( $p_step == 3 ) {
        if ( db_is_connected() ) {
            $t_query = 'DROP TABLE IF EXISTS ' . config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' );
            if ( !db_query( $t_query ) ) {
                return PLUGINS_PLUGINMANAGER_FAIL;
            }
        }
    }    
    return PLUGINS_PLUGINMANAGER_OK;
}

function plugins_pluginmanager_uninstall_undo_plugin( &$p_msg, $p_step ) {
    $p_msg = '';
    
    return PLUGINS_PLUGINMANAGER_OK;
}

function plugins_pluginmanager_uninstall_plugin( &$p_msg, $p_step ) {
    global $t_file_list;
    global $g_db;
        
    $p_msg = '';

    if ( $p_step == 1 ) {
        foreach( $t_file_list as $t_file ) {
            if ( !pm_file_move( $t_file, 'plugins' . DIRECTORY_SEPARATOR . 'pluginmanager' . DIRECTORY_SEPARATOR . $t_file ) ) {
                return PLUGINS_PLUGINMANAGER_FAIL;
            }
        }
    } else if ( $p_step == 2 ) {
        $t_main_menu_custom_options = config_get( 'main_menu_custom_options', array() );
        for( $i=0; $i<count( $t_main_menu_custom_options ); $i++ ) {
            if ( $t_main_menu_custom_options[$i] == array( 'plugins_pluginmanager_link', VIEWER, 'plugins_page.php' ) ) {
                unset( $t_main_menu_custom_options[$i] );
            }
        }
        config_set( 'main_menu_custom_options', $t_main_menu_custom_options );
        $t_file = fopen( 'core.php', 'a' );
        $t_new_file_content = '<?php' . "\n";
        $t_new_file_content .= '    if ( !function_exists( \'plugins_pluginmanager_function_overwrite\' ) ) {' . "\n";
        $t_new_file_content .= '        function plugins_pluginmanager_function_overwrite( $p_function_name, $p_arguments, $p_file ) {' . "\n";
        $t_new_file_content .= '            return call_user_func_array( \'pm_old_\' . $p_function_name, $p_arguments );' . "\n";
        $t_new_file_content .= '        }' . "\n";
        $t_new_file_content .= '    }' . "\n?>\n";
        fwrite( $t_file, $t_new_file_content );
        fclose( $t_file );
    } else {
        if ( db_is_connected() ) {
            $t_dict = NewDataDictionary( $g_db );
            $t_table_name = config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' );
            if ( !db_table_exists( $t_table_name ) ) {
                $t_res = $t_dict->ExecuteSQLArray( $t_dict->DropTableSQL( $t_table_name ) );
                if ( $t_res != 2 ) {
                    return PLUGINS_PLUGINMANAGER_FAIL;
                }
            }
            $t_table_name = config_get( 'db_table_prefix' ) . '_plugins_pm_dependencies' . config_get( 'db_table_suffix' );
            if ( !db_table_exists( $t_table_name ) ) {
                $t_res = $t_dict->ExecuteSQLArray( $t_dict->DropTableSQL( $t_table_name ) );
                if ( $t_res != 2 ) {
                    return PLUGINS_PLUGINMANAGER_FAIL;
                }
            }
            $t_table_name = config_get( 'db_table_prefix' ) . '_plugins_pm_function_overwrite' . config_get( 'db_table_suffix' );
            if ( !db_table_exists( $t_table_name ) ) {
                $t_res = $t_dict->ExecuteSQLArray( $t_dict->DropTableSQL( $t_table_name ) );
                if ( $t_res != 2 ) {
                    return PLUGINS_PLUGINMANAGER_FAIL;
                }
            }
        } else {
            $p_msg = 'Database not connected';
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
    }
    return PLUGINS_PLUGINMANAGER_OK;
}

function plugins_pluginmanager_mantis_upgrade_plugin( &$p_msg, $p_step ) {
    $p_msg = '';

    return PLUGINS_PLUGINMANAGER_OK;
}

function plugins_pluginmanager_mantis_repair_plugin( &$p_msg, $p_step ) {
    if ( $p_step == 1 ) {
	return plugins_pluginmanager_install_plugin( $p_msg, 2 );
    } else if ( $p_step <= $t_plugins_plugin_manager_step_count['upgrade'] ) {
        return plugins_pluginmanager_upgrade_plugin( $p_msg, $p_step - 1 );
    } else {
        plugins_pluginmanager_function_repair_overwrite();
        
        return PLUGINS_PLUGINMANAGER_OK;
    }
}

?>

<?php

/**
 * This file is dedicated to all plugin functions.
 *
 * Created: 2007-02-12
 * Last update: 2008-09-21
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @package PluginManager
 * @version 0.2.0
 */

/**
 * This function returns the plugin info.
 *
 * @param string $p_plugin The plugin
 * @param bool $p_check_installed If set to true, check also if the
 * plugin is installed.
 * @return array
 */
function pm_get_plugin_info( $p_plugin, $p_check_installed = true ) {
    $t_directory = dirname( dirname( dirname( __FILE__ ) ) );
    if ( $p_check_installed && !pm_plugin_is_installed( $p_plugin ) ) {
        return false;
    }
    if ( is_dir( $t_directory . DIRECTORY_SEPARATOR . $p_plugin ) ) {
        $t_file = $t_directory . DIRECTORY_SEPARATOR . $p_plugin . DIRECTORY_SEPARATOR . 'info.php';
        $t_function = $p_plugin . '_get_info';
        if ( file_exists( $t_file ) && !function_exists( $t_function ) ) {
            include( $t_file );
            if ( function_exists( $t_function ) ) {
                return $t_function();
            } else {
                return false;
            }
        } elseif( function_exists( $t_function ) ) {
            return $t_function();
        }
    }
    return false;
}

/**
 * This function returns the list of installed plugins.
 *
 * @return array
 */
function pm_get_installed_plugin_list() {
    $t_query = 'SELECT `plugin` FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' ) . ' WHERE `installed`=1';
    $t_result = db_query( $t_query );
    $t_res = array();
    while( $t_row = db_fetch_array( $t_result ) ) {
        $t_res[] = $t_row['plugin'];
    }
    return $t_res;
}

/** 
 * This function returns true if the plugin is installed
 * on the bugtracker and returns false in other cases.
 *
 * @param string $p_plugin The plugin id (name of the directory).
 * @return bool
 */
function pm_plugin_is_installed( $p_plugin ) {
    $t_table = config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' );
    $t_found = db_table_exists( $t_table );
    if ( !$t_found ) {
        return false;
    }
    $t_query = 'SELECT `plugin` FROM ' . $t_table . ' WHERE `plugin`=\'' . db_prepare_string( $p_plugin ) . '\' AND `installed`=1';
    $t_result = db_query( $t_query );
    if ( db_num_rows( $t_result ) != 1 ) {
        return false;
    }
    return true;
}

/**
 * @access private
 */
function plugins_pluginmanager_get_plugin_list( $p_check = true, $p_include = false ) {
    $t_directory = dirname( dirname( dirname( __FILE__ ) ) );
    $t_dir = opendir( $t_directory );
    
    if ( !$p_include ) {
        $t_access_level = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
    } else {
        $t_access_level = VIEWER;
    }
    
    $t_plugin_list = array();
    $t_installed_plugin = pm_get_installed_plugin_list();
    $t_repaired_plugin = plugins_pluginmanager_get_repaired_plugin_list();
    while( ( $t_file = readdir( $t_dir ) ) !== false ) {
        if ( is_dir( $t_directory . DIRECTORY_SEPARATOR . $t_file ) && $t_file != '.' && $t_file != '..' ) {
            $t_plugin = array();
            $t_plugin['id'] = $t_file;
            $t_file = $t_directory . DIRECTORY_SEPARATOR . $t_file . DIRECTORY_SEPARATOR . 'info.php';
            $t_function = $t_plugin['id'] . '_get_info';
            if ( file_exists( $t_file ) && !function_exists( $t_function ) ) {
                include( $t_file );
                if ( function_exists( $t_function ) ) {
                    $t_plugin_info = $t_function();
                    $t_plugin['name'] = $t_plugin_info['name'];
                    if ( isset( $t_plugin_info['url'] ) && $t_plugin_info['url'] != '' ) {
                        $t_plugin['url'] = $t_plugin_info['url'];
                    }
                    $t_plugin['version'] = $t_plugin_info['version'];
                    if ( $p_check && isset( $t_plugin_info['check'] ) && config_get( 'plugins_pluginmanager_check_version', PLUGINS_PLUGINMANAGER_CHECK_VERSION_DEFAULT ) == ON ) {
                        if ( isset( $t_plugin_info['check_unstable'] ) && config_get( 'plugins_pluginmanager_check_unstable', PLUGINS_PLUGINMANAGER_CHECK_UNSTABLE_DEFAULT ) == ON ) {
                            $t_url = @fopen( $t_plugin_info['check_unstable'], 'r' );
                        } else {
                            $t_url = @fopen( $t_plugin_info['check'], 'r' );
                        }
                        if ( $t_url ) {
                            $t_version = fscanf( $t_url, "%s\n" );
                            $t_plugin['check'] = $t_version[0];
                            fclose( $t_url );
                        } else {
                            $t_plugin['check'] = '-';
                        }
                    } else {
                        $t_plugin['check'] = '-';
                    }
                    $t_threshold = config_get( 'plugins_pluginmanager_' . $t_plugin['id'] . '_threshold', PLUGINS_PLUGINMANAGER_CONFIGURE_THRESHOLD_DEFAULT );
                    if ( $t_access_level >= $t_threshold && in_array( $t_plugin['id'], $t_installed_plugin ) ) {
                        $t_plugin['has_access_level'] = true;
                    } else {
                        $t_plugin['has_access_level'] = false;
                    }
                    $t_file = str_replace( 'info.php', 'install_inc.php', $t_file );
                    if ( file_exists( $t_file ) ) {
                        $t_plugin['can_be_upgraded_uninstalled'] = true;
                    } else {
                        $t_plugin['can_be_upgraded_uninstalled'] = false;
                    }
                    $t_file = str_replace( 'install_inc.php', 'package_inc.php', $t_file );
                    if ( file_exists( $t_file ) && $t_plugin['can_be_upgraded_uninstalled'] ) {
                        $t_plugin['can_be_packaged'] = true;
                    } else {
                        $t_plugin['can_be_packaged'] = false;
                    }
                    $t_plugin_list[] = $t_plugin;
                }
            } elseif( function_exists( $t_function ) ) {
                $t_plugin_info = $t_function();
                $t_plugin['name'] = $t_plugin_info['name'];
                if ( isset( $t_plugin_info['url'] ) && $t_plugin_info['url'] != '' ) {
                    $t_plugin['url'] = $t_plugin_info['url'];
                }
                $t_plugin['version'] = $t_plugin_info['version'];
                if ( $p_check && isset( $t_plugin_info['check'] ) && config_get( 'plugins_pluginmanager_check_version', PLUGINS_PLUGINMANAGER_CHECK_VERSION_DEFAULT ) == ON ) {
                    if ( isset( $t_plugin_info['check_unstable'] ) && config_get( 'plugins_pluginmanager_check_unstable', PLUGINS_PLUGINMANAGER_CHECK_UNSTABLE_DEFAULT ) == ON ) {
                        $t_url = @fopen( $t_plugin_info['check_unstable'], 'r' );
                    } else {
                        $t_url = @fopen( $t_plugin_info['check'], 'r' );
                    }
                    if ( $t_url ) {
                        $t_version = fscanf( $t_url, '%s\n' );
                        $t_plugin['check'] = $t_version[0];
                        fclose( $t_url );
                    } else {
                        $t_plugin['check'] = '-';
                    }
                } else {
                    $t_plugin['check'] = '-';
                }
                $t_threshold = config_get( 'plugins_pluginmanager_' . $t_plugin['id'] . '_threshold', PLUGINS_PLUGINMANAGER_CONFIGURE_THRESHOLD_DEFAULT );
                if ( $t_access_level >= $t_threshold && in_array( $t_plugin['id'], $t_installed_plugin ) ) {
                    $t_plugin['has_access_level'] = true;
                } else {
                    $t_plugin['has_access_level'] = false;
                }
                $t_file = str_replace( 'info.php', 'install_inc.php', $t_file );
                if ( file_exists( $t_file ) ) {
                    $t_plugin['can_be_upgraded_uninstalled'] = true;
                } else {
                    $t_plugin['can_be_upgraded_uninstalled'] = false;
                }
                $t_file = str_replace( 'install_inc.php', 'package_inc.php', $t_file );
                if ( file_exists( $t_file ) && $t_plugin['can_be_upgraded_uninstalled'] ) {
                    $t_plugin['can_be_packaged'] = true;
                } else {
                    $t_plugin['can_be_packaged'] = false;
                }
                $t_plugin_list[] = $t_plugin;
            } elseif ( file_exists( $t_directory . DIRECTORY_SEPARATOR . $t_plugin['id'] . DIRECTORY_SEPARATOR . 'index.php' ) ) {
                $t_plugin['name'] = $t_plugin['id'];
                $t_plugin['version'] = '--';
                $t_plugin['check'] = '--';
                if ( $t_access_level >= ADMINISTRATOR && in_array( $t_plugin['id'], $t_installed_plugin ) ) {
                    $t_plugin['has_access_level'] = true;
                } else {
                    $t_plugin['has_access_level'] = false;
                }
                $t_plugin['can_be_upgraded_uninstalled'] = false;
                $t_plugin['can_be_packaged'] = false;
                $t_plugin_list[] = $t_plugin;
            }
        }
    }

    usort( $t_plugin_list, 'plugins_pluginmanager_cmp' );
    for( $i=0; $i<count( $t_plugin_list ); $i++ ) {
        if ( !in_array( $t_plugin_list[$i]['id'], $t_installed_plugin ) ) {
            $t_plugin_list[$i]['can_be_upgraded_uninstalled'] = false;
            $t_plugin_list[$i]['must_be_installed'] = true;
        }
        if ( in_array( $t_plugin_list[$i]['id'], $t_repaired_plugin ) ) {
            $t_plugin_list[$i]['can_be_upgraded_uninstalled'] = false;
            $t_plugin_list[$i]['can_be_packaged'] = false;
            $t_plugin_list[$i]['must_be_installed'] = false;
            $t_plugin_list[$i]['must_be_repaired'] = true;
        }
    }
    return $t_plugin_list;
}

/**
 * @access private
 */
function plugins_pluginmanager_get_file_plugin_list( $p_include = false ) {
    $t_directory = dirname( dirname( dirname( __FILE__ ) ) );
    $t_dir = opendir( $t_directory );
    
    if ( !$p_include ) {
        $t_access_level = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
    } else {
        $t_access_level = VIEWER;
    }
    
    $t_plugin_list = array();
    $t_installed_plugin = pm_get_installed_plugin_list();
    while( ( $t_file = readdir( $t_dir ) ) !== false ) {
        if ( is_dir( $t_directory . DIRECTORY_SEPARATOR . $t_file ) && $t_file != '.' && $t_file != '..' ) {
            $t_info_file = $t_directory . DIRECTORY_SEPARATOR . $t_file . DIRECTORY_SEPARATOR . 'info.php';
            $t_function = $t_file . '_get_info';
            if ( file_exists( $t_info_file ) && !function_exists( $t_function ) ) {
                include( $t_info_file );
                if ( function_exists( $t_function ) ) {
                    $t_plugin_info = $t_function();
                    $t_package_file = $t_directory . DIRECTORY_SEPARATOR . $t_file . DIRECTORY_SEPARATOR . 'file_package_inc.php';
                    if ( file_exists( $t_package_file ) ) {
                        include( $t_package_file );
                        foreach( $t_pluginmanager_file_package as $t_file_pkg ) {
                            if ( preg_match( '/^([A-z0-9_]+)$/', $t_file_pkg['id'] ) ) {
                                $t_plugin = array();
                                $t_plugin['id'] = $t_file . '-' . $t_file_pkg['id'];
                                $t_plugin['name'] = $t_plugin_info['name'] . ' - ' . $t_file_pkg['name'] . ' - ' . $t_file_pkg['version'];
                                $t_plugin['can_be_packaged'] = true;
                                $t_plugin_list[] = $t_plugin;
                            }
                        }
                    }
                }
            } elseif( function_exists( $t_function ) ) {
                $t_plugin_info = $t_function();
                $t_package_file = $t_directory . DIRECTORY_SEPARATOR . $t_file . DIRECTORY_SEPARATOR . 'file_package_inc.php';
                if ( file_exists( $t_package_file ) ) {
                    include( $t_package_file );
                    foreach( $t_pluginmanager_file_package as $t_file_pkg ) {
                        if ( preg_match( '/^([A-z0-9_]+)$/', $t_file_pkg['id'] ) ) {
                            $t_plugin = array();
                            $t_plugin['id'] = $t_file . '-' . $t_file_pkg['id'];
                            $t_plugin['name'] = $t_plugin_info['name'] . ' - ' . $t_file_pkg['name'] . ' - ' . $t_file_pkg['version'];
                            $t_plugin['can_be_packaged'] = true;
                            $t_plugin_list[] = $t_plugin;
                        }
                    }
                }
            } elseif ( file_exists( $t_directory . DIRECTORY_SEPARATOR . $t_file . DIRECTORY_SEPARATOR . 'index.php' ) ) {
                $t_package_file = $t_directory . DIRECTORY_SEPARATOR . $t_file . DIRECTORY_SEPARATOR . 'file_package_inc.php';
                if ( file_exists( $t_package_file ) ) {
                    include( $t_package_file );
                    foreach( $t_pluginmanager_file_package as $t_file_pkg ) {
                        if ( preg_match( '/^([A-z0-9_]+)$/', $t_file_pkg['id'] ) ) {
                            $t_plugin = array();
                            $t_plugin['id'] = $t_file . '-' . $t_file_pkg['id'];
                            $t_plugin['name'] = $t_file . ' - ' . $t_file_pkg['name'] . ' - ' . $t_file_pkg['version'];
                            $t_plugin['can_be_packaged'] = true;
                            $t_plugin_list[] = $t_plugin;
                        }
                    }
                }
            }
        }
    }

    asort( $t_plugin_list );
    return $t_plugin_list;
}

/**
 * @access private
 */
function plugins_pluginmanager_get_plugin_access_level( $p_plugin ) {
    return config_get( 'plugins_pluginmanager_' . $p_plugin . '_threshold', PLUGINS_PLUGINMANAGER_CONFIGURE_THRESHOLD_DEFAULT );
}

/** 
 * @access private
 */
function plugins_pluginmanager_print_test_result( $p_result, $p_message='' ) {
    global $g_failed;
    
    echo '<td class="center" ';
    if ( PLUGINS_PLUGINMANAGER_FAIL == $p_result ) {
        $g_failed = true;
        echo 'bgcolor="red">FAIL';
        if ( '' != $p_message ) {
            echo '<br />' . $p_message;
        }   
    } else if ( PLUGINS_PLUGINMANAGER_WARNING == $p_result ) {
        echo 'bgcolor="pink">WARNING';
        if ( '' != $p_message ) {
            echo '<br />' . $p_message;
        }
    } else if ( PLUGINS_PLUGINMANAGER_OK == $p_result ) {
        echo 'bgcolor="green">';
        if ( $p_message == '' ) {
            echo 'OK';
        } else {
            echo $p_message;
        }
    }
    
    echo '</td>';
}

/** 
 * @access private
 */
function plugins_pluginmanager_print_test( $p_test_description, $p_result, $p_message='' ) {
    echo "\n" . '<tr ' . helper_alternate_class() . '><td>' . $p_test_description . '</td>';
    plugins_pluginmanager_print_test_result( $p_result, $p_message );
    echo "</tr>\n";
}

/** 
 * @access private
 */
function plugins_pluginmanager_install_file( &$p_failed, &$p_msg, $p_file, $p_content ) {
    global $g_installed_files;
    
    if ( $p_failed == PLUGINS_PLUGINMANAGER_OK && file_exists( $p_file ) ) {
        if ( !pm_backup_file( $p_file ) ) {
            $p_failed = PLUGINS_PLUGINMANAGER_FAIL;
            $p_msg = 'could not backup file ' . $p_file;
        }
    }
    if ( $p_failed == PLUGINS_PLUGINMANAGER_OK ) {
        $t_directory = '';
        $t_dirs = explode( '/', $p_file );
        for( $i=0; $i<count( $t_dirs ) - 1; $i++ ) {
            $t_directory .= $t_dirs[$i] . DIRECTORY_SEPARATOR;
            if ( !is_dir( $t_directory ) ) {
                if ( $p_failed == PLUGINS_PLUGINMANAGER_OK && !@mkdir( $t_directory ) ) {
                    $p_failed = PLUGINS_PLUGINMANAGER_FAIL;
                    $p_msg = 'could not create directory ' . $t_directory;
                }
            }
        }
        if ( $p_failed == PLUGINS_PLUGINMANAGER_OK ) {
            $t_file = @fopen( $p_file, 'w' );
            if ( $t_file ) {
                fwrite( $t_file, base64_decode( $p_content ) );
                fclose( $t_file );
                $g_installed_files['new'][] = $p_file;
                $g_installed_files['rename'][] = $p_file;
            } else {
                $p_failed = PLUGINS_PLUGINMANAGER_FAIL;
                $p_msg = 'could not write file ' . $p_file;
            }
        }
    }
}

/** 
 * @access private
 */
function plugins_pluginmanager_restore( &$p_failed, &$p_msg ) {
    global $g_installed_files;

    $p_msg = '';
    if ( isset( $g_installed_files['new'] ) ) {
        foreach( $g_installed_files['new'] as $t_file ) {
            if ( !@unlink( $t_file ) ) {
                $p_failed = PLUGINS_PLUGINMANAGER_FAIL;
                $p_msg = 'unable to restore';
            }
        }
    }
    if ( isset( $g_installed_files['rename'] ) ) {
        foreach( $g_installed_files['rename'] as $t_file ) {
            if ( !pm_backup_restore_file( $t_file ) ) {
                $p_failed = PLUGINS_PLUGINMANAGER_FAIL;
                $p_msg = 'unable to restore';
            }
        }
    }
}

/** 
 * @access private
 */
function plugins_pluginmanager_default_description_plugin( $p_step ) {
    return '';
}

/** 
 * @access private
 */
function plugins_pluginmanager_get_plugin_dependencies( $p_plugin ) {
    $t_query = 'SELECT d.`plugin` FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_dependencies' . config_get( 'db_table_suffix' ) . ' d LEFT JOIN ' . config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' ) . ' l ON d.`plugin`=l.`plugin` WHERE l.`installed`=1 AND d.`dependencies` LIKE \'%,' . db_prepare_string( $p_plugin ) . ',%\'';
    $t_result = db_query( $t_query );
    if ( db_num_rows( $t_result ) > 0 ) {
        $t_res = array();
        while( $t_row = db_fetch_array( $t_result ) ) {
            $t_res[] = $t_row['plugin'];
        }
    }
    if ( isset( $t_res ) ) {
        return $t_res;
    } else {
        return false;
    }
}

/**
  * Function used to sort the plugin array
  *
  * @param mixed $p_arg1 First argument
  * @param mixed $p_arg2 Second argument
  * @return int
  * @access private
  */
function plugins_pluginmanager_cmp( $p_arg1, $p_arg2 ) {
    return ( $p_arg1['name'] > $p_arg2['name'] );
}

/**
  * This function is used to check if the plugin
  * is compatible with the Mantis upgrade.
  *
  * @param string $p_plugin The plugin to test
  * @return int
  * @access private
  */
function plugins_pluginmanager_check_mantis_upgrade( $p_plugin ) {
    $t_include_file = 'plugins' . DIRECTORY_SEPARATOR . $p_plugin . DIRECTORY_SEPARATOR . 'install_inc.php';
    if ( file_exists( $t_include_file ) ) {
        $_SERVER['PHP_SELF'] = 'plugins_install_page.php';
        include( $t_include_file );
        if ( isset( $t_plugins_plugin_manager_step_count['mantis_upgrade'] ) && function_exists( 'plugins_' . $p_plugin . '_mantis_upgrade_plugin' ) ) {
            return PLUGINS_PLUGINMANAGER_OK;
        } else {
            return PLUGINS_PLUGINMANAGER_FAIL;
        }
    } else {
        return PLUGINS_PLUGINMANAGER_FAIL;
    }
}

/**
  * This function is used to order the plugins
  * according to their dependencies.
  *
  * @param array $p_plugins_array Plugins to sort
  * @return array
  * @access private
  */
function plugins_pluginmanager_order_plugins( $p_plugins_array ) {
    $t_result = ',';
    foreach( $p_plugins_array as $t_plugin ) {
        $t_dependences = plugins_pluginmanager_get_plugin_dependencies( $t_plugin );
        if ( $t_dependences ) {
            if ( !preg_match( '/,' . $t_plugin . ',/', $t_result ) ) {
                $t_result .= $t_plugin . ',';
            }
            foreach( $t_dependences as $t_dep ) {
                if ( !preg_match( '/,' . $t_dep . ',/', $t_result ) ) {
                    $t_result = str_replace( ',' . $t_plugin . ',', ',' . $t_dep . ',' . $t_plugin . ',', $t_result );
                } else {
                    if ( preg_match( '/,' . $t_plugin . ',[a-z,]+,' . $t_dep . ',/', $t_result ) || preg_match( '/,' . $t_plugin . ',' . $t_dep . ',/', $t_result ) ) {
                        $t_result = str_replace( ',' . $t_dep, $t_result );
                        $t_result = str_replace( ',' . $t_plugin . ',', ',' . $t_dep . ',' . $t_plugin . ',', $t_result );
                    }
                }
            }
        } else {
            if ( !preg_match( '/,' . $t_plugin . ',/', $t_result ) ) {
                $t_result .= $t_plugin . ',';
            }
        }
    }
    $t_result = preg_replace( '/^,/', '', $t_result );
    $t_result = preg_replace( '/,$/', '', $t_result );
    return explode( ',', $t_result );
}

/**
 * This function returns the list of plugins to repair.
 *
 * @return array
 * @access private
 */
function plugins_pluginmanager_get_repaired_plugin_list() {
    $t_query = 'SELECT `plugin` FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' ) . ' WHERE `installed`=2';
    $t_result = db_query( $t_query );
    $t_res = array();
    while( $t_row = db_fetch_array( $t_result ) ) {
        $t_res[] = $t_row['plugin'];
    }
    return $t_res;
}

?>

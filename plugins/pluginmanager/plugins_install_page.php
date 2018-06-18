<?php

/**
 * Plugin manager
 *
 *
 * Created: 2007-05-20
 * Last update: 2008-09-21
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @version 0.2.0
 */

require_once( 'core.php' );
require_once( 'plugins' . DIRECTORY_SEPARATOR . 'pluginmanager' . DIRECTORY_SEPARATOR . 'core.php' );
require_once( 'plugins' . DIRECTORY_SEPARATOR . 'pluginmanager' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'install_api.php' );

if ( user_get_access_level( auth_get_current_user_id() ) < config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) ) {
    if ( !function_exists( 'pm_plugin_is_installed' ) || !pm_plugin_is_installed( 'pluginmanager' ) ) {
        die( 'You should be administrator to install the Plugin Manager' );
    }
    header( 'Location: ' . config_get( 'path' ) . 'plugins_page.php' );
    exit();
}

$t_error = false;
$t_url = gpc_get_string( 'url', '' );
if ( $t_url == '' ) {
    $t_url = gpc_get_file( 'upload', null );
    if ( !isset( $t_url ) || $t_url == null ) {
        $t_error = true;
        $t_url = gpc_get_string( 'plugin', '' );
        $t_plugin = $t_url;
    } else {
        $t_url = $t_url['tmp_name'];
    }
}

$t_result = '';
if ( !$t_error ) {
    $t_file = @fopen( $t_url, 'r' );
    if ( !$t_file ) {
        $t_msg = 'Issue to open the package';
        $t_error = true;
    } else {
        while( !feof( $t_file ) ) {
            $t_result .= fread( $t_file, 8192 );
        }
        fclose( $t_file );
    }
}

if ( !$t_error ) {
    $t_result = @gzuncompress( $t_result );
    $t_result = unserialize( $t_result );
}

$t_repaired = false;
if ( db_table_exists( config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' ) ) ) {
    $t_repaired_plugin = plugins_pluginmanager_get_repaired_plugin_list();
    if ( isset( $t_plugin ) && in_array( $t_plugin, $t_repaired_plugin ) ) {
        $t_error = false;
        $t_repaired = true;
        $t_result = array();
        $t_result['info_pm'] = pm_get_plugin_info( 'pluginmanager', false );
        $t_result['info'] = pm_get_plugin_info( $t_plugin, false );
        $t_result['check'] = array();
        $t_result['plugin'] = $t_plugin;
        if ( !$t_result['info'] ) {
            $t_error = true;
            $t_msg = 'Download the plugin';
        }
    } else if ( !isset( $t_plugin ) && isset( $t_result ) && in_array( $t_result['plugin'], $t_repaired_plugin ) ) {
        $t_repaired = true;
    }
}

$t_manual = false;
if ( $t_error && $t_url != '' && file_exists( 'plugins' . DIRECTORY_SEPARATOR . $t_url . DIRECTORY_SEPARATOR . 'install_manual_inc.php' ) ) {
    include( 'plugins' . DIRECTORY_SEPARATOR . $t_url . DIRECTORY_SEPARATOR . 'install_manual_inc.php' );
    $t_error = false;
    $t_manual = true;
    $t_msg = 'Missing instructions';
}

html_page_top1( lang_get( 'plugins_pluginmanager_install_title' ) );
html_page_top2();

echo "\n\n";
echo '<br />' . "\n" . '<div align="center">' . "\n";

echo '<b>' . lang_get( 'plugins_pluginmanager_install' ) . '</b><br /><br />' . "\n";

$g_failed = $t_error;
$t_failed = ( !$t_error ? PLUGINS_PLUGINMANAGER_OK : PLUGINS_PLUGINMANAGER_FAIL );
$t_res_failed = $t_failed;

if ( isset( $t_result['info_file_pm'] ) ) {
    $t_is_file_package = true;
    $t_info_pm = 'info_file_pm';
} else {
    $t_is_file_package = false;
    $t_info_pm = 'info_pm';
}

$t_restore_file = false;
echo '  <table class="width50">' . "\n";
if ( !$g_failed ) {
    if ( !isset( $t_result[$t_info_pm]['name'] ) || !isset( $t_result[$t_info_pm]['version'] ) ) {
        $t_msg = 'File corrupted or not a Mantis plugin';
        $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
    } else {
        $t_pm_version = pm_get_plugin_info( 'pluginmanager', false );
        $t_pm_version = $t_pm_version['version'];
        if ( version_compare( $t_result[$t_info_pm]['version'], $t_pm_version, '>' ) ) {
            $t_msg = 'Plugin Manager must be updated<br />';
            $t_msg .= $t_pm_version . ' < ' . $t_result[$t_info_pm]['version'];
            $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
        } else {
            $t_msg = $t_result[$t_info_pm]['version'];
        }
    }
    $t_res_failed = min( $t_res_failed, $t_failed );
    plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_pm' ), $t_failed, $t_msg );
}
if ( !$g_failed ) {
    if ( !isset( $t_result['info']['name'] ) ) {
        $t_msg = '';
        $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
    } else {
        $t_msg = $t_result['info']['name'];
    }
    $t_res_failed = min( $t_res_failed, $t_failed );
    plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_name' ), $t_failed, $t_msg );
}
if ( !$g_failed ) {
    if ( !isset( $t_result['info']['version'] ) ) {
        $t_msg = '';
        $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
    } else {
        $t_msg = $t_result['info']['version'];
    }
    $t_res_failed = min( $t_res_failed, $t_failed );
    plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_version' ), $t_failed, $t_msg );
}
$t_checks = array( 'apache' => 'Apache', 'php' => 'PHP', 'sql' => 'SQL', 'mantis' => 'Mantis' );
foreach( $t_checks as $t_key => $t_value ) {
    $t_block = false;
    $t_failed = PLUGINS_PLUGINMANAGER_OK;
    if ( !$g_failed ) {
        if ( !isset( $t_result['check'][$t_key] ) ) {
            $t_msg = 'No test performed/required';
            $t_failed = PLUGINS_PLUGINMANAGER_OK;
        } else {
            $t_msg = '';
            if ( $t_key == 'apache' ) {
                $t_version = apache_get_version();
                $t_version = explode( '/', $t_version );
                $t_version = $t_version[1];
            } else if ( $t_key == 'php' ) {
                $t_version = phpversion();
            } else if ( $t_key == 'sql' ) {
                $t_version = @$g_db->ServerInfo();
                $t_version = $t_version['version'];
            } else if ( $t_key == 'mantis' && defined( 'MANTIS_VERSION' ) ) {
                $t_version = MANTIS_VERSION;
            } else if ( $t_key == 'mantis' ) {
                $t_version = config_get( 'mantis_version' );
            }
            if ( isset( $t_result['check'][$t_key]['min'] ) ) {
                if ( version_compare( $t_version, $t_result['check'][$t_key]['min'], '<' ) ) {
                    $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                    $t_block = true;
                    $t_msg = $t_version . ' < ' . $t_result['check'][$t_key]['min'];
                }
            }
            if ( isset( $t_result['check'][$t_key]['max'] ) && $t_failed != PLUGINS_PLUGINMANAGER_FAIL ) {
                if ( version_compare( $t_version, $t_result['check'][$t_key]['max'], '>' ) ) {
                    $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                    $t_block = true;
                    $t_msg = $t_version . ' > ' . $t_result['check'][$t_key]['max'];
                }
            }
            if ( isset( $t_result['check'][$t_key]['except'] ) && $t_failed != PLUGINS_PLUGINMANAGER_FAIL ) {
                if ( is_array( $t_result['check'][$t_key]['except'] ) ) {
                    for( $i=0; $i<count( $t_result['check'][$t_key]['except'] ); $i++ ) {
                        if ( version_compare( $t_version, $t_result['check'][$t_key]['except'][$i], '=' ) && $t_failed != PLUGINS_PLUGINMANAGER_FAIL ) {
                            $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                            $t_block = true;
                            $t_msg = $t_version . ' = ' . $t_result['check'][$t_key]['except'][$i];
                        }
                    }
                } else {
                    if ( version_compare( $t_version, $t_result['check'][$t_key]['except'], '=' ) ) {
                        $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                        $t_block = true;
                        $t_msg = $t_version . ' = ' . $t_result['check'][$t_key]['except'];
                    }
                }
            }
        }
        $t_res_failed = min( $t_res_failed, $t_failed );
        plugins_pluginmanager_print_test( sprintf( lang_get( 'plugins_pluginmanager_test_check' ), $t_value ), $t_failed, $t_msg );
    }
}
if ( !$g_failed ) {
    if ( isset( $t_result['check']['plugins'] ) ) {
        if ( is_array( $t_result['check']['plugins'] ) ) {
            $t_dependences = '';
            foreach( $t_result['check']['plugins'] as $t_plugin => $t_value ) {
                $t_dependences .= ',' . $t_plugin . ',';
                $t_version = pm_get_plugin_info( $t_plugin );
                $t_name = $t_version['name'];
                $t_version = $t_version['version'];
                if ( isset( $t_value['min'] ) ) {
                    if ( version_compare( $t_version, $t_value['min'], '<' ) ) {
                        $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                        $t_msg = $t_name . ': ' . $t_version . ' < ' . $t_value['min'];
                    }
                }
                if ( isset( $t_value['max'] ) && $t_failed != PLUGINS_PLUGINMANAGER_FAIL ) {
                    if ( version_compare( $t_version, $t_value['max'], '>' ) ) {
                        $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                        $t_msg = $t_name . ': ' . $t_version . ' > ' . $t_value['max'];
                    }
                }
                if ( isset( $t_value['except'] ) && $t_failed != PLUGINS_PLUGINMANAGER_FAIL ) {
                    if ( is_array( $t_value['except'] ) ) {
                        for( $i=0; $i<count( $t_value['except'] ); $i++ ) {
                            if ( version_compare( $t_version, $t_value['except'][$i], '=' ) && $t_failed != PLUGINS_PLUGINMANAGER_FAIL ) {
                                $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                                $t_msg = $t_name . ': ' . $t_version . ' = ' . $t_value['except'][$i];
                            }
                        }
                    } else {
                        if ( version_compare( $t_version, $t_value['except'], '=' ) ) {
                            $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                            $t_msg = $t_name . ': ' . $t_version . ' = ' . $t_value['except'];
                        }
                    }
                }
            }
            if ( $t_failed == PLUGINS_PLUGINMANAGER_OK ) {
                $t_query = 'DELETE FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_dependencies' . config_get( 'db_table_suffix' ) . ' WHERE `plugin`=\'' . db_prepare_string( $t_result['plugin'] ) . '\'';
                db_query( $t_query );
                $t_query = 'INSERT INTO ' . config_get( 'db_table_prefix' ) . '_plugins_pm_dependencies' . config_get( 'db_table_suffix' ) . ' ( `plugin`, `dependencies` ) VALUES ( \'' . db_prepare_string( $t_result['plugin'] ) . '\', \'' . db_prepare_string( $t_dependences ) . '\' )';
                db_query( $t_query );
            }
        } else {
            $t_msg = 'Corrupted file';
            $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
        }
    } else {
        $t_msg = '';
        $t_failed = PLUGINS_PLUGINMANAGER_OK;
    }
    $t_res_failed = min( $t_res_failed, $t_failed );
    plugins_pluginmanager_print_test( sprintf( lang_get( 'plugins_pluginmanager_test_check' ), 'Plugins' ), $t_failed, $t_msg );
}
if ( !$g_failed ) {
    if ( $t_is_file_package ) {
        if ( !isset( $t_result['file_plugin'] ) ) {
            $t_msg = 'File corrupted';
            $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
        } else {
            $t_msg = 'File Plugin';
            $t_failed = PLUGINS_PLUGINMANAGER_OK;
        }
        $t_res_failed = min( $t_res_failed, $t_failed );
        plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_install_upgrade' ), $t_failed, $t_msg );
    } else {
        if ( !isset( $t_result['plugin'] ) ) {
            $t_msg = 'File corrupted';
            $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
        } else {
            if ( $t_repaired ) {
                $t_msg = 'Repair';
                $g_upgrade = false;
                $t_failed = PLUGINS_PLUGINMANAGER_OK;                
            } else {
                $t_version = pm_get_plugin_info( $t_result['plugin'], ( $t_result['plugin'] != 'pluginmanager' ) );
                if ( $t_version ) {
                    $t_version = $t_version['version'];
                    if ( version_compare( $t_version, $t_result['info']['version'], '<' ) ) {
                        $t_msg = 'Upgrade';
                        $g_upgrade = true;
                    } else {
                        $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                        $t_msg = 'New version is older than the installed<br />';
                        $t_msg .= $t_version . ' >= ' . $t_result['info']['version'];
                        if ( $t_manual ) {
                            if ( pm_plugin_is_installed( $t_result['plugin'] ) ) {
                                $t_msg = 'Upgrade';
                                $g_upgrade = true;
                            } else {
                                $t_msg = 'Install';
                                $g_upgrade = false;
                            }
                            $t_failed = PLUGINS_PLUGINMANAGER_OK;
                        }
                    }
                } else {
                    $t_msg = 'Install';
                    $g_upgrade = false;
                    $t_failed = PLUGINS_PLUGINMANAGER_OK;
                }
            }
        }
        $t_res_failed = min( $t_res_failed, $t_failed );
        plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_install_upgrade' ), $t_failed, $t_msg );
    }
}
if ( $t_is_file_package ) {
    $t_result['plugin'] = $t_result['plugin_dir'];
}
if ( !$g_failed && $g_upgrade && isset( $t_result['remove'] ) ) {
    $t_msg = '';
    foreach( $t_result['remove'] as $t_file ) {
        if ( $t_failed != PLUGINS_PLUGINMANAGER_FAIL ) {
            if ( file_exists( 'plugins' . DIRECTORY_SEPARATOR . $t_result['plugin'] . DIRECTORY_SEPARATOR . $t_file ) ) {
                if ( !is_file( 'plugins' . DIRECTORY_SEPARATOR . $t_result['plugin'] . DIRECTORY_SEPARATOR . $t_file ) ) {
                    $t_msg = 'plugins' . DIRECTORY_SEPARATOR . $t_result['plugin'] . DIRECTORY_SEPARATOR . $t_file . ' is not a file';
                    $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                } else if ( !unlink( 'plugins' . DIRECTORY_SEPARATOR . $t_result['plugin'] . DIRECTORY_SEPARATOR . $t_file ) ) {
                    $t_msg = 'could not remove plugins' . DIRECTORY_SEPARATOR . $t_result['plugin'] . DIRECTORY_SEPARATOR . $t_file;
                    $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                }
            } else {
                $t_failed = PLUGINS_PLUGINMANAGER_WARNING;
            }
        }
    }
    $t_res_failed = min( $t_res_failed, $t_failed );
    plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_remove' ), $t_failed, $t_msg );
}
$g_installed_files = array();
$t_msg = '';
if ( !$g_failed && isset( $t_result['files'] ) ) {
    foreach( $t_result['files'] as $t_file => $t_content ) {
        plugins_pluginmanager_install_file( $t_failed, $t_msg, $t_file, $t_content );
    }
    $t_res_failed = min( $t_res_failed, $t_failed );
    plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_new_file' ), $t_failed, $t_msg );
    if ( $g_failed ) {
        $t_restore_file = true;
    }
}
if ( !$g_failed && !$t_is_file_package ) {
    if ( !file_exists( 'plugins' . DIRECTORY_SEPARATOR . $t_result['plugin'] . DIRECTORY_SEPARATOR . 'install_inc.php' ) ) {
        $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
        $t_msg = 'Corrupted file';
    } else {
        include( 'plugins' . DIRECTORY_SEPARATOR . $t_result['plugin'] . DIRECTORY_SEPARATOR . 'install_inc.php' );
        if ( !function_exists( 'plugins_' . $t_result['plugin'] . '_uninstall_plugin' ) ) {
            $t_failed = PLUGINS_PLUGINMANAGER_WARNING;
            $t_msg = 'Uninstall instructions missing';
        }
        if ( $t_repaired ) {
            $t_function = 'plugins_' . $t_result['plugin'] . '_mantis_repair_plugin';
        } else {
            if ( $g_upgrade ) {
                $t_function = 'plugins_' . $t_result['plugin'] . '_upgrade_plugin';
            } else {
                $t_function = 'plugins_' . $t_result['plugin'] . '_install_plugin';
            }
        }
        if ( !function_exists( $t_function ) ) {
            $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
            $t_msg = 'Install/Upgrade instructions missing';
        } else {
            if ( !$t_repaired && !function_exists( preg_replace( '/_plugin$/', '_undo_plugin', $t_function ) ) ) {
                $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                $t_msg = 'Cancel install/upgrade instructions missing';
            }
        }
    }
    $t_res_failed = min( $t_res_failed, $t_failed );
    plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_check_script' ), $t_failed, $t_msg );
    if ( $g_failed ) {
        $t_restore_file = true;
    }
}
if ( !$g_failed && !$t_is_file_package ) {
    if ( $t_repaired ) {
        $t_type = 'mantis_repair';
    } else {
        if ( $g_upgrade ) {
            $t_type = 'upgrade';
        } else {
            $t_type = 'install';
        }
    }
    if ( function_exists( 'plugins_' . $t_result['plugin'] . '_' . $t_type . '_description_plugin' ) ) {
        $t_description = 'plugins_' . $t_result['plugin'] . '_' . $t_type . '_description_plugin';
    } else {
        $t_description = 'plugins_pluginmanager_default_description_plugin';
    }
    if ( isset( $t_plugins_plugin_manager_step_count[$t_type] ) ) {
        for( $t_step=1; $t_step<=$t_plugins_plugin_manager_step_count[$t_type]; $t_step++ ) {
            $t_desc = $t_description( $t_step );
            $t_failed = $t_function( $t_msg, $t_step );
            $t_res_failed = min( $t_res_failed, $t_failed );
            plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_' . $t_type ) . '<br />Step ' . $t_step . ' / ' . $t_plugins_plugin_manager_step_count[$t_type] . ': ' . $t_desc, $t_failed, $t_msg );
            if ( $g_failed ) {
                break;
            }
        }
        if ( $g_failed && !$t_repaired ) {
            $t_function = preg_replace( '/_plugin$/', '_undo_plugin', $t_function);
            for( $i=$t_step; $i>0; $i-- ) {
                $t_desc = $t_description( $i );
                $t_failed = $t_function( $t_msg, $i );
                $t_res_failed = min( $t_res_failed, $t_failed );
                plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_' . $t_type ) . '<br />Step ' . ( $t_step - $i + 1 ) . ' / ' . $t_step . ': ' . $t_desc, $t_failed, $t_msg );
            }
        }
    } else {
        $t_failed = $t_function( $t_msg, 0 );
        plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_' . $t_type ), $t_failed, $t_msg );
        if ( $g_failed && !$t_repaired ) {
            $t_function = preg_replace( '/_plugin$/', '_undo_plugin', $t_function);
            $t_failed = $t_function( $t_msg, 0 );
            $t_res_failed = min( $t_res_failed, $t_failed );
            plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_cancel_' . $t_type ), $t_failed, $t_msg );
        }
    }
    if ( $g_failed ) {
        $t_restore_file = true;
    }
}
if ( $t_restore_file ) {
    $t_failed = PLUGINS_PLUGINMANAGER_OK;
    plugins_pluginmanager_restore( $t_failed, $t_msg );
    $t_res_failed = min( $t_res_failed, $t_failed );
    plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_restore' ), $t_failed, $t_msg );
}
if ( $t_res_failed != PLUGINS_PLUGINMANAGER_FAIL && !$t_is_file_package ) {
    $t_failed = PLUGINS_PLUGINMANAGER_OK;
    if ( $g_upgrade || $t_repaired ) {
        $t_query = 'UPDATE ' . config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' ) . ' SET `installed`=1 WHERE `plugin`=\'' . db_prepare_string( $t_result['plugin'] ) . '\'';
    } else {
        $t_query = 'INSERT INTO ' . config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' ) . ' ( `plugin`, `installed` ) VALUES ( \'' . db_prepare_string( $t_result['plugin'] ) . '\', 1 )';
    }
    if ( !db_query( $t_query ) ) {
        $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
        $t_msg = 'Register fails';
    }
    $t_res_failed = min( $t_res_failed, $t_failed );
    plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_register' ), $t_failed, $t_msg );
}
plugins_pluginmanager_print_test( '<b>' . lang_get( 'plugins_pluginmanager_installation' ) . '</b>', $t_res_failed );
echo '  </table><br /><br />' . "\n";

print_bracket_link( 'plugins_page.php', lang_get( 'plugins_pluginmanager_return' ), false );

echo '</div>' . "\n";

html_page_bottom1( __FILE__ );

?>

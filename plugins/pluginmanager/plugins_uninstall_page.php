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

if ( user_get_access_level( auth_get_current_user_id() ) < config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) ) {
    header( 'Location: ' . config_get( 'path' ) . 'plugins_page.php' );
    exit();
}   

$t_plugin = gpc_get_string( 'plugin' );
$t_action = gpc_get_string( 'action', 'none' );

html_page_top1( lang_get( 'plugins_pluginmanager_uninstall_title' ) );
html_page_top2();
echo '  <div align="center"><br /><br />' . "\n";

if ( $t_action == 'confirmed' ) {
    $t_dependencies = plugins_pluginmanager_get_plugin_dependencies( $t_plugin );
    if ( $t_dependencies ) {
        echo '  <p>';
        echo lang_get( 'plugins_pluginmanager_dependencies' );
        echo '</p><br />' . "\n";
        echo '<table class="width50">' . "\n";
        foreach( $t_dependencies as $t_dependence ) {
            echo '  <tr ' . helper_alternate_class() . '>' . "\n";
            $t_row = pm_get_plugin_info( $t_dependence );
            echo '    <td>' . $t_row['name'] . '</td>' . "\n";
            echo '    <td class="center" width="25%">';
            print_bracket_link( 'plugins_uninstall_page.php?plugin=' . $t_dependence, lang_get( 'plugins_pluginmanager_uninstall_link' ), false );
            echo '</td>' . "\n";
            echo '  </tr>' . "\n";
        }
        echo '</table>';
    } else {
        echo '<b>' . lang_get( 'plugins_pluginmanager_uninstall' ) . '</b><br /><br />' . "\n";
        $g_failed = false;
        $t_failed = PLUGINS_PLUGINMANAGER_OK;
        $t_res_failed = $t_failed;
        echo '  <table class="width50">' . "\n";
        if ( !$g_failed ) {
            if ( !file_exists( 'plugins' . DIRECTORY_SEPARATOR . $t_plugin . DIRECTORY_SEPARATOR . 'install_inc.php' ) ) {
                $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                $t_msg = 'Corrupted file';
            } else {
                include( 'plugins' . DIRECTORY_SEPARATOR . $t_plugin . DIRECTORY_SEPARATOR . 'install_inc.php' );
                $t_function = 'plugins_' . $t_plugin . '_uninstall_plugin';
                if ( !function_exists( $t_function ) || !function_exists( preg_replace( '/_plugin$/', '_undo_plugin', $t_function ) ) ) {
                    $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
                    $t_msg = 'Uninstall instructions missing';
                }
            }
            $t_res_failed = min( $t_res_failed, $t_failed );
            plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_check_script' ), $t_failed, $t_msg );
        }
        if ( !$g_failed ) {
            $t_type = 'uninstall';
            if ( function_exists( 'plugins_' . $t_plugin . '_' . $t_type . '_description_plugin' ) ) {
                $t_description = 'plugins_' . $t_plugin . '_' . $t_type . '_description_plugin';
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
                if ( $g_failed ) {
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
                if ( $g_failed ) {
                    $t_function = preg_replace( '/_plugin$/', '_undo_plugin', $t_function);
                    $t_failed = $t_function( $t_msg, 0 );
                    $t_res_failed = min( $t_res_failed, $t_failed );
                    plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_cancel_' . $t_type ), $t_failed, $t_msg );
                }
            }
        }
        if ( !$g_failed ) {
            $t_failed = PLUGINS_PLUGINMANAGER_OK;
            if ( !pm_file_delete( 'plugins' . DIRECTORY_SEPARATOR . $t_plugin ) ) {
                $t_failed = PLUGINS_PLUGINMANAGER_WARNING;
            }
            $t_res_failed = min( $t_res_failed, $t_failed );
            plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_delete' ), $t_failed, $t_msg );
        }
        if ( !$g_failed && $t_plugin != 'pluginmanager' ) {
            $t_failed = PLUGINS_PLUGINMANAGER_OK;
            $t_query = 'DELETE FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' ) . ' WHERE `plugin`=\'' . db_prepare_string( $t_plugin ) . '\'';
            db_query( $t_query );
            $t_query = 'DELETE FROM ' . config_get( 'db_table_prefix' ) . '_plugins_pm_dependencies' . config_get( 'db_table_suffix' ) . ' WHERE `plugin`=\'' . db_prepare_string( $t_plugin ) . '\'';
            db_query( $t_query );
            $t_res_failed = min( $t_res_failed, $t_failed );
            plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_unregister' ), $t_failed, $t_msg );
        }
        plugins_pluginmanager_print_test( '<b>' . lang_get( 'plugins_pluginmanager_installation' ) . '</b>', $t_res_failed );
        echo '  </table><br /><br />' . "\n";

        if ( $t_plugin = 'pluginmanager' ) {
            $t_url = 'index.php';
        } else {
            $t_url = 'plugins_page.php';
        }
        print_bracket_link( $t_url, lang_get( 'plugins_pluginmanager_return' ), false );
    }
} else {
    echo '  <p>';
    $t_row = pm_get_plugin_info( $t_plugin );
    printf( lang_get( 'plugins_pluginmanager_uninstall_question' ), $t_row['name'] );
    echo '</p><br />' . "\n";
    print_bracket_link( 'plugins_uninstall_page.php?action=confirmed&plugin=' . $t_plugin, lang_get( 'yes' ), false );
    echo '&nbsp;';
    print_bracket_link( 'plugins_page.php', lang_get( 'no' ), false );
}

echo '  </div>';
html_page_bottom1( __FILE__ );
?>
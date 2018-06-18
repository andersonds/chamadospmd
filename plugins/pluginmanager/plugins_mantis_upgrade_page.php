<?php

/**
 * Plugin manager
 *
 *
 * Created: 2008-01-12
 * Last update: 2008-09-21
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @version 0.2.0
 */

require_once( 'core.php' );

if ( !function_exists( 'pm_plugin_is_installed' ) || !pm_plugin_is_installed( 'pluginmanager' ) ) {
    header( 'Location: ' . config_get( 'path' ) . 'main_page.php' );
    exit();
}

if ( user_get_access_level( auth_get_current_user_id() ) < config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) ) {
    header( 'Location: ' . config_get( 'path' ) . 'plugins_page.php' );
    exit();
}


html_page_top1( lang_get( 'plugins_pluginmanager_mantis_upgrade_title' ) );
html_page_top2();

echo "\n\n";
echo '<br />' . "\n" . '<div align="center">' . "\n";

echo '<b>' . lang_get( 'plugins_pluginmanager_mantis_upgrade' ) . '</b><br /><br />' . "\n";

echo '  <table class="width50">' . "\n";
$t_res_failed = PLUGINS_PLUGINMANAGER_OK;
$g_failed = false;

$t_plg_list = pm_get_installed_plugin_list();
$t_plg_list = plugins_pluginmanager_order_plugins( $t_plg_list );
$t_display_note = false;
foreach( $t_plg_list as $t_plg ) {
    if ( !is_blank( $t_plg ) ) {
        $t_plugin_info = pm_get_plugin_info( $t_plg, false );
        $t_failed = plugins_pluginmanager_check_mantis_upgrade( $t_plg );
        if ( $t_failed == PLUGINS_PLUGINMANAGER_FAIL ) {
            $t_msg = 'Contact developer*';
            $t_display_note = true;
        } else {
            $t_msg = '';
        }
        $t_res_failed = min( $t_res_failed, $t_failed );
        plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_check_plugin' ) . ' <b>' . $t_plugin_info['name'] . '</b>', $t_failed, $t_msg );
        if ( $g_failed ) {
            break;
        }
        $t_type = 'mantis_upgrade';
        if ( function_exists( 'plugins_' . $t_plg . '_' . $t_type . '_description_plugin' ) ) {
            $t_description = 'plugins_' . $t_plg . '_' . $t_type . '_description_plugin';
        } else {
            $t_description = 'plugins_pluginmanager_default_description_plugin';
        }
        $t_function = 'plugins_' . $t_plg . '_mantis_upgrade_plugin';
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
        }
        $t_failed = PLUGINS_PLUGINMANAGER_OK;
        $t_query = 'UPDATE ' . config_get( 'db_table_prefix' ) . '_plugins_pm_list' . config_get( 'db_table_suffix' ) . ' SET `installed`=2 WHERE `plugin`=\'' . db_prepare_string( $t_plg ) . '\'';
        if ( !db_query( $t_query ) ) {
            $t_failed = PLUGINS_PLUGINMANAGER_FAIL;
            $t_msg = 'Register fails';
        }
        $t_res_failed = min( $t_res_failed, $t_failed );
        plugins_pluginmanager_print_test( lang_get( 'plugins_pluginmanager_test_register' ), $t_failed, $t_msg );
    }
}

plugins_pluginmanager_print_test( '<b>' . lang_get( 'plugins_pluginmanager_mupgrade' ) . '</b>', $t_res_failed );
echo '  </table><br /><br />' . "\n";

print_bracket_link( 'main_page', lang_get( 'main_link' ), false );

echo '</div>' . "\n";

if ( $t_display_note ) {
    echo '*: Contact the developer of the plugin to know how to upgrade Mantis.';
}

html_page_bottom1( __FILE__ );


?>

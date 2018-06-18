<?php

/**
 * Plugin manager
 *
 *
 * Created: 2007-02-04
 * Last update: 2008-08-26
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @version 0.2.0
 */

require_once( 'core.php' );

if ( !function_exists( 'pm_plugin_is_installed' ) || !pm_plugin_is_installed( 'pluginmanager' ) ) {
    header( 'Location: ' . config_get( 'path' ) . 'plugins_install_page.php?plugin=pluginmanager' );
    exit();
}

$t_url_list = config_get( 'plugins_pluginmanager_url_list', PLUGINS_PLUGINMANAGER_URL_LIST_DEFAULT );

$t_url_list = explode( "\n", $t_url_list );

$t_current_user_id = auth_get_current_user_id();

$t_main_menu = config_get( 'main_menu_custom_options' );
$t_access_level = ADMINISTRATOR;
foreach( $t_main_menu as $t_menu ) {
    if ( ereg( 'plugins_page.php$', $t_menu[2] ) ) {
        $t_access_level = $t_menu[1];
    }
}

if ( user_get_access_level( $t_current_user_id ) < $t_access_level ) {
    header( 'Location: index.php' );
    exit();
}

html_page_top1( lang_get( 'plugins_pluginmanager_list_title' ) );
html_page_top2();

$t_user_ok = ( user_get_access_level( $t_current_user_id ) >= config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) );

?>

<br />
<div align="center">

<table class="width75" cellspacing="1">

<!-- Title -->
<tr>
<td class="center"><?php echo lang_get( 'plugins_pluginmanager_plugin' ) ?></td>
<?php if ( $t_user_ok ) { ?>
<td class="center"><?php echo lang_get( 'plugins_pluginmanager_install' ) ?></td>
<?php } else { ?>
<td class="center"></td>
<?php } ?>
</tr>
<?php

foreach( $t_url_list as $t_url ) {
    $t_url = trim( $t_url );
    $t_handle = @fopen( $t_url, 'r' );
    if ( $t_handle ) {
        $t_content = '';
        while( !feof( $t_handle ) ) {
            $t_content .= fread( $t_handle, 1024 );
        }
        fclose( $t_handle );    
        $t_result = plugins_pluginmanager_xml_parse( $t_content );
        if ( count( $t_result[0]['plugins'] ) > 0 ) {
            foreach( $t_result[0]['plugins'] as $t_plg ) {
                echo '<tr ' . helper_alternate_class() . '><td class="left">';
                echo '<b>' . lang_get( 'plugins_pluginmanager_plugin' ) . '</b><a href="' . $t_plg['url'] . '" target="_blank">' . $t_plg['name'] . '</a><br />';
                echo '<b>' . lang_get( 'plugins_pluginmanager_description' ) . '</b>' . $t_plg['description'] . '<br />';
                echo '<b>' . lang_get( 'plugins_pluginmanager_author' ) . '</b>' . $t_plg['author'] . '<br />';
                echo '<b>' . lang_get( 'plugins_pluginmanager_date' ) . '</b>' . $t_plg['date'] . '<br />';
                echo '<b>' . lang_get( 'plugins_pluginmanager_version' ) . '</b>' . $t_plg['version'] . '<br />';
                echo '</td><td class="center">';
                if ( $t_user_ok ) {
                    if ( pm_plugin_is_installed( $t_plg['id'] ) ) {
                        $t_info = pm_get_plugin_info( $t_plg['id'] );
                        if ( version_compare( $t_info['version'], $t_plg['version'], '>=' ) ) {
                            echo '<font color="green">' . lang_get( 'plugins_pluginmanager_installed' ) . '</a>';
                        } else {
                            echo '<a href="plugins_install_page.php?url=' . $t_plg['download'] . '">' . lang_get( 'plugins_pluginmanager_upgrade_link' ) . '</a>';
                        }
                    } else {
                        echo '<a href="plugins_install_page.php?url=' . $t_plg['download'] . '">' . lang_get( 'plugins_pluginmanager_install_link' ) . '</a>';
                    }
                } else {
                    if ( pm_plugin_is_installed( $t_plg['id'] ) ) {
                        echo '<font color="green">' . lang_get( 'plugins_pluginmanager_installed' ) . '</font>';
                    } else {
                        echo lang_get( 'plugins_pluginmanager_not_installed' );
                    }
                }
                echo '</td></tr>';
            }
        }
    }
}

?>
</table><br />

<?php print_bracket_link( 'plugins_page.php', lang_get( 'plugins_pluginmanager_return' ), false ); ?>

</div>
<?php

html_page_bottom1( __FILE__ );

?>
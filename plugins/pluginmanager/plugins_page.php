<?php

/**
 * Plugin manager
 *
 *
 * Created: 2007-02-04
 * Last update: 2008-08-23
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

define( 'PLUGINS_PM_OK', 1 );

$t_plugin = gpc_get_string( 'plugin', 'none' );

require_once( 'plugins' . DIRECTORY_SEPARATOR . 'pluginmanager' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'plugin_api.php' );

if ( $t_plugin != 'none' && pm_plugin_is_installed( $t_plugin ) ) {
    $t_index = 'plugins' . DIRECTORY_SEPARATOR . $t_plugin . DIRECTORY_SEPARATOR . 'index.php';
    if ( file_exists( $t_index ) ) {
        $t_access_level = user_get_access_level( auth_get_current_user_id(), helper_get_current_project() );
        $t_info = pm_get_plugin_info( $t_plugin );
        if ( isset( $t_info['bypass'] ) && $t_access_level >= $t_info['bypass'] ) {
            include( $t_index );
            exit();
        }
        if ( $t_access_level >= config_get( 'plugins_pluginmanager_' . $t_plugin . '_threshold', PLUGINS_PLUGINMANAGER_CONFIGURE_THRESHOLD_DEFAULT ) ) {
            include( $t_index );
            exit();
        }
    }   
}

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

html_page_top1( lang_get( 'plugins_pluginmanager_title' ) );
html_page_top2();

?>

<br />
<div align="center">

<?php

if ( user_get_access_level( $t_current_user_id ) >= config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) ) {
    if ( is_writeable( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'plugins' ) ) {
        $t_max_file_size = (int)min( ini_get_number( 'upload_max_filesize' ), ini_get_number( 'post_max_size' ), config_get( 'max_file_size' ) );

?>
  <form name="install" action="plugins_install_page.php" method="post" enctype="multipart/form-data" action="plugins_page.php">
    <input type="hidden" name="max_file_size" value="<?php echo $t_max_file_size ?>" />
    <table class="width75" cellspacing="1">

      <!-- Title -->
      <tr>
        <td class="form-title"><?php echo lang_get( 'plugins_pluginmanager_install_plugin' )  ?></td>
      </tr>

      <!-- URL -->
      <tr <?php echo helper_alternate_class() ?>>
        <td width="30%" class="category"><?php echo lang_get( 'plugins_pluginmanager_url' ) ?></td>
        <td><input type="text" name="url" size="50" /></td>
      </tr>

      <!-- OR -->
      <tr>
        <td class="center" width="30%"><b><?php echo lang_get( 'plugins_pluginmanager_or' ) ?></b></td>
        <td></td>
      </tr>

      <!-- Upload file -->
      <tr <?php echo helper_alternate_class() ?>>
        <td width="30%" class="category"><?php echo lang_get( 'plugins_pluginmanager_upload' ) ?></td>
        <td><input type="file" name="upload" /></td>
      </tr>

      <!-- Submit Button -->
      <tr>
        <td class="center">
          [ <a href="plugins_list_page.php"><?php echo lang_get( 'plugins_pluginmanager_list' ); ?></a> ]
        </td>
        <td class="center">
          <input type="submit" class="button" value="<?php echo lang_get( 'submit_button' ) ?>" />
        </td>
      </tr>
    </table>
  </form>
  <br />
<?php

    } else {

?>
  <table class="width75" cellspacing="1">
    <tr>
      <td class="form-title"><?php echo lang_get( 'plugins_pluginmanager_install_plugin' )  ?></td>
      <td></td>
    </tr>
    <tr>
      <td colspan="2" class="center"><font color="red"><?php echo lang_get( 'plugins_pluginmanager_directories_not_writeable' )?></font></td>
    </tr>
  </table>
  <br />
<?php

    }
} else if ( config_get( 'plugins_pluginmanager_display_website', PLUGINS_PLUGINMANAGER_DISPLAY_WEBSITE_DEFAULT ) == ON ) {
?>
<table class="width75" cellspacing="1">

      <tr>
        <td class="center">
          [ <a href="plugins_list_page.php"><?php echo lang_get( 'plugins_pluginmanager_list' ); ?></a> ]
        </td>
      </tr>
    </table><br />
<?php
  
}

?>

<table class="width75" cellspacing="1">

<!-- Title -->
<tr>
<td class="center"><?php echo lang_get( 'plugins_pluginmanager_plugin' ) ?></td>
<?php if ( config_get( 'plugins_pluginmanager_display_website', PLUGINS_PLUGINMANAGER_DISPLAY_WEBSITE_DEFAULT ) == ON ) { ?><td class="center"><?php echo lang_get( 'plugins_pluginmanager_website' ) ?></td><?php } ?>
<td class="center"><?php echo lang_get( 'plugins_pluginmanager_installed_current_version' ) ?></td>
<?php if ( user_get_access_level( $t_current_user_id ) >= config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) ) { ?>
<td class="center"><?php echo lang_get( 'plugins_pluginmanager_uninstall' ) ?></td>
<?php } ?>
</tr>

<?php

$t_plugin_list = plugins_pluginmanager_get_plugin_list();

foreach( $t_plugin_list as $t_plugin ) {
    echo '<tr ' . helper_alternate_class() . ">\n";
    echo '<td class="center"><b>';
    if ( $t_plugin['has_access_level'] ) {
        echo '<a href="plugins_page.php?plugin=' . $t_plugin['id'] . '">' . $t_plugin['name'] . '</a>';
    } else {
        echo $t_plugin['name'];
    }
    echo '</b></td>' . "\n";
    if ( config_get( 'plugins_pluginmanager_display_website', PLUGINS_PLUGINMANAGER_DISPLAY_WEBSITE_DEFAULT ) == ON ) {
        echo '<td class="center">';
        if ( isset( $t_plugin['url'] ) ) {
            echo '<a href="' . $t_plugin['url'] . '">www</a> [<a href="' . $t_plugin['url'] . '" target="_blank">^</a>]';
        } else {
            echo '--';
        }
        echo "</td>\n";
    }
    if ( version_compare( $t_plugin['version'], $t_plugin['check'], '<' ) ) {
        echo '<td class="center">' . $t_plugin['version'] . ' / <b>' . $t_plugin['check'] . '</b>';
        if ( $t_plugin['can_be_upgraded_uninstalled' ] && user_get_access_level( $t_current_user_id ) >= config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) ) {
            echo '&nbsp; &nbsp; <a href="plugins_upgrade_page.php?plugin=' . $t_plugin['id'] . '">' . lang_get( 'plugins_pluginmanager_upgrade_link' ) . '</a>';
        }
    } else {
        echo '<td class="center">' . $t_plugin['version'] . ' / ' . $t_plugin['check'];
    }
    if ( isset( $t_plugin['must_be_installed'] ) && $t_plugin['must_be_installed'] && user_get_access_level( $t_current_user_id ) >= config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) ) {
        echo '&nbsp; &nbsp; <a href="plugins_install_page.php?plugin=' . $t_plugin['id'] . '">' . lang_get( 'plugins_pluginmanager_install_link' ) . '</a>';
    }
    if ( isset( $t_plugin['must_be_repaired'] ) && $t_plugin['must_be_repaired'] && user_get_access_level( $t_current_user_id ) >= config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) ) {
        echo '&nbsp; &nbsp; <a href="plugins_install_page.php?plugin=' . $t_plugin['id'] . '">' . lang_get( 'plugins_pluginmanager_repair_link' ) . '</a>';
    }
    echo '</td>' . "\n";
    if ( user_get_access_level( $t_current_user_id ) >= config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) ) {
        if ( $t_plugin['can_be_upgraded_uninstalled' ] ) {
            echo '<td class="center"><a href="plugins_uninstall_page.php?plugin=' . $t_plugin['id'] . '">' . lang_get( 'plugins_pluginmanager_uninstall_link' ) . '</a></td>' . "\n";
        } else {
            echo '<td></td>' . "\n";
        }
    }   
    echo "</tr>\n";
}

?>

</table>

<?php

if ( user_get_access_level( $t_current_user_id ) >= config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT ) ) {

?>
  <br />
  <form name="package" action="plugins_package_page.php" method="post">
    <table class="width75" cellspacing="1">

      <!-- Title -->
      <tr>
        <td class="form-title"><?php echo lang_get( 'plugins_pluginmanager_package_plugin' )  ?></td>
      </tr>

      <!-- Plugin -->
      <tr <?php echo helper_alternate_class() ?>>
        <td width="30%" class="category"><span class="required">*</span><?php echo lang_get( 'plugins_pluginmanager_plugin' ) ?></td>
        <td>
          <select name="plugin">
<?php

$t_plugin_list = plugins_pluginmanager_get_plugin_list( false );
foreach( $t_plugin_list as $t_plugin ) {
    if ( $t_plugin['can_be_packaged'] ) {
        echo '            <option value="' . $t_plugin['id'] . '">' . $t_plugin['name'] . '</option>' . "\n";
    }   
}

?>
          </select>
        </td>
      </tr>

      <!-- Submit Button -->
      <tr>
        <td class="left">
          <span class="required"> * <?php echo lang_get( 'required' ) ?></span>
        </td>
        <td class="center">
          <input type="submit" class="button" value="<?php echo lang_get( 'submit_button' ) ?>" />
        </td>
      </tr>
    </table>
  </form>
    
  <br />
  <form name="package" action="plugins_file_package_page.php" method="post">
    <table class="width75" cellspacing="1">

      <!-- Title -->
      <tr>
        <td class="form-title"><?php echo lang_get( 'plugins_pluginmanager_file_package_plugin' )  ?></td>
      </tr>

      <!-- Plugin -->
      <tr <?php echo helper_alternate_class() ?>>
        <td width="30%" class="category"><span class="required">*</span><?php echo lang_get( 'plugins_pluginmanager_file_plugin' ) ?></td>
        <td>
          <select name="file">
<?php

$t_plugin_list = plugins_pluginmanager_get_file_plugin_list();
foreach( $t_plugin_list as $t_plugin ) {
    if ( $t_plugin['can_be_packaged'] ) {
        echo '            <option value="' . $t_plugin['id'] . '">' . $t_plugin['name'] . '</option>' . "\n";
    }   
}

?>
          </select>
        </td>
      </tr>

      <!-- Submit Button -->
      <tr>
        <td class="left">
          <span class="required"> * <?php echo lang_get( 'required' ) ?></span>
        </td>
        <td class="center">
          <input type="submit" class="button" value="<?php echo lang_get( 'submit_button' ) ?>" />
        </td>
      </tr>
    </table>
  </form>
<?php

}
    
?>

</div>

<?php

html_page_bottom1( __FILE__ );

?>

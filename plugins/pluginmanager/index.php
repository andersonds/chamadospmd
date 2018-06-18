<?php

/**
 * Plugin Manager
 *
 *
 * Created: 2007-02-12
 * Last update: 2008-08-23
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @version 0.1.4
 */

if ( !ereg( 'plugins_page.php', $_SERVER['PHP_SELF'] ) ) {
    header( 'Location: ' . config_get( 'path' ) . 'plugins_page.php' );
    exit();
}

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'plugin_api.php' );

$t_action = gpc_get_string( 'action', 'none' );
if ( $t_action == 'update' ) {
    $t_project_id = gpc_get_int( 'project_id' );
    $t_check_version = gpc_get_bool( 'check_version' );
    $t_check_unstable = gpc_get_bool( 'check_unstable' );
    $t_display_www = gpc_get_bool( 'display_www' );
    $t_install = gpc_get_int( 'install' );
    $t_menu_threshold = gpc_get_int( 'menu_threshold' );
    $t_url_list = gpc_get_string( 'url_list', '' );
    config_set( 'plugins_pluginmanager_install_threshold', $t_install, NO_USER, ALL_PROJECTS );
    config_set( 'plugins_pluginmanager_check_version', $t_check_version, NO_USER, $t_project_id );
    config_set( 'plugins_pluginmanager_check_unstable', $t_check_unstable, NO_USER, $t_project_id );
    config_set( 'plugins_pluginmanager_display_website', $t_display_www, NO_USER, $t_project_id );
    config_set( 'plugins_pluginmanager_url_list', $t_url_list, NO_USER, ALL_PROJECTS );
    $t_main_menu_custom_options = config_get( 'main_menu_custom_options', array() );
    for( $i=0; $i<count( $t_main_menu_custom_options ); $i++ ) {
        if ( $t_main_menu_custom_options[$i][0] == 'plugins_pluginmanager_link' ) {
            $t_main_menu_custom_options[$i][1] = $t_menu_threshold;
        }
    }
    config_set( 'main_menu_custom_options', $t_main_menu_custom_options );
    $t_plugin_list = plugins_pluginmanager_get_plugin_list( false );
    foreach( $t_plugin_list as $t_plugin ) {
        $t_threshold = gpc_get_int( $t_plugin['id'] . '_configure_threshold', PLUGINS_PLUGINMANAGER_CONFIGURE_THRESHOLD_DEFAULT );
        config_set( 'plugins_pluginmanager_' . $t_plugin['id'] . '_threshold', $t_threshold, NO_USER, $t_project_id );
    }   
    print_successful_redirect( 'plugins_page.php' );
    exit();
}

$t_project_id = helper_get_current_project();

html_page_top1( lang_get( 'plugins_pluginmanager_page_title' ) );
html_page_top2();

$t_main_menu_custom_options = config_get( 'main_menu_custom_options', array() );
for( $i=0; $i<count( $t_main_menu_custom_options ); $i++ ) {
    if ( $t_main_menu_custom_options[$i][0] == 'plugins_pluginmanager_link' ) {
        $t_menu_threshold = $t_main_menu_custom_options[$i][1];
    }
}

$t_temporary_directory = config_get( 'plugins_pluginmanager_temporary_directory', PLUGINS_PLUGINMANAGER_TEMPORARY_DIRECTORY_DEFAULT );
if ( $t_temporary_directory == '' ) {
    $t_temporary_directory = dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . 'tmp';
}

echo "\n\n";
echo "<br />\n<div align=\"center\">\n";
echo '  ' . str_replace( '%%project%%', '<b>' . project_get_name( helper_get_current_project() ) . '</b>', lang_get( 'plugins_pluginmanager_configuration_for_project' ) ) . "\n";

?>
  <br /><br />
  <form name="plugins_pluginmanager" method="post" action="plugins_page.php">
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="project_id" value="<?php echo $t_project_id ?>" />
    <input type="hidden" name="plugin" value="pluginmanager" />
    <table class="width75" cellspacing="1">

      <!-- Check Version -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo lang_get( 'plugins_pluginmanager_check_version' ) ?>
        </td>
        <td width="70%">
          <input type="checkbox" name="check_version"<?php check_checked( config_get( 'plugins_pluginmanager_check_version', PLUGINS_PLUGINMANAGER_CHECK_VERSION_DEFAULT ), ON ) ?> />
        </td>
      </tr>

      <!-- Check Unstable Version -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo lang_get( 'plugins_pluginmanager_check_unstable' ) ?>
        </td>
        <td width="70%">
          <input type="checkbox" name="check_unstable"<?php check_checked( config_get( 'plugins_pluginmanager_check_unstable', PLUGINS_PLUGINMANAGER_CHECK_UNSTABLE_DEFAULT ), ON ) ?> />
        </td>
      </tr>

      <!-- Display WWW -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo lang_get( 'plugins_pluginmanager_display_website' ) ?>
        </td>
        <td width="70%">
          <input type="checkbox" name="display_www"<?php check_checked( config_get( 'plugins_pluginmanager_display_website', PLUGINS_PLUGINMANAGER_DISPLAY_WEBSITE_DEFAULT ), ON ) ?> />
        </td>
      </tr>

      <!-- spacer -->
      <tr>
        <td class="spacer" colspan="2">&nbsp;</td>
      </tr>

      <!-- URL for available plugin list -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <?php echo lang_get( 'plugins_pluginmanager_url_list' ) ?>
        </td>
        <td width="70%">
          <textarea name="url_list" cols="50" rows="5"><?php echo config_get( 'plugins_pluginmanager_url_list', PLUGINS_PLUGINMANAGER_URL_LIST_DEFAULT ) ?></textarea>
        </td>
      </tr>

      <!-- spacer -->
      <tr>
        <td class="spacer" colspan="2">&nbsp;</td>
      </tr>

      <!-- Who can see Menu link -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo lang_get( 'plugins_pluginmanager_menu_link_threshold' ) ?>
        </td>
        <td width="70%">
          <select name="menu_threshold">
            <?php
              $t_access_levels_enum_string = config_get( 'access_levels_enum_string' );
              $t_arr = explode_enum_string( $t_access_levels_enum_string );
              $t_val = $t_menu_threshold;
              $enum_count = count( $t_arr );
              for( $i=0; $i<$enum_count; $i++ ) {
                  $t_elem = explode_enum_arr( $t_arr[$i] );
                  $t_access_level = get_enum_element( 'access_levels', $t_elem[0] );
                  PRINT "<option value=\"$t_elem[0]\"";
                  check_selected( $t_val, $t_elem[0] );
                  PRINT ">$t_access_level</option>";
              }
            ?>
          </select>
        </td>
      </tr>

      <!-- spacer -->
      <tr>
        <td class="spacer" colspan="2">&nbsp;</td>
      </tr>

      <!-- Who can Install/package -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo lang_get( 'plugins_pluginmanager_install_uninstall_package' ) ?>
        </td>
        <td width="70%">
          <select name="install">
            <?php
              $t_access_levels_enum_string = config_get( 'access_levels_enum_string' );
              $t_arr = explode_enum_string( $t_access_levels_enum_string );
              $t_val = config_get( 'plugins_pluginmanager_install_threshold', PLUGINS_PLUGINMANAGER_INSTALL_THRESHOLD_DEFAULT );
              $enum_count = count( $t_arr );
              for( $i=0; $i<$enum_count; $i++ ) {
                  $t_elem = explode_enum_arr( $t_arr[$i] );
                  $t_access_level = get_enum_element( 'access_levels', $t_elem[0] );
                  PRINT "<option value=\"$t_elem[0]\"";
                  check_selected( $t_val, $t_elem[0] );
                  PRINT ">$t_access_level</option>";
              }
            ?>
          </select>
        </td>
      </tr>

      <!-- spacer -->
      <tr>
        <td class="spacer" colspan="2">&nbsp;</td>
      </tr>

<?php

$t_plugin_list = plugins_pluginmanager_get_plugin_list( false );
foreach( $t_plugin_list as $t_plugin ) {
?>
      <!-- <?php echo $t_plugin['name'] ?> Plugin -->
      <tr <?php echo helper_alternate_class() ?>>
        <td class="category" width="30%">
          <span class="required">*</span><?php echo lang_get( 'plugins_pluginmanager_configure_threshold' ) . ' (' . $t_plugin['name'] . ')' ?>
        </td>
        <td width="70%">
          <select name="<?php echo $t_plugin['id'] ?>_configure_threshold">
            <?php
              $t_access_levels_enum_string = config_get( 'access_levels_enum_string' );
              $t_arr = explode_enum_string( $t_access_levels_enum_string );
              $t_val = config_get( 'plugins_pluginmanager_' . $t_plugin['id'] . '_threshold', PLUGINS_PLUGINMANAGER_CONFIGURE_THRESHOLD_DEFAULT );
              $enum_count = count( $t_arr );
              for( $i=0; $i<$enum_count; $i++ ) {
                  $t_elem = explode_enum_arr( $t_arr[$i] );
                  $t_access_level = get_enum_element( 'access_levels', $t_elem[0] );
                  PRINT "<option value=\"$t_elem[0]\"";
                  check_selected( $t_val, $t_elem[0] );
                  PRINT ">$t_access_level</option>";
              }
            ?>
          </select>
        </td>
      </tr>
        
<?php

}

?>

      <!-- spacer -->
      <tr>
        <td class="spacer" colspan="2">&nbsp;</td>
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

  <br /><br />
  <table class="width75" cellspacing="1">
    <tr>
      <td class="center"><?php print_bracket_link( 'plugins_mantis_upgrade_page.php', lang_get( 'plugins_pluginmanager_mantis_upgrade' ) ); ?></td>
    </tr>
  </table>

<?php

echo "</div>\n";

html_page_bottom1( __FILE__ );

?>

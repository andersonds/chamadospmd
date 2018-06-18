<?php

/**
 * This file is dedicated to all functions used to backup the
 * orginal Mantis files.
 *
 * Created: 2007-05-26
 * Last update: 2008-09-13
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @package PluginManager
 * @version 0.1.3
 */

$p_pm_backup_error_string = null;

/** 
 * This function is used to backup a file.
 * It returns true in case of success else false.
 *
 * @param string $p_file The file to backup. The file
 * must be relative to the Mantis root directory.
 * @return bool
 */
function pm_backup_file( $p_file ) {
    global $p_pm_backup_error_string;
    
    $t_os_file = str_replace( '/', DIRECTORY_SEPARATOR, $p_file );
    $t_backup_directory = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . 'backup';
    if ( is_dir( $t_backup_directory ) && !is_writeable( $t_backup_directory ) ) {
        $p_pm_backup_error_string = $t_backup_directory . ' is not writeable';
        return false;
    } else if ( !is_dir( $t_backup_directory ) && file_exists( $t_backup_directory ) ) {
        $p_pm_backup_error_string = $t_backup_directory . ' is not a directory';
        return false;
    } else if ( !is_dir( $t_backup_directory ) && !file_exists( $t_backup_directory ) ) {
        mkdir( $t_backup_directory );
        $t_handle = fopen( $t_backup_directory . DIRECTORY_SEPARATOR . '.htaccess', 'w' );
        fwrite( $t_handle, 'deny from all' );
        fclose( $t_handle );
    }
    if ( substr( $p_file, 1 ) == '/' || !file_exists( dirname( $t_backup_directory ) . DIRECTORY_SEPARATOR . $t_os_file ) ) {
        $p_pm_backup_error_string = $t_backup_directory . DIRECTORY_SEPARATOR . $t_os_file . ' does not exist';
        return false;
    }
    $t_file = explode( '/', $p_file );
    $t_final_backup_directory = $t_backup_directory;
    for( $i=0; $i<count( $t_file ) - 1; $i++ ) {
        $t_final_backup_directory = $t_final_backup_directory . DIRECTORY_SEPARATOR . $t_file[$i];
        if ( !is_dir( $t_final_backup_directory ) && !file_exists( $t_final_backup_directory ) ) {
            mkdir( $t_final_backup_directory );
        } else if ( is_file( $t_final_backup_directory ) ) {
            $p_pm_backup_error_string = $t_final_backup_directory . ' already exists';
            return false;
        }
    }
    $t_file = $t_file[count( $t_file ) - 1];
    if ( file_exists( $t_final_backup_directory . DIRECTORY_SEPARATOR . $t_file ) ) {
        $t_dir = opendir( $t_final_backup_directory );
        $t_id = 1;
        while( false !== ( $t_fl = readdir( $t_dir ) ) ) {
            if ( preg_match( '/' . $t_file . '.([0-9]+)/', $t_fl, $t_matches ) ) {
                $t_id = max( $t_id, ( $t_matches[1] + 1 ) );
            }
        }
        closedir( $t_dir );
        return copy( dirname( $t_backup_directory ) . DIRECTORY_SEPARATOR . $t_os_file, $t_final_backup_directory . DIRECTORY_SEPARATOR . $t_file . '.' . $t_id );
    } else {
        return copy( dirname( $t_backup_directory ) . DIRECTORY_SEPARATOR . $t_os_file, $t_final_backup_directory . DIRECTORY_SEPARATOR . $t_file );
    }
}

/** 
 * This function is used to remove a file from the backup
 * directory.
 * This function returns true if success else false.
 *
 * @param string $p_file The file to remove. The file
 * must be relative to the Mantis root directory.
 * @return bool
 */
function pm_backup_remove_file( $p_file ) {
    $t_backup_directory = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . 'backup';
    $t_file = str_replace( '/', DIRECTORY_SEPARATOR, $p_file );
    if ( file_exists( $t_backup_directory . DIRECTORY_SEPARATOR . $t_file ) ) {
        @unlink( $t_backup_directory . DIRECTORY_SEPARATOR . $t_file );
        return true;
    } else {
        return false;
    }
}

/** 
 * This function is used to restore a file from the backup
 * directory.
 * This function returns true if success else false.
 *
 * @param string $p_file The file to remove. The file
 * must be relative to the Mantis root directory.
 * @return bool
 */
function pm_backup_restore_file( $p_file ) {
    $t_backup_directory = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR . 'backup';
    $t_file = str_replace( '/', DIRECTORY_SEPARATOR, $p_file );
    if ( file_exists( $t_backup_directory . DIRECTORY_SEPARATOR . $t_file ) ) {
        if ( file_exists( dirname( $t_backup_directory ) . DIRECTORY_SEPARATOR . $t_file ) ) {
            @unlink( dirname( $t_backup_directory ) . DIRECTORY_SEPARATOR . $t_file );
        }
        @copy( $t_backup_directory . DIRECTORY_SEPARATOR . $t_file, dirname( $t_backup_directory ) . DIRECTORY_SEPARATOR . $t_file );
        return true;
    } else {
        return false;
    }
}

/** 
 * This function returns the last error message that occured during
 * a backup command.
 *
 * @return string
 */
function pm_backup_error() {
    global $p_pm_backup_error_string;
    
    if ( $p_pm_backup_error_string != null ) {
        return $p_pm_backup_error_string;
    } else {
        return '';
    }
}

?>

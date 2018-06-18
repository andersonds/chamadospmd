<?php

/**
 * This file is dedicated to all functions used to move/copy
 * files.
 *
 * Created: 2007-05-27
 * Last update: 2007-10-13
 *
 * @link http://deboutv.free.fr/mantis/
 * @author Vincent DEBOUT <deboutv@free.fr>
 * @package PluginManager
 * @version 0.1.3
 */

/** 
 * This function is used to move a file without any backup.
 * This function returns true if success else false.
 *
 * @param string $p_file_location The file location relative
 * to the Mantis Root directory.
 * @param string $p_file_new_location The new file location
 * relative to the Mantis Root directory.
 * @return bool
 */
function pm_file_move( $p_file_location, $p_file_new_location ) {
    if ( substr( $p_file_location, 0 ) == '/' || substr( $p_file_new_location, 0 ) == '/' ) {
        return false;
    }
    $t_file_location = str_replace( '/', DIRECTORY_SEPARATOR, $p_file_location );
    $t_file_new_location = str_replace( '/', DIRECTORY_SEPARATOR, $p_file_new_location );
    if ( !file_exists( $t_file_location ) ) {
        return false;
    }
    $t_directories = explode( '/', $p_file_new_location );
    if ( count( $t_directories ) > 1 ) {
        $t_dir = '';
        for( $i=0; $i<count( $t_directories ) - 1; $i++ ) {
            $t_dir .= $t_directories[$i] . DIRECTORY_SEPARATOR;
            if ( file_exists( $t_dir ) && !is_dir( $t_dir ) ) {
                return false;
            }
            if ( !file_exists( $t_dir ) ) {
                mkdir( $t_dir );
            }
        }
    }
    if ( file_exists( $t_file_new_location ) ) {
        unlink( $t_file_new_location );
    }
    return @rename( $t_file_location, $t_file_new_location );
}

/** 
 * This function is used to copy a file without any backup.
 * This function returns true if success else false.
 *
 * @param string $p_file_location The file location relative
 * to the Mantis Root directory.
 * @param string $p_file_new_location The new file location
 * relative to the Mantis Root directory.
 * @return bool
 */
function pm_file_copy( $p_file_location, $p_file_new_location ) {
    if ( substr( $p_file_location, 0 ) == '/' || substr( $p_file_new_location, 0 ) == '/' ) {
        return false;
    }
    $t_file_location = str_replace( '/', DIRECTORY_SEPARATOR, $p_file_location );
    $t_file_new_location = str_replace( '/', DIRECTORY_SEPARATOR, $p_file_new_location );
    if ( !file_exists( $t_file_location ) ) {
        return false;
    }
    $t_directories = explode( '/', $p_file_new_location );
    if ( count( $t_directories ) > 1 ) {
        $t_dir = '';
        for( $i=0; $i<count( $t_directories ) - 1; $i++ ) {
            $t_dir .= $t_directories[$i] . DIRECTORY_SEPARATOR;
            if ( file_exists( $t_dir ) && !is_dir( $t_dir ) ) {
                return false;
            }
            if ( !file_exists( $t_dir ) ) {
                mkdir( $t_dir );
            }
        }
    }
    if ( file_exists( $t_file_new_location ) ) {
        unlink( $t_file_new_location );
    }
    return @copy( $t_file_location, $t_file_new_location );
}

/** 
 * This function is used to delete a file/directory without any backup.
 * This function returns true if success else false.
 *
 * @param string $p_file_location The file/directory location relative
 * to the Mantis Root directory.
 * @return bool
 */
function pm_file_delete( $p_file_location ) {
    if ( substr( $p_file_location, 0 ) == '/' ) {
        return false;
    }
    $t_file_location = str_replace( '/', DIRECTORY_SEPARATOR, $p_file_location );
    if ( !is_file( $t_file_location ) && !is_dir( $t_file_location ) ) {
        return false;
    }
    if ( is_dir( $t_file_location ) ) {
        $t_dir = opendir( $t_file_location );
        while( ( $t_file = readdir( $t_dir ) ) !== false ) {
            if ( $t_file != '.' && $t_file != '..' ) {
                if ( !pm_file_delete( $p_file_location . '/' . $t_file ) ) {
                    return false;
                }
            }
        }
        closedir( $t_dir );
        return rmdir( $t_file_location );
    } else {
        return unlink( $t_file_location );
    }
}

?>
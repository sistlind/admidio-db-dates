<?php
/**
 * Plugin Name: Admidio DB Dates
 * Plugin URI:  https://github.com/sistlind/wp-min-user-avatars
 * Description: Read and show dates from an Admidio database
 * Version:     git
 * Author:      Stefan Lindner
 * ---------------------------------------------------------------------------//
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Forms. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Stefan Lindner
 * @version    git
 * @package    admidio_db_dates
 * @copyright  Copyright (c) 2015, Stefan Lindner
 * @link       
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

require_once(dirname(__FILE__).'/class_wp_admidio_db_dates.php');

$admidio_db_dates = new wp_admidio_db_dates();
/**
 * During uninstallation, remove the custom field from the users and delete the local avatars
 *
 * @since 1.0.0
 */
function admidio_db_dates_uninstall() {
/*	
	$admidio_db_dates = new admidio_db_dates;
	$users = get_users_of_blog();
	foreach ( $users as $user )
		$admidio_db_dates->avatar_delete( $user->user_id );
	delete_option( 'admidio_db_dates_caps');*/
}
register_uninstall_hook( __FILE__, 'admidio_db_dates_uninstall' );

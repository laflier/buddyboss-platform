<?php
/**
 * Members: Random member action handler
 *
 * @package BuddyBoss
 * @subpackage MembersActions
 * @since BuddyPress 3.0.0
 */

/**
 * Redirect to a random member page when visiting a ?random-member URL.
 *
 * @since BuddyPress 1.0.0
 */
function bp_core_get_random_member() {
	if ( ! isset( $_GET['random-member'] ) )
		return;

	$user = bp_core_get_users( array( 'type' => 'random', 'per_page' => 1 ) );
	bp_core_redirect( bp_core_get_user_domain( $user['users'][0]->id ) );
}
add_action( 'bp_actions', 'bp_core_get_random_member' );
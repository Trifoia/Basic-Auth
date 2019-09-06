<?php
/**
 * Plugin Name: JSON Basic Authentication
 * Description: Fork of the Basic Authentication handler for the JSON API
 * Author: WordPress API Team
 * Author URI: https://github.com/WP-API
 * Version: 0.3
 * Plugin URI: https://github.com/Trifoia/Basic-Auth
 */
function json_basic_auth_handler( $user ) {
	global $wp_json_basic_auth_error;
	$authenticating_users_key_prefix = "authenticating_users_cache_group";
	$authenticating_users_transient_expiration = 2;
	$allowed_uris = array(
		'/wp-json/basic-auth/v1/check-auth'
	);

	$wp_json_basic_auth_error = null;

	// Only use this authentication method on the correct paths
	if ( !in_array($_SERVER['REQUEST_URI'], $allowed_uris) ) {
		return $user;
	}

	// Don't authenticate twice
	if ( !empty( $user ) ) {
		return $user;
	}

	// Check that we're trying to authenticate
	if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
		return $user;
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	// Use transient storage to make sure the same user can't try to authenticate if they are already authenticating
	$transient_username_key = $authenticating_users_key_prefix . $username;

	if (false !== get_transient( $transient_username_key ) ) {
		$wp_json_basic_auth_error = new WP_Error( 'already_processing_user_auth', 'Authentication for this user is already being processed' );
		return null;
	}

	set_transient( $transient_username_key, true, $authenticating_users_transient_expiration );

	/**
	 * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
	 * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
	 * recursion and a stack overflow unless the current function is removed from the determine_current_user
	 * filter during authentication.
	 */
	remove_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

	$user = wp_authenticate( $username, $password );

	add_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

	if ( is_wp_error( $user ) ) {
		/**
		 * In case of an authentication error, delay the response for a couple of seconds (helping to prevent brute force
		 * password guessing attacks)
		 */
		sleep( $authenticating_users_transient_expiration );

		$wp_json_basic_auth_error = $user;
		return null;
	}

	$wp_json_basic_auth_error = true;
	delete_transient( $transient_username_key );

	return $user->ID;
}
add_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

function json_basic_auth_error( $error ) {
	// Passthrough other errors
	if ( ! empty( $error ) ) {
		return $error;
	}

	global $wp_json_basic_auth_error;

	return $wp_json_basic_auth_error;
}
add_filter( 'rest_authentication_errors', 'json_basic_auth_error' );

function return_completed_auth_json() {
	$successObj->code = "authentication_success";
	$successObj->message = "Authentication succeeded!";
	return $successObj;
}

add_action( 'rest_api_init', function () {
	// Very basic route that can be used to just check if a password is correct
	register_rest_route( 'basic-auth/v1', '/check-auth', array(
		'methods' => 'GET',
		'callback' => 'return_completed_auth_json'
	) );
} );

<?php
/**
 * Plugin Name: Addressix Login
 * Plugin URI: https://www.addressix.com
 * Plugin URL: https://www.addressix.com
 * Description: Addressix OAuth2 provider
 * Version: 1.0.2
 * Author: Meworla GmbH
 * Author URI: https://www.meworla.com
 * License: GPLv2 or later
 * Text Domain: addressixoauth
 * GitHub Plugin URI: https://github.com/addressix/wordpress-addressix-oauth
 * GitHub Branch:     master
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'AIXOAUTH2__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
require_once(AIXOAUTH2__PLUGIN_DIR . 'class.aixoauth.php' );

add_action( 'init', array( 'Aixoauth', 'init' ) );

add_option('addressixoauth',array('clientid'=>'', 'secret'=>''));

if (is_admin()) {
  include('setttings.php');
  AddressixOAuthSettings::init();
}

<?php
/*
Plugin Name: #BW Twitter Blocks
Version: 1.0
Description: Fetches tweets from Twitter user and stores in DB, with hash tags as custom fields.
Author: #BRITEWEB
Author URI: http://www.briteweb.com/
*/

@session_start(); // for twitter oauth

define( 'BW_TB_DIR', plugin_dir_path( __FILE__ ) );
define( 'BW_TB_URL', plugin_dir_url( __FILE__ ) );
define( 'BW_TB_SETTINGS_OPTION', 'bw_twitter_blocks' );
define( 'BW_TB_TITLE', "<h2><img src=\"" . plugins_url('/images/bw-page-logo.png', __FILE__) . "\" alt=\"Briteweb\" /></h2>" );
define( 'BW_TB_BEFORE', '<div class="wrap">' );
define( 'BW_TB_AFTER', '</div>' );
define( 'BW_TB_ADMIN_URL', admin_url( 'admin.php?page=bw-twitter-blocks' ) );
define( 'BW_TB_SCHEDULE', 'bw_twitter_event' ); // Name of custom scheduled event for fetching tweets

define( 'BW_TB_OAUTH_OPTION', 'bw_twitter_blocks_oauth' );
define( 'BW_TB_OAUTH_CONSUMER_KEY', 'OGOKQwNu4VQXC0A8HrJeLw' );
define( 'BW_TB_OAUTH_CONSUMER_SECRET', 'z89j0jXUWchBhnnpb95e3EM4FhcvOsqJ17UvJZCqm3k' );
define( 'BW_TB_OAUTH_CALLBACK', admin_url( 'admin.php?page=bw-twitter-blocks&action=oauth_callback' ) );

global $options, $oauth;
$options = get_option( BW_TB_SETTINGS_OPTION ); // Fetch saved plugin options
$oauth = get_option( BW_TB_OAUTH_OPTION ); // Fetch Twitter OAuth keys

require_once( BW_TB_DIR . 'bw_menu.php' ); // Add BW top-level menu
require_once( BW_TB_DIR . '/twitteroauth/twitteroauth.php' ); // Twitter OAuth library
require_once( BW_TB_DIR . 'bw_admin.php' ); // Admin pages
require_once( BW_TB_DIR . 'bw_functions.php' ); // Plugin functionality

add_action( 'setup_theme', 'bw_tb_widget' );
function bw_tb_widget() {
	require_once( BW_TB_DIR . 'bw_widget.php' ); // Widget
}

add_action( 'init', 'bw_twitter_blocks_cpt' ); // Create custom post types
add_action( BW_TB_SCHEDULE, 'bw_fetch_tweets' ); // Fetch new tweets when called by WP event scheduler
add_action( 'add_meta_boxes', 'bw_twitter_blocks_add_custom_box' ); // Create custom field box for twitter block CPT
add_action( 'save_post', 'bw_twitter_blocks_save_postdata' ); // Handle saving custom fields for twitter block CPT
add_action( 'admin_menu', 'bw_twitter_blocks_admin_menu'); // Add admin settings page for plugin
add_filter( 'cron_schedules', 'bw_add_cron' );  // Add event scheduler intervals
register_activation_hook( __FILE__, 'bw_twitter_activation' );
register_deactivation_hook(__FILE__, 'bw_twitter_deactivation');

?>
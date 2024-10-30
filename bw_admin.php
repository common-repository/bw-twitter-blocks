<?php

/* ========| SCHEDULE TWITTER UPDATE |======== */

function bw_twitter_activation() {
	if ( !wp_next_scheduled( BW_TB_SCHEDULE ) ) wp_schedule_event( time(), 'hourly', BW_TB_SCHEDULE );
	
	$existing = get_option( BW_TB_SETTINGS_OPTION );
	if ( empty( $existing ) ) {
		$new_fields = array( 'sched' => 'hourly', 'convert_user' => 1, 'convert_hash' => 1, 'convert_other' => 1, 'long_links' => array( 'ow.ly' ) );
		update_option( BW_TB_SETTINGS_OPTION, $new_fields );
	}
	
}


function bw_twitter_deactivation() {
	wp_clear_scheduled_hook( BW_TB_SCHEDULE );
	bw_twitter_oauth_unlink( false );
}

function bw_add_cron( $schedules )
{
	// Adds once weekly to the existing schedules.
	$schedules = array(
		'15min' => array( 'interval' => 900, 'display' => __('Every 15 Minutes') ),
		'30min' => array( 'interval' => 1800, 'display' => __('Every 30 Minutes') )
	);
	return $schedules;
}

/* ========| CREATE CUSTOM POST TYPES |======== */


function bw_twitter_blocks_cpt() {

	// CPT for display of blocks of tweets
	register_post_type('twitterblocks', array(	'label' => 'Twitter Post Blocks','description' => '','public' => false,'show_ui' => true,'show_in_menu' => true,'capability_type' => 'post','hierarchical' => true,'rewrite' => array('slug' => ''),'query_var' => true,'has_archive' => false,'supports' => array('title','editor','excerpt','custom-fields','thumbnail','author','page-attributes'),'labels' => array (
	  'name' => 'Twitter Post Blocks',
	  'singular_name' => 'Twitter Post Block',
	  'menu_name' => 'Twitter Post Blocks',
	  'add_new' => 'Add Twitter Post Block',
	  'add_new_item' => 'Add New Twitter Post Block',
	  'edit' => 'Edit',
	  'edit_item' => 'Edit Twitter Post Block',
	  'new_item' => 'New Twitter Post Block',
	  'view' => 'View Twitter Post Block',
	  'view_item' => 'View Twitter Post Block',
	  'search_items' => 'Search Twitter Post Blocks',
	  'not_found' => 'No Twitter Post Blocks Found',
	  'not_found_in_trash' => 'No Twitter Post Blocks Found in Trash',
	  'parent' => 'Parent Twitter Post Block'
	) ) );
	
	// CPT for 'caching' tweets
	register_post_type('twittercache', array(	'label' => 'Twitter Posts','description' => '','public' => true,'show_ui' => true,'show_in_menu' => true,'capability_type' => 'post','hierarchical' => false,'rewrite' => array('slug' => ''),'query_var' => true,'has_archive' => true,'supports' => array('title','editor','excerpt','custom-fields','page-attributes'),'labels' => array (
	  'name' => 'Twitter Posts',
	  'singular_name' => 'Twitter Post',
	  'menu_name' => 'Twitter Posts',
	  'add_new' => 'Add Twitter Post',
	  'add_new_item' => 'Add New Twitter Post',
	  'edit' => 'Edit',
	  'edit_item' => 'Edit Twitter Post',
	  'new_item' => 'New Twitter Post',
	  'view' => 'View Twitter Post',
	  'view_item' => 'View Twitter Post',
	  'search_items' => 'Search Twitter Post',
	  'not_found' => 'No Twitter Post Found',
	  'not_found_in_trash' => 'No Twitter Post Found in Trash',
	  'parent' => 'Parent Twitter Post'
	) ) );

}

/* ========| CREATE CUSTOM META BOX |======== */

/* Adds a box to the main column on the Post and Page edit screens */
function bw_twitter_blocks_add_custom_box() {
    add_meta_box( 'bw_twitter_blocks_section', 'Twitter Block Options', 'bw_twitter_blocks_meta_box', 'twitterblocks', 'side' );
}

/* Prints the box content */
function bw_twitter_blocks_meta_box() {

	global $post;

  	// Use nonce for verification
  	wp_nonce_field( plugin_basename( __FILE__ ), 'bw_twitter_blocks_noncename' );
  	
  	$val = get_post_meta( $post->ID, 'bw_mention', TRUE );
  	
  	echo '<p><input type="checkbox" id="bw_mention" name="bw_mention" value="1" ' . checked( $val, 1, false ) . ' /> <label for="bw_mention"><strong>Display mentions</strong></label></p><br />';
  	
  	$val = get_post_meta( $post->ID, 'bw_hash', TRUE );
  	if ( empty( $val ) ) $val = "";
  	else $val = implode( ",", $val );

	echo '<p><strong>Hash tag to filter tweets (do not include "#")</strong></p>';
	echo '<p><input type="text" id="bw_hash" name="bw_hash" size="20" value="' . $val . '" /></p>';
	echo '<p>Enter multiple hash tags by seperating with a comma (eg. tim,is,awesome).</p><p>Note: if left blank, will fetch tweets that don\'t have hash tags from all other twitter blocks.</p>';
}

/* When the post is saved, saves our custom data */
function bw_twitter_blocks_save_postdata( $post_id ) {
  // verify if this is an auto save routine. 
  // If it is our form has not been submitted, so we dont want to do anything
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times

  if ( !wp_verify_nonce( $_POST['bw_twitter_blocks_noncename'], plugin_basename( __FILE__ ) ) ) return;
  
  // Check permissions
  if ( 'page' == $_POST['post_type'] ) {
    if ( !current_user_can( 'edit_page', $post_id ) ) return;
  } else {
    if ( !current_user_can( 'edit_post', $post_id ) ) return;
  }

  // OK, we're authenticated: we need to find and save the data
  
	$old = get_post_meta( $post_id, 'bw_hash', true );
	$new = explode( ',', trim( $_POST['bw_hash'], " #" ) );
	array_walk( $new, 'bw_trim_value' );
	bw_remove_empty( $new );
	if ( empty( $new ) ) $new = "";
		
	if  ( $new ) update_post_meta( $post_id, 'bw_hash', $new );
	elseif ( empty( $new ) ) delete_post_meta( $post_id, 'bw_hash' );
	
	$old = get_post_meta( $post_id, 'bw_mention', true );
	$new = $_POST['bw_mention'];
		
	if  ( $new ) update_post_meta( $post_id, 'bw_mention', $new );
	elseif ( empty( $new ) ) delete_post_meta( $post_id, 'bw_mention' );
}

/* ========| CREATE ADMIN PAGE |======== */

function bw_twitter_blocks_admin_menu() {
	add_submenu_page( 'bw_plugin_menu', '#BW Twitter Blocks', 'Twitter Blocks', 'manage_options', 'bw-twitter-blocks', 'bw_twitter_blocks_admin');
}

function bw_twitter_blocks_admin_css() {
	wp_enqueue_style( 'bw_admin_css', plugins_url( 'bw_admin.css', __FILE__ ) );
}
add_action( 'admin_print_styles', 'bw_twitter_blocks_admin_css' );

function bw_twitter_blocks_admin() {

	global $options, $oauth;
	
	if ( $_GET['action'] == "oauth_callback" ) {
		bw_twitter_oauth_verify();
		return;
	}
	
	if ( $_GET['action'] == "unlink" ) {
		bw_twitter_oauth_unlink();
		return;
	}
	
	if ( $_GET['action'] == "update" ) {
		bw_fetch_tweets();
		return;
	}
	
	if ( $_GET['action'] == "clear" ) {
		bw_clear_tweets();
		return;
	}
	
	if ( !empty( $_POST ) && $_POST['action'] == 'bw_twitter_blocks_save' && check_admin_referer( 'bw_twitter_blocks_save' ) ) {
	
		$convert_user = ( $_POST['convert_user'] == 1 ) ? 1 : 0;
		$convert_hash = ( $_POST['convert_hash'] == 1 ) ? 1 : 0;
		$convert_other = ( $_POST['convert_other'] == 1 ) ? 1 : 0;
	
		$new_fields = array( 'sched' => $_POST['twitter_sched'], 'convert_user' => $convert_user, 'convert_hash' => $convert_hash, 'convert_other' => $convert_other );
		
		if ( !empty( $_POST['twitter_long_links'] ) ) {
			$new = explode( ',', trim( $_POST['twitter_long_links'] ) );
			array_walk( $new, 'bw_trim_value' );
			$new_fields['long_links'] = $new;
		}
					
		update_option( BW_TB_SETTINGS_OPTION, $new_fields );
		
		wp_clear_scheduled_hook( BW_TB_SCHEDULE );
		wp_schedule_event( time(), $new_fields['sched'], BW_TB_SCHEDULE );
		
		$options = $new_fields;
	
	}
	
	$cron_sched = wp_get_schedules();
		
	?>
	<?php echo BW_TB_BEFORE . BW_TB_TITLE; ?>
	
	<div id="info"></div>
	
	<form method="post">
	
	<div id="bw-widgets-list">
				
		<table class="form-table">
		
			<?php if ( bw_twitter_oauth_verified() ) : ?>
			<tr valign="top">
				<th scope="row"><label for="twitter_user">Authorized Twitter User</label></th>
				<td><strong><?php echo $oauth['screen_name']; ?></strong> <a href="<?php echo BW_TB_ADMIN_URL; ?>&action=unlink">(Unlink Account)</a></td>
			</tr>
			<?php else : ?>
			<tr valign="top">
				<th scope="row"><label for="twitter_user">Connect Twitter Account</label></th>
				<td><a href="<?php echo bw_twitter_oauth_url(); ?>"><img src="<?php echo BW_TB_URL; ?>images/sign-in-with-twitter-d.png" /></a></td>
			</tr>
			<?php endif; ?>
		
			<tr valign="top">
				<th scope="row"><label for="twitter_sched">Twitter Update Frequency</label></th>
				<td><select name="twitter_sched" id="twitter_sched">
					<?php foreach ( $cron_sched as $key=>$row ) : ?>
					<option <?php selected( $options['sched'], $key ); ?> value='<?php echo $key; ?>'><?php echo $row['display']; ?></option>
					<?php endforeach; ?>
				</select></td>
			</tr>
			
			<?php 
	
			$options['convert_user'] = ( $options['convert_user'] !== 0 ) ? 1 : 0;
			$options['convert_hash'] = ( $options['convert_hash'] !== 0 ) ? 1 : 0;
			$options['convert_other'] = ( $options['convert_other'] !== 0 ) ? 1 : 0;
									
			?>
			
			<tr valign="top">
				<th scope="row"><label>Convert to links</label></th>
				<td>
					<input type="checkbox" name="convert_user" value="1" <?php checked( $options['convert_user'], 1 ); ?> /> <label>@user</label>&nbsp;&nbsp;
					<input type="checkbox" name="convert_hash" value="1" <?php checked( $options['convert_hash'], 1 ); ?> /> <label>#hash</label>&nbsp;&nbsp;
					<input type="checkbox" name="convert_other" value="1" <?php checked( $options['convert_other'], 1 ); ?> /> <label>other links</label>
				</td>
			</tr>
			
			<?php if ( !empty( $options['long_links'] ) ) $long_links = implode( ',', $options['long_links'] ); ?>
			
			<tr valign="top">
				<th scope="row"><label for="twitter_long_links">Unshorten Links</label></th>
				<td><input type="text" class="regular-text" name="twitter_long_links" id="twitter_long_links" value="<?php echo $long_links; ?>" /></td>
			</tr>
			
			<tr><td colspan="2"><p style="font-size:10px;width:520px;">Enter the short url services to be converted to the original long url. For performance reasons, please only enter the ones you absolutely need to be converted. Enter just the service domain, seperated by commas (eg. ow.ly,bit.ly,goo.gl)</p></td></p>
			
			<tr valign="top">
				<th scope="row"><label>Manual Update Twitter Blocks</label></th>
				<td><a href="<?php echo BW_TB_ADMIN_URL; ?>&action=update" class="button">Update Now!</a></td>
			</tr>
			
			<tr valign="top">
				<th scope="row"><label>Clear Stored Tweets</label></th>
				<td><a href="<?php echo BW_TB_ADMIN_URL; ?>&action=clear" class="button">Clear Tweets Now</a></td>
			</tr>
			
		</table>
		
	<?php echo BW_TB_AFTER; ?>
	
	<p class="submit"><input type="submit" class="button-primary" value="Save Changes" /></p>
	<input type="hidden" name="action" value="bw_twitter_blocks_save" />
	<?php wp_nonce_field( "bw_twitter_blocks_save" ); ?>
	
	</form>
	
	<?php
	
}

?>
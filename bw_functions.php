<?php

/* ========| TWITTER UPDATE |======== */

// Clear all posts in twittercache CPT
function bw_clear_tweets() {
	$pages = get_posts( array( 'post_type' => 'twittercache', 'posts_per_page' => -1 ) );
	foreach ( $pages as $page ) wp_delete_post( $page->ID, TRUE );	
	
	echo BW_TB_BEFORE . BW_TB_TITLE . '<div><p><strong>Twitter cache has been cleared.</strong></p><p><a href="' . BW_TB_ADMIN_URL . '" class="button-primary">Continue</a></p></div>' . BW_TB_AFTER;
			
}

// Fetch tweets and store in twittercache CPT
function bw_fetch_tweets( $num = 120 ) {

	global $options, $oauth;
	$twitter_username = $oauth['screen_name'];
	
	if ( $oauth['oauth_token'] ) {
		
		$api = bw_twitter_oauth_api();
		$all_tags = array();
		$allid = array();
		
		$posts = get_posts( array( 'post_type' => 'twittercache', 'posts_per_page' => -1 ) );
		foreach ( $posts as $post ) 
			$allid[] = get_post_meta( $post->ID, 'twitter_id', true );
					
		$json1 = $api->get( 'statuses/user_timeline', array( 'screen_name' => $twitter_username, 'count' => $num, 'include_entities' => true ) );
		$json2 = $api->get( 'statuses/mentions', array( 'include_entities' => true ) );
		$json = array_merge( $json1, $json2 );
								
		$alltweets = array();
		foreach ( $json as $key=>$row ) {
		
			$hashtags = array();
						
			if ( !empty( $row->entities->hashtags ) )
				foreach ( $row->entities->hashtags as $tag ) 
					$hashtags[] = $tag->text;
			
			$mentions = array();
			if ( !empty( $row->entities->user_mentions ) )
				foreach ( $row->entities->user_mentions as $tag ) 
					$mentions[] = $tag->screen_name;
				
			$alltweets[$key] = array( 'text' => $row->text, 'id' => $row->id, 'hashtags' => $hashtags, 'mentions' => $mentions, 'date' => $row->created_at );
			
			if ( in_arrayi( $twitter_username, $mentions ) ) $alltweets[$key]['self_mention'] = 1;
		}
		
		echo BW_TB_BEFORE . BW_TB_TITLE . '<div class="msg-hide" style="display:none;"><p><strong>Twitter cache has been updated.</strong></p><p><a href="' . BW_TB_ADMIN_URL . '" class="button-primary">Continue</a></p><br /><br /></div>';
					
		foreach ( $alltweets as $row ) {
					
			if ( is_array( $allid ) && !in_array( $row['id'], $allid ) ) {
				
				$allid[] = $row['id'];
				$time = strtotime( $row['date'] );
				$date = date( 'Y-m-d H:i:s', $time );
				$content = bw_twitterit( $row['text'] );
				echo "<p>$content</p>";
							
				$newpost = array( 	'post_title' 	=> $row['text'],
									'post_content' 	=> $content,
									'post_status' 	=> 'publish',
									'post_parent' 	=> 0,
									'post_type' 	=> 'twittercache',
									'post_date' 	=> $date,
									'post_name'		=> $row['id']
								);
				//pre_dump($newpost);
				$newpostid = wp_insert_post( $newpost );
				
				if ( $newpostid ) {
					add_post_meta( $newpostid, 'twitter_parsed', $content );
					add_post_meta( $newpostid, 'twitter_raw', $row['text'] );
					add_post_meta( $newpostid, 'twitter_id', $row['id'] );
					if ( $row['self_mention'] == 1 ) add_post_meta( $newpostid, 'twitter_self_mention', 1 );
					
					$alltags = "";
					if ( !empty( $row['hashtags'] ) ) {
						foreach ( $row['hashtags'] as $tag ) 
							add_post_meta( $newpostid, 'twitter_hashtags', $tag );
						$alltags = implode( ",", $row['hashtags'] );
					}
					add_post_meta( $newpostid, 'twitter_hashtags_all', $alltags );
					
					$allmentions = "";
					if ( !empty( $row['mentions'] ) ) {
						foreach ( $row['mentions'] as $mention ) 
							add_post_meta( $newpostid, 'twitter_mentions', $mention );
						$allmentions = implode( ",", $row['mentions'] );
					}
					add_post_meta( $newpostid, 'twitter_mentions_all', $allmentions );
					
				}
			}
				
		}
		
		echo '<div class="msg-hide" style="display:none;"><br /><br /><p><strong>Twitter cache has been updated.</strong></p><p><a href="' . BW_TB_ADMIN_URL . '" class="button-primary">Continue</a></p></div>' . BW_TB_AFTER;
		
		echo '<script>jQuery(".msg-hide").show();</script>';
	
	}
}

function bw_twitterit( $text, $id = 0 ) {  

	global $options;
	$options['convert_user'] = ( $options['convert_user'] !== 0 ) ? 1 : 0;
	$options['convert_hash'] = ( $options['convert_hash'] !== 0 ) ? 1 : 0;
	$options['convert_other'] = ( $options['convert_other'] !== 0 ) ? 1 : 0;

	if ( $options['convert_other'] == 1 ) {

		// Process regular link URLs (using function callback to convert to html links).
		$text = preg_replace_callback("/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \,\"\n\r\t<]*)/is", "bw_parse_longurl", $text);
		$text = preg_replace_callback("/(^|[\n ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r<]*)/is", "bw_parse_longurl", $text);
		
		// Process email addresses (convert to mailto links).
		$text = preg_replace("/(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i", "$1<a href=\"mailto:$2@$3\" rel='email'>$2@$3</a>", $text);
	}
	
	if ( $options['convert_user'] == 1 ) // Convert @users to links
		$text = preg_replace("/@(\w+)/", '<a href="http://www.twitter.com/#!/$1" data-url="http://search.twitter.com/search?q=+from%3A$1" rel="user"  class="link-user" target="_blank">@$1</a>', $text);
	
	if ( $options['convert_hash'] == 1 ) // Convert #hash tags to links
		$text = preg_replace("/\#(\w+)/", '<a href="http://search.twitter.com/search?q=%23$1" rel="hash" class="link-hash" target="_blank">#$1</a>', $text);
				
	return $text;
}


// Convert URLs to html links.
function bw_parse_longurl( $m ) {

	global $options;
	$url = $m[3];

	// Convert ow.ly short-urls to original.
	$longurl = false;
	if ( !empty( $options['long_links'] ) ) foreach ( $options['long_links'] as $link ) {
		if ( strpos( $url, $link ) !== false ) $longurl = true;
	}
	
	if ( $longurl ) $url = bw_get_longurl( $url );
	
	// Check if video url. If yes, add data-type attribute to link and set data-url attribute as video embed URL for javascript function to use.
	$video = bw_get_video_url( $url );
	if ( $video ) {
		$url = $video;
		$extra = ' data-type="video" class="link-video"';
	}
	
	$image = bw_get_owly_image_url( $url );
	if ( $image ) {
		$url = $image;
		$extra = ' data-type="image" class="link-image"';
	}
	
	return $m[1] . $m[2] . '<a href="' . $m[3] . '" data-url="' . $url . '"' . $extra . ' rel="link">' . $m[3] . '</a>';
}

// Check for ow.ly image. If yes, return original image src.
function bw_get_owly_image_url( $url ) {

	if ( strpos( $url, 'ow.ly' ) !== false ) {
		$parts = parse_url( $url );
		return "http://static.ow.ly/photos/normal/" . basename($parts['path']) . ".jpg";
	} else {
		return false;
	}

}

// Check for video url. If yes, return embed url.
function bw_get_video_url( $url ) {

	if ( strpos( $url, 'youtube' ) !== false ) { // Check for youtube links
		$parts = parse_url( $url );
		parse_str( $parts['query'], $q ); // extract video ID
		return "http://www.youtube.com/embed/" . $q['v']; // create youtube embed url
	} elseif ( strpos( $url, 'vimeo' ) !== false ) { // Check for vimeo links
		// DO THIS!!
	} else { // not video link
		return false;
	}	

}

// Use longurl.org to fetch original URL
function bw_get_longurl( $shorturl ) {  

	//$json = json_decode( file_get_contents( "http://api.longurl.org/v2/expand?format=json&url=$shorturl" ), true );
	//return $json['long-url'];
	
	$json = json_decode( file_get_contents( "http://www.longurlplease.com/api/v1.1?q=$shorturl" ), true );
	if ( $json[$shorturl] != NULL && !empty( $json[$shorturl] ) ) return $json[$shorturl];
	return $shorturl;
	
}

/* ========| DISPLAY TWITTER POSTS |======== */

function bw_twitter_blocks_fetch( $args = false ) {

	$defaults = array (
 		'orderby' 	=> 'menu_order',
 		'order'		=> 'ASC',
 		'blocks'	=> -1,
 		'tweets_per_block'	=> 6,
 		'single' 	=> 0 // pass a post ID to fetch a specific block
	);
	
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	$numblocks = $blocks;
	
	$blocks = array();
	if ( $single ) $blocks[] = get_post( $single );
	else $blocks = get_posts( array( 'post_type' => 'twitterblocks', 'posts_per_page' => $numblocks, 'post_parent' => 0, 'orderby' => $orderby, 'order' => $order ) );
		
	$allhash = array();
	$allblocks = get_posts( array( 'post_type' => 'twitterblocks', 'posts_per_page' => -1, 'post_parent' => 0 ) );
	if ( !empty( $blocks ) ) foreach ( $allblocks as $block ) {
		$block->hash = get_post_meta( $block->ID, 'bw_hash', true );
		if ( !empty( $block->hash ) ) $allhash = array_merge( $allhash, $block->hash );
	}
	array_unique( $allhash );
	bw_remove_empty( $allhash );
		
	if ( !empty( $blocks ) ) {
		
		foreach ( $blocks as $block ) { 
					
			$mention = get_post_meta( $block->ID, 'bw_mention', true );
			$hash = get_post_meta( $block->ID, 'bw_hash', true );
			
			bw_remove_empty( $block->hash );
			
			
			if ( $mention == 1 )
				$meta_query = array( array( 'key' => 'twitter_self_mention', 'value' => 1 ) );
			elseif ( !empty ( $hash ) )
				$meta_query = array( array( 'key' => 'twitter_hashtags', 'value' => $hash, 'compare' => 'IN' ) );
			else
				$meta_query = array( array( 'key' => 'twitter_hashtags_all', 'value' => $allhash, 'compare' => 'NOT IN' ) );
														
			$block->tweets = get_posts( array( 'post_type' => 'twittercache', 'posts_per_page' => $tweets_per_block, 'orderby' => 'date', 'order' => 'DESC', 'meta_query' => $meta_query ) );
						
		}
		
		if ( $single ) return $blocks[0];
		else return $blocks;
		
	} else {
		return false;
	}

}

/* ========| TWITTER AUTH |======== */

function bw_twitter_oauth_api() {

	global $oauth;

	$api = new TwitterOAuth( BW_TB_OAUTH_CONSUMER_KEY, BW_TB_OAUTH_CONSUMER_SECRET, $oauth['oauth_token'], $oauth['oauth_token_secret'] );
	return $api;
}

function bw_twitter_oauth_verify() {

	global $oauth;
	
	$api = new TwitterOAuth( BW_TB_OAUTH_CONSUMER_KEY, BW_TB_OAUTH_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret'] );
	$token = $api->getAccessToken( $_GET['oauth_verifier'] );
	
	
	if ( $token && $token['oauth_token'] && $token['oauth_token_secret'] ) {
		update_option( BW_TB_OAUTH_OPTION, $token );
		$oauth = $token;
		
		echo BW_TB_BEFORE . BW_TB_TITLE . '<p><strong>' . $token['screen_name'] . '</strong> has been authorized.</p><p><a href="' . BW_TB_ADMIN_URL . '" class="button-primary">Continue</a></p>' . BW_TB_AFTER;
	}
	
}

function bw_twitter_oauth_verified() {
	global $oauth;
	return ( $oauth['oauth_token'] ) ? true : false;
}

function bw_twitter_oauth_unlink( $display = true ) {

	global $oauth;

	update_option( BW_TB_OAUTH_OPTION, '' );
	delete_option( BW_TB_OAUTH_OPTION );
	
	if ( $display ) echo BW_TB_BEFORE . BW_TB_TITLE . '<p><strong>' . $oauth['screen_name'] . '</strong> has been deauthorized.</p><p><a href="' . BW_TB_ADMIN_URL . '" class="button-primary">Continue</a></p>' . BW_TB_AFTER;
	
	unset( $oauth );
}

function bw_twitter_oauth_url() {
	$api = new TwitterOAuth( BW_TB_OAUTH_CONSUMER_KEY, BW_TB_OAUTH_CONSUMER_SECRET );
	$request_token = $api->getRequestToken( BW_TB_OAUTH_CALLBACK );
	
	$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
	$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
	
	return $api->getAuthorizeURL( $token );
}

/* ========| UTILITY FUNCTIONS |======== */

if ( !function_exists( 'bw_trim_value' ) ) {
function bw_trim_value( &$value ) { 
    $value = trim( $value ); 
}
}

if ( !function_exists( 'bw_remove_empty' ) ) {
function bw_remove_empty( &$arr ) {
	if ( !empty( $arr ) ) foreach ( $arr as $key=>$val ) {
		if ( empty( $val ) ) unset( $arr[$key] );
	}
}
}

if ( !function_exists( 'data_store' ) ) {
function data_store( $params = null ) {
    static $data;
    if ( $params ) $data = $params;
    return $data;
}
}

if ( !function_exists( 'in_arrayi' ) ) {
function in_arrayi($needle, $haystack) {
    return in_array(strtolower($needle), array_map('strtolower', $haystack));
}
}


?>
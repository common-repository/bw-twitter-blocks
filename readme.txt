=== #BW Twitter Blocks ===
Contributors: briteweb
Donate link: http://briteweb.com/
Tags: twitter,tweet,post,social
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: trunk

\#BW Twitter Blocks fetches tweets from the authenticated user and displays them in blocks based on hash tags and mentions.

== Description ==

\#BW Twitter Blocks fetches tweets using the Twitter API, stores them as posts and provides functionality to display them in blocks based on hash tags and mentions.

Two custom post types are created:

1. **Twitter Post Blocks (twitterblocks):** To create the blocks of posts for display. Include custom fields for displaying based on either mentions of the authenticated user or hash tags.
2. **Twitter Posts (twittercache):** To store fetched tweets as a cache. Uses custom fields to store hash tags and mentions (for filtering on display), twitter ID and raw (un-linkified) tweet text. 

Tweets are fetched from the Twitter API. Due to limitations with the API, only the most recent couple hundred tweets can be fetched, so this plugin stores tweets to ensure older tweets can still be accessed after they disappear from the Twitter API. The plugin fetches tweets from the authenticated user's account as well as any tweets in which they are mentioned.

Also includes a widget to display individual blocks of tweets in the sidebar (or other widgetized area).

== Installation ==

1. Upload bw-twitter-blocks folder to your /wp-content/plugins/ directory
2. Activate the plugin in Wordpress admin
3. Setup plugin options under #BW Options -> Twitter Blocks  
	3a. **Connect Twitter Account:** Connect to your Twitter account by clicking the "Sign in with Twitter" button. You will be taken to twitter.com to sign in and authorize the plugin to access your tweets and then be redirected back to Wordpress.  
	3b. **Twitter Update Frequency:** The plugin will automatically fetch new tweets on a regular schedule, set here.  
	3c. **Convert to Links:** Selected types of links in tweets will be converted to HTML links, with some additional data tags for use by custom scripts.  
	3d. **Unshorten Links:** The plugin will convert shortened links to their original full URL. The conversion significantly affects performance so please specify only the services you absolutely need to convert. In practice most links do not need to be converted.   
	3e. **Manual Update Twitter Blocks:** Click "Update Now!" to manually fetch new tweets. You will need to click this when setting up the plugin, after connecting your twitter account.  
	3f. **Clear Stored Tweets:** Delete all cached twitter posts.   
4. After connecting your Twitter account for the first time, you must manually fetch new tweets (see 3e above).
5. Add bw_twitter_blocks_fetch() to template file to display tweet blocks.

How to display tweet blocks:

Use `<?php $blocks = bw_twitter_blocks_fetch( $args ); ?>` in a template file.

`<?php $default_args = array(
	'orderby' => 'menu_order',	// orderby for blocks of tweets
 	'order'	=> 'ASC',			// order for blocks
 	'blocks' => -1,				// how many blocks to display (-1 for all)
 	'tweets_per_block' => 6, 	// how many tweets to display per block,
 	'single' => 0 				// pass a post ID to fetch a single specific block (returns a single post object)
); ?>`

Return value is an array of objects from [get_posts()](http://codex.wordpress.org/Template_Tags/get_posts "Wordpress get_posts documentation") for the blocks. The 'tweets' key for each object holds an array from get_posts for the tweets within that block. Tweets within each block are ordered by original tweet date. 

== Frequently Asked Questions ==

= How do I display blocks of tweets in my template file? =

Example usage:

`<?php $blocks = bw_twitter_blocks_fetch( $args );
foreach ( $blocks as $block ) {
	$title = $block->post_title;
	$description = $block->post_content;
	
	foreach ( $block->tweets as $tweet ) {
		$tweet_content = $tweet->post_content;
		$raw_tweet = get_post_meta( $tweet->ID, 'twitter_raw', TRUE );
	}
} ?>`

= How do I fetch a single block of tweets? =

Setting the 'single' argument for bw_twitter_blocks_fetch() will return the block of tweets with that ID. For example:

`<?php $blocks = bw_twitter_blocks_fetch( array( 'single' => 22 ) );
$title = $block->post_title;
foreach ($block->tweets as $tweet ) {
	// See previous question
}
?> `

== Screenshots ==

1. Plugin settings page

== To-do ==

* Shortcode to display block of tweets on a page

== Changelog ==

= 1.0 =
*  First public release
*  Something else happened

== Upgrade Notice ==

= 1.0 =
First public release

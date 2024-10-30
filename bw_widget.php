<?php
/*---------------------------------------------------------------------------------*/
/* Custom Sidebar Blocks widget */
/*---------------------------------------------------------------------------------*/

class BW_Widget_Twitter_Blocks extends WP_Widget {

	function BW_Widget_Twitter_Blocks() {
  	   	$widget_ops = array('description' => 'Displays block of tweets from Twitter Blocks plugin' );
       	parent::WP_Widget(false, $name = "#BW - Twitter Block", $widget_ops);    
   	}
   	
   	function widget($args, $instance) {  
	
		extract( $args );
		
		//$title = empty( $instance['title'] ) ? "Recent Posts" : $instance['title'];
		$num = empty( $instance['num'] ) ? 3 : $instance['num'];
		$block = $instance['block'];
		
		if ( $block ) :
		
		$block = bw_twitter_blocks_fetch( array( 'single' => $block, 'tweets_per_block' => $num ) );
		
		if ( $block ) :
		
		//pre_dump($block);
				
		?>
		
		<?php echo $before_widget; ?>
		
			<?php echo $before_title . $block->post_title . $after_title; ?>
			
			<ul>
				<?php foreach ( $block->tweets as $tweet ) : ?>
				<li><p><?php echo $tweet->post_content; ?></p></li>
				<?php endforeach; ?>		
			</ul>
				
		<?php echo $after_widget; ?>
		
		<?php endif;
		
		endif;
	}
                   		
   function update($new_instance, $old_instance) {                
       return $new_instance;
   }

   function form($instance) {
        
     	//$title = esc_attr($instance['title']);
     	$num = esc_attr($instance['num']);
     	$curblock = esc_attr($instance['block']);
     	
     	$blocks = get_posts( array( 'post_type' => 'twitterblocks', 'post_parent' => 0, 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
     	
     	?>


		<p>
		<label for="<?php echo $this->get_field_id('num'); ?>"><strong>How many tweets?</strong></label>
		<select class="widefat" id="<?php echo $this->get_field_id('num'); ?>" name="<?php echo $this->get_field_name('num'); ?>">
			<option value="0">Select number...</option>
			<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
			<option value="<?php echo $i; ?>" <?php selected( $num, $i ); ?>><?php echo $i; ?></option>
			<?php endfor; ?>
		</select>
		</p>
				
		<p>
		<label><strong>Block</strong></label>
		<select class="widefat" id="<?php echo $this->get_field_id('block'); ?>" name="<?php echo $this->get_field_name('block'); ?>">
			<option value="0">Select block to display...</option>
			<?php foreach ( $blocks as $block ) : ?>
			<option value="<?php echo $block->ID; ?>" <?php selected( $block->ID, $curblock ); ?>><?php echo $block->post_title; ?></option>
			<?php endforeach; ?>
		</select>
		</p>
		       
     <?php

       	   
          
     }
   
   

} 
register_widget('BW_Widget_Twitter_Blocks');

?>
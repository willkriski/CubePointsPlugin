<?php

/** Protect Page Module */

cp_module_register(__('Protect Page', 'cp') , 'protectpage' , '1.0', 'Will Kriski', 'http://imedia-ventures.com', 'http://imedia-ventures.com/plugin-development/' , __('This module gives you the ability to protect a page or post with deducting points.', 'cp'), 1);

if(cp_module_activated('protectpage')){

	/* Define the custom box */
	add_action('admin_init', 'cp_module_protectpage_add_custom_box', 1);

	/* Do something with the data entered */
	add_action('save_post', 'cp_module_protectpage_save_postdata');

	/* Adds a box to the main column on the Post and Page edit screens */
	function cp_module_protectpage_add_custom_box() {
		add_meta_box( 'cp_module_protectpage_set', 'CubePoints - Protect Page', 'cp_module_protectpage_box', 'post', 'normal', 'high' );
		add_meta_box( 'cp_module_protectpage_set', 'CubePoints - Protect Page', 'cp_module_protectpage_box', 'page', 'normal', 'high' );
	}

	/* Prints the box content */
	function cp_module_protectpage_box() {

		global $post;

		// Use nonce for verification
		wp_nonce_field( plugin_basename(__FILE__), 'cp_module_protectpage_nonce' );

		// The actual fields for data entry
		echo '<br /><input type="checkbox" id="cp_module_protectpage_enable" name="cp_module_protectpage_enable" value="1" size="25" '.((bool)(get_post_meta($post->ID , 'protect_page_enable', 1))?'checked="yes"':'').' /> ';
		echo '<label for="cp_module_protectpage_enable">' . __("Enable page protection on this page" , 'cp') . '</label> ';
		echo '<br /><br />';
		echo '<label for="cp_module_protectpage_points">' . __("Number of points required to view page" , 'cp') . ':</label> ';
		echo '<input type="text" id= "cp_module_protectpage_points" name="cp_module_protectpage_points" value="'.(int)get_post_meta($post->ID , 'protectpage_points', 1).'" size="25" /><br /><br />';
	}

	/* When the post is saved, saves our custom data */
	function cp_module_protectpage_save_postdata( $post_id ) {

		// get post id from the revision id
		if($parent_id = wp_is_post_revision($post_id)){
			$post_id = $parent_id;
		}

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times

		if ( !wp_verify_nonce( $_POST['cp_module_protectpage_nonce'], plugin_basename(__FILE__) )) {
			return $post_id;
		}

		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return $post_id;

	  
		// Check permissions
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
				return $post_id;
			} else {
				if ( !current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		// OK, we're authenticated: we need to find and save the data
		update_post_meta($post_id, 'protect_page_enable', (int)$_POST['cp_module_protectpage_enable']);
		update_post_meta($post_id, 'protectpage_points', (int)$_POST['cp_module_protectpage_points']);

	}

	add_filter( 'the_content', 'cp_module_protectpage_post_content' );
	
	function cp_module_protectpage_post_content($content){
		global $post;
		$protectpage_enabled = (bool) get_post_meta($post->ID,'protect_page_enable', 1);
		if(!$protectpage_enabled){
			return $content;
		}
		if(current_user_can( 'read_private_pages' )){
			return $content;
		}
		$points = get_post_meta($post->ID,'protectpage_points', 1);
		
		if(cp_getPoints(cp_currentUser()) >= $points ){
			return $content;
		}
		$c = 'You need to have ' . $points . ' points to view this page.';
		
		return $c;
		
	}

}
	
?>
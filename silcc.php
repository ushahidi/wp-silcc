<?php
/*
Plugin Name: SiLCC
Plugin URI: http://opensilcc.com/
Description: SiLCC auto-generates tags for the blogs. You need a <a href="http://opensilcc.com/get/">WordPress.com API key</a> to use it. You can review the tags it generates.
Version: 1.0.0
Author: Ivan Kavuma
Author URI: http://opensilcc.com/
*/

define('SILCC_VERSION', '1.0.0');

define('SILCC_API_KEY','IVANALPHA');
// If you hardcode a WP.com API key here, all key config screens will be hidden
if ( defined('SILCC_API_KEY') )
	$silcc_api_key = constant('SILCC_API_KEY');
else
{
    $silcc_api_key = '';

}
/*
 *
 */
function silcc_init() {
	global $silcc_api_key, $silcc_api_host, $silcc_api_port;

	if ( $silcc_api_key )
		$silcc_api_host = 'http://opensilcc.com/api/tag?key='.$silcc_api_key .'&';
	else
		$silcc_api_host = 'http://opensilcc.com/api/tag?key='.get_option('wordpress_api_key') . '&';

        $posts_get_offset = 0;
        $silcc_api_port = 80;

}
add_action('init', 'silcc_init');

/*
 * Get Posts 5 at a time and add tags to then
 */
function silcc_submit_posts()
{
    global $posts_get_offset;

   $arr = array('offset' => $posts_get_offset);
   $posts = get_posts($arr);
    $response = '';
   foreach($posts as $post) {
       silcc_submit_post($post->ID);
   }
   $posts_get_offset += 5;
}
/*
 * Helper to connect to the silcc API
 */
function silcc_http_get($text){
    global $silcc_api_host;
    $url = $silcc_api_host."&text=".$text;
    $returnData = file_get_contents($url, false);
     return $returnData;
}

/**
 * Get a blog and add tags from the silcc API
 */
function silcc_submit_post( $post_id ) {
        global $wpdb;
	$post_id = (int) $post_id;
        $blog = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID = '$post_id' ");
	if ( !$blog ) // it was deleted
		return;
	$query_string = ''.urlencode( stripslashes($blog->post_content) ) . '&';

        $response = silcc_http_get($query_string);
        $clean_response = str_replace('[','',$response);
        $clean_response = str_replace(']','',$clean_response);
        $clean_response = str_replace('\"','',$clean_response);
        $tags = explode(',',$clean_response);
        wp_set_post_terms($post_id, $tags);

}
add_action('admin_footer', 'silcc_submit_posts');
//add_action('new_posts', 'silcc_submit_posts');

?>

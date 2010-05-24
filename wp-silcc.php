<?php
/*
Plugin Name: WP-SiLCC
Plugin URI: http://swift.ushahidi.com/
Description: SiLCC auto-generates tags for blogs. You need an <a href="http://opensilcc.com/">OpenSiLCC API key</a> to use it. 
Version: 1.0
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
        silcc_init_options();

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

/*
 *
 */
function silcc_insert_option($name,$value)
{
    global $wpdb;
    $options = $wpdb->query("INSERT INTO $wpdb->options ( blog_id,  option_name,  option_value,  autoload )
                                    VALUES (0,'$name','$value','yes') ");

    return $options;
}
/*
 *
 */
function silcc_update_option($name,$value)
{
    global $wpdb;
    $options = $wpdb->query("UPDATE $wpdb->options  SET  option_value = '$value'
                            WHERE option_name = '$name'");
    return $options;
}
/*
 * 
 */
function silcc_init_options()
{
    global $wpdb,$silcc_api_key, $silcc_number_of_posts_taged , $silcc_number_of_tags_generated;

    $option_tags = get_option('silcc_number_of_tags_generated');
    if(!$option_tags)
    {  silcc_insert_option('silcc_number_of_tags_generated','0');
    }
    
    $option_posts = get_option('silcc_number_of_posts_taged');
    if(!$option_posts)
    {  silcc_insert_option('silcc_number_of_posts_taged','0');
    }
    
    $option_key = get_option('wordpress_api_key');
    if(!$option_key)
    {  silcc_insert_option('wordpress_api_key',$silcc_api_key);
    }
}


function silcc_update_options($num_tags)
{
    $silcc_number_of_posts_taged = (int)get_option('silcc_number_of_posts_taged');
    $silcc_number_of_posts_taged += 1;    
    $options = silcc_update_option('silcc_number_of_posts_taged',$silcc_number_of_posts_taged);

    $silcc_number_of_tags_generated = (int)get_option('silcc_number_of_tags_generated');
    $silcc_number_of_tags_generated += $num_tags;
    $options = silcc_update_option('silcc_number_of_tags_generated',$silcc_number_of_tags_generated);
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
        silcc_update_options(count($tags));
      
}
//add_action('admin_footer', 'silcc_submit_posts'); //This function can be called if one whats to tag everything.
add_action ( 'publish_post', 'silcc_submit_post');

function silcc_rightnow() {
      echo "<p class='first b b-tags'>SiLCC has tagged <strong>".get_option('silcc_number_of_posts_taged')."</strong> items with <strong>".get_option('silcc_number_of_tags_generated')."</strong> tags since it was installed.</p>\n";
}
add_action('rightnow_end', 'silcc_rightnow');

?>

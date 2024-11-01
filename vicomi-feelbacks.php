<?php
/*
Plugin Name: Vicomi Feelbacks
Plugin URI: http://vicomi.com/
Description: Feelbacks is a new voting engagement widget that allows users to express their feelings about your content
Author: Vicomi <support@vicomi.com>
Version: 3.00
Author URI: http://vicomi.com/
*/

require_once(dirname(__FILE__) . '/lib/vc-api.php');
define('VICOMI_FEELBACKS_V', '2.07');
include_once(dirname(__FILE__) . '/settings-page.php');



/* hook plugin installed */
register_activation_hook(__FILE__, 'vicomi_plugin_activate');
add_action('admin_init', 'vicomi_redirect');
add_action('admin_init','setup_sections');
add_action('admin_init', 'setup_fields');
 
function vicomi_plugin_activate() {
    add_option('vicomi_activation_redirect', true);
}
 
function vicomi_redirect() {
    if (get_option('vicomi_activation_redirect', false)) {
       delete_option('vicomi_activation_redirect');
		wp_redirect("admin.php?page=vicomi-feelbacks");
    }
}

// set unique id
if(!get_option('vicomi_feelbacks_uuid')) {
    update_option('vicomi_feelbacks_uuid', vcfGetGUID());
}

function vicomi_feelbacks_plugin_basename($file) {
    $file = dirname($file);

    // From WP2.5 wp-includes/plugin.php:plugin_basename()
    $file = str_replace('\\','/',$file); // sanitize for Win32 installs
    $file = preg_replace('|/+|','/', $file); // remove any duplicate slash
    $file = preg_replace('|^.*/' . PLUGINDIR . '/|','',$file); // get relative path from plugins dir

    if ( strstr($file, '/') === false ) {
        return $file;
    }

    $pieces = explode('/', $file);
    return !empty($pieces[count($pieces)-1]) ? $pieces[count($pieces)-1] : $pieces[count($pieces)-2];
}

if ( !defined('WP_CONTENT_URL') ) {
    define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
}
if ( !defined('PLUGINDIR') ) {
    define('PLUGINDIR', 'wp-content/plugins'); // back compat.
}

define('VICOMI_FEELBACKS_PLUGIN_URL', WP_CONTENT_URL . '/plugins/' . vicomi_feelbacks_plugin_basename(__FILE__));

// api ref
$vicomi_feelbacks_api = new VicomiAPI();

function vicomi_feelbacks_is_installed() {
    return get_option('vicomi_feelbacks_api_key');
}

/**************************************************
* register plugin state events
**************************************************/
function vicomi_feelbacks_activate() {
    if (!get_option('vicomi_feelbacks_api_key')) {
        $vicomi_feelbacks_api = new VicomiAPI();

        $site_name = get_option('blogname');

        if (!$site_name || $site_name == '') {
            $site_name = 'wordpress site';
        } else {
            $site_name = get_option('blogname');
        }

        $access_token = $vicomi_feelbacks_api->register_source
                            ($site_name, get_option('home'));
         if(!$access_token) {
            deactivate_plugins(basename(__FILE__)); // Deactivate ourself
            wp_die("Activation failed please try again later");
         } else {
            update_option('vicomi_feelbacks_api_key',$access_token);
            update_option('vicomi_feelbacks_replace', 'all');
            update_option('vicomi_feelbacks_active','1');
        }
    }

    $vicomi_feelbacks_api = new VicomiAPI();
    $vicomi_feelbacks_api->plugin_activate
    (get_option('vicomi_feelbacks_api_key'), 'feelbacks', get_option('vicomi_feelbacks_uuid'));
}

function vicomi_feelbacks_deactivate() {
    $vicomi_feelbacks_api = new VicomiAPI();
    $vicomi_feelbacks_api->plugin_deactivate(get_option('vicomi_feelbacks_api_key'), 'feelbacks', get_option('vicomi_feelbacks_uuid'));
}

function vicomi_feelbacks_uninstall() {
    $vicomi_feelbacks_api = new VicomiAPI();
    $vicomi_feelbacks_api->plugin_uninstall(get_option('vicomi_feelbacks_api_key'), 'feelbacks', get_option('vicomi_feelbacks_uuid'));
}

register_activation_hook( __FILE__, 'vicomi_feelbacks_activate' );
register_deactivation_hook( __FILE__, 'vicomi_feelbacks_deactivate' );
register_uninstall_hook( __FILE__, 'vicomi_feelbacks_uninstall' );

function vicomi_feelbacks_can_replace() {
    global $id, $post;

    if (get_option('vicomi_feelbacks_active') === '0'){ return false; }

    $replace = get_option('vicomi_feelbacks_replace');

    if ( is_feed() )                       { return false; }
    if ( 'draft' == $post->post_status )   { return false; }
    if ( !get_option('vicomi_feelbacks_api_key') ) { return false; }
    else if ( 'all' == $replace )          { return true; }
}

/**************************************************
* add vicomi to settings menu
**************************************************/
add_action( 'admin_menu', 'create_plugin_settings_page' );

function create_plugin_settings_page() {
    	// Add the menu item and page
    	$page_title = 'Vicomi Feelbacks';
    	$menu_title = 'Vicomi';
    	$capability = 'manage_options';
    	$slug = 'vicomi-feelbacks';
		$slug_cpt_setting = 'vicomi-feelbacks-settings';//'vicomi-feelbacks-cpts';
    	$callback = 'vicomi_feelbacks_manage' ;
    	$icon ='dashicons-admin-plugins'; //plugin_dir_url( __FILE__ ) .'icon.svg';
    	$position = 100;
    	add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
		add_submenu_page( $slug, 'Vicomi Dashbord', 'Dashbord',
			'manage_options', $slug);
		add_submenu_page( $slug, 'Vicomi Settings', 'Settings',
			'manage_options', $slug_cpt_setting,'plugin_settings_page');
    }

function vicomi_feelbacks_manage() {
    include_once(dirname(__FILE__) . '/manager.php');
}
	
	

/**************************************************
* add action links to plgins page
**************************************************/
function vicomi_feelbacks_plugin_action_links($links, $file) {
    $plugin_file = basename(__FILE__);
    if (basename($file) == $plugin_file) {
		$settings_links = array();
        if (!vicomi_feelbacks_is_installed()) {
			array_push($settings_links,'<a href="options-general.php?page=vicomi-feelbacks">Configure</a>');
		} else {
			array_push($settings_links,'<a href="admin.php?page=vicomi-feelbacks">Dashbord</a>');
			array_push($settings_links,'<a href="admin.php?page=vicomi-feelbacks-settings">Settings</a>');
        }

		foreach ($settings_links as $value){
			array_unshift($links,$value);
		}	
    }
    return $links;
}
add_filter('plugin_action_links', 'vicomi_feelbacks_plugin_action_links', 10, 2);

/**************************************************
* add feelbacks container and script to page
**************************************************/
/*
echo do_shortcode( '[vicomi_feelbacks]' );
when $with_content=true, will append Vicomi feedbacks at the end of the page content
$content - is "the_content" of the page
*/
function vicomi_feelbacks_shortcode($with_content,$content){
	$plugin_content = '<div id="vc-feelback-main" data-access-token="' . get_option('vicomi_feelbacks_api_key') . '" style="max-width:600px; margin:0 auto;"></div>';// .
	if($with_content){
		return $content . $plugin_content;
	}else{
		return $plugin_content;
	}
}

add_shortcode('vicomi_feelbacks', 'vicomi_feelbacks_shortcode'); 

/*
* check which check boxes are selected in our Vicomi settings page, check if to show our widget in the current page or not
* we check the vicomi_exclude_pages_id value\s as well (text input convert to array), and make sure the current page isn't in this array
*/
function vicomi_feelbacks_template($content) {
	 if (get_option('vicomi_checkboxes',false)) {
		 $vicomi_checkboxes_selected = get_option('vicomi_checkboxes',false);
	 }else{
		$vicomi_checkboxes_selected = array('page','post'); /* default values like we set in the settings page */ 
	 }
	 
	 	 
	 $exclude_this_page = false;
	 if (get_option('vicomi_exclude_pages_id',false)) {
		 $vicomi_exclude_pages_id_str = get_option('vicomi_exclude_pages_id',false);
		 $vicomi_exclude_pages_id = array();
		 $vicomi_exclude_pages_id = explode(',', $vicomi_exclude_pages_id_str);
		 if(in_array(get_the_ID(),$vicomi_exclude_pages_id)){
			  $exclude_this_page = true;
		 }
	 }
	
	$show_vicomoki_fellbacks = false;
	
	if ( !vicomi_feelbacks_is_installed() || !vicomi_feelbacks_can_replace() || in_array('none',$vicomi_checkboxes_selected)  ) {
			// don't do anything
			return $content;
	}else{
		if(in_array('front_page',$vicomi_checkboxes_selected) && (is_front_page() || is_home()) && (!$exclude_this_page) ){
			$show_vicomoki_fellbacks = true;
		}
		if(in_array('post',$vicomi_checkboxes_selected) && (is_single()) && (is_singular('post')) && (!$exclude_this_page)){
			$show_vicomoki_fellbacks = true;
		}
		if(in_array('page',$vicomi_checkboxes_selected) && (is_singular() && !is_single() && !(is_front_page() || is_home())) && (!$exclude_this_page) ){ 
			$show_vicomoki_fellbacks = true;
		}		
		if(in_array('archive',$vicomi_checkboxes_selected) && (is_archive()) && (!$exclude_this_page)){
			$show_vicomoki_fellbacks = true;
		}
		// if(in_array('product',$vicomi_checkboxes_selected) && (is_product()) && (!$exclude_this_page)){
		// 	$show_vicomoki_fellbacks = true;
		// }

		$curent_post_type = get_post_type();
		$basic_cpt_array = ['post','page','archive','attachment'];
		if(in_array($curent_post_type,$vicomi_checkboxes_selected) && !in_array($curent_post_type,$basic_cpt_array) && (!$exclude_this_page)){ 
			$show_vicomoki_fellbacks = true;
		}	
		 
		if($show_vicomoki_fellbacks == true){
			echo vicomi_feelbacks_shortcode(true,$content);
		}else{
			return $content;
		}
	}
}

add_action('the_content', 'vicomi_feelbacks_template');

/* Add Vicomi scripts */
function vicomi_enqueue_script() {   
	$current_user_token = get_option('vicomi_feelbacks_api_key');
	$vicomi_js = 'https://assets-prod.vicomi.com/vicomi.js?token='. $current_user_token .'&amp;';
    wp_enqueue_script( 'vicomi', $vicomi_js, '', '', TRUE);
}
add_action('wp_enqueue_scripts', 'vicomi_enqueue_script');


/**
 * JSON ENCODE for PHP < 5.2.0
 * Checks if json_encode is not available and defines json_encode
 * to use php_json_encode in its stead
 * Works on iteratable objects as well - stdClass is iteratable, so all WP objects are gonna be iteratable
 */
if(!function_exists('cf_json_encode')) {
    function cf_json_encode($data) {
// json_encode is sending an application/x-javascript header on Joyent servers
// for some unknown reason.
//         if(function_exists('json_encode')) { return json_encode($data); }
//         else { return cfjson_encode($data); }
        return cfjson_encode($data);
    }

    function cfjson_encode_string($str) {
        if(is_bool($str)) {
            return $str ? 'true' : 'false';
        }

        return str_replace(
            array(
                '"'
                , '/'
                , "\n"
                , "\r"
            )
            , array(
                '\"'
                , '\/'
                , '\n'
                , '\r'
            )
            , $str
        );
    }

    function cfjson_encode($arr) {
        $json_str = '';
        if (is_array($arr)) {
            $pure_array = true;
            $array_length = count($arr);
            for ( $i = 0; $i < $array_length ; $i++) {
                if (!isset($arr[$i])) {
                    $pure_array = false;
                    break;
                }
            }
            if ($pure_array) {
                $json_str = '[';
                $temp = array();
                for ($i=0; $i < $array_length; $i++) {
                    $temp[] = sprintf("%s", cfjson_encode($arr[$i]));
                }
                $json_str .= implode(',', $temp);
                $json_str .="]";
            }
            else {
                $json_str = '{';
                $temp = array();
                foreach ($arr as $key => $value) {
                    $temp[] = sprintf("\"%s\":%s", $key, cfjson_encode($value));
                }
                $json_str .= implode(',', $temp);
                $json_str .= '}';
            }
        }
        else if (is_object($arr)) {
            $json_str = '{';
            $temp = array();
            foreach ($arr as $k => $v) {
                $temp[] = '"'.$k.'":'.cfjson_encode($v);
            }
            $json_str .= implode(',', $temp);
            $json_str .= '}';
        }
        else if (is_string($arr)) {
            $json_str = '"'. cfjson_encode_string($arr) . '"';
        }
        else if (is_numeric($arr)) {
            $json_str = $arr;
        }
        else if (is_bool($arr)) {
            $json_str = $arr ? 'true' : 'false';
        }
        else {
            $json_str = '"'. cfjson_encode_string($arr) . '"';
        }
        return $json_str;
    }
}

// generate unique id
function vcfGetGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
        return $uuid;
    }
}

?>

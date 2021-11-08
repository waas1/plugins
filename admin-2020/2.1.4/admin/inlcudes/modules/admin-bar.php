<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_bar
{
    public function __construct($version, $path, $utilities)
    {
        $this->version = $version;
        $this->path = $path;
        $this->utils = $utilities;
		$this->front = false;
		$this->notices = '';
		$this->kill = false;
    }

    /**
     * Loads menu actions
     * @since 1.0
     */

    public function start()
    {
				
		///REGISTER THIS COMPONENT
		add_filter('admin2020_register_component', array($this,'register'));
		
		if(!$this->utils->enabled($this)){
			return;
		}
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$notification_center = $this->utils->get_option($optionname,'notification-center-disabled');
		
		if($this->utils->is_locked($optionname)){
			return;
		}
		
		
		add_action('wp_before_admin_bar_render', array($this,'should_we_run'));
		
        add_action('admin_head', [$this, 'rebuild_admin_bar']);
        add_action('admin_enqueue_scripts', [$this, 'add_styles'], 0);
		add_action('admin_enqueue_scripts', [$this, 'add_scripts']);
		add_filter('pre_get_posts', array($this, 'modify_query'));
		add_filter('admin_body_class', array($this, 'add_body_classes'));
		///MODIFY WP QUERY
		add_action('admin_enqueue_scripts', [$this, 'add_scripts']);
		///AJAX
		add_action('wp_ajax_a2020_master_search', array($this,'a2020_master_search'));
		add_action('wp_ajax_a2020_get_users_for_select', array($this,'a2020_get_users_for_select'));
		//CAPTURE NOTICES
		if($notification_center != 'true'){
			add_action('admin_notices', array($this,'start_capture_admin_notices'),-99);
			add_action('admin_notices', [$this, 'capture_admin_notices'],999);
		}
		
		
    }
	
	public function should_we_run(){
		
		global $wp_admin_bar;
		
		//echo '<pre>' . print_r($wp_admin_bar, true) . '</pre>';
		
		$admin_bar_class = apply_filters( 'wp_admin_bar_class', 'WP_Admin_Bar' );
		if ( !class_exists( $admin_bar_class ) ) {
			$this->kill = true;
			echo 'help';
			return;
		}
		
		$show_admin_bar = _get_admin_bar_pref();
		$show_admin_bar = apply_filters( 'show_admin_bar', $show_admin_bar );
		
		//echo $show_admin_bar;

		if(empty($wp_admin_bar->get_nodes()) || !$show_admin_bar ){
			$this->kill = true;
		}
		
	}
	
	/**
	* Modifies query to search in meta AND title
	* @since 2.9
	*/
	public function modify_query($q){
		
		if( $title = $q->get( '_a2020_meta_or_title' ) ) {
			
			add_filter( 'get_meta_sql', function( $sql ) use ( $title )
			{
				global $wpdb;
	
				// Only run once:
				static $nr = 0; 
				if( 0 != $nr++ ) return $sql;
	
				// Modify WHERE part:
				$sql['where'] = sprintf(
					" AND ( %s OR %s ) ",
					$wpdb->prepare( "{$wpdb->posts}.post_title like '%%%s%%'", $title),
					mb_substr( $sql['where'], 5, mb_strlen( $sql['where'] ) )
				);
				return $sql;
			});
		}
	}
	
	/**
	* Capture admin notices
	* @since 2.9
	*/
	
	public function start_capture_admin_notices(){
		ob_start();
	}
	/**
	* End Capture admin notices
	* @since 2.9 
	*/
	
	public function capture_admin_notices(){
		$this->notices = ob_get_clean();
	}
	
	/**
	* Output body classes
	* @since 1 
	*/
	
	public function add_body_classes($classes) {
		
		$bodyclass = " a2020_admin_bar";
		
		return $classes.$bodyclass;
	}
	
	
	/**
	 * Loads admin bar on front
	 * @since 1.0
	 */
	public function start_front(){
		
		
		
		if(!$this->utils->enabled($this)){
			return;
		}
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$admin_front = $this->utils->get_option($optionname,'load-front');
		$hide_admin = $this->utils->get_option($optionname,'hide-admin');
		
		if($this->utils->is_locked($optionname)){
			return;
		}
		
		if($hide_admin == 'true') {
			add_filter('show_admin_bar', 'is_blog_admin');
			return;
		}
		
		if($admin_front != 'true') {
			return;
		}
		
		$this->front = true;
		
		add_action('wp_head', [$this, 'rebuild_admin_bar']);
		add_action('wp_head',array($this,'add_body_styles'));
		add_action('wp_body_open', [$this, 'output_admin_front']);
		
		add_action('wp_enqueue_scripts', [$this, 'add_styles'], 99);
		add_action('wp_enqueue_scripts', [$this, 'add_scripts'], 0);
		
	}
	
	/**
	 * Register admin bar component
	 * @since 1.4
	 * @variable $components (array) array of registered admin 2020 components
	 */
	public function register($components){
		
		array_push($components,$this);
		return $components;
		
	}
	
	/**
	 * Returns component info for settings page
	 * @since 1.4
	 */
	public function component_info(){
		
		$data = array();
		$data['title'] = __('Admin Bar','admin2020');
		$data['option_name'] = 'admin2020_admin_bar';
		$data['description'] = __('Creates new admin bar, adds user off canvas menu and builds global search','admin2020');
		return $data;
		
	}
	
	
	/**
	 * Returns settings options for settings page
	 * @since 2.1
	 */
	public function get_settings_options(){
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		
		$settings = array();
		
		
		$temp = array();
		$temp['name'] = __('Admin Bar Disabled For','admin2020');
		$temp['description'] = __("UiPress admin bar module will be disabled for any users or roles you select",'admin2020');
		$temp['type'] = 'user-role-select';
		$temp['optionName'] = 'disabled-for'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Logo Light Mode','admin2020');
		$temp['description'] = __("Sets the logo for the admin bar in light mode.",'admin2020');
		$temp['type'] = 'image';
		$temp['optionName'] = 'light-logo'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Logo Dark Mode','admin2020');
		$temp['description'] = __("Optional dark mode logo for admin bar.",'admin2020');
		$temp['type'] = 'image';
		$temp['optionName'] = 'dark-logo'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Background color (light mode)','admin2020');
		$temp['description'] = __("Sets admin bar background color in light mode.",'admin2020');
		$temp['type'] = 'color';
		$temp['optionName'] = 'light-background'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$temp['default'] = '#fff';
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Background color (dark mode)','admin2020');
		$temp['description'] = __("Sets admin bar background color in dark mode.",'admin2020');
		$temp['type'] = 'color';
		$temp['optionName'] = 'dark-background'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$temp['default'] = '#222';
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Hide admin bar links (left side)','admin2020');
		$temp['description'] = __("Disables legacy links on left side of admin bar for all users. Also hides the user preference.",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'legacy-admin'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Disable Search','admin2020');
		$temp['description'] = __("Disables search icon and global search function from admin bar.",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'search-enabled'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Disable Create Button','admin2020');
		$temp['description'] = __("Disables the 'create' button in the admin bar.",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'new-enabled'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Disable View Website Button','admin2020');
		$temp['description'] = __("Disables the view website link button in the admin bar.",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'view-enabled'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Set Dark Mode as Default','admin2020');
		$temp['description'] = __("If enabled, dark mode will default to true for users that haven't set a preference.",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'dark-enabled'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Load UIPress admin bar on front end','admin2020');
		$temp['description'] = __("If enabled, UiPress admin bar will be load on the front end. Please note, this will not work on all themes and styling will vary..",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'load-front'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Hide admin bar on front end','admin2020');
		$temp['description'] = __("If enabled, front end admin bar will not load.",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'hide-admin'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Disable Notification Center','admin2020');
		$temp['description'] = __("If disabled, notifcations will show in the normal way",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'notification-center-disabled'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Post Types available in Search','admin2020');
		$temp['description'] = __("The global search will only search the selected post types.",'admin2020');
		$temp['type'] = 'post-type-select';
		$temp['optionName'] = 'post-types-search'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Post Types available in create button (new)','admin2020');
		$temp['description'] = __("Only the selected post types will show up in the create dropdown.",'admin2020');
		$temp['type'] = 'post-type-select';
		$temp['optionName'] = 'post-types-create'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Only show notifcations to','admin2020');
		$temp['description'] = __("UiPress will hide all notifications from all users except those selected below",'admin2020');
		$temp['type'] = 'user-role-select';
		$temp['optionName'] = 'notifcations-disabled-for'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Only show updates to','admin2020');
		$temp['description'] = __("UiPress will hide all updates from all users except those selected below",'admin2020');
		$temp['type'] = 'user-role-select';
		$temp['optionName'] = 'updates-disabled-for'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Hide edit profile button','admin2020');
		$temp['description'] = __("If enabled, the edit profile button in the offcanvas bar will be hidden",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'hide-edit-profile'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		return $settings;
		
	}
	
	
	public function valid_for_user($rolesandusernames){
		
		if( empty($rolesandusernames)) {
			return 'true';
		}
		
		if(!function_exists('wp_get_current_user')){
			return 'true';
		}
		
		
		$current_user = $this->utils->get_user();
		
		
		$current_name = $current_user->display_name;
		$current_roles = $current_user->roles;
		$formattedroles = array();
		$all_roles = wp_roles()->get_names();
		
		
		if(in_array($current_name, $rolesandusernames)){
			return 'true';
		}
		
		
		///MULTISITE SUPER ADMIN
		if(is_super_admin() && is_multisite()){
			if(in_array('Super Admin',$rolesandusernames)){
				return 'true';
			} else {
				return 'false';
			}
		}
		
		///NORMAL SUPER ADMIN
		if($current_user->ID === 1){
			if(in_array('Super Admin',$rolesandusernames)){
				return 'true';
			} else {
				return 'false';
			}
		}
		
		foreach ($current_roles as $role){
			
			$role_name = $all_roles[$role];
			if(in_array($role_name,$rolesandusernames)){
				return 'true';;
			}
			
		}
		
	}
	
    /**
     * Adds admin bar styles
     * @since 1.0
     */

    public function add_styles()
    {
		
		///ENSURE WE ARE NOT LOADING ON FRONT UNLESS NECESSARY
		global $pagenow;
		if(!is_user_logged_in() && $pagenow != 'wp-login.php'){
			return;
		}
		
        wp_register_style(
            'admin2020_admin_bar',
            $this->path . 'assets/css/modules/admin-bar.css',
            array('admin-bar'),
            $this->version
        );
        wp_enqueue_style('admin2020_admin_bar');
		
    }
	
	/**
	* Enqueue Admin Bar 2020 scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
	  
	  
	  ///ENSURE WE ARE NOT LOADING ON FRONT UNLESS NECESSARY
	  global $pagenow;
	  if(!is_user_logged_in() && $pagenow != 'wp-login.php'){
		  return;
	  }
	  ///ADMIN BAR JS FRAMEWORK
	  $info = $this->component_info();
	  $optionname = $info['option_name'];
	  
	  $post_types_create = $this->utils->get_option($optionname,'post-types-create');
	  $supressedNotifications = $this->utils->get_user_preference('a2020_supressed_notifications');
	  $screenOptions = $this->utils->get_user_preference('screen_options');
	  $legacyLinks = $this->utils->get_user_preference('legacy_admin_links');
	  $darkmode = $this->utils->get_user_preference('darkmode');
	  $searchDisabled = $this->utils->get_option($optionname,'search-enabled');
	  $post_types_enabled = $this->utils->get_option($optionname,'post-types-search');
	  $new_enabled = $this->utils->get_option($optionname,'new-enabled');
	  $home_enabled = $this->utils->get_option($optionname,'view-enabled');
	  $legacy_admin = $this->utils->get_option($optionname,'legacy-admin');
	  $notification_center = $this->utils->get_option($optionname,'notification-center-disabled');
	  
	  if($post_types_enabled == '' || !$post_types_enabled || !is_array($post_types_enabled)){
		  $post_types = 'any';
	  } else {
		  $post_types = $post_types_enabled;
	  }
	  
	  
	  $front = is_admin();
	  
	  if(!$supressedNotifications or !is_array($supressedNotifications)){
		  $supressedNotifications = array();
	  }
	  
	  $preferences = array();
	  
	  $preferences['screenOptions'] = $screenOptions;
	  $preferences['darkmode'] = $darkmode;
	  $preferences['legacyLinks'] = $legacyLinks;
	  
	  
	 
	  $showUpdates = $this->valid_for_user( $this->utils->get_option($optionname,'updates-disabled-for', true)); 
	  $showNotifcations = $this->valid_for_user( $this->utils->get_option($optionname,'notifcations-disabled-for', true));
	  
	  $master = array();
	  $master['searchDisabled'] = $searchDisabled;	
	  $master['backend'] = $front;	 
	  $master['postTypesSearch'] = $post_types_enabled; 
	  $master['createEnabled'] = $new_enabled;
	  $master['homeEnabled'] = $home_enabled;
	  $master['legacyAdmin'] = $legacy_admin;
	  $master['notificationCenter'] = $notification_center;
	  $master['showUpdates'] = $showUpdates;
	  $master['showNotifications'] = $showNotifcations;
	  $master['hideEditProfile'] = $this->utils->get_option($optionname,'hide-edit-profile');
	  
	  
	  
	  
	  
	  if($post_types_create == '' || !$post_types_create){
		  $args = array('public'   => true);
		  $output = 'objects'; 
		  $post_types = get_post_types($args,$output);
	  } else {
		  $post_types = $this->utils->get_post_types();
	  }
	  
	  $formattedPostTypes = array();
	  
		foreach($post_types as $type){
			$temp = array();
			
			if($post_types_create == '' || !$post_types_create){
				$name = $type->name;
				$temp['href'] = 'post-new.php?post_type='.$name;
				$temp['name'] = $type->labels->singular_name;
				$temp['all'] = $type;
				$formattedPostTypes[] = $temp;
			} else {
				if(in_array($type->name, $post_types_create)){
					$name = $type->name;
					$temp['href'] = 'post-new.php?post_type='.$name;
					$temp['name'] = $type->labels->singular_name;
					$formattedPostTypes[] = $temp;
				}
			}
			
		}
		
	  	$allnotices = array();
		$updates = array();
		if(is_admin() == true && $notification_center != 'true'){
		  $updates = $this->utils->get_total_updates();
		  do_action('admin_notices');
		  
		  $notices = json_encode($this->notices);
		  $allnotices = $this->notices;
		  if(!json_decode($notices)){
			  $notices = array();
			  $allnotices = json_encode($notices);
		  }
		} 
		
		if(!is_array($updates)){
			$updates = array();
		}
	    
	  
		wp_enqueue_script('admin-bar-app', $this->path . 'assets/js/admin2020/admin-bar-app.min.js', array('jquery'),$this->version, true );
		wp_localize_script('admin-bar-app', 'admin2020_admin_bar_ajax', array(
		   'ajax_url' => admin_url('admin-ajax.php'),
		   'security' => wp_create_nonce('admin2020-admin-bar-security-nonce'),
		   'postTypes' => json_encode($formattedPostTypes),
		   'updates' => json_encode($updates),
		   'notices' => $allnotices,
		   'supressed' => json_encode($supressedNotifications),
		   'preferences' => json_encode($preferences),
		   'master' => json_encode($master),
		)); 
	  
	}
	
	
	/**
	* Adds custom css html element
	* @since 1.4
	*/
	
	public function add_body_styles(){
		
		  echo '<style type="text/css">';
		  echo 'html { margin-top: 0 !important; }';
		  echo '</style>';
		  
	}
	
	/**
	* Searches all WP content
	* @since 1.4
	*/
	
	public function a2020_master_search(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-bar-security-nonce', 'security') > 0) {
			
			$term = $_POST['search'];
			$page = $_POST['currentpage'];
			$perpage = $_POST['perpage'];
			
			$info = $this->component_info();
			$optionname = $info['option_name'];
			$post_types_enabled = $this->utils->get_option($optionname,'post-types-search');
			
			if($post_types_enabled == '' || !$post_types_enabled || !is_array($post_types_enabled)){
				$post_types = 'any';
			} else {
				$post_types = $post_types_enabled;
			}
			
			//BUILD SEARCH ARGS//shop_order
			$args = array(
			  '_a2020_meta_or_title' => $term, 
			  'posts_per_page' => $perpage,
			  'post_type' => $post_types,
			  'paged' => $page,
			  'post_status' => 'all',
			  'meta_query' => array(
				  'relation' => 'OR',
				  array(
					  'value' => $term,
					  'compare' => 'LIKE',
				  )
			  )
			);
			
			
			if(isset($_POST['posttypes'])){
				$postTypes = $_POST['posttypes'];
				$args['post_type'] = $postTypes;
				$args_meta['post_type'] = $postTypes;
			}
			if(isset($_POST['categories'])){
				$categories = $_POST['categories'];
				$args['category'] = $categories;
				$args_meta['category'] = $categories;
			}
			if(isset( $_POST['users'])){
				$users =  $_POST['users'];
				$args['author__in'] = $users;
				$args_meta['author__in'] = $users;
			}
			
			//$q1 = new WP_Query($args);
			//$q2 = new WP_Query($args_meta);
			
			//$result = new WP_Query();
			//$result->posts = array_unique( array_merge( $q1->posts, $q2->posts ), SORT_REGULAR );
			$result = new WP_Query($args);
			$result->post_count = count( $result->posts );
			
			$foundposts = $result->posts;
			$searchresults = array();
			$categorized = array();
			$categ = array();
				
			foreach ($foundposts as $item){
				
				
				$temp = array();
				$author_id = $item->post_author;
				$title =  $item->post_title;
				$status = get_post_status_object( get_post_status( $item->ID));
				$label = $status->label;
				
				$postype_single = get_post_type($item);
				$postype = get_post_type_object($postype_single);
				$postype_label = $postype->label;
				
				$editurl = get_edit_post_link($item, '&');
				$public = get_permalink($item);
				
				if ($postype_single == 'attachment' && wp_attachment_is_image($item)){
					
					$temp['image'] = wp_get_attachment_thumb_url(  $item->ID );
					
				}
				
				if ($postype_single == 'attachment'){
					$temp['attachment'] = true;
					
					$mime = get_post_mime_type($item->ID);
					$actualMime = explode("/", $mime);
					$actualMime = $actualMime[1];
					
					$temp['mime'] = $actualMime;
				}
				
				$temp['name'] = $title;
				
				if($term != ''){
					
					$foundtitle = str_ireplace($term, '<highlight>'.$term.'</highlight>', $title);
					$temp['name'] = $foundtitle;
					
				}
				
				$temp['editUrl'] = $editurl;
				$temp['type'] = $postype_label;
				$temp['status'] = $label;
				$temp['author'] = get_the_author_meta( 'user_login' , $author_id );
				$temp['date'] = get_the_date('j M y',$item);
				$temp['url'] = $public;
				
				
				$categorized[$postype_single]['label'] = $postype_label;
				$categorized[$postype_single]['found'][] = $temp;
				
				$searchresults[] = $temp;
				
			}
			
			$totalFound = $result->found_posts;
			$totalPages = $result->max_num_pages;
				
			$returndata = array();
			$returndata['founditems'] = $searchresults;
			$returndata['totalfound'] = $totalFound;
			$returndata['totalpages'] = $totalPages;
			$returndata['categorized'] = $categorized;
			echo json_encode($returndata);
		}
		die();
	}
	
	
	
	
	
	
	

    /**
     * Disables default admin bar and outputs new
     * @since 1.0
     */

    public function rebuild_admin_bar() {
		
		
		if($this->kill){
			return false;
		}
		
		if (!is_admin_bar_showing()) {
			return false;
		}
		
		global $wp_admin_bar;
		
		if (empty($wp_admin_bar)) {
			return false;
		}
		
		$darkmode = $this->utils->get_user_preference('darkmode');
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$light_background = $this->utils->get_option($optionname,'light-background');
		$dark_background = $this->utils->get_option($optionname,'dark-background');
		$dark_enabled = $this->utils->get_option($optionname,'dark-enabled');
		
		$class = '';
			
		
		if($light_background != ""){
			$light_without_hex = str_replace('#', '', $light_background);
			$hexRGB = $light_without_hex;
			if(hexdec(substr($hexRGB,0,2))+hexdec(substr($hexRGB,2,2))+hexdec(substr($hexRGB,4,2))< 381){
				$class = " a2020_night_mode uk-light";
			}
			?>
			<style type="text/css">
			body:not(.a2020_night_mode) .a2020-admin-bar {background:<?php echo $light_background?> !important;}
			</style>
			<?php
		}
		if($dark_background != ""){
			$light_without_hex = str_replace('#', '', $dark_background);
			$hexRGB = $light_without_hex;
			if(hexdec(substr($hexRGB,0,2))+hexdec(substr($hexRGB,2,2))+hexdec(substr($hexRGB,4,2)) > 381){
				$class = "";
			}
			?>
			<style type="text/css">
			.a2020_night_mode .a2020-admin-bar {background:<?php echo $dark_background?> !important;}
			
			<?php if($this->front == true){ ?>
			   .a2020_night_mode.a2020-admin-bar {background:<?php echo $dark_background?> !important;}
			<?php } ?>
			
			</style>
			<?php
		}

        /// START MENU BUILD
        ob_start();
        ?>
		
		<div uk-sticky="sel-target: . a2020-admin-bar;" id="a2020-admin-bar-app" >
				<nav class="uk-navbar-container uk-navbar-transparent uk-background-default a2020-admin-bar uk-padding-small uk-padding-remove-vertical a2020_dark_anchor"
				:class="{'a2020_night_mode uk-light' : prefs.darkMode}"  
				uk-navbar style="max-height:61px;">
					<div  class="uk-navbar-left show-after-load" v-if="!loading" :class="{'loaded' : !loading}">
					
						<?php $this->build_logo(); ?>
						
						<div v-if="!prefs.legacyAdmin && !masterPrefs.legacyAdmin" class="admin2020_legacy_admin">
							<?php echo wp_admin_bar_render(); ?>
						</div>
					
					</div>
					
					<div  class="uk-navbar-right show-after-load" v-if="!loading" :class="{'loaded' : !loading}">
					
						<?php $this->build_nav_right(); ?>
					
					</div>
				
				</nav>
			
				<?php $this->build_user_offcanvas(); ?>
		</div>
		<?php 
		
		
	    ///OUTPUT NEW MENU
	    $wp_admin_bar = ob_get_clean();
		
		if($this->front === false){
	    	echo $wp_admin_bar;
		} else {
			$this->a2020_admin_bar = $wp_admin_bar;
		}
    }
	
	/**
	 * Outputs the admin bar on front
	 * @since 2.0.4
	 */
	
	public function output_admin_front(){
		
		if(isset($this->a2020_admin_bar)){
			echo $this->a2020_admin_bar;
		}
		
	}
	
	/**
	 * Disables off canvas user menu
	 * @since 1.0
	 */
	
	public function build_search_bar() {
		
		
		
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$post_types_enabled = $this->utils->get_option($optionname,'post-types-search');
		
		if($post_types_enabled == '' || !$post_types_enabled){
			$args = array('public'   => true);
			$output = 'objects'; 
			$post_types = get_post_types($args,$output);
		} else {
			$post_types = $this->utils->get_post_types();
		}
		
		
		$temp = array();
		
		
		
		
		
		
		if(is_array($post_types_enabled)){
			foreach($post_types as $type){
				if(in_array($type->name, $post_types_enabled)){
					array_push($temp, $type);
				}
			}
			
			$post_types = $temp;
		}
		
		
		///GET CATEGORIES
		$categories = get_categories( array(
			'orderby' => 'name',
			'order'   => 'ASC',
			'hide_empty' => false,
		));
		
		$darkmode = $this->utils->get_user_preference('darkmode');
		
		$class = '';
		
		if($darkmode == 'true'){
			$class= 'uk-light';
		}
		?>
		
		<li v-if="!masterPrefs.searchDisabled" id="a2020_admin_bar_search_wrap">
			<a class="uk-navbar-toggle" href="#" uk-toggle="target: .ma-admin-search-results; animation: uk-animation-slide-top">
				<span class="uk-background-muted uk-border-rounded " style="padding: 6px 10px;">
					<span style="font-size: 20px;display: block;"uk-tooltip="<?php _e('Search website','admin2020')?>" class="material-icons">search</span>
				</span>
			</a>
		</li>
		
		<div v-if="!masterPrefs.searchDisabled" class="ma-admin-search-results <?php echo $class?>" uk-dropdown="mode: click;pos:bottom-right">
			<div class="ma-admin-search-results-inner uk-padding-remove" style="width:550px;max-width:100%">
		
				
				<div class="uk-padding-small">
					<div class="uk-grid-small" uk-grid>
						<div class="uk-width-expand">
							<div class="uk-inline" style="width: 100%;">
								<span class="uk-form-icon material-icons uk-margin-small-right">search</span>
								<input class="uk-input a2020-muted-input"  
								v-on:keyup.enter="masterSearch()"
								v-model="search.string" style="border: none;"type="search" id="a2020_master_search" placeholder="<?php _e('Search','admin2020') ?>"autofocus>
								<span class="uk-position-right" style="padding:10px;">
									<div uk-spinner="ratio: 0.7" id="a2020_master_search_progress" style="display: none;"></div>
								</span>
							</div>
						</div>
						<div class="uk-width-auto">
							<button class="uk-button uk-button-secondary" @click="masterSearch()"><?php _e('Search','admin2020')?></button>
						</div>
						
					</div>
					
					
					
					<div class="uk-width-1-1 uk-margin-top uk-overflow-auto uk-margin-small-top" style="max-height: 600px">
						
						<svg v-if="search.loading"
						  role="img"
							width="400"
							height="430"
							aria-labelledby="loading-aria"
							viewBox="0 0 400 460"
							preserveAspectRatio="none"
						  >
							<title id="loading-aria">Loading...</title>
							<rect
							  x="0"
							  y="0"
							  width="100%"
							  height="100%"
							  clip-path="url(#clip-path)"
							  style='fill: url("#fill");'
							></rect>
							<defs>
							  <clipPath id="clip-path">
								  <rect x="0" y="18" rx="2" ry="2" width="211" height="16" /> 
								  <rect x="0" y="47" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="279" y="47" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="0" y="94" rx="2" ry="2" width="211" height="16" /> 
								  <rect x="0" y="123" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="279" y="123" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="0" y="173" rx="2" ry="2" width="211" height="16" /> 
								  <rect x="0" y="202" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="279" y="202" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="0" y="253" rx="2" ry="2" width="211" height="16" /> 
								  <rect x="0" y="282" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="279" y="282" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="0" y="335" rx="2" ry="2" width="211" height="16" /> 
								  <rect x="0" y="364" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="279" y="364" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="0" y="412" rx="2" ry="2" width="211" height="16" /> 
								  <rect x="0" y="441" rx="2" ry="2" width="120" height="16" /> 
								  <rect x="279" y="441" rx="2" ry="2" width="120" height="16" />
							  </clipPath>
							  <linearGradient id="fill">
								<stop
								  offset="0.599964"
								  stop-color="#f3f3f3"
								  stop-opacity="1"
								>
								  <animate
									attributeName="offset"
									values="-2; -2; 1"
									keyTimes="0; 0.25; 1"
									dur="2s"
									repeatCount="indefinite"
								  ></animate>
								</stop>
								<stop
								  offset="1.59996"
								  stop-color="#ecebeb"
								  stop-opacity="1"
								>
								  <animate
									attributeName="offset"
									values="-1; -1; 2"
									keyTimes="0; 0.25; 1"
									dur="2s"
									repeatCount="indefinite"
								  ></animate>
								</stop>
								<stop
								  offset="2.59996"
								  stop-color="#f3f3f3"
								  stop-opacity="1"
								>
								  <animate
									attributeName="offset"
									values="0; 0; 3"
									keyTimes="0; 0.25; 1"
									dur="2s"
									repeatCount="indefinite"
								  ></animate>
								</stop>
							  </linearGradient>
							</defs>
						  </svg>
						<p v-if="searchedCats.length  < 1 && !search.loading && search.string.length > 0">
							<?php _e('Nothing found for your query','admin2020') ?>
						</p>  
						  
						<template v-for="cat in searchedCats" v-if="!search.loading">
							<div class="uk-text-meta uk-text-bold uk-margin-small-bottom" style="padding: 5px;">{{cat.label}}</div>
							<div class="cat-area uk-margin-small-bottom">
								<template v-for="foundItem in cat.found" v-if="!search.loading">
									<div class="a2020-found-item">
										<div class="uk-grid uk-grid-small">
											
											<div class="uk-width-auto  ">
												<img v-if="foundItem.image" :src="foundItem.image" style="height:26px;border-radius: 4px;">
												
												<span v-if="foundItem.attachment && !foundItem.image" class="a2020-post-label" style="display: block;">{{foundItem.mime}}</span>
												
												<span v-if="!foundItem.attachment && !foundItem.image" 
												class="a2020-post-label" :class="foundItem.status" style="display: block;">{{foundItem.status}}</span>
											</div>
											<div class="uk-width-expand uk-flex uk-flex-middle">
													<a class="uk-text-bold uk-margin-small-right uk-link-muted" :href="foundItem.editUrl" v-html="foundItem.name"></a>
													<!--<span class="uk-text-muted">{{foundItem.date}}</span>-->
												<div>
													<!--<span class="a2020-post-label uk-margin-small-right" style="display: inline;">{{foundItem.type}}</span>-->
												</div>
											</div>
											<div class="uk-width-auto a2020-search-actions">
												<a :href="foundItem.editUrl" 
												uk-tooltip="title:<?php _e('Edit','admin2020')?>"
												class="uk-button uk-button-small uk-flex-middle uk-background-default" style="height: 26px;display: inline-flex">
													<span class="material-icons" style="font-size: 18px;">edit_note</span>
												</a>
											</div>
											<div class="uk-width-auto a2020-search-actions">
												<a :href="foundItem.url" 
												uk-tooltip="title:<?php _e('View','admin2020')?>"
												class="uk-button uk-button-small uk-flex-middle uk-background-default" style="height: 26px;display: inline-flex">
													<span class="material-icons" style="font-size: 18px;">pageview</span>
												</a>
											</div>
											
										</div>
									</div>
								</template>
							</div>
						</template>
						
						
					</div>
					
					<div class="uk-width-1-1 uk-margin-top" v-if="search.totalPages > 1">
						<button class="uk-button uk-button-default uk-button-small  uk-width-1-1" @click="loadMoreResults">
							<span><?php _e('Show more','admin2020')?> </span>
							<span>({{search.totalFound - search.results.length}}</span>
							<span> <?php _e('other matches','admin2020')?>)</span>
						</button>
					</div>
				</div>

		
			</div>
		</div>
		
		<?php
		
	}	
	
	/**
	 * Disables off canvas user menu
	 * @since 1.0
	 */
	
	public function build_user_offcanvas() {
		
		$current_user = $this->utils->get_user();
		
		$username = $current_user->user_login;
		$email = $current_user->user_email;
		$first = $current_user->user_firstname;
		$last = $current_user->user_lastname;
		$roles = $current_user->roles;
		$userid = $current_user->ID;
		
		$darkmode = $this->utils->get_user_preference('darkmode');
		$dark_enabled = $this->utils->get_option('admin2020_admin_bar','dark-enabled');
		
		$screenoptions = $this->utils->get_user_preference('screen_options');
		$legacyadmin = $this->utils->get_user_preference('legacy_admin_links');
		
		if($first == "" || $last == ""){
			$name_string = $username;
		} else {
			$name_string = $first . " " . $last;
		}
		
		$dark_on = '';
		
		if ($darkmode == 'true') {
			$dark_on = 'checked';
		} else if ($darkmode == '' && $dark_enabled == 'true'){
			$dark_on = 'checked';
		}
		?>
		
		<!-- OFFCANVAS USER MENU -->
		<div v-if="userMenu.offcanvas" class="uk-position-fixed uk-width-1-1 uk-height-viewport hidden" style="background:rgba(0,0,0,0.3);z-index:99999;top:0;" 
		:class="{'nothidden' : userMenu.offcanvas}">
			
			<div class="uk-grid">
				<div class="uk-width-expand" @click="userMenu.offcanvas = false"></div>
				
				<div class="uk-width-auto">
					
					
					<div class="uk-background-default uk-position-relative uk-padding-remove" style="height:100vh;width: 400px;max-width:100%;border-left:1px solid rgba(162,162,162,.2)">
						
						<button class="uk-offcanvas-close" type="button" uk-close @click="userMenu.offcanvas = false"></button>
						
						<div class="" style="height: 100%;overflow:auto;">
					
							<div class="uk-grid-small uk-padding" uk-grid>
								<div class="uk-width-auto">
									<div class="offcanvas_user_image">
										<img class="uk-border-circle" width="50" height="50" src="<?php echo get_avatar_url($this->utils->get_user_id()) ?>">  
									</div>
								</div>
								<div class="uk-width-expand uk-flex uk-flex-middle">
									<span>
										<div class="uk-h4 uk-margin-remove" style="line-height: 1"><?php echo $name_string ?></div>
										<div class="uk-flex uk-flex-middle uk-text-meta" >
											<span class="material-icons " style="font-size: 14px;margin-right: 3px;">mail_outline</span>
											<span><?php echo $email ?></span>
										</div>
									</span>
								</div>
								
								<div class="uk-width-1-1 uk-margin-top">
									
									<div class="a2020-switch-container uk-margin-bottom">
										  <button  
										  uk-tooltip="title:<?php _e('Overview','admin2020') ?>;delay:300"
										  :class="{ 'active' : userMenu.panel == 'overview'}" 
										  @click="userMenu.panel = 'overview'">
											  <span class="material-icons-outlined" style="font-size: 20px">dashboard</span>
										  </button>
										  
										  <button  
										  uk-tooltip="title:<?php _e('Preferences','admin2020') ?>;delay:300"
										  :class="{ 'active' :  userMenu.panel == 'settings'}" 
										  @click="userMenu.panel = 'settings'">
											  <span class="material-icons-outlined" style="font-size: 20px">tune</span>
										  </button>
									</div>
									
									<a v-if="masterPrefs.backend" href="<?php echo get_home_url() ?>" class="a2020-label-tag uk-flex uk-flex-middle uk-margin-small-bottom">
										<span class="material-icons-outlined uk-margin-small-right ">launch</span>
										<span><?php _e('View site','admin2020') ?></span>
									</a>
									
									<a v-if="!masterPrefs.backend" href="<?php echo get_home_url() ?>" class="a2020-label-tag uk-flex uk-flex-middle uk-margin-small-bottom">
										<span class="material-icons-outlined uk-margin-small-right ">launch</span>
										<span><?php _e('View Dashboard','admin2020') ?></span>
									</a>
									
									
									
									
									<ul style="margin-top: 30px;">
										
										<li v-if="userMenu.panel == 'overview'" class="uk-animation-slide-right">
											
											<?php $this->build_notifications() ?>
										</li>
										
										
										<li v-if="userMenu.panel == 'settings'" class="uk-animation-slide-right">
											
											<div class="uk-grid-small" uk-grid>
												
												<div class="uk-width-2-3">
													<?php _e('Dark Mode','admin2020')?>
												</div>
												
												<div class="uk-width-1-3">
													<label class="admin2020_switch uk-margin-left">
														<input type="checkbox" v-model="prefs.darkMode">
														<span class="admin2020_slider "></span>
													</label>
												</div>
												
												<div class="uk-width-2-3 a2020_wp_admin_screen_option">
													<?php _e('Show screen options','admin2020')?>
												</div>
												
												<div class="uk-width-1-3 a2020_wp_admin_screen_option">
													<label class="admin2020_switch uk-margin-left">
														<input type="checkbox" v-model="prefs.screenOptions">
														<span class="admin2020_slider "></span>
													</label>
												</div>
												
												<div v-if="!masterPrefs.legacyAdmin" class="uk-width-2-3 a2020_wp_admin_bar_option">
													<?php _e('Hide admin bar links (left)','admin2020')?>
												</div>
												
												<div v-if="!masterPrefs.legacyAdmin"  class="uk-width-1-3 a2020_wp_admin_bar_option">
													<label class="admin2020_switch uk-margin-left">
														<input type="checkbox" v-model="prefs.legacyAdmin">
														<span class="admin2020_slider "></span>
													</label>
												</div>
												
											</div>	
											
										</li>
										
									</ul>
									
									
								</div>	
								
							</div>	
						
						</div>
						
						<div class="uk-position-bottom uk-padding uk-width-1-1 a2020_logout uk-background-default " style="padding-top:15px;padding-bottom:15px;">
							
							<div class="uk-grid uk-grid-small  uk-child-width-1-2">
								<div v-if="!masterPrefs.hideEditProfile">
									<a href="<?php echo get_edit_profile_url($userid) ?>" class="a2020-label-tag uk-flex uk-flex-middle">
										<span class="material-icons-outlined uk-margin-small-right">account_circle</span>
										<span><?php _e('Edit profile','admin2020') ?></span>
									</a>
								</div>
							
								<div>
									<a href="<?php echo wp_logout_url() ?>" class="a2020-label-tag muted uk-flex uk-flex-middle">
										<span class="material-icons-outlined uk-margin-small-right">logout</span>
										<span><?php _e('Logout','admin2020') ?></span>
									</a>
								</div>
							</div>
							
						</div>
					
					</div>
					
				</div>
			</div>
			
		</div>
		<?php
	}	
	
	/**
	 * Builds notification area
	 * @since 1.4
	 */

	public function build_notifications() {
		
		$adminurl = get_admin_url();
		
		?> 
		<div v-if="masterPrefs.backend && masterPrefs.showUpdates" id="a2020-update-wrap">
			<div class="uk-h5 uk-text-bold uk-flex uk-flex-middle uk-flex-between">
				<div class="uk-flex uk-flex-middle"> 
					<span class="material-icons-outlined uk-margin-small-right" style="font-size: 20px">update</span>
					<span><?php _e('Updates','admin2020')?></span>
				</div>
				<span v-if="updates.total > 0" class="a2020-warning-count">{{updates.total}}</span>
				<span v-if="updates.total < 1" class="a2020-warning-count success"><?php _e('Up to date','admin2020') ?></span>
			</div> 
		
			<p v-if="updates.total < 1" class="uk-text-meta"><?php _e('Everything is up to date','admin2020') ?></p>
			
		
			<ul v-if="updates.total > 0" class="uk-nav uk-nav-default uk-margin-bottom" id="admin2020_updates_center">
			
			
			   <li>
					<a href="<?php echo $adminurl.'update-core.php'?>" >
						<div class="uk-flex uk-flex-between uk-flex-middle">
							<div class="uk-flex uk-flex-middle"> 
								<span class="material-icons-outlined uk-margin-small-right" style="font-size: 20px">system_update_alt</span>
								<span class="uk-margin-small-right"><?php _e('Core','admin2020')?></span>
							</div>
							<span v-if="updates.wordpress > 0" class="a2020-warning-count">{{updates.wordpress}}</span>
							<span v-if="updates.wordpress < 1" class="material-icons-outlined uk-text-success" style="font-size: 16px">check_circle</span>	
						</div>
					</a>  
			   </li>
			
			   <li>
				   
				 <a href="<?php echo $adminurl.'plugins.php'?>" >
					 <div class="uk-flex uk-flex-between uk-flex-middle">
						 <div class="uk-flex uk-flex-middle"> 
						 	<span class="material-icons-outlined uk-margin-small-right" style="font-size: 20px">extension</span>
						 	<span class="uk-margin-small-right"><?php _e('Plugins','admin2020')?></span>
						 </div>
						 <span v-if="updates.pluginCount > 0" class="a2020-warning-count">{{updates.pluginCount}}</span>
						 <span v-if="updates.pluginCount < 1" class="material-icons-outlined uk-text-success" style="font-size: 16px">check_circle</span>	
					 </div>
				 </a>    
			   </li>
			
			   <li>
				 <a href="<?php echo $adminurl.'themes.php'?>" >
					 <div class="uk-flex uk-flex-between uk-flex-middle">
						  <div class="uk-flex uk-flex-middle"> 
						  	<span class="material-icons-outlined uk-margin-small-right" style="font-size: 20px">color_lens</span>
						  	<span class="uk-margin-small-right"><?php _e('Themes','admin2020')?></span>
						  </div>
						  <span v-if="updates.themeCount > 0" class="a2020-warning-count">{{updates.themeCount}}</span>
						  <span v-if="updates.themeCount < 1" class="material-icons-outlined uk-text-success" style="font-size: 16px">check_circle</span>	
					  </div>
				  </a>   
			   </li>
			
			</ul>
		</div>
		
		<div v-if="masterPrefs.backend && !masterPrefs.notifcations && masterPrefs.showNotifications" id="a2020-notification-wrap" style="margin-top: 30px !important;padding-bottom:30px;">
			
			<div class="uk-h5 uk-text-bold uk-flex uk-flex-middle uk-flex-between">
				<div class="uk-flex uk-flex-middle"> 
					<span class="material-icons-outlined uk-margin-small-right" style="font-size: 20px">notifications</span>
					<span><?php _e('Notifications','admin2020')?></span>
				</div>
				<span v-if="notifications.total > 0" class="a2020-warning-count">{{notifications.total}}</span>
			</div> 
			
			<div id="a2020-notifications" >
				<template v-for="notification in allNotifications">
					<div class="a2020-notification-tag  uk-margin-small-bottom" :class="notification.type" >
						<div class="uk-flex uk-flex-between" @click="notification.open = !notification.open">
							<div class="uk-flex">
								<span v-if="notification.type == 'info'"class="material-icons-outlined uk-margin-small-right">info</span>
								<span v-if="notification.type == 'warning'"class="material-icons-outlined uk-margin-small-right">announcement</span>
								<span v-if="notification.type == 'success'"class="material-icons-outlined uk-margin-small-right">check_circle_outline</span>
								<span v-if="notification.type == 'errormsg'"class="material-icons-outlined uk-margin-small-right">error_outline</span>
								<span v-if="notification.type == 'primary'"class="material-icons-outlined uk-margin-small-right">info</span>
								<span>{{notification.shortDes}}...</span>
							</div>
							<a href="#">
								<span v-if="!notification.open" class="material-icons-outlined " >chevron_left</span>
								<span v-if="notification.open" class="material-icons-outlined " >expand_more</span>
							</a>
						</div>
						<div v-if="notification.open" class="uk-margin-top" >
							<div class="uk-margin">
									<button class="uk-button uk-button-small uk-button-secondary" 
									@click="supressNotification(notification.shortDes, notifications.supressed)"
									type="button"><?php _e('Don\'t show again','admin2020')?></button>
							</div>
							<div v-html="notification.content"></div>
						</div>
					</div>
				</template>
				
				<p v-if="notifications.supressedPage > 0" class="uk-text-muted">
					{{notifications.supressedPage}}
					<span v-if="notifications.supressedPage == 1"><?php _e('hidden notification','admin2020')?>. </span>
					<span v-if="notifications.supressedPage > 1"><?php _e('hidden notifications','admin2020')?>. </span>
					<a href="#"class="uk-link-meta" @click="notifications.supressed = []"><?php _e('Show all')?></a>
				</p>
			</div>
		</div>		
		<?php
		
	}
	
    /**
     * Builds admin bar logo
     * @since 1.4
     */

    public function build_logo() {
		
		
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
        //GET LOGOS
        $logo = $this->utils->get_logo($optionname);
        $dark_logo = $this->utils->get_dark_logo($optionname);
        global $wp_admin_bar;
        //GET HOME URL
        $adminurl = get_admin_url();
        $homeurl = $adminurl;
		
		$redirect = $this->utils->get_option('admin2020_admin_login','login-redirect');
		
		if($redirect == 'true'){
			$homeurl =  admin_url() . "admin.php?page=admin_2020_overview";
		} 
        ?>
		
		<ul class="uk-navbar-nav">
			<li class="" v-if="isSmallScreen()">
				<a href="#" style="padding-left: 0;" uk-toggle="target: #a2020-mobile-nav">
					<span uk-tooltip="delay:500;title:<?php _e('Toggle menu') ?>" class="material-icons">menu_open</span>
				</a>
			</li>
			<li class="uk-active">
				<a href="<?php echo $homeurl; ?>" class="uk-padding-remove-horizontal ma-admin-site-logo">
					<img alt="<?php echo get_bloginfo( 'name' )?>" class="light" src="<?php echo $logo; ?>">
					<img alt="<?php echo get_bloginfo( 'name' )?>" class="dark" src="<?php echo $dark_logo; ?>">
				</a>
			</li>
			
		</ul>
		
		<?php
    }

    /**
     * Build Right admin bar Links
     * @since 1.4
     */

    public function build_nav_right()
    {
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$new_enabled = $this->utils->get_option($optionname,'new-enabled');
		$view_enabled = $this->utils->get_option($optionname,'view-enabled');
		$post_types_create = $this->utils->get_option($optionname,'post-types-create');
		
		
		
		if($post_types_create == '' || !$post_types_create){
			$args = array('public'   => true);
			$output = 'objects'; 
			$post_types = get_post_types($args,$output);
		} else {
			$post_types = $this->utils->get_post_types();
		}
		
		$temp = array();
		
		if(is_array($post_types_create)){
			foreach($post_types as $type){
				if(in_array($type->name, $post_types_create)){
					array_push($temp, $type);
				}
			}
			$post_types = $temp;
		}
			
		
		
        $total_updates = $this->utils->get_total_updates();
		$screenoptions = $this->utils->get_user_preference('screen_options');
        
		$gavar_url = get_avatar_url($this->utils->get_user_id());
		
		$current_user = $this->utils->get_user();
		
		$username = $current_user->user_login;
		$first = $current_user->user_firstname;
		$last = $current_user->user_lastname;
		
		$darkmode = $this->utils->get_user_preference('darkmode');
		$screenoptions = $this->utils->get_user_preference('screen_options');
		
		if($first == "" || $last == ""){
			$name_string = str_split($username,1);
			$name_string = $name_string[0];
		} else {
			$name_string = str_split($first,1)[0].str_split($last,1)[0];
		}	
		
        ?>
		
		<div class="uk-navbar-right">
		
			<ul class="uk-navbar-nav">
				
				<?php $this->build_search_bar();  ?>
				
				<li  v-if="!masterPrefs.viewHome">
					<a v-if="masterPrefs.backend && !isSmallScreen()" href="<?php echo get_home_url() ?>">
						<span class="uk-background-muted uk-border-rounded " style="padding: 6px 10px;">
							<span style="font-size: 20px;display: block;"uk-tooltip="<?php _e('View website','admin2020')?>" class="material-icons">home</span>
						</span>
					</a>
					<a v-if="!masterPrefs.backend" href="<?php echo get_admin_url() ?>">
						<span class="uk-background-muted uk-border-rounded " style="padding: 6px 10px;">
							<span style="font-size: 20px;display: block;"uk-tooltip="<?php _e('Dashboard','admin2020')?>" class="material-icons">dashboard</span>
						</span>
					</a>
				</li>
				
				<li v-if="prefs.screenOptions">
					<a href="#" id="maAdminToggleScreenOptions" onclick="jQuery('#screen-meta').toggleClass('a2020_open_sc');">
						<span class="uk-background-muted uk-border-rounded " style="padding: 6px 10px;">
							<span style="font-size: 20px;display: block;" uk-tooltip="<?php _e('Show screen options','admin2020')?>" class=" material-icons">tune</span>
						</span>
					</a>
				</li>
				
				<li v-if="!masterPrefs.create">
					<a href="#" target="_blank">
						<span class="uk-button uk-button-small uk-button-secondary" ><?php _e('Create','admin2020')?></span>
					</a>
					
					<div uk-dropdown="pos:bottom-justify;">
						<ul class="uk-nav uk-dropdown-nav">
							
							<li v-for="posttype in postTypes">
								<a :href="posttype.href">
									<span class="uk-text-bold"> {{posttype.name}}</span>
								</a>
							</li>
						</ul>
					</div>
				</li>
				
				
				<li @click="userMenu.offcanvas = true" style="position:relative">
					<a href="#" class="ma-admin-profile-img">
						
						<div style="position:relative;">
							
							<?php 
							if(strpos($gavar_url,'gravatar.com')!==false){ ?>
								
								<span  class="uk-icon-button uk-button-primary uk-text-bold uk-text-small" style="font-size:12px;"><?php echo $name_string?></span>
								
							<?php } else { ?>
							
								<img src="<?php echo $gavar_url ?>">
							
							<?php } ?>
						
						</div>
						
						<span v-if="updates.total + notifications.total > 0 && masterPrefs.showUpdates" 
							class="uk-badge uk-position-top-right-out admin2020notificationBadge uk-animation-scale-up" >
							{{updates.total + notifications.total}}
						</span>
					</a>
				</li>
			
			
			
			</ul>
		
		</div>
		
		<?php
    }
	
	
	
	/**
	* Fetches users and roles
	* @since 2.0.8
	*/
	
	public function a2020_get_users_for_select(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-bar-security-nonce', 'security') > 0) {
			
			$term = $this->utils->clean_ajax_input($_POST['search']); 
			
			if(!$term || $term == ""){
				echo json_encode(array());
				die(); 
			}
			
			$term = strtolower($term);
			
			$users = new WP_User_Query( array(
				'search'         => '*'.esc_attr( $term ).'*',
				'fields'         => array('display_name','ID'),
				'search_columns' => array(
					'user_login',
					'user_nicename',
					'user_email',
					'user_url',
				),
			) );
			
			$users_found = $users->get_results();
			$empty_array = array();
			
			foreach($users_found as $user){
				
				$temp = array();
				$temp['value'] = $user->ID;
				$temp['text'] = $user->display_name;
				
				array_push($empty_array,$temp);
				
			}
			
			echo json_encode($empty_array,true);
			
			
		}
		die();	
		
	}
}

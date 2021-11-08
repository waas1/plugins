<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_menu_creator
{
    public function __construct($version, $path, $utilities, $menob)
    {
        $this->version = $version;
        $this->path = $path;
        $this->utils = $utilities;
		$this->menuActions = $menob;
    }

    /**
     * Loads menu editor actions
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
		
		if($this->utils->is_locked($optionname)){
			return;
		}
		
		if(function_exists('is_network_admin')){ 
			if(is_network_admin()){
				return;
			}
		}
		
		
		add_action('admin_init', [$this, 'add_settings'], 0);
		add_filter('uipress_get_custom_menu', [$this, 'load_custom_menu'], 0);
        add_action('admin_enqueue_scripts', [$this, 'add_styles'], 0);
		add_action('admin_menu', array( $this, 'add_menu_item'));
		add_action('admin_footer', [$this, 'add_scripts'], 0);
		add_action('init', [$this, 'uipress_create_menu_cpt'], 0);
		
		
		//AJAX
		add_action('wp_ajax_uipress_get_users_and_roles', array($this,'uipress_get_users_and_roles'));
		add_action('wp_ajax_uipress_save_menu', array($this,'uipress_save_menu'));
		add_action('wp_ajax_uipress_get_menus', array($this,'uipress_get_menus'));
		add_action('wp_ajax_uipress_delete_menu', array($this,'uipress_delete_menu'));
		add_action('wp_ajax_uipress_switch_menu_status', array($this,'uipress_switch_menu_status'));
		add_action('wp_ajax_uipress_duplicate_menu', array($this,'uipress_duplicate_menu'));
		
		
		
		
		
		
		
		
		
		
		
    }
	
	
	public function load_custom_menu($menu){
		
		$custommenu = false;
		
		$args = array(
		  'post_type' => 'uipress_admin_menu',
		  'post_status' => 'publish',
		  'numberposts' => -1,
		  'meta_query' => array(
				array(
					'key' => 'status',
					'value' => 'true',
					'compare' => '=',
				)
			)
		);
			
		$menus = get_posts( $args );
			
		foreach ($menus as $menu){
			
			$temp = array();
			$temp['id'] = $menu->ID;
			$temp['items'] = get_post_meta($menu->ID,"items", true );
			$temp['status'] = get_post_meta($menu->ID,"status",true );
			$temp['roleMode'] = get_post_meta($menu->ID,"role_mode",true );
			$temp['appliedTo'] = get_post_meta($menu->ID,"applied_to", true );
			
			$status = false;
			
			if(is_array($temp['appliedTo']) && count($temp['appliedTo']) > 0){
				$status = $this->menu_valid_for_user($temp['appliedTo'], $temp['roleMode'] );
			}
			
			if($status && $temp['roleMode'] == 'inclusive'){
				
				if(is_array($temp['items']) && count($temp['items']) > 0){
					$custommenu = $temp['items'];
					break;
				}
				
			}
			
			if(!$status && $temp['roleMode'] == 'exclusive'){
				
				if(is_array($temp['items']) && count($temp['items']) > 0){
					$custommenu = $temp['items'];
					break;
				}
				
			}
			
			
		}
		
		return $custommenu;
		
	}
	
	
	
	public function menu_valid_for_user($rolesandusernames, $mode){
		
		
		
		if(!function_exists('wp_get_current_user')){
			return false;
		}
		
		
		$current_user = $this->utils->get_user();
		
		
		$current_name = $current_user->display_name;
		$current_roles = $current_user->roles;
		$formattedroles = array();
		$all_roles = wp_roles()->get_names();
		
		
		if(in_array($current_name, $rolesandusernames)){
			return true;
		}
		
		
		///MULTISITE SUPER ADMIN
		if(is_super_admin() && is_multisite()){
			if(in_array('Super Admin',$rolesandusernames)){
				return true;
			} else {
				return false;
			}
		}
		
		///NORMAL SUPER ADMIN
		if($current_user->ID === 1){
			if(in_array('Super Admin',$rolesandusernames)){
				return true;
			} else {
				return false;
			}
		}
		
		foreach ($current_roles as $role){
			
			$role_name = $all_roles[$role];
			if(in_array($role_name,$rolesandusernames)){
				return true;
			}
			
		}
		
	}
	/**
	* Creates custom folder post type
	* @since 1.4
	*/
	public function uipress_create_menu_cpt(){
	
		 $labels = array(
		  'name'               => _x( 'Admin Menu', 'post type general name', 'admin2020' ),
		  'singular_name'      => _x( 'admin menu', 'post type singular name', 'admin2020' ),
		  'menu_name'          => _x( 'Admin Menus', 'admin menu', 'admin2020' ),
		  'name_admin_bar'     => _x( 'Admin Menu', 'add new on admin bar', 'admin2020' ),
		  'add_new'            => _x( 'Add New', 'Admin Menu', 'admin2020' ),
		  'add_new_item'       => __( 'Add New Admin Menu', 'admin2020' ),
		  'new_item'           => __( 'New Admin Menu', 'admin2020' ),
		  'edit_item'          => __( 'Edit Admin Menu', 'admin2020' ),
		  'view_item'          => __( 'View Admin Menu', 'admin2020' ),
		  'all_items'          => __( 'All Admin Menus', 'admin2020' ),
		  'search_items'       => __( 'Search Admin Menus', 'admin2020' ),
		  'not_found'          => __( 'No Admin Menus found.', 'admin2020' ),
		  'not_found_in_trash' => __( 'No Admin Menus found in Trash.', 'admin2020' )
		);
		 $args = array(
		  'labels'             => $labels,
		  'description'        => __( 'Description.', 'Add New Admin Menu' ),
		  'public'             => false,
		  'publicly_queryable' => false,
		  'show_ui'            => false,
		  'show_in_menu'       => false,
		  'query_var'          => false,
		  'has_archive'        => false,
		  'hierarchical'       => false,
		);
		register_post_type( 'uipress_admin_menu', $args );
	}
	
	/**
	* Fetches users and roles
	* @since 2.0.8
	*/
	
	public function uipress_get_menus(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-menu-creator-security-nonce', 'security') > 0) {
			
			$returndata = array();
			
			$args = array(
			  'post_type' => 'uipress_admin_menu',
			  'post_status' => 'publish',
			  'numberposts' => -1,
			);
			
			$menus = get_posts( $args );
			$formattedmenus = array();
			
			foreach ($menus as $menu){
				
				$temp = array();
				$temp['id'] = $menu->ID;
				$temp['name'] = esc_html(get_the_title($menu->ID));
				$temp['items'] = get_post_meta($menu->ID,"items", true );
				$temp['status'] = get_post_meta($menu->ID,"status",true );
				$temp['roleMode'] = get_post_meta($menu->ID,"role_mode",true );
				$temp['appliedTo'] = get_post_meta($menu->ID,"applied_to", true );
				
				if(!is_array($temp['appliedTo'])){
					$temp['appliedTo'] = array();
				}
				
				if(!is_array($temp['items'])){
					$temp['items'] = array();
				}
				
				$temp['date'] = get_the_date(get_option('date_format'), $menu->ID);
				
				$formattedmenus[] = $temp;
				
			}
			
			$returndata['menus'] = $formattedmenus;	
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
		
	}
	
	
	public function uipress_switch_menu_status(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-menu-creator-security-nonce', 'security') > 0) {
			
			$menuid = $this->utils->clean_ajax_input($_POST['menuid']); 
			$status = $this->utils->clean_ajax_input($_POST['status']); 
			
			$returndata = array();
			
			if(!$menuid || $menuid == "" || $status == ""){
				$returndata['error'] = _e('Something went wrong','admin2020');
				echo json_encode($returndata);
				die(); 
			}
			
			
			update_post_meta($menuid,"status",$status);
			
			
			$returndata['message'] = __("Status Updated",'admin2020');
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
		
	}
	
	public function uipress_delete_menu(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-menu-creator-security-nonce', 'security') > 0) {
			
			$menuid = $this->utils->clean_ajax_input($_POST['menuid']); 
			
			$returndata = array();
			
			if(!$menuid || $menuid == ""){
				$returndata['error'] = _e('Something went wrong','admin2020');
				echo json_encode($returndata);
				die(); 
			}
			
			
			if(!current_user_can('delete_post', $menuid)){
				$returndata['error'] = _e('You don\'t have permission to delete this','admin2020');
				echo json_encode($returndata);
				die(); 
			}
		
			$status = wp_delete_post($menuid);
			
			if(!$status){
				$returndata['error'] = _e('Unable to delete menu','admin2020');
				echo json_encode($returndata);
				die(); 
			}
			
			
			$returndata['message'] = __("Menu deleted",'admin2020');
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
		
	}
	
	
	public function uipress_duplicate_menu(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-menu-creator-security-nonce', 'security') > 0) {
			
			$menu = $this->utils->clean_ajax_input_html($_POST['menu']); 
			
			$returndata = array();
			
			if(!$menu || $menu == ""){
				$returndata['error'] = _e('Something went wrong','admin2020');
				echo json_encode($returndata);
				die(); 
			}
			
			if(!isset($menu['items']) || !is_array($menu['items'])){
				$returndata['error'] = _e('Unable to duplicate menu, menu is corrupted','admin2020');
				echo json_encode($returndata);
				die(); 
			}
			
			$my_post = array(
			  'post_title'    => $menu['name'] . ' ' . __('(copy)','admin2020'),
			  'post_status'   => 'publish',
			  'post_type'     => 'uipress_admin_menu'
			);
			
			$themenuID = wp_insert_post( $my_post );
			
			
			if(!$themenuID){
				$returndata['error'] = __("Unable to duplicate menu",'admin2020');
				echo json_encode($returndata);
				die();
			}
			
			update_post_meta($themenuID,"items",$menu['items']);
			update_post_meta($themenuID,"status",'false');
			update_post_meta($themenuID,"role_mode",$menu['roleMode']);
			update_post_meta($themenuID,"applied_to",$menu['appliedTo']);
			
			
			$returndata['message'] = __("Menu duplicated",'admin2020');
			$returndata['original'] = $menu['items'];
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
		
	}
	
	/**
	* Fetches users and roles
	* @since 2.0.8
	*/
	
	public function uipress_save_menu(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-menu-creator-security-nonce', 'security') > 0) {
			
			$menu = $this->utils->clean_ajax_input_html($_POST['menu']); 
			
			$returndata = array();
			
			if(!$menu || $menu == ""){
				$returndata['error'] = _e('Something went wrong','admin2020');
				echo json_encode($returndata);
				die(); 
			}
			
			if(!isset($menu['items']) || !is_array($menu['items'])){
				$returndata['error'] = _e('Unable to save, menu is corrupted','admin2020');
				echo json_encode($returndata);
				die(); 
			}
			
			$my_post = array(
			  'post_title'    => wp_strip_all_tags($menu['name']),
			  'post_status'   => 'publish',
			  'post_type'     => 'uipress_admin_menu'
			);
			
			// Insert the post into the database.
			// UPDATE OR CREATE NEW
			if($menu['id'] && $menu['id'] > 0 ){
				$my_post['ID'] = $menu['id'];
				$themenuID = wp_update_post( $my_post );
			} else {
				$themenuID = wp_insert_post( $my_post );
			}
			
			if(!$themenuID){
				$returndata['error'] = __("Unable to save menu",'admin2020');
				echo json_encode($returndata);
				die();
			}
			
			update_post_meta($themenuID,"items",$menu['items']);
			update_post_meta($themenuID,"status",$menu['status']);
			update_post_meta($themenuID,"role_mode",$menu['roleMode']);
			update_post_meta($themenuID,"applied_to",$menu['appliedTo']);
			
			
			$returndata['message'] = __("Menu Saved",'admin2020');
			$returndata['original'] = $menu['items'];
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
		
	}
	
	public function uipress_get_users_and_roles(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-menu-creator-security-nonce', 'security') > 0) {
			
			$term = $this->utils->clean_ajax_input($_POST['searchString']); 
			
			$returndata = array();
			
			if(!$term || $term == ""){
				$returndata['error'] = _e('Something went wrong','admin2020');
				echo json_encode($returndata);
				die(); 
			}
			
			$term = strtolower($term);
			
			$users = new WP_User_Query( array(
				'search'         => '*'.esc_attr( $term ).'*',
				'fields'         => array('display_name'),
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
				$temp['name'] = $user->display_name;
				$temp['label'] = $user->display_name;
				
				array_push($empty_array,$temp);
				
			}
			
			global $wp_roles;
			
			foreach ($wp_roles->roles as $role){
				
				  $rolename = $role['name'];
				  
				  if (strpos(strtolower($rolename), $term) !== false) {
					  
					  $temp = array();
					  $temp['label'] = $rolename;
					  $temp['name'] = $rolename;
					  
					  array_push($empty_array,$temp);
				  }
				  
			}
			
			if (strpos(strtolower('Super Admin'), $term) !== false) {
				  
				  $temp = array();
				  $temp['name'] = 'Super Admin';
				  $temp['label'] = 'Super Admin';
				  
				  array_push($empty_array,$temp);
			}
			
			
			$returndata['roles'] = $empty_array;
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
	}
	
	/**
	 * Grabs unmodified menu 
	 * @since 1.4
	 */
	
	public function set_menu($parent_file){
		
		global $menu, $submenu;
		$this->menu = $this->sort_menu_settings($menu);
		$this->submenu = $this->sort_sub_menu_settings($this->menu,$submenu);
		
		return $parent_file;
		
	}
	
	/**
	 * Adds menu settings
	 * @since 1.4
	 */
	
	public function add_settings(){
		
		register_setting( 'admin2020_global_settings', 'admin2020_menu_settings' );
		
	}
	
	/**
	 * Register menu editor component
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
		$data['title'] = __('Menu Creator','admin2020');
		$data['option_name'] = 'admin2020_menu_creator';
		$data['description'] = __('Creates the menu creator and allows you to create completely custom admin menus. Only works with the Uipress admin menu module.','admin2020');
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
		$temp['name'] = __('Menu Editor disabled for','admin2020');
		$temp['description'] = __("UiPress menu editor will be disabled for any users or roles you select.",'admin2020');
		$temp['type'] = 'user-role-select';
		$temp['optionName'] = 'disabled-for'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		
		
		return $settings;
		
	}
	
    /**
     * Adds menu editor styles
     * @since 1.0
     */

    public function add_styles()
    {
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin-2020-menu-creator'){
				
		        wp_register_style(
		            'admin2020_menu_creator',
		            $this->path . 'assets/css/modules/admin-menu-creator.css',
		            array(),
		            $this->version
		        );
		        wp_enqueue_style('admin2020_menu_creator');
				
			}
			
		}
		
    }
	
	/**
	* Enqueue menu editor scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin-2020-menu-creator'){
				
				
				
				global $menu, $submenu, $parent_file, $submenu_file;
				$newmenu = $this->menuActions->a2020_format_admin_menu($menu, $submenu);
				$formattedMenu = $this->menuActions->build_top_level_menu_items($newmenu);
			
			
			
				wp_enqueue_script('vue-menu-creator-js', $this->path . 'assets/js/vuejs/vue-menu-creator.js', array('jquery'), $this->version, false );
				wp_enqueue_script('sortable-js', $this->path . 'assets/js/sortable/sortable.js', array('jquery'), $this->version, false );
				wp_enqueue_script('vue-sortable-js', $this->path . 'assets/js/sortable/vuedraggable.umd.js', array('jquery'), $this->version, false );
				
				///MENU EDITOR
				wp_enqueue_script('admin-menu-creator-js', $this->path . 'assets/js/admin2020/admin-menu-creator-app.min.js', array('jquery'), $this->version, true );
				wp_localize_script('admin-menu-creator-js', 'a2020_menucreator_ajax', array( 
				  'ajax_url' => admin_url('admin-ajax.php'),
				  'security' => wp_create_nonce('uipress-menu-creator-security-nonce'),
				  'menuItems' => json_encode($formattedMenu),  
				));
				
				
				
				
				
			}
			
		}
	  
	}
	
	/**
	* Adds menu editor page to settings
	* @since 1.4
	*/
	
	public function add_menu_item(){
		
		add_options_page( 'UiPress Menu Creator', __('Menu Creator','admin2020'), 'manage_options', 'admin-2020-menu-creator', array($this,'admin2020_menu_creator_app') );
		
	}
	
	/**
	* Creates menu editor page
	* @since 1.4
	*/
	
	public function admin2020_menu_creator_app(){
		
		?>
		
		<div id="menu-creator-app">
			
			<template v-if="!loading">
			
				
				
				<?php $this->build_menu_list(); ?>
				<?php $this->build_editor(); ?>
			
			</template>
			
		</div>
		
		<?php
		
		
	}
	
	public function build_menu_list(){
		
		?>
		<div class="uk-container uk-padding uk-animation-fade" v-if="!ui.editingMode">
			
			<div class="uk-grid uk-grid-small uk-margin-large">
				<div class="uk-width-expand">
					<div class="uk-h2">
						<?php _e('Menu Creator','admin2020')?>
					</div>
				</div>
				
				<div class="uk-width-auto">
					<button @click="createNewMenu()" class="uk-button uk-button-primary" type="button"><?php _e('New','admin2020')?></button>
				</div>
			
			</div>
			
			<div v-if="user.allMenus.length < 1" class="uk-padding  uk-text-center">
				<p class="uk-h4 uk-text-center uk-text-muted"><?php _e('Looks like you haven\'t created any admin menus yet','admin2020')?></p>
				<button class="uk-button uk-button-primary" type="button" @click="createNewMenu()"><?php _e('Create your first admin menu','admin2020','admin2020')?></button>
			</div>
			
			<div v-if="user.allMenus.length > 0" class="uk-background-default a2020-border all uk-border-rounded uk-padding-small uk-margin-small-bottom a2020-content-table-head" uk-sticky="offset: 100">
				<div class="uk-grid uk-grid-small">
					
					
					<div class="content-table-title uk-width-medium@m uk-width-large@xl uk-text-bold">
						<?php _e('Name','admin2020') ?>
					</div>
					
					<div class="content-table-type uk-width-expand uk-text-bold">
						<?php _e('Status','admin2020') ?>
					</div>
										
					<div class=" uk-visible@s uk-width-expand uk-text-bold">
						<?php _e('Date','admin2020') ?>
					</div>
					
					<div style="width:40px;">
					</div>
					
					
				</div>
				
			</div>
			
			<template v-for="menu in user.allMenus">
			
				<div class="uk-padding-small uk-margin-small-bottom">
					
					<div class="uk-grid uk-grid-small">
						
						
						<div class="content-table-title uk-width-medium@m uk-width-large@xl uk-text-bold">
							<a href="#" @click="openMenu(menu)">{{menu.name}}</a>
						</div>
						
						<div class="content-table-type uk-width-expand uk-text-bold">
							<label class="admin2020_switch ">
								<input type="checkbox" v-model="menu.status"  @change="switchStatus(menu.id, menu.status)">
								<span class="admin2020_slider "></span>
							</label>
						</div>
						
						<div class=" uk-visible@s uk-width-expand uk-text-bold">
							{{menu.date}}
						</div>
						
						<div style="width:40px;">
							<a href="#" class="uk-icon-button uk-icon uk-open" uk-icon="more"></a>
							
							<div uk-dropdown="pos: bottom-right;mode:click;" class="uk-padding-remove">
								
								<div class="uk-padding-small ">
									
									<ul class="uk-nav uk-dropdown-nav">
										<li >
											<a href="#" @click="openMenu(menu)" >
												<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">edit</span>
												<?php _e('Edit','admin2020')?>
											</a>
										</li>
										
										<li >
											<a href="#" @click="duplicateMenu(menu)" >
												<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">copy</span>
												<?php _e('Duplicate','admin2020')?>
											</a>
										</li>
										
										<li >
											<a href="#" @click="exportMenu(menu)" >
												<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">file_download</span>
												<?php _e('Export','admin2020')?>
											</a>
											<a href="#" hidden id="uipress_export_menus"></a>
										</li>
										
									</ul>
									
								</div>
								
								<div class="uk-padding-small a2020-border top">
									<ul class="uk-nav uk-dropdown-nav">
										<li >
											<a href="#" @click="confirmDelete(menu)" class="uk-text-danger">
												<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">delete</span>
												<?php _e('Delete','admin2020')?>
											</a>
										</li>
										
									</ul>
								</div>
								
							</div>
							
							
						</div>
						
						
					</div>
					
				</div>
				
			</template>
			
		</div>
		
		<?php
	}
	
	public function build_editor(){
		
		$logo = $this->utils->get_logo('admin2020_admin_bar');
		$dark_logo = $this->utils->get_dark_logo('admin2020_admin_bar');
		
		?>
		
		<div class="uk-padding-small a2020-border bottom uk-background-default uk-animation-slide-fade" v-if="ui.editingMode"><?php $this->build_header();?></div>
		
		<div  v-if="ui.editingMode && isSmallScreen()">
			<div class="uk-container uk-padding">
				<div class="uk-alert-warning" uk-alert>
					<a class="uk-alert-close" uk-close></a>
					<p class="uk-text-bold"><?php _e('Menu creator isn\'t optimised for mobile devices. For best results switch to a larger screen','admin2020' )?></p>
				</div>
			</div>
		</div>
		
		<div  class="uk-grid uk-animation-slide-fade" v-if="ui.editingMode">
			
			<div class="uk-width-large uk-background-default uk-height-viewport a2020-border right uk-overflow-auto" v-if="!isSmallScreen()" style="max-height:calc(100vh - 125px)">
				<div class=" ">
					<ul uk-tab class="uk-margin-remove">
						<li class="uk-active"><a  @click="ui.activeTab = 'items'" href="#" style="padding:15px">Menu items</a></li>
						<li><a  @click="ui.activeTab = 'settings'" href="#" style="padding:15px">Menu Settings</a></li>
					</ul>
				</div>
				
				<div class="uk-padding" v-if="ui.activeTab == 'settings'">
					
					<form class="uk-form uk-form-stacked">
						
						<div class="uk-margin" v-if="user.currentItem.type != 'sep'">
							<label class="uk-form-label uk-margin-small-bottom uk-text-bold" for="form-stacked-text"><?php _e('Status','admin2020')?></label>
							<label class="admin2020_switch ">
								<input type="checkbox" v-model="user.currentMenu.status">
								<span class="admin2020_slider "></span>
							</label>
						</div>
						
						<div class="uk-margin">
							<label class="uk-form-label uk-margin-small-bottom uk-text-bold" for="form-stacked-text"><?php _e('Menu Name','admin2020')?></label>
							<input class="uk-input" v-model="user.currentMenu.name" type="text" placeholder="<?php _e('Menu Name','admin2020')?>">
						</div>
						
						<div class="uk-margin">
							
							<label class="uk-form-label uk-margin-small-bottom uk-text-bold" for="form-stacked-text"><?php _e('Menu Applies to','admin2020')?></label>
							
							<div class="a2020-switch-container uk-margin-bottom">
								<button type="button"  :class="{ 'active' : user.currentMenu.roleMode == 'inclusive'}" 
								  @click="user.currentMenu.roleMode = 'inclusive'"> 
									<?php _e('Inclusive','admin2020')?>
								</button>
								<button type="button"  :class="{ 'active' : user.currentMenu.roleMode == 'exclusive'}" 
								  @click="user.currentMenu.roleMode = 'exclusive'">
									<?php _e('Exclusive','admin2020')?>
								</button>
							</div>
							
							<p v-if="user.currentMenu.roleMode == 'inclusive'"><?php _e('In Inclusive mode, this menu will load for all Usernames and roles selected below.','admin2020')?></p>
							<p v-if="user.currentMenu.roleMode == 'exclusive'"><?php _e('In Exclusive mode, this menu will load for every user except those Usernames and roles selected below.','admin2020')?></p>
						
							<multi-select :selected="user.currentMenu.appliedTo"
							:name="'<?php _e('Choose users or roles...','admin2020')?>'"
							:single='false'
							:placeholder="'<?php _e('Search roles and users...','admin2020')?>'"></multi-select>
						
						</div>
						
						
						
					</form>
					
				</div>
				
				<div class="uk-padding" v-if="ui.activeTab == 'items'">
					<button class="uk-button uk-button-default uk-button-small uk-width-1-1"><?php _e('Add Custom...','admin2020')?></button>
					
					<div uk-dropdown="mode:click">
						<ul class="uk-nav uk-dropdown-nav">
							<li><a @click="addDivider()" href="#"><?php _e('Divider','admin2020')?></a></li>
							<li><a @click="addBlank()" href="#"><?php _e('Custom Link','admin2020')?></a></li>
						</ul>
					</div>
					
					<div class="uk-text-bold uk-margin"><?php _e('Available Menu Items','admin2020')?></div>
					
					<div class="uk-margin uk-width-1-1">
						<div class="uk-inline uk-width-1-1">
							<span class="uk-form-icon" uk-icon="icon: search"></span>
							<input class="uk-input " type="text" v-model="master.searchString" placeholder="<?php _e('Search menu items')?>">
						</div>
					</div>
					
					<div class="uk-height-xlarge uk-overflow-auto" id="">
						
						<template v-for="menuitem in originalMenu">
							
							<div v-if="menuitem.type == 'menu' && menuitem.name.toLowerCase().includes(master.searchString.toLowerCase())" class="uk-border-rounded addable_menu_item sortable-top-level uk-background-muted" style="padding:5px;margin-bottom:10px">
								
								<div class="uk-flex uk-flex-between uk-flex-middle">
									
									
									<div @click="menuitem.expand = !menuitem.expand" style="cursor:pointer;">
										
										<span v-if="menuitem.submenu  && menuitem.submenu.length > 0 && menuitem.expand" class="material-icons-outlined uk-margin-small-right" 
										style="font-size: 16px;position: relative;top: 3px;">expand_more</span>
										<span v-if="menuitem.submenu && menuitem.submenu.length > 0 && !menuitem.expand"  class="material-icons-outlined uk-margin-small-right" 
										style="font-size: 16px;position: relative;top: 3px;">chevron_right</span>
									
										<span  class="uk-text-bold" v-html="menuitem.name" ></span>
									
									</div>
									
									<button @click="addToMenu(menuitem)"
									class="uk-button uk-button-small add_menu_item uk-background-default" style="line-height: 18px;"><?php _e('add','admin2020')?></button>
									
								</div>
								
							</div>
							
							<template v-if="menuitem.submenu">
								
								<div v-if="menuitem.expand || master.searchString.length > 0" style="padding-left:30px;margin-bottom:15px;">
									
									<div v-for="sub in menuitem.submenu" class="uk-background-muted">
										
										<div v-if="sub.name.toLowerCase().includes(master.searchString.toLowerCase())"
										class="uk-border-rounded addable_menu_item  uk-flex uk-flex-between uk-flex-middle" style="padding:5px;margin-bottom:5px">
											
											<span  v-html="sub.name"></span>
											
											<button @click="addToMenu(sub)"
											class="uk-button uk-button-small add_menu_item uk-background-default" style="line-height: 18px;"><?php _e('add','admin2020')?></button>
											
										</div>
										
									</div>
									
								</div>
								
							</template>
							
							
							
						</template>
						
					</div>
				</div>
			</div>
			
			<div class="uk-width-expand">
				
				<div class="uk-padding" style="padding-right:0;">
					
					<div class="uk-h3"><?php _e('Preview')?></div>	
					
					
					<div class="uk-grid uk-grid-collapse uk-border-rounded uk-box-shadow-small">
						
						<div class="uk-width-1-1"> 
							<div class="uk-background-default a2020-border all uk-padding-small" >
								<a class="uk-padding-remove-horizontal ma-admin-site-logo">
									<img style="height:30px;" alt="<?php echo get_bloginfo( 'name' )?>" class="light" src="<?php echo $logo; ?>">
								</a>
							</div>
						</div>
						
						<div class="uk-width-medium uk-padding-small a2020-border left bottom right uk-background-default" style="min-height:600px;">
					
							<div id="menu_preview" class="drop-zone"  >
							
								<?php $this->build_menu_area() ?>
							
							</div>
						
						</div>
						
						<div class="uk-width-expand uk-padding a2020-border  bottom  uk-background-default" style="min-height:600px;">
							
							<div class="uk-margin-large"><?php $this->add_loader_placeholder()?></div>
							<div><?php $this->add_loader_placeholder()?></div>
							
						</div>
					
					</div>
				
				</div>
				
			</div>
			
		</div>
		
		<?php
		
	}
	
	
	
	public function build_menu_area(){
		
		?>
		
		<div v-if="user.currentMenu.items.length < 1" class="uk-text-meta">
			<?php _e('Add some menu items from the left toolbar to get started','admin2020')?>
		</div>
		
		
		<draggable 
		  v-model="user.currentMenu.items" 
		  group="menuitems" 
		  @start="drag=true" 
		  @end="drag=false" 
		  item-key="id">
		  <template #item="{element, index, parentindex = index}">
			
			<span style="display:block;">
				
				<div  v-if="element.type == 'sep'" class=" uipress-seperator">
					
					<div v-if="!element.name" class="sep_place_holder addable_menu_item uk-flex uk-flex-between">
						<span @click="editMenuItem(element)"><?php _e('Seperator','admin2020'); ?></span>
						
						<a href="#" class="add_menu_item uk-link-muted" 
						  @click="removeMenuItem(index)"
						  style="line-height: 18px;">
							  <span class="material-icons-outlined" style="font-size:18px;">
							  delete_forever
							</span>
						</a>
					</div>
					
					<div v-if="element.name.length > 0" class="uk-text-bold uk-text-emphasis sep_place_holder addable_menu_item uk-flex uk-flex-between">
						<span @click="editMenuItem(element)">{{element.name}}</span>
						
						<a href="#" class="add_menu_item uk-link-muted" 
						  @click="removeMenuItem(index)"
						  style="line-height: 18px;">
							  <span class="material-icons-outlined" style="font-size:18px;">
							  delete_forever
							</span>
						</a>
						
					</div>
					
				</div>
				
				<div v-if="element.type == 'menu' || element.type == 'submenu'" class="uk-border-rounded addable_menu_item "
				style="padding:5px;margin-bottom:10px"
				>
					
					<div class="uk-flex uk-flex-between uk-flex-middle">
						
						<div class="uk-flex">
							
							<div @click="element.expand = !element.expand" style="margin-right:10px;">
								<span v-if="element.expand" class="material-icons-outlined" 
								style="font-size: 18px;position: relative;top: 3px;">expand_more</span>
								<span v-if="!element.expand"  class="material-icons-outlined" 
								style="font-size: 18px;position: relative;top: 3px;">chevron_right</span>
							</div>
							
							
							<div  @click="editMenuItem(element)" style="cursor:pointer;">
								
								<span v-if="element.icon"  v-html="element.icon" style="font-size:16px;"></span>
							
								<span  class="uk-text-bold" v-html="element.name" ></span>
							
							</div>
						
						</div>
						
						
							
						<a href="#" class="add_menu_item uk-link-muted" 
						  @click="removeMenuItem(index)"
						  style="line-height: 18px;">
							  <span class="material-icons-outlined" style="font-size:18px;">
							  delete_forever
							</span>
						</a>
							
					</div>
					
				</div>
				
				
				<div v-if="element.expand" class="sub_menu_drag">
					
					<draggable 
					  v-model="element.submenu" 
					  group="menuitems" 
					  @start="drag=true" 
					  @end="drag=false"
					  item-key="name">
					  <template #item="{element, index, parentPlace = parentindex}" >
						  
						  <div class="uk-border-rounded addable_menu_item  uk-flex uk-flex-between uk-flex-middle" 
						  style="padding:5px;margin-bottom:5px">
						  
							  <span @click="editMenuItem(element)" style="cursor:pointer" v-html="element.name"></span>
							  
							  <a href="#" class="add_menu_item uk-link-muted" 
							  @click="removeSubMenuItem(index, parentPlace)"
							  style="line-height: 18px;">
							  	<span class="material-icons-outlined" style="font-size:18px;">
								  delete_forever
								</span>
							  </a>
							  
							  
						  </div>
						  
					  </template>
					</draggable>
					
				</div>
				
			</span>
			
			
			
			
			
		   </template>
		</draggable>
		
		
		<div id="edit-menu-item" uk-offcanvas="flip: true; overlay: true">
			<div class="uk-offcanvas-bar">
		
				<button class="uk-offcanvas-close" type="button" uk-close></button>
				<div class="uk-h4 uk-margin-remove-top"><?php _e('Edit Menu Item','admin2020')?></div>
				
				
				<form class="uk-form uk-form-stacked">
					<div class="uk-margin">
						<label class="uk-form-label uk-margin-small-bottom" for="form-stacked-text"><?php _e('Name','admin2020')?></label>
						<input class="uk-input" v-model="user.currentItem.name" type="text" placeholder="<?php _e('Name','admin2020')?>">
					</div>
					
					<div class="uk-margin" v-if="user.currentItem.type != 'sep'">
						<label class="uk-form-label uk-margin-small-bottom" for="form-stacked-text"><?php _e('Link','admin2020')?></label>
						<input class="uk-input" v-model="user.currentItem.href" type="text" placeholder="<?php _e('Link','admin2020')?>">
					</div>
					
					<div class="uk-margin" v-if="user.currentItem.type != 'sep'">
						<label class="uk-form-label uk-margin-small-bottom" for="form-stacked-text"><?php _e('Icon','admin2020')?></label>
						<input hidden class="uk-input" v-model="user.currentItem.icon" type="text" placeholder="<?php _e('Icon','admin2020')?>">
						
						<div class="uk-flex">
							<span v-if="user.currentItem.icon" class="uk-border-rounded a2020-border all" v-html="user.currentItem.icon" style="padding:5px 5px 0px 5px;margin-right:5px;"></span>
							<button class="uk-button uk-button-default uk-button-small" type="button"><?php _e('Change Icon...')?></button>
							<icon-select @icon-change="user.currentItem.icon = getdatafromIcon($event)"></icon-select>
							
						</div>
					</div>
				</form>
				
		
			</div>
		</div>
		
		
		<?php 
		
		
	}
	
	public function build_header(){
		
		$logo = esc_url($this->path.'/assets/img/default_logo.png');
		?>
		<div class="uk-grid-small " uk-grid >
			<div class="uk-width-auto">
				<div class="uk-h4 uk-margin-remove-bottom"><?php _e('Menu Creator','admin2020') ?></div>
				<a v-if="ui.editingMode" @click="ui.editingMode = false"href="#" class="uk-link-muted">
					<span class="material-icons-outlined" style="	font-size: 18px;position: relative;top: 4px;">
						chevron_left
						</span>
					<?php _e('Back to all menus','admin2020')?>
				</a>
			</div>
			<div class="uk-width-expand">
				
				<div class="uk-flex uk-flex-right uk-flex-middle" style="height:100%">
					
					<button class="uk-button uk-button-primary uk-button-small" @click="saveSettings()"><?php _e('Save','admin2020')?></button>
					
					<button class="uk-button uk-button-default uk-button-small a2020_make_light a2020_make_square uk-margin-small-left" aria-expanded="false">
						<span uk-icon="icon:settings;ratio:0.8" class="uk-icon"></span>
					</button>
					
					<div uk-dropdown="mode:click">
						<ul class="uk-nav uk-dropdown-nav">
							<li >
								<a href="#" @click="exportMenu(user.currentMenu)" >
									<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">file_download</span>
									<?php _e('Export','admin2020')?>
								</a>
								<a href="#" hidden id="uipress_export_menus"></a>
							</li>
							<li class="">
								<a href="#">
								<label>
									<span class="material-icons-outlined" style="font-size: 16px;position: relative;top: 3px;">file_upload</span>
									<?php _e('Import Menu','admin2020')?>
									<input hidden accept=".json" type="file" single="" id="uipress_import_menu" @change="import_menu()">
								</label>
								</a>
							</li>
							<li class="uk-nav-divider"></li>
							<li class="">
								<a href="#" class="uk-text-danger" @click="reset_settings()">
								<span class="material-icons-outlined" style="font-size: 16px;position: relative;top: 3px;">restart_alt</span>
								<?php _e('Reset Settings','admin2020')?></a>
							</li>
						</ul>
					</div>
				
				</div>
			</div>
		</div>
		<?php 
		
	}
	
	public function add_loader_placeholder(){
		
		?>
		
		<svg
		  role="img"
		  width="70%"
		  height="84"
		  aria-labelledby="loading-aria"
		  viewBox="0 0 340 84"
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
				<rect x="0" y="0" rx="3" ry="3" width="67" height="11" /> 
				<rect x="76" y="0" rx="3" ry="3" width="140" height="11" /> 
				<rect x="127" y="48" rx="3" ry="3" width="53" height="11" /> 
				<rect x="187" y="48" rx="3" ry="3" width="72" height="11" /> 
				<rect x="18" y="48" rx="3" ry="3" width="100" height="11" /> 
				<rect x="0" y="71" rx="3" ry="3" width="37" height="11" /> 
				<rect x="18" y="23" rx="3" ry="3" width="140" height="11" /> 
				<rect x="166" y="23" rx="3" ry="3" width="173" height="11" />
			</clipPath>
			<linearGradient id="fill">
			  <stop
				offset="0.599964"
				stop-color="#f3f3f3"
				stop-opacity="1"
			  >
				
			  </stop>
			</linearGradient>
		  </defs>
		</svg>
		
		<?php 
	}
	
	
}

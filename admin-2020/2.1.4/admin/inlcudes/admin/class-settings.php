<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Admin_2020_settings {
	
	public function __construct($version, $path, $utilities, $plugin_name) {
	
		$this->version = $version;
		$this->path = $path;
		$this->utils = $utilities;
		$this->plugin_name = $plugin_name;
	
	}
	
	/**
	 * Loads Admin 2020 settings on init
	 * @since 1.4
	 */
	
	public function start(){
		
		///REGISTER THIS COMPONENT
		add_filter('admin2020_register_component', array($this,'register'));
		
		add_action('plugins_loaded', array( $this, 'load_modules' ));
		add_action('admin_init', array( $this, 'add_settings' ),0);
		add_action('admin_menu', array( $this, 'add_menu_items'));
		add_action('admin_head', array( $this, 'admin_color_scheme'));
		add_action('network_admin_menu', array( $this, 'add_menu_items_network'));
		add_action('admin_enqueue_scripts', array( $this, 'add_scripts' ),0);
		add_filter('plugin_row_meta' , array($this,'add_settings_link'),10,2 );
		
		
		add_action('wp_ajax_a2020_save_modules', array($this,'a2020_save_modules'));
		
		add_action('wp_ajax_a2020_remove_licence', array($this,'a2020_remove_licence'));
		add_action('wp_ajax_a2020_save_videos', array($this,'a2020_save_videos'));
		add_action('wp_ajax_a2020_delete_video', array($this,'a2020_delete_video'));
		add_action('wp_ajax_a2020_get_users_and_roles', array($this,'a2020_get_users_and_roles'));
		add_action('wp_ajax_a2020_save_settings_from_app', array($this,'a2020_save_settings_from_app'));
		add_action('wp_ajax_a2020_reset_settings', array($this,'a2020_reset_settings'));
		
		
		
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
		$data['title'] = __('General','admin2020');
		$data['option_name'] = 'admin2020_general';
		$data['description'] = __('General settings for UIPress','admin2020');
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
		
		
		////CHECK IF ACTIVATED
		$key = $this->utils->get_option('activation','key');
		$activated = true;
		
		if($key != "" && !get_transient( 'admin2020_activated')){
			$activated = false;
		} 
		
		if($key == "" || !$key || !get_transient( 'admin2020_activated')){
			$activated = false;
		}
		
		
		if($activated) {
			$temp = array();
			$temp['name'] = __('UIpress Licence','admin2020');
			$temp['description'] = __("UIPress is activated. If you want to remove the licence the click remove.",'admin2020');
			$temp['type'] = 'licence-remove';
			$temp['optionName'] = 'key'; 
			$temp['value'] = $this->utils->get_option('activation',$temp['optionName'], true);
			$settings[] = $temp;
		}
		
		$temp = array();
		$temp['name'] = __('Global font','admin2020');
		$temp['description'] = __("Choose your desired font from google fonts.",'admin2020');
		$temp['type'] = 'font-select';
		$temp['optionName'] = 'font'; 
		$temp['value'] = $this->utils->get_option('admin2020_general',$temp['optionName'], true);
		$settings[] = $temp;
		
		if(is_network_admin()){
		
			$temp = array();
			$temp['name'] = __('Override Subsite Settings','admin2020');
			$temp['description'] = __("If this is enabled then all the settings here will be applied to your subsites and they will not be able to be changed from a subsite.",'admin2020');
			$temp['type'] = 'switch';
			$temp['optionName'] = 'network_override'; 
			$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], false);
			$settings[] = $temp;
			
		}
		
		
		return $settings;
		
	}
	
	/**
	* Removes default wp color schema
	* @since 1.4
	*/
	public function admin_color_scheme() {
		
		///LOAD CUSTOM FONT IF SELECTED
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$font = $this->utils->get_option($optionname,'font');
		
		if(!$font){
			return;
		}
		
		//update_option( 'admin2020_settings', array());
		
		if($font[0] && $font[1]){
			
			$formattedfont = "'" . $font[0] . "', " . $font[1];
			
			echo '<style type="text/css">';
			echo "@import url('https://fonts.googleapis.com/css2?family=" . $font[0] . "&display=swap');";
			echo 'body, .a2020-admin-bar, .uk-navbar-nav > li > a, .uk-navbar-item, .uk-navbar-toggle, .admin2020_menu, #wpadminbar * {font-family: ' . $formattedfont . '}';
			echo 'h1, .uk-h1, h2, .uk-h2, h3, .uk-h3, h4, .uk-h4, h5, .uk-h5, h6, .uk-h6, .uk-heading-small, .uk-heading-medium, .uk-heading-large, .uk-heading-xlarge, .uk-heading-2xlarge {font-family: ' . $formattedfont . '}';
			echo '</style>';
		}
		
		
	}
	
	/**
	* Adds link to admin 2020 settings page
	* @since 1.4
	*/
	public function add_settings_link( $plugin_meta, $plugin_file_name ) {
	
		if ($plugin_file_name == "admin-2020/admin-2020.php"){
		  $plugin_meta[] = '<a href="admin.php?page=admin2020-settings">'.__('Settings','admin2020').'</a>';
		  
		  if(!$this->utils->is_premium()){
			  $plugin_meta[] = '<a href="https://admintwentytwenty.com/pricing/" target="_BLANK" class="uk-text-success uk-text-bold">'.__('Upgrade to Pro','admin2020').'</a>';
		  }
		  
		}
		return $plugin_meta;
	}
	
	
	
	
	
	/**
	* Enqueue Admin 2020 settings scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
		
		if(isset($_GET['page'])) {
			
			if($_GET['page'] == 'admin2020-settings'){
				
				
				$args = array();
				$output = 'objects';  
				$post_types = get_post_types( $args, $output );
				
				if($post_types == ""){
					$post_types = array();
				}
				
				$thePostTypes = array();
				
				foreach ($post_types as $posy){
					
					$name = $posy->name;
					$label = $posy->label;
					$temp = array();
					$temp['name'] = $name;
					$temp['label'] = $label;
					array_push($thePostTypes, $temp);
					
				}
				
				////CHECK IF ACTIVATED
				$key = $this->utils->get_option('activation','key');
				$activated = 'true';
				
				if($key != "" && !get_transient( 'admin2020_activated')){
					$activated = 'false';
				} 
				
				if($key == "" || !$key || !get_transient( 'admin2020_activated')){
					$activated = 'false';
				}
				
				//CODEJAR
				wp_enqueue_script('a2020-codejar-js', $this->path . 'assets/js/codejar/codejar-alt.js', array('jquery'), $this->version);
				wp_enqueue_script('a2020-highlight-js', $this->path . 'assets/js/codejar/highlight.js', array('jquery'), $this->version);
				wp_register_style('a2020-codejar-css', $this->path . 'assets/js/codejar/highlight.css', array(),  $this->version);
				wp_enqueue_style('a2020-codejar-css');
		  
				wp_enqueue_script('admin2020-settings', $this->path . 'assets/js/admin2020/settings.min.js', array('jquery'), $this->version);
				wp_localize_script('admin2020-settings', 'admin2020_settings_ajax', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'security' => wp_create_nonce('admin2020-settings-security-nonce'),
				));
				
				
				$allOptions = $this->build_options_object();
				
				
				
				//echo '<pre>' . print_r( $allOptions, true ) . '</pre>';
				
				wp_enqueue_script('a2020-settings-app', $this->path . 'assets/js/admin2020/settings-app.min.js', array('jquery'), $this->version, true);
				wp_localize_script('a2020-settings-app', 'a2020_settings_app_ajax', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'security' => wp_create_nonce('a2020-settings-app-security-nonce'),
					'optionsObject' => json_encode($allOptions),
					'postTypes' => json_encode($thePostTypes),
					'network' => is_network_admin(), 
					'premium' => $this->utils->is_premium(), 
					'activated' => $activated,
				));
				
				wp_enqueue_script('admin-bar-settings-js', $this->path . 'assets/js/admin2020/admin-bar-settings.min.js', array('jquery'));
				
				
				///COLOR PICKER
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker');
				
			}
		}
	  
	}
	
	
	/**
	* Builds options object for vue
	* @since 1.4
	*/
	public function build_options_object(){
		
		$components = array();
		$components = apply_filters( 'admin2020_register_component', $components );
		$this->components = $components;
		
		if(is_network_admin()){
			$a2020_options = get_option( 'admin2020_settings_network' );
		} else {
			$a2020_options = get_option( 'admin2020_settings' );
		}
		
		$allOptions = array();
			
		foreach($components as $component) { 
				
				$temp = array();
				$info = $component->component_info();
				$title = $info['title'];
				
				$temp['title'] = $info['title'];
				$temp['description'] = $info['description'];
				$temp['option_name'] = $info['option_name'];
				
				$optionname = $info['option_name'];
				
				if(isset($a2020_options['modules'][$optionname]['status'])){
					$temp['status'] = $a2020_options['modules'][$optionname]['status'];
				} else {
					$temp['status'] = 'true';
				}
				
				if(method_exists($component, 'get_settings_options' )){
					$temp['componentOptions'] = $component->get_settings_options();
				}
				
				
				$allOptions[] = $temp;
				
		}
		
		return $allOptions;
		
	}
	
	
	
	
	
	/**
	* remove licence from ajax
	* @since 1.4
	*/
	
	public function a2020_remove_licence(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-settings-app-security-nonce', 'security') > 0) {
			
			$network = $this->utils->clean_ajax_input($_POST['network']);
			
			if($network === 'true'){
				$a2020_options = get_option( 'admin2020_settings_network' );
			} else {
				$a2020_options = get_option( 'admin2020_settings' );
			}
			$productkey = $a2020_options['modules']['activation']['key'];
			$a2020_options['modules']['activation']['key'] = "";
			
			if(isset($a2020_options['modules']['activation']['instance'])){
				$instance = $a2020_options['modules']['activation']['instance'];
				$a2020_options['modules']['activation']['instance'] = "";
				
				$ch = curl_init();
			  
				curl_setopt($ch, CURLOPT_URL,"https://api.lemonsqueezy.com/v1/licenses/deactivate?license_key={$productkey}&instance_id={$instance}");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				  "Accept: application/json",
				));
				
				$server_output = curl_exec($ch);
				curl_close ($ch);
			}
			
			if($network === 'true'){
				update_option( 'admin2020_settings_network', $a2020_options);
			} else {
				update_option( 'admin2020_settings', $a2020_options);
			}
			
			$returndata = array();
			$returndata['success'] = true;
			$returndata['message'] = __('Licence removed','admin2020');
			echo json_encode($returndata);
			
			die();
			
			
		}
		die();	
		
	}
	
	
	/**
	* Save admin 2020 settings from ajax
	* @since 1.4
	*/
	
	public function a2020_save_settings_from_app(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-settings-app-security-nonce', 'security') > 0) {
			
			$options = $_POST['allsettings'];
			$network = $this->utils->clean_ajax_input($_POST['a2020network']);
			
			if($network === 'true'){
				$a2020_options = get_option( 'admin2020_settings_network');
			} else {
				$a2020_options = get_option( 'admin2020_settings');
			}
			
			if($options == "" || !is_array($options)){
				$message = __("No options supplied to save",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			foreach($options as $option){
				
				$module_name = $option['option_name'];
				$componentOptions = $option['componentOptions'];
				$a2020_options['modules'][$module_name]['status'] = $option['status'];
				
				foreach($componentOptions as $childOption){
					$optionname = $childOption['optionName'];
					$value = $childOption['value'];
					$a2020_options['modules'][$module_name][$optionname] = $value;
				}
				
				
			}
			
			if(is_array($a2020_options)){
				if($network === 'true'){
					update_option( 'admin2020_settings_network', $a2020_options);
				} else {
					update_option( 'admin2020_settings', $a2020_options);
				}
				$returndata = array();
				$returndata['success'] = true;
				$returndata['message'] = __("Settings saved. You may need to refresh for changes to take effect",'admin2020');;
				$returndata['saved'] = $options;
				echo json_encode($returndata);
			} else {
				$message = __("Something went wrong",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			
			
			die();
			
			
		}
		die();	
		
	}
	
	
	/**
	* Reset admin 2020 settings from ajax
	* @since 1.4
	*/
	
	public function a2020_reset_settings(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-settings-app-security-nonce', 'security') > 0) {
			
			$network = $this->utils->clean_ajax_input($_POST['a2020network']);
			
			if($network === 'true'){
				update_option( 'admin2020_settings_network', array());
			} else {
				update_option( 'admin2020_settings', array());
			}
			
			$returndata = array();
			$returndata['success'] = true;
			$returndata['message'] = __("Settings reset",'admin2020');
			$returndata['newoptions'] = $this->build_options_object();
			echo json_encode($returndata);
			
			
			
			die();
			
			
		}
		die();	
		
	}
	
	
	

	
	/**
	* Fetches users and roles
	* @since 2.0.8
	*/
	
	public function a2020_get_users_and_roles(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-settings-app-security-nonce', 'security') > 0) {
			
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
	* Saves custom user videos
	* @since 1.4
	*/
	
	public function a2020_save_videos(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-settings-security-nonce', 'security') > 0) {
			
			$video = $this->utils->clean_ajax_input($_POST['data']);
			$a2020_options = get_option( 'admin2020_settings' );
			
			if($video == "" || !is_array($video)){
				$message = __("No video supplied to save",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			if(isset($a2020_options['modules']['admin2020_admin_overview']['videos'])){
				
				$currentvideos = $a2020_options['modules']['admin2020_admin_overview']['videos'];
				
				foreach($currentvideos as $avideo){
					if($video[0] == $avideo[0]){
						$message = __("Video name must be unique",'admin2020');
						echo $this->utils->ajax_error_message($message);
						die();
					}
				}
				
				array_push($currentvideos,$video);
				$a2020_options['modules']['admin2020_admin_overview']['videos'] = $currentvideos;
				
				
			} else {
				
				$a2020_options['modules']['admin2020_admin_overview']['videos'] = array($video);
			}
			
			
			
			if(is_array($a2020_options)){
				
				ob_start();
				
				foreach ($a2020_options['modules']['admin2020_admin_overview']['videos'] as $video) { ?>
					<tr>
						<td><?php echo $video[0]?></td>
						<td><?php echo $video[1]?></td>
						<td><?php echo $video[2]?></td>
						<td><?php echo $video[3]?></td>
						<td><a href="#" class="uk-button-danger uk-icon-button" onclick="a2020_delete_video('<?php echo $video[0]?>')" style="width:25px;height:25px;" uk-icon="icon:trash;ratio:0.8"></a></td>
					</tr>
				<?php } 
				
				
				$table = ob_get_clean();
				
				update_option( 'admin2020_settings', $a2020_options);
				$returndata = array();
				$returndata['success'] = true;
				$returndata['message'] = __('Video saved','admin2020');
				$returndata['content'] = $table;
				echo json_encode($returndata);
			} else {
				$message = __("Something went wrong",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			
			
			die();
			
			
		}
		die();	
		
	}
	
	
	public function a2020_delete_video(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-settings-security-nonce', 'security') > 0) {
			
			$video_name = $this->utils->clean_ajax_input($_POST['name']);
			$a2020_options = get_option( 'admin2020_settings' );
			
			if($video_name == ""){
				$message = __("No video supplied to delete",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			if(isset($a2020_options['modules']['admin2020_admin_overview']['videos'])){
				
				$currentvideos = $a2020_options['modules']['admin2020_admin_overview']['videos'];
				$tempvideos = array();
				
				foreach($currentvideos as $avideo){
					if($video_name != $avideo[0]){
						array_push($tempvideos,$avideo);
					}
				}
				
				$a2020_options['modules']['admin2020_admin_overview']['videos'] = $tempvideos;
				
				
			} 
			
			
			
			if(is_array($a2020_options)){
				
				ob_start();
				
				foreach ($tempvideos as $video) { ?>
					<tr>
						<td><?php echo $video[0]?></td>
						<td><?php echo $video[1]?></td>
						<td><?php echo $video[2]?></td>
						<td><?php echo $video[3]?></td>
						<td><a href="#" class="uk-button-danger uk-icon-button" onclick="a2020_delete_video('<?php echo $video[0]?>')" style="width:25px;height:25px;" uk-icon="icon:trash;ratio:0.8"></a></td>
					</tr>
				<?php } 
				
				
				$table = ob_get_clean();
				
				update_option( 'admin2020_settings', $a2020_options);
				$returndata = array();
				$returndata['success'] = true;
				$returndata['message'] = __('Video deleted','admin2020');
				$returndata['content'] = $table;
				echo json_encode($returndata);
			} else {
				$message = __("Something went wrong",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			
			
			die();
			
			
		}
		die();	
		
	}
	
	/**
	 * Adds admin 2020 settings
	 * @since 1.4
	 */
	
	public function add_settings(){
		
		register_setting( 'admin2020_global_settings', 'admin2020_settings' );
		register_setting( 'admin2020_global_settings', 'admin2020_settings_network' );
		
	}
	
	/**
	 * Loads admin2020 modules
	 * @since 1.4
	 */
	
	public function load_modules(){
		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/admin-bar.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/admin-menu.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/admin-theme.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/admin-login.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/admin-overview.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/admin-analytics.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/admin-woocommerce.php';
		
		
		$admin_bar = new Admin_2020_module_admin_bar($this->version,$this->path,$this->utils);
		$admin_bar->start();
		
		$admin_menu = new Admin_2020_module_admin_menu($this->version,$this->path,$this->utils);
		$admin_menu->start();
		
		$admin_theme = new Admin_2020_module_admin_theme($this->version,$this->path,$this->utils);
		$admin_theme->start();
		
		$admin_login = new Admin_2020_module_admin_login($this->version,$this->path,$this->utils);
		$admin_login->start();
		
		$admin_overview_new = new Uipress_module_overview($this->version,$this->path,$this->utils);
		$admin_overview_new->start();
		
		$admin_analytics = new uipress_module_google_analytics($this->version,$this->path,$this->utils);
		$admin_analytics->start();
		
		$admin_commerce = new uipress_module_woocommerce($this->version,$this->path,$this->utils);
		$admin_commerce->start();
		
		
		
		///PREMIUM
		if($this->utils->is_premium()){
			
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/premium/admin-folders.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/premium/admin-content.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/premium/admin-advanced.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/premium/admin-menu-editor.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/premium/admin-menu-creator.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/premium/admin-overview-cards.php';
			//require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/premium/admin-pages.php';
			
			
			$admin_folders = new Admin_2020_module_admin_folders($this->version,$this->path,$this->utils);
			$admin_folders->start();
			
			$admin_content = new Admin_2020_module_admin_content($this->version,$this->path,$this->utils);
			$admin_content->start();
	
			$admin_advanced = new Admin_2020_module_admin_advanced($this->version,$this->path,$this->utils);
			$admin_advanced->start();
			
			$admin_menu_editor = new Admin_2020_module_admin_menu_editor($this->version,$this->path,$this->utils);
			$admin_menu_editor->start();
			
			$admin_menu_creator = new Admin_2020_module_admin_menu_creator($this->version,$this->path,$this->utils, $admin_menu);
			$admin_menu_creator->start();
			
			//$adminpages = new Admin_2020_module_admin_pages($this->version,$this->path,$this->utils);
			//$adminpages->start();
			
			$cards = new Admin_2020_overview_cards();
			$cards->start(); 
			
		}
		
	}	
	
	
	/**
	 * Renders Admin Pages
	 * @since 1.4
	 */
	
	public function add_menu_items(){
		
		$enabled = false;
		if(is_multisite() && $this->utils->is_site_wide('admin-2020/admin-2020.php')){
			
			$a2020_network_options = get_blog_option(get_main_network_id(),'admin2020_settings_network');
			
			if(isset($a2020_network_options['modules']['admin2020_general']['network_override'])){
				$enabled = $a2020_network_options['modules']['admin2020_general']['network_override'];
				
			}
		}
		
		if($enabled == 'true'){
			return;
		}
		//add_submenu_page( 'admin2020-top-level', __('Settings','admin2020'), __('Settings','admin2020'), 'manage_options', 'admin2020-settings', array($this,'options_page_render'));
		add_options_page( 'UiPress', 'UiPress', 'manage_options', 'admin2020-settings', array($this,'options_page_render') );
		
	}
	
	/**
	 * Renders Admin Pages
	 * @since 1.4
	 */
	
	public function add_menu_items_network(){
		
		add_submenu_page(
			'settings.php', // Parent element
			'UiPress', // Text in browser title bar
			'UiPress', // Text to be displayed in the menu.
			'manage_options', // Capability
			'admin2020-settings', // Page slug, will be displayed in URL
			array($this,'options_page_render')  // Callback function which displays the page
		);
		
	}
	
	/**
	 * Renders Options Page
	 * @since 1.4
	 */
	
	public function options_page_render(){
		
		wp_enqueue_media();
		?>
		
		<script type="module">
			
			//import { CodeJar } from <?php echo "'" . $this->path . "assets/js/codejar/codejar.js" . "'" ?>;
			
		</script>
			
		<div class="wrap" id="a2020-settings-app" style="padding:0;">
				
			<div class="uk-padding-small a2020-border bottom uk-background-default"><?php $this->build_header();?></div>
			
			
			<div v-if="search.string.length < 1"class="">
				
				
				<div class="uk-grid ">
					<div class="uk-width-medium uk-background-default uk-height-viewport" v-if="!isSmallScreen()">
						<div class="uk-padding">
							<ul class="uk-nav uk-nav-default">
								<template v-for="option in settings">
									<li :class="{'uk-active' : activeTab == option.option_name}" >
										<a href="#" class="uk-text-bold"  @click="activeTab = option.option_name">{{option.title}}</a>
									</li>
								</template>
							</ul>
						</div>
					</div>
					
					<template v-if="isSmallScreen()" >
						
						<div class="float-menu-icon">
							<a href="#" uk-toggle="target:#offcanvas-settings-menu" ><span class="material-icons-outlined">
								menu_open
								</span></a>
						</div>
						
						<div id="offcanvas-settings-menu" uk-offcanvas >
							<div class="uk-offcanvas-bar">
						
								<ul class="uk-nav uk-nav-default">
									<template v-for="option in settings">
										<li :class="{'uk-active' : activeTab == option.option_name}" >
											<a href="#" class="uk-text-bold"  @click="activeTab = option.option_name">{{option.title}}</a>
										</li>
									</template>
								</ul>
						
							</div>
						</div>
					
					</template>
					
					
					<div class="uk-width-expand">
						<div class="uk-padding uk-container uk-container-small">
							<template v-for="option in settings">
								
								<!-- MODULE OPTIONS -->
								
								<div v-if="option.option_name == activeTab">
									<div  class="uk-grid uk-grid uk-margin-bottom "  v-if="option.option_name != 'admin2020_general'">
										<div class="uk-width-1-2">
											<div class="uk-h5  uk-margin-small-bottom uk-text-bold">{{option.title}} <?php _e('module','admin2020')?></div>
											<div class="uk-text-meta uk-margin-bottom">{{option.description}}</div>
										</div>
										<div class="uk-width-1-2">
											<div class="uk-width-1-1@ uk-width-1-3@m">
												<label class="admin2020_switch">
													<input class="a2020_module"  type="checkbox" v-model="option.status">
													<span class="admin2020_slider constant_dark"></span>
												</label>
											</div>	
										</div>
									</div>
									
									<div v-if="option.componentOptions">
										<template v-if="option.status == 'true' || option.status === true" v-for="setting in option.componentOptions">
											<div  class="uk-grid uk-margin-bottom " >
												<div class="uk-width-1-2">
													<div class="uk-h5  uk-margin-small-bottom uk-text-bold uk-flex uk-flex-middle">
														<span class="uk-margin-small-right">{{setting.name}}</span>
														<span v-if="setting.premium && !premium" class="uk-flex uk-flex-middle a2020-post-label" style="background-color: rgb(50, 210, 150) !important;color:#fff" >
															<span class="material-icons-outlined uk-margin-small-right" style="font-size: 16px;">redeem</span><?php _e('Pro feature','admin2020')?>
														</span>
													</div>
													<div class="uk-text-meta uk-margin-bottom">{{setting.description}}</div>
												</div>
												<div class="uk-width-1-2">
													
													<!-- SWITCH -->
													<div v-if="setting.type == 'switch'" class="uk-width-1-1@ uk-width-1-3@m">
														<label class="admin2020_switch">
															<input class="a2020_module"  type="checkbox" v-model="setting.value">
															<span class="admin2020_slider constant_dark"></span>
														</label>
													</div>	
													
													<!-- IMAGE -->
													<div v-if="setting.type == 'image'">
														<div class="uk-grid uk-grid-small">
															
															<div class="uk-width-1-2">
																<input class="uk-input uk-margin-small-bottom a2020-white-input " placeholder="<?php _e('Image URL','admin2020')?>" v-model="setting.value">
																<button class="uk-button uk-button-default uk-background-default" @click="chooseImage(setting)">
																	<?php _e('Choose Image','admin2020')?>
																</button>
															</div>
															<div v-if="setting.value" class="uk-width-auto">
																<img class="a2020-border all" :src="setting.value" style="max-height:95px;border-radius:4px;">
															</div>
														</div>
													</div>	
													
													<!-- COLOR -->
													<div v-if="setting.type == 'color'">
																
														<label class="a2020-color-picker"
														v-bind:style="{'background-color' : setting.value}">
														
															<input class="" 
															type="color"
															v-model="setting.value" style="visibility: hidden;">
															
														</label>
														
														<input class="a2020-white-input" 
														type="text"
														placeholder="<?php _e('Hex code','admin2020')?>"
														v-model="setting.value" style="padding-left:50px;width:150px;">
														
													</div>	
													
													<!-- ROLE SELECT -->
													<div v-if="setting.type == 'user-role-select'">
														
														<multi-select :selected="setting.value"
														:name="'<?php _e('Choose users / roles','admin2020')?>'"
														:single='false'
														:placeholder="'<?php _e('Search users and roles...','admin2020')?>'"></multi-select>
													
													</div>
													
													<!-- POST TYPE SELECT -->
													<div v-if="setting.type == 'post-type-select'">
														
														<multi-select-posts :selected="setting.value"
														:name="'<?php _e('Choose posts and CPTs' ,'admin2020')?>'"
														:single='false'
														:placeholder="'<?php _e('Search posts and CPTs...','admin2020')?>'"></multi-select-posts>
														
													</div>
													
													<!-- NUMBER -->
													<div v-if="setting.type == 'number'">
														<input class="" type="number" v-model="setting.value" >
													</div>
													
													<!-- LICENCE REMOVE -->
													<div v-if="setting.type == 'licence-remove'">
														<button class="uk-button uk-button-default uk-background-default" @click="removeLicence()"><?php _e('Remove licence','admin2020')?></button>
													</div>
													
													<!-- FONT SELECT-->
													<div v-if="setting.type == 'font-select'">
														
														<font-select :selected="setting.value"
														:name="'<?php _e('font','admin2020')?>'"
														:single='true'
														:placeholder="'<?php _e('Search google fonts...','admin2020')?>'"></font-select>
													
													</div>
													
													<!-- CODE BLOCK-->
													<div v-if="setting.type == 'code-area-css'">
														
														<code-flask  :language="setting.language"  :usercode="setting.value" 
														@code-change="setting.value = getDataFromComp(setting.value, $event)"></code-flask>
														
													</div>
													
													<!-- TEXT -->
													<div v-if="setting.type == 'text'">
														<input class="" type="text" v-model="setting.value" >
													</div>
													
													<!-- MULTIPLE TEXT -->
													<div v-if="setting.type == 'multiple-text'">
														<button class="uk-button uk-button-default uk-button-small uk-margin uk-background-default" @click="setting.value.push('')">
														<?php _e('Add Script','admin2020')?></button>
														
														
														<div v-for="(ascript,index) in setting.value">
															<div class="uk-grid uk-grid-small">
																<div class="uk-width-expand uk-margin">
																	<input placeholder="<?php _e('URL to script','admin2020')?>" 
																	class="uk-input uk-form-small" v-model="setting.value[index]" type="text">
																</div>
																<div class="uk-width-auto">
																	<button @click="setting.value.splice(index, 1)"
																	class="uk-button uk-button-default uk-button-small uk-background-default"><?php _e('Remove','admin2020')?></button>
																</div>
															</div>
														</div>
													</div>
													
													
													
													<!-- TEXT / CODE -->
													<div v-if="setting.type == 'text-code-input'">
														<ul v-if="premium" uk-tab>
															<li><a href="#"><?php _e('Text','admin2020') ?></a></li>
															<li><a href="#"><?php _e('HTML','admin2020') ?></a></li>
															<li><a href="#"><?php _e('Preview','admin2020') ?></a></li>
														</ul>
														
														<ul v-if="premium" class="uk-switcher uk-margin">
															<li>
																<textarea class="uk-textarea uk-background-default uk-border-rounded"  style="height:200px;" v-model="setting.value" 
																placeholder="<?php _e('Custom welcome message...','admin2020')?>"></textarea>
															</li>
															
															<li>
																<code-flask  :language="setting.language"  :usercode="setting.value" 
																@code-change="setting.value = getDataFromComp(setting.value, $event)"></code-flask>
															</li>
															
															<li v-html="setting.value" >
															
															</li>
														</ul>
													</div>
													
													
													
												</div>
											</div>
											
											
										</template>
									</div>
									
									
								</div>
								<!-- MODULE OPTIONS -->
									
									
							</template>
						</div>
					</div>
				</div>
			</div>
			
			
			
			
			<!-- SEARCH RESULTS -->
			<div v-if="search.string.length > 0"class="">
				
				
				
				<div class="uk-grid ">
					<div class="uk-width-expand">
						<div class="uk-padding uk-container uk-container-small">
							<div class="uk-h4 uk-margin-large-bottom"><?php _e('Search results for','admin2020')?> <strong>{{search.string}}</strong></div>
							<template v-for="option in settings">
								
								<!-- MODULE OPTIONS -->
								
								<div >
									<div  class="uk-grid uk-grid uk-margin-bottom "  v-if="option.title.includes(search.string) || option.description.includes(search.string)">
										<div class="uk-width-1-2">
											<div class="uk-h5  uk-margin-small-bottom uk-text-bold">
												<span class="uk-label uk-margin-small-right">{{option.title}}</span>
												{{option.title}} <?php _e('module','admin2020')?></div>
											<div class="uk-text-meta uk-margin-bottom">{{option.description}}</div>
										</div>
										<div class="uk-width-1-2">
											<div class="uk-width-1-1@ uk-width-1-3@m">
												<label class="admin2020_switch">
													<input class="a2020_module"  type="checkbox" v-model="option.status">
													<span class="admin2020_slider constant_dark"></span>
												</label>
											</div>	
										</div>
									</div>
									
									
									
									
									<div v-if="option.componentOptions">
										<template v-for="setting in option.componentOptions">
											<div  class="uk-grid uk-margin-bottom " v-if="setting.name.includes(search.string) || setting.description.includes(search.string)">
												<div class="uk-width-1-2">
													<div class="uk-h5  uk-margin-small-bottom uk-text-bold">
														
														<span class="uk-label uk-margin-small-right">{{option.title}}</span>
														{{setting.name}}</div>
													<div class="uk-text-meta uk-margin-bottom">{{setting.description}}</div>
												</div>
												<div class="uk-width-1-2">
													
													<!-- SWITCH -->
													<div v-if="setting.type == 'switch'" class="uk-width-1-1@ uk-width-1-3@m">
														<label class="admin2020_switch">
															<input class="a2020_module"  type="checkbox" v-model="setting.value">
															<span class="admin2020_slider constant_dark"></span>
														</label>
													</div>	
													
													<!-- IMAGE -->
													<div v-if="setting.type == 'image'">
														<div class="uk-grid uk-grid-small">
															
															<div class="uk-width-1-2">
																<input class="uk-input uk-margin-small-bottom a2020-white-input " placeholder="<?php _e('Image URL','admin2020')?>" v-model="setting.value">
																<button class="uk-button uk-button-default uk-background-default" @click="chooseImage(setting)">
																	<?php _e('Choose Image','admin2020')?>
																</button>
															</div>
															<div v-if="setting.value" class="uk-width-auto">
																<img class="a2020-border all" :src="setting.value" style="max-height:95px;border-radius:4px;">
															</div>
														</div>
													</div>	
													
													<!-- COLOR -->
													<div v-if="setting.type == 'color'">
																
														<label class="a2020-color-picker"
														v-bind:style="{'background-color' : setting.value}">
														
															<input class="" 
															type="color"
															v-model="setting.value" style="visibility: hidden;">
															
														</label>
														
														<input class="a2020-white-input" 
														type="text"
														v-model="setting.value" style="padding-left:50px;width:150px;">
														
													</div>	
													
													<!-- ROLE SELECT -->
													<div v-if="setting.type == 'user-role-select'">
														
														<multi-select :selected="setting.value"
														:name="'<?php _e('Choose users / roles','admin2020')?>'"
														:single='false'
														:placeholder="'<?php _e('Search users and roles...','admin2020')?>'"></multi-select>
													
													</div>
													
													<!-- POST TYPE SELECT -->
													<div v-if="setting.type == 'post-type-select'">
														
														<multi-select-posts :selected="setting.value"
														:name="'<?php _e('Choose posts and CPTs' ,'admin2020')?>'"
														:single='false'
														:placeholder="'<?php _e('Search posts and CPTs...','admin2020')?>'"></multi-select-posts>
														
													</div>
													
													<!-- NUMBER -->
													<div v-if="setting.type == 'number'">
														<input class="" type="number" v-model="setting.value" >
													</div>
													
													<!-- LICENCE REMOVE -->
													<div v-if="setting.type == 'licence-remove'">
														<button class="uk-button uk-button-default uk-background-default" @click="removeLicence()"><?php _e('Remove licence','admin2020')?></button>
													</div>
													
													<!-- FONT SELECT-->
													<div v-if="setting.type == 'font-select'">
														
														<font-select :selected="setting.value"
														:name="'<?php _e('font','admin2020')?>'"
														:single='true'
														:placeholder="'<?php _e('Search google fonts...','admin2020')?>'"></font-select>
													
													</div>
													
													<!-- CODE BLOCK-->
													<div v-if="setting.type == 'code-area-css'">
														
														<code-flask  :language="setting.language"  :usercode="setting.value" 
														@code-change="setting.value = getDataFromComp(setting.value, $event)"></code-flask>
														
													</div>
													
													<!-- TEXT -->
													<div v-if="setting.type == 'text'">
														<input class="" type="text" v-model="setting.value" >
													</div>
													
													<!-- MULTIPLE TEXT -->
													<div v-if="setting.type == 'multiple-text'">
														<button class="uk-button uk-button-default uk-button-small uk-margin uk-background-default" @click="setting.value.push('')">
														<?php _e('Add Script','admin2020')?></button>
														
														
														<div v-for="(ascript,index) in setting.value">
															<div class="uk-grid uk-grid-small">
																<div class="uk-width-expand uk-margin">
																	<input placeholder="<?php _e('URL to script','admin2020')?>" 
																	class="uk-input uk-form-small" v-model="setting.value[index]" type="text">
																</div>
																<div class="uk-width-auto">
																	<button @click="setting.value.splice(index, 1)"
																	class="uk-button uk-button-default uk-button-small uk-background-default"><?php _e('Remove','admin2020')?></button>
																</div>
															</div>
														</div>
													</div>
													
													<!-- TEXT /CODE -->
													<div v-if="setting.type == 'text-code-input'">
														
														
														<ul v-if="premium" uk-tab>
															<li><a href="#"><?php _e('Text','admin2020') ?></a></li>
															<li><a href="#"><?php _e('HTML','admin2020') ?></a></li>
															<li><a href="#"><?php _e('Preview','admin2020') ?></a></li>
														</ul>
														
														<ul v-if="premium" class="uk-switcher uk-margin">
															<li>
																<textarea class="uk-textarea uk-background-default uk-border-rounded"  style="height:200px;" v-model="setting.value" 
																placeholder="<?php _e('Custom welcome message...','admin2020')?>"></textarea>
															</li>
															
															<li>
																<code-flask  :language="setting.language"  :usercode="setting.value" 
																@code-change="setting.value = getDataFromComp(setting.value, $event)"></code-flask>
															</li>
															
															<li v-html="setting.value" >
															
															</li>
														</ul>
													</div>
													
												</div>
											</div>
											
											
										</template>
									</div>
									
									
								</div>
								<!-- MODULE OPTIONS -->
									
									
							</template>
						</div>
					</div>
				</div>
			</div>	
			<!-- SEARCH RESULTS -->
		</div>
		<?php
	}
	
	/**
	 * Renders a2020-settings-app-security-nonce
	 * @since 1.4
	 */
	
	public function build_header(){
		
		//$a2020_options = get_option( 'admin2020_settings');
		//echo '<pre>' . print_r( $a2020_options, true ) . '</pre>';
		$logo = esc_url($this->path.'/assets/img/default_logo.png');
		?>
		<div class="uk-grid-small " uk-grid>
			<div class="uk-width-auto">
				<img src="<?php echo $logo ?>" width="40">
			</div>
			<div class="uk-width-auto">
				<div class="uk-h4 uk-margin-remove-bottom"><?php echo $this->plugin_name ?></div>
				<div class="uk-text-meta"><?php echo __('Version','admin2020').': '.$this->version ?></div>
			</div>
			<div class="uk-width-expand">
				
				<div class="uk-flex uk-flex-right uk-flex-middle" style="height:100%">
				
					<div class=" uk-margin-right">
						<div class="uk-inline">
							<span class="uk-form-icon" uk-icon="icon: search"></span>
							<button class="" 
							style="position: absolute;right: 8px;top: 5px;border: none;border-radius: 4px;"
							v-if="search.string.length > 0"
							@click="search.string = ''"><?php _e('Clear','admin2020')?></button>
							<input class="uk-input uk-form-small" v-model="search.string" type="text" placeholder="<?php _e('Search Settings...','admin2020')?>">
						</div>
					</div>
					
					<button class="uk-button uk-button-primary uk-button-small" @click="saveSettings()"><?php _e('Save','admin2020')?></button>
					
					<button class="uk-button uk-button-default uk-button-small a2020_make_light a2020_make_square uk-margin-small-left" aria-expanded="false">
						<span uk-icon="icon:settings;ratio:0.8" class="uk-icon"></span>
					</button>
					
					<div uk-dropdown="mode:click">
						<ul class="uk-nav uk-dropdown-nav">
							<li class=""><a href="#" @click="export_settings();">
								<span class="material-icons-outlined" style="font-size: 16px;position: relative;top: 3px;">file_download</span>
								<?php _e('Export Settings','admin2020')?></a>
								<a id="admin2020_download_settings" href="#" ></a>
							</li>
							<li class="">
								<a href="#">
								<label>
									<span class="material-icons-outlined" style="font-size: 16px;position: relative;top: 3px;">file_upload</span>
									<?php _e('Import Settings','admin2020')?>
									<input hidden accept=".json" type="file" single="" id="admin2020_export_settings" @change="import_settings()">
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
	
	/**
	 * Renders Navigation
	 * @since 1.4
	 */
	
	public function build_navigation(){
		
		$components = array();
		$components = apply_filters( 'admin2020_register_component', $components );
		$this->components = $components;
		
		
		?>
		
		
		<ul class="uk-nav " uk-switcher>
			
			<li><a href="#"><?php _e('Modules','admin2020') ?></a></li>
			
			<?php foreach($components as $component) { 
				
				$info = $component->component_info();
				$title = $info['title'];
				
				?>
				
				<li><a href="#"><?php echo $title; ?></a></li>
			
			<?php } ?>
		</ul>
		
		<?php
	}
	
	
	
	/**
	 * Renders module settings
	 * @since 1.4
	 */
	 
	public function render_module_settings(){
		
		$components = $this->components;
		$a2020_options = get_option( 'admin2020_settings' );
		$network = 'false';
		
		if(is_network_admin()){
			$network = 'true';
		}
		
		$key = $this->utils->get_option('activation','key');
		$message = true;
		$activated = true;
		
		if($key != "" && !get_transient( 'admin2020_activated')){
			$activated = false;
		} 
		
		if($key == "" || !$key || !get_transient( 'admin2020_activated')){
			$activated = false;
		}
		
		?>
		<div class="uk-h3"><?php _e('Modules','admin2020')?></div>
		<div class="uk-card uk-card-default uk-card-body " id="a2020_all_modules">
			
			
			<?php if($activated) { ?>
			
				<div class="uk-width-xlarge" uk-grid>
					<div class="uk-width-1-1@ uk-width-2-3@m">
						<div class="uk-h5 uk-margin-remove"><?php echo _e('Licence','admin2020')?></div>
						<div class="uk-text-meta"><?php echo _e('Admin 2020 licence is active. To remove licence or change licence click remove.','admin2020')?></div>
					</div>
					<div class="uk-width-1-1@ uk-width-1-3@m">
						<button class="uk-button uk-button-small uk-button-default" onclick="a2020_remove_licence(<?php echo $network?>)" ><?php _e('Remove','admin2020')?></button>
					</div>	
				</div>	
			
			<?php } ?>
			
			<?php if( is_network_admin() ) { ?>
			
				<?php
				$a2020_options = get_option( 'admin2020_settings_network' );
				$enabled;
				
				//echo '<pre>' . print_r( $a2020_options, true ) . '</pre>';
				
				if(isset($a2020_options['modules']['network_override']['status'])){
					$enabled = $a2020_options['modules']['network_override']['status'];
				}
				
				$checked = 'checked';
				
				if($enabled == 'false'){
					$checked = '';
				}
				?>
				
				<div class="uk-width-xlarge" uk-grid>
					<div class="uk-width-1-1@ uk-width-2-3@m">
						<div class="uk-h5 uk-margin-remove"><?php echo _e('Override Subsite Settings?','admin2020')?></div>
						<div class="uk-text-meta"><?php echo _e('If this is enabled then all the settings here will be applied to your subsites and they will not be able to be changed from a subsite.','admin2020')?></div>
					</div>
					<div class="uk-width-1-1@ uk-width-1-3@m">
						<label class="admin2020_switch uk-margin-left">
							<input class="a2020_module" name="network_override" type="checkbox" <?php echo $checked ?>>
							<span class="admin2020_slider constant_dark"></span>
						</label>
					</div>	
				</div>	
				
			<?php } ?>
			
			<?php foreach($components as $component) { 
			
				$info = $component->component_info();
				$title = $info['title']; 
				$description = $info['description']; 
				$optionname = $info['option_name'];
				if(isset($a2020_options['modules'][$optionname]['status'])){
					$enabled = $a2020_options['modules'][$optionname]['status'];
				} else {
					$enabled = 'true';
				}
				
				$checked = 'checked';
				
				if($enabled == 'false'){
					$checked = '';
				}
				
				?>
				<div class="uk-width-xlarge" uk-grid>
					<div class="uk-width-1-1@ uk-width-2-3@m">
						<div class="uk-h5 uk-margin-remove"><?php echo $title?></div>
						<div class="uk-text-meta"><?php echo $description?></div>
					</div>
					<div class="uk-width-1-1@ uk-width-1-3@m">
						<label class="admin2020_switch uk-margin-left">
							<input class="a2020_module" name="<?php echo $optionname?>" type="checkbox" <?php echo $checked ?>>
							<span class="admin2020_slider constant_dark"></span>
						</label>
					</div>	
				</div>	
				
			<?php } ?>	
		</div>	
		<div class="uk-width-1-1 uk-margin-top">
			<button class="uk-button uk-button-primary" onclick="a2020_save_modules(<?php echo $network ?>)" type="button"><?php _e('Save','admin2020') ?></button>	
		</div>
		<?php
	}
	
}

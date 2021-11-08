<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_login
{
    public function __construct($version, $path, $utilities)
    {
        $this->version = $version;
        $this->path = $path;
        $this->utils = $utilities;
    }

    /**
     * Loads menu actions
     * @since 1.0
     */

    public function start()
    {
		///REGISTER THIS COMPONENT
		add_filter('admin2020_register_component', array($this,'register'));
		
    }
	
	public function build_front(){
		
		if(!$this->utils->enabled($this)){
			return;
		}
		
		add_action('login_head', [$this, 'add_styles'], 0);
		add_filter('login_body_class', array($this, 'add_body_classes'));
		add_filter('login_redirect', array($this,'redirectToOverview'), 10, 3 );
		add_filter( 'login_headerurl', array($this,'login_logo_url') );
		
	}
	
	public function login_logo_url($url) {
		return get_home_url();
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
	 * redirects to overview page after login
	 * @since 1.4
	 */
	public function redirectToOverview($redirect_to, $request, $user){
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$redirect = $this->utils->get_option($optionname,'login-redirect');
		
		$redirectCustom = $this->utils->get_option($optionname,'login-redirect-custom');
		
		if($redirect == 'true' && !$redirectCustom){
			$redirect_to = admin_url() . "admin.php?page=admin_2020_overview";
		}
		
		if($redirectCustom && $redirectCustom != ''){
			
			if($this->isAbsoluteUrl($redirectCustom)){
				$redirect_to = $redirectCustom;
			} else {
				$redirect_to = admin_url() . $redirectCustom;
			}
			
			
		}
		
		return $redirect_to;
	}
	
	public function isAbsoluteUrl($url)
	{
		$pattern = "/^(?:ftp|https?|feed)?:?\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
		(?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
		(?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
		(?:[\w#!:\.\?\+\|=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";

		return (bool) preg_match($pattern, $url);
	}
	
	/**
	 * Returns component info for settings page
	 * @since 1.4
	 */
	public function component_info(){
		
		$data = array();
		$data['title'] = __('Login','admin2020');
		$data['option_name'] = 'admin2020_admin_login';
		$data['description'] = __('Styles the admin login page.','admin2020');
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
		$temp['name'] = __('Background Image','admin2020');
		$temp['description'] = __("Sets an optional background image for the login page.",'admin2020');
		$temp['type'] = 'image';
		$temp['optionName'] = 'login-background'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		
		$temp = array();
		$temp['name'] = __('Dark Mode','admin2020');
		$temp['description'] = __("Login style will match dark theme if enabled.",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'dark-enabled'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Redirect to overview page','admin2020');
		$temp['description'] = __("If enabled, after logging in users will be redirected to the overview page",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'login-redirect'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Redirect to custom page','admin2020');
		$temp['description'] = __("If enabled, after logging in users will be redirected to entered link. For admin pages use a relative URL (path after /wp-admin/), for other pages use an absolute URL",'admin2020');
		$temp['type'] = 'text';
		$temp['optionName'] = 'login-redirect-custom'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		
		return $settings;
		
	}
	
    /**
     * Adds admin bar styles
     * @since 1.0
     */

    public function add_styles()
    {
		
        ///GOOGLE FONTS
		wp_register_style('custom-google-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;700&display=swap', array());
		wp_enqueue_style('custom-google-fonts');
		///UIKIT FRAMEWORK
		wp_register_style('admin2020_app', $this->path . 'assets/css/app.css', array(), $this->version);
		wp_enqueue_style('admin2020_app');
		///A2020 THEME
		wp_register_style('admin2020_theme', $this->path . 'assets/css/modules/admin-theme.css', array(), $this->version);
		wp_enqueue_style('admin2020_theme');
		///LOGIN STYLES
		wp_register_style('admin2020_login', $this->path . 'assets/css/modules/admin-login.css', array(), $this->version);
		wp_enqueue_style('admin2020_login');
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		
		$logo = $this->utils->get_logo('admin2020_admin_bar');
		$darkmode = $this->utils->get_option($optionname,'dark-enabled');
		
		if($darkmode == 'true'){
			$logo = $this->utils->get_dark_logo('admin2020_admin_bar');
		}
		
		$background = $this->utils->get_option($optionname,'login-background');
		
		?>
		<style type="text/css"> h1 a {  background-image:url('<?php echo $logo?>')  !important; } </style>
		<?php
		
		if($background != ''){
			?>
			<style type="text/css"> body {  background-image:url('<?php echo $background?>')  !important; } </style>
			<?php
		}
		
    }
	
	
	/**
	* Output body classes
	* @since 1 
	*/
	
	public function add_body_classes($classes) {
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$darkmode = $this->utils->get_option($optionname,'dark-enabled');
	
		if ($darkmode == 'true') {
			$classes[] = "a2020_night_mode uk-light";
		}
		
		return $classes;
	}
	
	
}

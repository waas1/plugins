<?php
if (!defined('ABSPATH')) {
    exit();
}

class Uipress_module_overview
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
		
		if(!$this->utils->enabled($this)){
			return;
		}
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		
		if($this->utils->is_locked($optionname)){
			return;
		}
		
		add_action('admin_menu', array( $this, 'add_menu_item'));
        add_action('admin_enqueue_scripts', [$this, 'add_styles'], 0);
		add_action('admin_enqueue_scripts', [$this, 'add_scripts'], 0);
		
		//AJAX
		add_action('wp_ajax_uipress_get_posts', array($this,'uipress_get_posts'));
		add_action('wp_ajax_uipress_get_comments', array($this,'uipress_get_comments'));
		add_action('wp_ajax_uipress_get_system_info', array($this,'uipress_get_system_info'));
		add_action('wp_ajax_uipress_get_system_health', array($this,'uipress_get_system_health'));
		add_action('wp_ajax_uipress_save_dash', array($this,'uipress_save_dash'));
		add_action('wp_ajax_uipress_get_shortcode', array($this,'uipress_get_shortcode'));
		add_action('wp_ajax_uipress_reset_overview', array($this,'uipress_reset_overview'));
		
		
		///
		add_filter('uipress_register_card', array($this,'register_default_cards'));
		
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
	
	
	public function uipress_get_posts(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			$dates = $this->utils->clean_ajax_input($_POST['dates']); 
			$page = $this->utils->clean_ajax_input($_POST['currentPage']); 
			
			$startDate = date('Y-m-d', strtotime($dates['startDate']));
			$endDate = date('Y-m-d', strtotime($dates['endDate']));
			
			$args = array(
			'post_type'   => 'any',
			'post_status' => 'publish',
			'posts_per_page' => 5,
			'paged' => $page,
			'date_query' => array(
			  array(
				  'after'     => $startDate,
				  'before'    => $endDate,
				  'inclusive' => true,
				  ),
			  ),
			);
			
			wp_reset_query();
			$theposts = new WP_Query($args);
			$foundPosts = $theposts->get_posts();
			
			$formatted = array();
			
			foreach($foundPosts as $apost){
				
				$postdate = human_time_diff( get_the_date( 'U', $apost),current_time( 'timestamp' ) ) . ' ' . __('ago','admin2020');
				$author_id=$apost->post_author;
				$author_meta = get_the_author_meta( 'user_nicename' , $author_id );
				
				$temp = array();
				$temp['title'] = get_the_title($apost);	
				$temp['href'] = get_the_permalink($apost);	
				$temp['author'] = $author_meta;	
				$temp['date'] = $postdate;	
				$temp['type'] = get_post_type($apost);
				
				$formatted[] = $temp;
				
			}
			
			$returndata = array();
			
			
			$returndata['message'] = __("Posts fetched",'admin2020');
			$returndata['posts'] = $formatted;
			$returndata['totalFound'] = $theposts->found_posts;
			$returndata['maxPages'] = $theposts->max_num_pages;
			$returndata['testdate'] = $startDate;
			
			$returndata['nocontent'] = 'false';
			if($theposts->found_posts < 1){
				$returndata['nocontent'] = __('No posts posted during the date range.','admin2020'); 
			}
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
		
	}
	
	
	public function uipress_save_dash(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			$cards = $this->utils->clean_ajax_input_html($_POST['cards']); 
			
			if(!$cards && !is_array($cards) ){
				$message = __("Unable to save dash at this time",'admin2020');
				echo $this->utils->utils->ajax_error_message($message);
				die();
			}
			
			$a2020_options = get_option( 'admin2020_settings' );
			$a2020_options['modules']['Uipress_module_overview']['dashcards'] = $cards;
			update_option( 'admin2020_settings', $a2020_options);
			
			$returndata = array();
			$returndata['message'] = __('Dashboard settings saved','admin2020');
			echo json_encode($returndata);
			
			
		}
		
		die();	
		
		
	}
	
	public function uipress_reset_overview(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			$a2020_options = get_option( 'admin2020_settings' );
			$a2020_options['modules']['Uipress_module_overview']['dashcards'] = '';
			update_option( 'admin2020_settings', $a2020_options);
			
			$returndata = array();
			$returndata['message'] = __('Dashboard settings reset','admin2020');
			echo json_encode($returndata);
			
		}
		
		die();	
		
		
	}
	
	
	public function uipress_get_shortcode(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			$shortcode = $this->utils->clean_ajax_input($_POST['shortCode']); 
			
			if(!$shortcode){
				$message = __("Unable to load shortcode at this time",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			$data = do_shortcode(stripslashes($shortcode));
			
			if(!$data){
				$message = __("Unable to load shortcode at this time",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			$returndata = array();
			$returndata['shortCode'] = $data;
			$returndata['message'] = __('Shortcode loaded','admin2020');
			$returndata['test'] = stripslashes($shortcode);
			echo json_encode($returndata);
			
			
		}
		
		die();	
		
		
	}
	
	
	
	
	public function uipress_get_comments(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			$dates = $this->utils->clean_ajax_input($_POST['dates']); 
			$page = $this->utils->clean_ajax_input($_POST['currentPage']); 
			
			$startDate = date('Y-m-d', strtotime($dates['startDate']));
			$endDate = date('Y-m-d', strtotime($dates['endDate']));
			
			$args = array(
			'type' => 'comment',
			'status' => 'approve',
			'number' => 1000,
			'date_query' => array(
			  array(
				  'after'     => $startDate,
				  'before'    => $endDate,
				  'inclusive' => true,
				  ),
			  ),
			);
			
			$maxperpage = 5;
			$currentStart = $page * $maxperpage - $maxperpage;
			$currentEnd = $currentStart + $maxperpage + 1;
			
			$comments_query = new WP_Comment_Query;
			$comments = $comments_query->query($args);
			
			$formatted = array();
			$count = 0;
			
			foreach (array_slice($comments, $currentStart) as $acomment){
				
				if($count == 5){
					break;
				}
				
				$comment_date = get_comment_date( 'Y-m-y', $acomment->comment_ID );
				$string = '';
				
				if($comment_date != date('Y-m-d')){
				  $string = __('ago','admin2020');
				} 
				
				$commentdate = human_time_diff( get_comment_date( 'U', $acomment->comment_ID ),current_time( 'timestamp' ) ) . ' ' . $string;
				$author = $acomment->comment_author;
				$user = get_user_by( 'login', $author );
				$thepostid = $acomment->comment_post_ID;
				$commentlink = get_comment_link($acomment);
				$img = false;
				
				if (isset($user->ID)){
					$img = get_avatar_url($user->ID);
				} else {
				  
				  if (strpos($author, ' ') !== false) {
						$parts = str_split($author,1);
						$parts = explode(" ", $author);
						$first = str_split($parts[0]);
						$first = $first[0];
						
						$name_string = $first;
						
				  } else {
						$parts = str_split($author,1);
						$name_string = $parts[0];
				  }
				}
				
				
				$fullcontent = get_comment_text($acomment->comment_ID);
				
				if(strlen($fullcontent) > 40){
					$shortcontent = substr(get_comment_text($acomment->comment_ID), 0, 40).'...';
				} else {
					$shortcontent = $fullcontent;
				}
				
				$temp = array();
				$temp['title'] = get_the_title($thepostid);	
				$temp['href'] = $commentlink;	
				$temp['author'] = $author;	
				$temp['date'] = $commentdate;	
				$temp['text'] = esc_html($shortcontent);	
				
				if($img){
					$temp['img'] = $img;
				} else {
					$temp['initials'] = $name_string;
				}
				
				$formatted[] = $temp;
				$count += 1;
			}
			
			$returndata = array();
			$totalcomments = count($comments);
			
			$returndata['message'] = __("Posts fetched",'admin2020');
			$returndata['posts'] = $formatted;
			$returndata['totalFound'] = $totalcomments;
			$returndata['maxPages'] = ceil($totalcomments / $maxperpage);
			
			$returndata['nocontent'] = 'false';
			if($totalcomments < 1){
				$returndata['nocontent'] = __('No comments during the date range.','admin2020'); 
			}
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
		
	}
	
	public function uipress_get_system_info(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			
			$wp_v = get_bloginfo( 'version' );
			$phph_v = phpversion();
			$plugins = get_plugins();
			$activePlugins = get_option('active_plugins');
			$inactive = count($plugins) - count($activePlugins);
			
			$holder = array();
			
			$temp = array();
			$temp['name'] = __('Core version','admin2020');
			$temp['version'] = get_bloginfo( 'version' );
			$holder[] = $temp;
			
			$temp = array();
			$temp['name'] = __('PHP version','admin2020'); 
			$temp['version'] = $phph_v;
			$holder[] = $temp;
			
			$temp = array();
			$temp['name'] = __('Active Plugins','admin2020');
			$temp['version'] = count($activePlugins);
			$holder[] = $temp;
			
			$temp = array();
			$temp['name'] = __('Inactive Plugins','admin2020');
			$temp['version'] = $inactive;
			$holder[] = $temp;
			
			$temp = array();
			$temp['name'] = __('Installed Themes','admin2020');
			$temp['version'] = count(wp_get_themes());
			$holder[] = $temp;
			
			
			$returndata['posts'] = $holder;
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
	}
	
	public function uipress_get_system_health(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			
			$sitehealth = get_transient( 'health-check-site-status-result' );
			
			$issue_counts = array();
		 
			if ( false !== $sitehealth ) {
				$issue_counts = json_decode( $sitehealth, true );
			}
		 
			if ( ! is_array( $issue_counts ) || ! $issue_counts ) {
				$issue_counts = array(
					'good'        => 0,
					'recommended' => 0,
					'critical'    => 0,
				);
			}
			
			$issues_total = $issue_counts['recommended'] + $issue_counts['critical'];
			$returndata = array();
			
			$chartData = array();
			$chartLabels = array();
			
			$colors = array('rgba(50, 210, 150, 1)','rgba(250, 160, 90, 1)','rgba(240, 80, 110,1)');
			
			$temp = array();
			$temp['name'] = __('Passed Checks','admin2020');
			$temp['value'] = $issue_counts['good'];
			$temp['color'] = $colors[0];
			array_push($chartData, $issue_counts['good']);
			array_push($chartLabels, $temp['name']);
			$returndata['issues'][] = $temp;
			
			$temp = array();
			$temp['name'] = __('Recommended','admin2020');
			$temp['value'] = $issue_counts['recommended'];
			$temp['color'] = $colors[1];
			array_push($chartData, $issue_counts['recommended']);
			array_push($chartLabels, $temp['name']);
			$returndata['issues'][] = $temp;
			
			$temp = array();
			$temp['name'] = __('Critical','admin2020');
			$temp['value'] = $issue_counts['critical'];
			$temp['color'] = $colors[2];
			array_push($chartData, $issue_counts['critical']);
			array_push($chartLabels, $temp['name']);
			$returndata['issues'][] = $temp;
		
			
			
			$returndata['colours']['bgColors'] = array('#0c5cef','rgba(250, 160, 90, 0.5)','rgba(240, 80, 110, 0.5)');
			$returndata['colours']['borderColors'] = array('rgba(12, 92, 239, 1)');
				
			if($issue_counts['critical'] + $issue_counts['recommended'] > 0){
				$returndata['message'] = sprintf(__('Take a look at the %d items on the','admin2020'), $issue_counts['critical'] + $issue_counts['recommended']);
				$returndata['linkMessage'] = __('Site Health screen','admin2020');
				$returndata['healthUrl'] = esc_url( admin_url( 'site-health.php' ) );
			}
			
			
			
			$returndata['dataSet'] = array( 
				'labels' => $chartLabels,
				'datasets' => array(
					array(
						'label' => __("Device Visits",'admin2020'),
						  'fill' =>  true,
						  'data' =>  $chartData,
						  'backgroundColor' =>  $colors,
						  'borderWidth' =>  0,
					),
				)
			);
			
			$output = array();
			
			
			
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
	}
	
	
	
	/**
	 * Returns component info for settings page
	 * @since 1.4
	 */
	public function component_info(){
		
		$data = array();
		$data['title'] = __('Overview','admin2020');
		$data['option_name'] = 'admin2020_admin_overview';
		$data['description'] = __('Creates the overview page. If this is disabled, you will not be able to see analytics cards or woocommerce cards.','admin2020');
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
		$temp['name'] = __('Overview Disabled for','admin2020');
		$temp['description'] = __("Overview page will be disabled for any users or roles you select",'admin2020');
		$temp['type'] = 'user-role-select';
		$temp['optionName'] = 'disabled-for'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Who can edit the overview page','admin2020');
		$temp['description'] = __("Any role or user chosen here will be able to edit the overview page. If non are chosen it will fall baxck to administrators only",'admin2020');
		$temp['type'] = 'user-role-select';
		$temp['optionName'] = 'editing-disabled-for'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Custom welcome message','admin2020');
		$temp['description'] = __("Add a custom welcome message here to displayed on the overview page",'admin2020');
		$temp['type'] = 'text-code-input';
		$temp['optionName'] = 'custom-welcome'; 
		$temp['language'] = 'html';
		$temp['premium'] = true;
		$temp['value'] = stripslashes($this->utils->get_option($optionname,$temp['optionName']));
		$settings[] = $temp;
		
		
		
		
		return $settings;
		
	}
	
    /**
     * Adds admin bar styles
     * @since 1.0
     */

    public function add_styles()
    {
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'uip-overview'){
					
		        wp_register_style('uipress-overview-css', $this->path . 'assets/css/modules/admin-overview-app.css',array(),$this->version);
		        wp_enqueue_style('uipress-overview-css');
				
				wp_register_style('admin2020_daterangepicker', $this->path . 'assets/css/daterangepicker/daterangepicker.css',array(),$this->version);
				wp_enqueue_style('admin2020_daterangepicker');
				
				wp_register_style('a2020-codejar-css', $this->path . 'assets/js/codejar/highlight.css', array(),  $this->version);
				wp_enqueue_style('a2020-codejar-css');
				
			}
		}
    }
	
	/**
	* Enqueue Admin Bar 2020 scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'uip-overview'){ 
				
				
				
				$settings = $this->build_overview_data();
				
				
				$modules = $this->get_modules();
				$translations = array();
				$translations['cardWidth'] = __('Card width','admin2020');
				$translations['columnWidth'] = __('Column width','admin2020'); 
				$translations['columnSettings'] = __('Column settings','admin2020'); 
				$translations['remove'] = __('Remove Card','admin2020');
				$translations['deleteCol'] = __('Delete Column','admin2020');
				$translations['inTheLast'] = __('In the','admin2020');
				$translations['days'] = __('day range','admin2020'); 
				$translations['xxsmall'] = __('xxsmall (1/6)','admin2020'); 
				$translations['xsmall'] = __('xsmall (1/5)','admin2020'); 
				$translations['small'] = __('small (1/4)','admin2020'); 
				$translations['smallmedium'] = __('small medium (1/3)','admin2020'); 
				$translations['medium'] = __('medium (1/2)','admin2020'); 
				$translations['mediumlarge'] = __('medium large (2/3)','admin2020'); 
				$translations['large'] = __('large (3/4)','admin2020'); 
				$translations['xlarge'] = __('xlarge (1/1)','admin2020'); 
				$translations['emptycolumn'] = __('I am an empty columnm. Drag cards into me.','admin2020'); 
				$translations['colAdded'] = __('Column Added','admin2020'); 
				$translations['addCard'] = __('Add card','admin2020'); 
				$translations['sectionAdded'] = __('Section added','admin2020'); 
				$translations['searchCards'] = __('Search Cards','admin2020'); 
				$translations['premium'] = __('Pro','admin2020'); 
				$translations['title'] = __('Title','admin2020'); 
				$translations['shortcode'] = __('Shortcode','admin2020'); 
				$translations['videourl'] = __('Video URL','admin2020'); 
				$translations['embedType'] = __('Embed Type','admin2020'); 
				$translations['upgradMsg'] = __('Premium feature. Upgrade to pro to unlock','admin2020'); 
				$translations['html'] = __('HTML','admin2020'); 
				$translations['cardAdded'] = __('Card Added','admin2020'); 
				$translations['bgcolor'] = __('Card Background colour','admin2020'); 
				$translations['colorPlace'] = __('# Hex code only (#fff)','admin2020');
				$translations['lightText'] = __('Use light color for text','admin2020'); 
				$translations['chartType'] = __('Chart Type','admin2020'); 
				$translations['lineChart'] = __('Line Chart','admin2020'); 
				$translations['barChart'] = __('Bar Chart','admin2020'); 
				$translations['vsPrevious'] = __('vs previous','admin2020'); 
				$translations['vsdays'] = __('days','admin2020'); 
				$translations['doughnut'] = __('Doughnut','admin2020'); 
				$translations['polarArea'] = __('Polar Area','admin2020'); 
				$translations['bar'] = __('Bar','admin2020'); 
				$translations['horizbar'] = __('Horizontal Bar','admin2020'); 
				$translations['country'] = __('Country','admin2020'); 
				$translations['visits'] = __('Visits','admin2020'); 
				$translations['change'] = __('Change','admin2020'); 
				$translations['removeBackground'] = __('No Background','admin2020'); 
				$translations['showmap'] = __('Hide Map','admin2020'); 
				$translations['noaccount'] = __('No Google Analytics account connected','admin2020'); 
				$translations['hbar'] = __('Horizontal Bar','admin2020'); 
				$translations['hidechart'] = __('Hide Chart','admin2020'); 
				$translations['source'] = __('Source','admin2020'); 
				$translations['page'] = __('Page','admin2020'); 
				$translations['product'] = __('Product','admin2020'); 
				$translations['sold'] = __('Sold','admin2020'); 
				$translations['value'] = __('Value','admin2020'); 
				$translations['woocommerce'] = __('WooCommerce is required to use this card','admin2020'); 
				
				$translations['validJSON'] = __('Please select a valid JSON file','admin2020'); 
				$translations['fileBig'] = __('File is to big','admin2020'); 
				$translations['layoutImported'] = __('Layout Imported','admin2020'); 
				$translations['layoutExportedProblem'] = __('Unable to import layout','admin2020'); 
				
				$translations['confirmReset'] = __('Are you sure you want to reset the overview page to the default layout? There is no undo.','admin2020'); 
				
				//CODEFLASK
				wp_enqueue_script('a2020-codejar-js', $this->path . 'assets/js/codejar/codejar-alt.js', array('jquery'), $this->version);
				wp_enqueue_script('a2020-highlight-js', $this->path . 'assets/js/codejar/highlight.js', array('jquery'), $this->version);
				
				//VUE
				wp_enqueue_script('vue-menu-creator-js', $this->path . 'assets/js/vuejs/vue-menu-creator.js', array('jquery'), $this->version, false );
				wp_enqueue_script('sortable-js', $this->path . 'assets/js/sortable/sortable.js', array('jquery'), $this->version, false );
				wp_enqueue_script('vue-sortable-js', $this->path . 'assets/js/sortable/vuedraggable.umd.js', array('jquery'), $this->version, false );
				
				///CHART JS
				wp_enqueue_script('admin2020-charts', $this->path . 'assets/js/chartjs/charts-3.js', array('jquery'), $this->version, false);
				wp_enqueue_script('uipress-chart-geo', $this->path . 'assets/js/chartjs/chartjs-geo.min.js', array('jquery'), $this->version, false);
				//MOMENT
				wp_enqueue_script('admin2020-moment', $this->path . 'assets/js/moment/moment.min.js', array('jquery'), $this->version);
				//LITE PICKER
				wp_enqueue_script('uipress-date-picker', $this->path . 'assets/js/litepicker/litepicker.js', array('jquery'), $this->version);
				wp_enqueue_script('uipress-date-ranges', $this->path . 'assets/js/litepicker/litepicker-ranges.js', array('jquery'), $this->version);
				//wp_enqueue_script('uipress-date-mobile', $this->path . 'assets/js/litepicker/litepicker-mobile.min.js', array('jquery'), $this->version);
				
				
				///OVERVIEW SCRIPTS
				wp_enqueue_script('admin-overview-app', $this->path . 'assets/js/admin2020/admin-overview-app.min.js', array('jquery'), $this->version, true);
				wp_localize_script('admin-overview-app', 'uipress_overview_ajax', array(
				  'ajax_url' => admin_url('admin-ajax.php'),
				  'security' => wp_create_nonce('uipress-overview-security-nonce'),
				  'options'  => json_encode($settings),
				  'modules' => json_encode($modules),
				  'translations' => json_encode($translations),
				));
			}
		}
	  
	}
	
	public function get_modules(){
		
		
		$cards = array();
		$extended_cards = apply_filters( 'uipress_register_card', $cards );
		
		return $extended_cards;
	}
	
	public function register_default_cards($cards){
		
		if(!is_array($cards)){
			$cards = array();
		}
		
		$temp = array();
		$temp['name'] = __('Recently Published','admin2020');
		$temp['moduleName'] = __('recent-posts','admin2020');
		$temp['description'] = __('Display posts, pages and CPTs published within the date range.','admin2020');
		$temp['category'] = __('General','admin2020');
		$temp['premium'] = false;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/generic/recent-posts.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Recent Comments','admin2020');
		$temp['moduleName'] = __('recent-comments','admin2020');
		$temp['description'] = __('Displays total comments and recent comments published within the date range.','admin2020');
		$temp['category'] = __('General','admin2020');
		$temp['premium'] = false;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/generic/recent-comments.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('System Info','admin2020');
		$temp['moduleName'] = __('system-info','admin2020');
		$temp['description'] = __('Displays info our about your cms and server setup.','admin2020');
		$temp['category'] = __('General','admin2020');
		$temp['premium'] = false;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/generic/system-info.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Site Health','admin2020');
		$temp['moduleName'] = __('site-health','admin2020');
		$temp['description'] = __('Displays info our about your sites health.','admin2020');
		$temp['category'] = __('General','admin2020');
		$temp['premium'] = false;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/generic/site-health.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Date','admin2020');
		$temp['moduleName'] = __('calendar','admin2020');
		$temp['description'] = __('Displays current time and date','admin2020');
		$temp['category'] = __('General','admin2020');
		$temp['premium'] = false;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/generic/calendar.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Video','admin2020');
		$temp['moduleName'] = __('custom-video','admin2020');
		$temp['description'] = __('Displays a custom video','admin2020');
		$temp['category'] = __('General','admin2020');
		$temp['premium'] = true;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/generic/custom-video.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Shortcode','admin2020');
		$temp['moduleName'] = __('shortcode','admin2020');
		$temp['description'] = __('Outputs a WordPress shortcode to the card','admin2020');
		$temp['category'] = __('General','admin2020');
		$temp['premium'] = true;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/generic/shortcode.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Custom HTML','admin2020');
		$temp['moduleName'] = __('custom-html','admin2020');
		$temp['description'] = __('Outputs custom HTML to the card','admin2020');
		$temp['category'] = __('General','admin2020');
		$temp['premium'] = true;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/generic/custom-html.min.js';
		$cards[] = $temp;
		
		
		return $cards;
		
		
	}
	
	
	public function check_for_google_account(){
		
		$optionname = 'admin2020_google_analytics';
		$a2020_options = get_option( 'admin2020_settings' );
		
		if(isset($a2020_options['modules'][$optionname]['view_id']) && $a2020_options['modules'][$optionname]['refresh_token']){
			$view = $a2020_options['modules'][$optionname]['view_id'];
			$code = $a2020_options['modules'][$optionname]['refresh_token'];
		} else {
			return  false;
		}
		
		if(!$view || $view == '' || !$code || $code == ''){
			return  false;
		}
		
		return true;
		
	}
	
	
	public function build_overview_data(){
		
		$settings = array();
		$current_user = wp_get_current_user();
		$username = $current_user->user_login;
		$first = $current_user->user_firstname;
		$last = $current_user->user_lastname;
		
		
		
		if($first == "" || $last == ""){
			$name_string = str_split($username,1);
			$name_string = $name_string[0];
			$displayname = $username;
		} else {
			$name_string = str_split($first,1)[0].str_split($last,1)[0];
			$displayname = $first;
		}	
		if($first == ""){
			$displayname = $username;
		}
		
		$daterange = array();
		$daterange['endDate'] = date('Y-m-d');
		$daterange['startDate'] = date('Y-m-d',  strtotime(date("Y-m-d", strtotime("-7 day"))) );
		
		
		$settings['user']['username'] = $displayname;
		$settings['user']['initial'] = $name_string;
		$settings['user']['welcomemessage'] = __('Hello','admin2020');
		$settings['user']['date'] = date(get_option('date_format'));
		$settings['user']['dateRange'] = $daterange;
		$settings['user']['dateFormat'] = get_option('date_format');
		$settings['premium'] = $this->utils->is_premium();
		$settings['canEdit'] = $this->can_edit_overview();
		$settings['analyticsAccount'] = $this->check_for_google_account();
		
		
		
		
		$str = file_get_contents($this->path . 'assets/js/admin2020/overview-modules/generic/default-layout.json');
		$cards = json_decode($str);
		
		$a2020_options = get_option( 'admin2020_settings' );
		if(isset($a2020_options['modules']['Uipress_module_overview']['dashcards'])){
			$tempcards = $a2020_options['modules']['Uipress_module_overview']['dashcards'];
			
			if(is_array($tempcards) && is_array(json_decode(json_encode($tempcards)))){
				$cards = $tempcards;
			}
		}
		
		  //echo '<pre>' . print_r( $cards, true ) . '</pre>';
		
		
		$settings['cards']['formatted'] = $cards;
		
		return $settings;
	}
	
	
	public function can_edit_overview(){
		
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		$enabledFor = $this->utils->get_option($optionname,'editing-disabled-for');
		
		if(empty($enabledFor)){
			
			if(current_user_can('administrator')){
				return true;
			} else {
				return false;
			}
			
		}
		
		if(!is_array($enabledFor)){
			if(current_user_can('administrator')){
				return true;
			} else {
				return false;
			}
		}
		
		if(!function_exists('wp_get_current_user')){
			if(current_user_can('administrator')){
				return true;
			} else {
				return false;
			}
		}
		
		
		$current_user = $this->utils->get_user();
		
		
		$current_name = $current_user->display_name;
		$current_roles = $current_user->roles;
		$formattedroles = array();
		$all_roles = wp_roles()->get_names();
		
		
		if(in_array($current_name, $enabledFor)){
			return true;
		}
		
		
		///MULTISITE SUPER ADMIN
		if(is_super_admin() && is_multisite()){
			if(in_array('Super Admin',$enabledFor)){
				return true;
			} else {
				return false;
			}
		}
		
		///NORMAL SUPER ADMIN
		if($current_user->ID === 1){
			if(in_array('Super Admin',$enabledFor)){
				return true;
			} else {
				return false;
			}
		}
		
		foreach ($current_roles as $role){
			
			$role_name = $all_roles[$role];
			if(in_array($role_name,$enabledFor)){
				return true;
			}
			
		}
		
	}
	
	/**
	* Adds overview menu item
	* @since 1.4
	*/
	
	public function add_menu_item() {
		
		add_menu_page( 'UiPress Dashboard', __('Overview',"admin2020"), 'read', 'uip-overview', array($this,'build_overview'),'dashicons-chart-bar', 0 );
		return;
	
	}
	
	
	
	public function build_overview(){
		
		?>
		
		
		
		<div class="wrap" id="overview-app">
			
			
			<div v-if="!loading" class="uk-width-1-1 hidden uk-animation-fade" :class="{'nothidden' : !loading}">
				<?php $this->build_head() ?>
				<?php $this->build_welcome_message() ?>
				<?php $this->build_cards(); ?>
				
			</div>
		</div>
		<?php
	}
	
	
	public function build_head(){
		
		
		?>
		
		<div v-if="ui.editingMode" 
		class="uip-overview-edit-header ">
		
			<div uk-sticky="offset: 61" class="uk-flex uk-flex-right uk-background-default a2020-border bottom uk-padding-small">
				
				<button  @click="newSection()"
				class="uk-button uk-button-small uk-margin-right"><?php _e('New Section','admin2020')?></button>
				
				<button  @click="saveDash()"
				class="uk-button uk-button-primary uk-button-small uk-margin-right"><?php _e('Save changes','admin2020')?></button>
				
				<button  @click="ui.editingMode = false;"
				class="uk-button uk-button-secondary uk-button-small"><?php _e('Exit edit mode','admin2020')?></button>
				
			</div>
			
		</div>
		
		<div v-if="ui.editingMode" class="uk-margin-large">
			
		</div>
		
		<div v-if="!ui.editingMode" class="uk-grid uk-grid-small uk-margin-large">
			
			
			<div class="uk-width-1-1@s uk-width-expand@m">
				
				<div class="uk-flex uk-flex-middle">
					
					<div class="uk-background-primary uk-text-large uk-text-bold  uk-border-circle uk-flex uk-flex-center uk-flex-middle uk-margin-right" style="color:#fff;width:50px;height:50px;">
						<span style="line-height: 9px;
						height: 12px;">{{settings.user.initial}}</span>
					</div>
						
					<div >
						<div class="uk-h3 uk-text-bold uk-margin-remove">{{settings.user.welcomemessage}} {{settings.user.username}}</div>
						<div class="uk-text-meta">{{settings.user.date}}</div>
					</div>
					
				</div>
				
			</div>
			
			
			
			<div class="uk-width-auto" :class="{'uk-margin-top' : isSmallScreen()}">
					
					<date-range-picker :dates="settings.user.dateRange"  @date-change="settings.user.dateRange = getdatafromComp($event)"></date-range-picker>
				
			</div>
			
			<div v-if="ui.editingMode" class="uip-overview-edit-header uk-flex uk-flex-right uk-background-default a2020-border bottom">
				
				<button  @click="newSection()"
				class="uk-button uk-button-small uk-margin-right"><?php _e('New Section','admin2020')?></button>
				
				<button  @click="saveDash()"
				class="uk-button uk-button-primary uk-button-small uk-margin-right"><?php _e('Save changes','admin2020')?></button>
				
				<button  @click="ui.editingMode = false;"
				class="uk-button uk-button-secondary uk-button-small"><?php _e('Exit edit mode','admin2020')?></button>
				
			</div>
			
			<div class="uk-width-auto"  v-if="!isSmallScreen()">
				
				<div class="uk-flex uk-flex-middle uk-flex-center uip-date-range-container">
					<span class="material-icons-outlined uk-text-muted" style="cursor:pointer;font-size:30px;">more_horiz</span>
				</div>
				
				<div uk-dropdown="mode: click">
					
					<div class="uk-h5"><?php _e('Settings','admin2020')?></div>
					
					<div v-if="settings.canEdit" class="">
						<?php _e('Editing Mode','admin2020')?>
						<label class="admin2020_switch uk-margin-left">
							<input type="checkbox" v-model="ui.editingMode">
							<span class="admin2020_slider "></span>
						</label>
					</div>
					
					
					
					<div v-if="settings.analyticsAccount" class="uk-margin">
						<button @click="removeGoogleAccount()" class="uk-button uk-button-small ">
							<?php _e('Disconnect Analytics','admin2020')?>
						</button>
					</div>
					
					<template v-if="settings.canEdit" >
						
						<hr>
					
						<div  class="uk-margin">
							<button @click="exportCards()" class="uk-button  uk-button-default uk-button-small uk-width-1-1 uk-flex uk-flex-middle ">
								<span class="material-icons-outlined uk-margin-small-right" style="font-size:18px;">file_download</span>
								<span><?php _e('Export Layout','admin2020')?></span>
								<a href="" id="uip_export_dash"></a>
							</button>
						</div>
						
						<div  class="uk-margin">
							<button  class="uk-button uk-button-default uk-button-small uk-width-1-1">
								<label class="uk-flex uk-flex-middle">
									<span class="material-icons-outlined uk-margin-small-right" style="font-size: 18px;">file_upload</span>
									<?php _e('Import Layout','admin2020')?>
									<input hidden accept=".json" type="file" single="" id="uipress_import_cards" @change="importCards()">
								</label>
							</button>
						</div>
					
					</template>
					
					<template v-if="settings.canEdit">
						<hr>
						
						<div class="">
							<button @click="resetOverview()" class="uk-button uk-button-small uk-button-danger">
								<?php _e('Reset Layout','admin2020')?>
							</button>
						</div>
					
					</template>
					
				</div>
				
			</div>
			
		</div>
		
		
		<?php
		
	}
	
	
	public function build_cards(){
		
		
		?>
		<div v-for='(category, index) in cardsWithIndex' class="uk-margin" :class="{'uip-section-editing' : ui.editingMode}">
			
			
			
			<div class="uk-width-1-1 uk-border-rounded" style="margin-bottom:40px;"
			:class="{'uk-background-default uk-box-shadow-small a2020-border all uk-padding-small' : !category.open}">
				<div class="uk-flex uk-flex-middle uk-flex-between" >
				
					
					<span v-if="!ui.editingMode" class="uk-h3 uk-text-bold uk-margin-remove">
					{{category.name}}
					</span>
					
					<div v-if="ui.editingMode" class="uk-flex uk-flex-middle">
						<span class="material-icons-outlined uk-margin-small-right">edit</span>
						<input class="uk-input uk-form-large uk-width-medium " v-model="category.name" type="text" style="font-size:25px;font-weight:bold;padding:0;background:none;border:none;height:20px;">
					</div>
					
					<div>
						
						<div class="uk-flex uk-flex-row">
							
							<span v-if="category.open" @click="category.open = !category.open" class="material-icons-outlined" style="cursor:pointer">
								expand_more
							</span>
							<span v-if="!category.open" @click="category.open = !category.open" class="material-icons-outlined" style="cursor:pointer">
								chevron_left
							</span>
						
							
								
							<button v-if="ui.editingMode" @click="addNewColumn(category.columns)"
							class="uk-button uk-button-small uk-button-small uk-margin-left ">
								<?php _e('Add New Column','admin2020')?></button>
								
							<button v-if="ui.editingMode" @click="deleteSection(index)"
							class="uk-button uk-button-danger uk-button-small uk-margin-left uk-flex uk-flex-middle">
								<?php _e('Remove Section')?></button>
							
							<button v-if="ui.editingMode" @click="moveColumnDown(index)" :disabled="index == settings.cards.formatted.length - 1"
							class="uk-button uk-button-small uk-button-small uk-margin-left uk-flex uk-flex-middle">
								<span class="material-icons-outlined" style="font-size:25px;">expand_more</span></button>
								
							<button v-if="ui.editingMode" @click="moveColumnUp(index)" :disabled="index == 0"
							class="uk-button uk-button-small uk-button-small uk-margin-left uk-flex uk-flex-middle">
								<span class="material-icons-outlined" style="font-size:25px;">expand_less</span></button>
								
						</div>
					</div>
				</div>
				<div v-if="!ui.editingMode" class="uk-text-meta">
					{{category.desc}}
				</div>
				
				<textarea v-if="ui.editingMode" class="uk-width-large uk-text-area uk-margin-small-top" v-model="category.desc" type="text" style="padding:0;background:none;border:none;"></textarea>
			</div>
			
			
			<div v-if="category.open" class="uk-grid uip-dash-cards">
				
				
				<template v-for="(column, index) in category.columns">
					<div 
					:class="['uip-width-' + column.size, { 'uip-edit-col' : ui.editingMode, 'uip-empty-col' : !column.cards || column.cards.length < 1}]" >
						
						<col-editer 
						v-if="ui.editingMode" 
						:modules="modules"
						:premium="settings.premium"
						@remove-col="removeCol(category.columns, index)" 
						:column="column" :translations="translations" @col-change="column = getdatafromComp($event)"></col-editer>
						
						
						<draggable 
						  v-model="column.cards" 
						  :component-data="setDragData()"
						  handle=".drag-handle"
						  group="uip-cards"
						  @start="drag=true" 
						  @end="drag=false" 
						  @change="logDrop()"
						  item-key="id">
						  <template 
						  #item="{element, index, mainDR = settings.user.dateRange, prem = settings.premium, allCards = column.cards}">
							  
							  <div class="top-level-card"  style="margin-bottom:40px;"
							  :class="['uip-width-' + element.size]">
								  <div class="uk-border-rounded uk-background-default uk-box-shadow-small" 
								  :class="{'uip-no-background' : element.nobg && element.nobg != 'false'}"
								  :style="{'background-color' : element.bgColor}">
									  <div class="" style="padding: var(--a2020-card-padding);padding-bottom:0;">
										  <div class="uk-flex uk-flex-between">
											  <div :class="{'uk-light' : element.lightDark && element.lightDark != 'false'}">
												  <div class="uk-h5 uk-margin-remove drag-title uk-text-emphasis uk-text-bold uk-flex uk-flex-middle ">
													  <span v-if="ui.editingMode" class="material-icons-outlined uk-margin-small-right drag-handle" style="cursor:pointer">drag_indicator</span>
													  {{element.name}}
												  </div>
											  </div>
											  <card-options  v-if="ui.editingMode" :translations="translations" :card="column.cards[index]" :cardindex="index" 
											   @remove-card="removeCard(column, index)" 
											   @card-change="column.cards[index] = getdatafromComp($event)"></card-options>
										  </div>
									  </div>
									  <div :class="{'uk-light' : element.lightDark && element.lightDark != 'false'}">
									  <component :is="element.compName" v-bind="{ cardData: element, dateRange: mainDR, translations: translations, editingMode: ui.editingMode, premium: prem, analytics: settings.analyticsAccount }"></component>
									  </div>
									  
								  </div>
							  </div>
							  
							  
							  
						  </template>
						  
						  
						</draggable>
						
						<p class="uk-text-meta uk-text-center" v-if="!column.cards || column.cards.length < 1 && ui.editingMode">
							{{translations.emptycolumn}}
						</p>
						
					</div>
						
				</template>
				
			</div>
			
			
			
			
			
		</div>
		
		
		
		<?php
	}
	
	public function build_welcome_message(){
		
		$info = $this->component_info();
		$optionname = $info['option_name'];
		
		$code = stripslashes($this->utils->get_option($optionname,'custom-welcome'));
		
		if($code != '' && $code){
			
			?>
			<div class="uk-card uk-border-rounded uk-background-default uk-box-shadow-small uk-margin-large-bottom" id="uipress-welcome-message">
				<div class="uk-position-small uk-position-top-right uk-flex uk-flex-right">
					<a href="#" class="uk-icon-link" uk-icon="close" onclick="jQuery('#uipress-welcome-message').remove();"></a>
				</div>	
				<div class="uk-card-body">
					<?php echo $code ?>
				</div>
			</div>
			
			<?php
			
			
		}
		
		
		
	}
	
	
	
}

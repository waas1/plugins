<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_overview
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
		add_filter('admin2020_register_dash_card', array($this,'register_video_cards'));
		
		add_action('wp_ajax_a2020_refresh_overview', array($this,'a2020_refresh_overview'));
		add_action('wp_ajax_a2020_make_primary_card', array($this,'a2020_make_primary_card'));
		add_action('wp_ajax_a2020_hide_single_card', array($this,'a2020_hide_single_card'));
		
		add_action('wp_ajax_a2020_save_analytics', array($this,'a2020_save_analytics'));
		add_action('wp_ajax_a2020_remove_analytics', array($this,'a2020_remove_analytics'));
		
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
		$temp['name'] = __('Custom welcome message','admin2020');
		$temp['description'] = __("Add a custom welcome message here to displayed on the overview page",'admin2020');
		$temp['type'] = 'text-code-input';
		$temp['optionName'] = 'custom-welcome'; 
		$temp['language'] = 'html';
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
		
				if($_GET['page'] == 'admin_2020_overview'){
					
		        wp_register_style(
		            'admin2020_overview_style',
		            $this->path . 'assets/css/modules/admin-overview.css',
		            array(),
		            $this->version
		        );
		        wp_enqueue_style('admin2020_overview_style');
				
				wp_register_style('admin2020_daterangepicker',$this->path . 'assets/css/daterangepicker/daterangepicker.css',array(),$this->version);
				wp_enqueue_style('admin2020_daterangepicker');
				
			}
		}
    }
	
	/**
	* Enqueue Admin Bar 2020 scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin_2020_overview'){
			
				///OVERVIEW SCRIPTS
				wp_enqueue_script('admin-overview-js', $this->path . 'assets/js/admin2020/admin-overview.min.js', array('jquery'));
				wp_localize_script('admin-overview-js', 'admin2020_admin_overview_ajax', array(
				  'ajax_url' => admin_url('admin-ajax.php'),
				  'security' => wp_create_nonce('admin2020-admin-overview-security-nonce'),
				));
				///CHART JS
				wp_enqueue_script('admin2020-charts', $this->path . 'assets/js/chartjs/chartjs.min.js', array('jquery'));
				wp_enqueue_script('admin2020-charts-rounded', $this->path . 'assets/js/chartjs/chartjs-rounded.min.js', array('jquery'));
				//MOMENT
				wp_enqueue_script('admin2020-moment', $this->path . 'assets/js/moment/moment.min.js', array('jquery'));
				//DATERANGE PICKER
				wp_enqueue_script('admin2020-daterangepicker', $this->path . 'assets/js/daterangepicker/daterangepicker.min.js', array('jquery'));
				
			
			}
		}
	  
	}
	
	/**
	* Adds overview menu item
	* @since 1.4
	*/
	
	public function add_menu_item() {
		
		add_menu_page( '2020 Dashboard', __('Overview',"admin2020"), 'read', 'admin_2020_overview', array($this,'build_overview'),'dashicons-chart-bar', 0 );
		return;
	
	}
	
	/**
	* Registers video cards
	* @since 1.4
	*/
	
	public function register_video_cards($dashitems){
		
		$videos = $this->utils->get_option('admin2020_admin_overview','videos');
		
		if(!is_array($videos)){
			return;
		}
		
		foreach($videos as $video){
			
			$name = $video[0];
			$url = $video[1];
			$category = $video[2];
			$type = $video[3];
			
			$lc_name = strtolower($name);
			$function_name = str_replace(" ", "_", $name);
			
			array_push($dashitems,array($this,$function_name,$name,$category,$url,$type));
			
			
		}
	
		return $dashitems;
	}
	
	public function create_user_videos($name,$url,$category,$type){
		
		$lc_name = strtolower($name);
		$function_name = str_replace(" ", "_", $name);
		$dashcard_options = $this->utils->get_user_preference('dash_card_options');
		
		$primary = '';
		$text = __('Make Primary','admin2020');
		
		if(isset($dashcard_options[$function_name]['primary'])){
			if($dashcard_options[$function_name]['primary'] == true){
				$primary = 'uk-card-primary';
				$text = __('Make Default','admin2020');
			}
		}
		
		?>
		<div class="uk-width-1-4@xl uk-width-1-3@l uk-width-1-2@m uk-width-1-1@s " id="<?php echo $function_name ?>" card-type='<?php echo strtolower($category)?>'>
			<div class="uk-card uk-card-default uk-card-small <?php echo $primary?>">
			
				
				<div class="uk-card-header">
					  <span class="uk-h5 a2020_card_drag"><?php echo $name ?></span>
					  <span uk-icon="icon:more;ratio:0.9" class="uk-text-muted uk-align-right uk-margin-remove" style="cursor: pointer"></span>
					  
					  <div uk-dropdown="mode: click;pos:bottom-right">
						  <ul class="uk-nav uk-dropdown-nav">
							  <li><a href="#" onclick="a2020_make_primary_card('<?php echo $function_name ?>')"><?php echo $text?></a></li>
							  <li><a href="#" onclick="a2020_hide_single_card('<?php echo $function_name ?>')"><?php _e('Hide','admin2020')?></a></li>
						  </ul>
					  </div>
				</div>
				
				<div>
				<?php if ($type == 'url') { ?>
				
					<video src="<?php echo $url?>" controls uk-video="autoplay: false"></video>
				
				<?php } else if ($type == 'youtube'){ ?>
				
					<iframe src="<?php echo $url ?>" width="1920" height="1080" frameborder="0" allowfullscreen uk-responsive uk-video="automute: false;autoplay: false"></iframe>
				
				<?php } else if ($type == 'vimeo'){ ?>
					
					<iframe src="<?php echo $url ?>" width="1920" height="1080" frameborder="0" allowfullscreen uk-responsive uk-video="automute: false;autoplay: false"></iframe>
				
				<?php } ?>
				</div>
				
			</div>
		</div>
		<?php
	}
	
	
	/**
	* Save modules from ajax
	* @since 1.4
	*/
	
	public function a2020_save_analytics(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-overview-security-nonce', 'security') > 0) {
			
			$view = $_POST['view'];
			$code = $_POST['code'];
			$modulename = $_POST['module'];
			
			$a2020_options = get_option( 'admin2020_settings' );
			
			if($view == "" || $code == "" || $modulename == ""){
				$message = __("Unable to connect account",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
			
			$a2020_options['modules'][$modulename]['view_id'] = $view;
			$a2020_options['modules'][$modulename]['refresh_token'] = $code;
			
			
			update_option( 'admin2020_settings', $a2020_options);
			
			$returndata = array();
			$returndata['success'] = true;
			$returndata['message'] = __('Analytics account connected','admin2020');
			echo json_encode($returndata);
			
			die();
			
			
		}
		die();	
		
	}
	
	
	/**
	* remove analytics from ajax
	* @since 1.4
	*/
	
	public function a2020_remove_analytics(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-overview-security-nonce', 'security') > 0) {
			
			$modulename = 'admin2020_google_analytics';
			
			$a2020_options = get_option( 'admin2020_settings' );
			
			$a2020_options['modules'][$modulename]['view_id'] = "";
			$a2020_options['modules'][$modulename]['refresh_token'] = "";
			
			
			update_option( 'admin2020_settings', $a2020_options);
			
			$returndata = array();
			$returndata['success'] = true;
			$returndata['message'] = __('Analytics account removed','admin2020');
			echo json_encode($returndata);
			
			die();
			
			
		}
		die();	
		
	}
	
	/**
	* Refreshes overview page
	* @since 1.4
	*/
	
	public function a2020_refresh_overview(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-overview-security-nonce', 'security') > 0) {
			
			$startdate = $this->utils->clean_ajax_input($_POST['startdate']);
			$enddate = $this->utils->clean_ajax_input($_POST['enddate']);
			$this->set_cards();
			
			echo $this->build_cards($startdate, $enddate);
		}
		die();
	}
	
	/**
	* Saves cards as primary
	* @since 2.0.4
	*/
	
	public function a2020_make_primary_card(){ 
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-overview-security-nonce', 'security') > 0) {
			
			$cardname = $this->utils->clean_ajax_input($_POST['cardname']);
			
			if($cardname == "" ){
				$message = __("No Card supplied to save",'admin2020');
				echo $this->ajax_error_message($message);
				die();
			}
			
			$userid = get_current_user_id();
			$current = get_user_meta($userid, 'admin2020_preferences', true);
			
			if(!is_array($current)){
				$current = array();
			}
			
			if(isset($current['dash_card_options'])){
				
				if(isset($current['dash_card_options'][$cardname]['primary'])){
					
					$currentvalue = $current['dash_card_options'][$cardname]['primary'];
					
					if($currentvalue == true){
						$current['dash_card_options'][$cardname]['primary'] = false;
					} else {
						$current['dash_card_options'][$cardname]['primary'] = true;
					}
					
				} else {
					
					$current['dash_card_options'][$cardname] = [];
					$current['dash_card_options'][$cardname]['primary'] = true;
					
				}
				
			} else {
				$current['dash_card_options'] = [];
				$current['dash_card_options'][$cardname] = [];
				$current['dash_card_options'][$cardname]['primary'] = true;
			}
			
			$state = update_user_meta($userid, 'admin2020_preferences', $current);
			
			if($state){
				$returndata = array();
				$returndata['success'] = true;
				$returndata['message'] = __('Card options saved','admin2020');
				echo json_encode($returndata);
			} else {	
				$message = __("Unable to save card preferences",'admin2020');
				echo $this->ajax_error_message($message);
				die();
			}
			
		}
		die();
	}
	
	
	/**
	* Saves cards as primary
	* @since 2.0.4
	*/
	
	public function a2020_hide_single_card(){ 
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-overview-security-nonce', 'security') > 0) {
			
			$cardname = $this->utils->clean_ajax_input($_POST['cardname']);
			
			if($cardname == "" ){
				$message = __("No Card supplied to hide",'admin2020');
				echo $this->ajax_error_message($message);
				die();
			}
			
			$userid = get_current_user_id();
			$current = get_user_meta($userid, 'admin2020_preferences', true);
			
			if(!is_array($current)){
				$current = array();
			}
			
			if(isset($current['dash_visibility'])){
				
				if(is_array($current['dash_visibility'])){
				
					array_push($current['dash_visibility'], $cardname); 
					
				} else {
					
					$current['dash_visibility'] = array();
					array_push($current['dash_visibility'], $cardname);
					
				}
				
			} else {
				
				$current['dash_visibility'] = array();
				array_push($current['dash_visibility'], $cardname);
				
			}
			
			$state = update_user_meta($userid, 'admin2020_preferences', $current);
			
			if($state){
				$returndata = array();
				$returndata['success'] = true;
				$returndata['message'] = __('Card options saved','admin2020');
				echo json_encode($returndata);
			} else {	
				$message = __("Unable to save card preferences",'admin2020');
				echo $this->ajax_error_message($message);
				die();
			}
			
		}
		die();
	}
	
	/**
	* Builds overview page
	* @since 1.4
	*/
	
	
	public function build_overview(){
		
		$this->set_cards();
		?>
		<div class="wrap">
			<div class="uk-width-1-1 a2020_filter_wrap" uk-filter="target: #a2020_overview_cards;">
				<?php $this->build_head() ?>
				<?php $this->build_categories() ?>
				<?php $this->build_welcome_message() ?>
				<?php $this->build_card_grid() ?>
			</div>
			<div class="admin2020loaderwrap" id="adminoverviewloader">
				<div class="admin2020loader"></div>
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
			<div class="uk-card uk-card-default uk-card-small " id="uipress-welcome-message">
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
	
	
	/**
	* Gets admin cards
	* @since 1.4
	*/
	
	public function set_cards(){
		
		$cards = array();
		$extended_cards = apply_filters( 'admin2020_register_dash_card', $cards );
		
		$dash_order = $this->utils->get_user_preference('dash_order');
		
		if($dash_order && is_array($dash_order)){
			
			$temparray = array();
				
			foreach($extended_cards as $card){
				
				$functionname = $card[1];
				
				$order = array_search($functionname,$dash_order);
				
				if($order){
					$card['order'] = $order;
				} else {
					$card['order'] = "";
				}
				
				array_push($temparray,$card);
			}
			$extended_cards = $temparray;
			
			function sort_order($a, $b)
			{
				return strcmp($a['order'], $b['order']);
			}
			usort($extended_cards, "sort_order");
		}
		
		
		$this->cards = $extended_cards;
		
	}
	
	
	
	/**
	* Builds overview categories
	* @since 1.4
	*/
	public function build_categories(){
		
		$cards = $this->cards;
		$usedcards = array();
		
		if(is_array($cards)){
			?>
			<ul uk-tab>
				<li uk-filter-control><a href="#"><?php _e('All','admin2020') ?></a></li>
				<?php if(is_array($cards)){
					
					foreach($cards as $card){ 
						
						if(in_array($card[3],$usedcards)){
							continue;
						}
						array_push($usedcards,$card[3]);
						
						?><li uk-filter-control="filter: [card-type='<?php echo strtolower($card[3]) ?>']; group: color"><a href="#"><?php echo $card[3] ?></a></li>
						
					<?php } 
					
				}?>
			</ul>
			<?php
		}
	}
	
	/**
	* Builds overview navigation
	* @since 1.4
	*/
	public function build_head(){
		
		$gavar_url = get_avatar_url(get_current_user_id());
		
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
		
		
		$optionname = 'admin2020_google_analytics';
		$a2020_options = get_option( 'admin2020_settings' );
		$googleStatus = true;
		
		if(!isset($a2020_options['modules'][$optionname]['view_id']) || !isset($a2020_options['modules'][$optionname]['refresh_token']) || $a2020_options['modules'][$optionname]['view_id'] == '' ||  $a2020_options['modules'][$optionname]['refresh_token'] == ''){
			
			$googleStatus = false;
			
		}
		
		?>
		
		<div class="" uk-grid>
			
			<!-- GREETING -->
			<div class="uk-width-1-1@s uk-width-expand@m">
				<div class="uk-grid-small" uk-grid>
					<div class="uk-width-auto">
						
						<?php 
						if(strpos($gavar_url,'gravatar.com')==!false){ ?>
							
							<span class="uk-icon-button uk-button-primary uk-text-bold uk-text-small uk-border-circle" style="font-size:16px;height:50px;width:50px;"><?php echo $name_string?></span>
							
						<?php } else { ?>
						
							<img class="uk-border-circle" style="height:50px;width:50px;" src="<?php echo $gavar_url ?>">
						
						<?php } ?>
						
					</div>
					<div class="uk-width-expand">
						<div class="uk-h3 uk-margin-remove-bottom">
							<?php echo __('Hello', 'admin2020').' '.$displayname;?>
						</div>
						<span class="uk-text-meta"><?php echo date('jS F Y') ?></span>
					</div>
				</div>
			</div>
			<!-- OPTIONS -->
			<div class="uk-width-1-1@s uk-width-auto@m">
				
				<div class="uk-grid-small" uk-grid>
				
					<div class="uk-width-auto">
						<div class="uk-inline">
							<span class="uk-form-icon" uk-icon="icon: calendar"></span>
							<input class="uk-input uk-form-small" type="text" id="admin2020-date-range">
						</div>
					</div>
						
					<div class="uk-width-auto">	
						<button class="uk-button uk-button-default uk-button-small a2020_make_light a2020_make_square"><span uk-icon="icon:settings;ratio:0.8"></span></button>
						
						<div uk-dropdown="mode: click;pos: bottom-right">
							
							
							<div class="uk-h5 uk-margin-bottom"><?php _e('Active Cards','admin2020')?></div>
							<ul class="uk-nav uk-nav-default" id="admin2020-visible-cards" style="max-height:500px;overflow:auto;">
							
								<?php
								$cat_check = array();
								$card_visibility = $this->utils->get_user_preference('dash_visibility');
								
								if(!is_array($card_visibility)){
									$card_visibility = array();
								}
								
								$thecards = $this->cards;
								
								usort($thecards, function($a, $b){
									return strcmp($a[3], $b[3]);
								});
								
								
								foreach ($thecards as $card){
								
									$category = $card[3];
									if(!in_array($category,$cat_check)){
										?>
										<li class="uk-nav-header"><?php echo ucwords($category) ?></li>
										<?php
									}
									array_push($cat_check,$category);
									
									$visible = 'checked';
									if(in_array($card[1],$card_visibility)){
										$visible = '';
									}
									?>
									<li >
										<a style="background:none;" href="#">
											<label><input class="uk-checkbox uk-margin-small-right" <?php echo $visible ?> value='1' type="checkbox" name="<?php echo $card[1] ?>"> <?php echo $card[2]?></label>
										</a>
									</li>
									<?php
								}
								
								?>
							</ul>
							<button class="uk-button uk-button-primary uk-margin-top uk-width-1-1" type="button" onclick="admin2020_save_visibility()"><?php _e("Save","admin2020")?></button>
						</div>
						
						
						<?php if( $googleStatus ) {?>
						
						<button class="uk-button uk-button-default uk-button-small a2020_make_light a2020_make_square"><span uk-icon="icon:cog;ratio:0.8"></span></button>
						
						<div uk-dropdown="mode: click;pos: bottom-right">
							
							<button class="uk-button uk-button-danger uk-button-small" onclick="a2020_remove_analytics()"><?php _e('Disconnect google account','admin2020') ?></button>
							
						</div>
						
						<?php } ?>
						
					</div>
				</div>
				
			</div>
			
		</div>
		<?php
	}
	
	/**
	* Builds overview card wrap
	* @since 1.4
	*/
	public function build_card_grid(){
		?>
		<div id="a2020_overview_cards" style="margin-top:30px;" uk-grid="masonry: true" uk-sortable="handle: .a2020_card_drag">
			<?php $this->build_cards() ?>
		</div>
		<?php
	}
	
	/**
	* Builds overview cards
	* @since 1.4
	*/
	public function build_cards($startdate = null, $enddate = null){
		
		$cards = $this->cards;
		
		$card_visibility = $this->utils->get_user_preference('dash_visibility');
		$dashcard_options = $this->utils->get_user_preference('dash_card_options');
		
		if(!is_array($card_visibility)){
			$card_visibility = array();
		}
		
		if(is_array($cards)){
			foreach($cards as $card){
				
				$object = $card[0];
				$function = $card[1];
				$name = $card[2];
				$category = $card[3];
				
				if(in_array($function,$card_visibility)){
					continue;
				}
				if(isset($card[4])){
					if($card[4] != ""){
						
						$name = $card[2];
						$category = $card[3];
						$url = $card[4];
						$type = $card[5];
						
						$object->create_user_videos($name,$url,$category,$type);
					}
				}
				if ( method_exists( $object , $function ) ) {
					
					$primary = '';
					$text = __('Make Primary','admin2020');
					
					if(isset($dashcard_options[$function]['primary'])){
						if($dashcard_options[$function]['primary'] == true){
							$primary = 'uk-card-primary';
							$text = __('Make Default','admin2020');
						}
					}
					
					?>
					
					<div class="uk-width-1-4@xl uk-width-1-3@l uk-width-1-2@m uk-width-1-1@s" id="<?php echo $function ?>" card-type='<?php echo strtolower($category)?>'>
					  <div class="uk-card uk-card-default uk-card-small <?php echo $primary?>">
							  
							  <div class="uk-card-header">
								  <span class="uk-h5 a2020_card_drag"><?php echo $name ?></span>
								  <span uk-icon="icon:more;ratio:0.9" class="uk-text-muted uk-align-right uk-margin-remove" style="cursor: pointer"></span>
								  
								  <div uk-dropdown="mode: click;pos:bottom-right">
									  <ul class="uk-nav uk-dropdown-nav">
										  <li><a href="#" onclick="a2020_make_primary_card('<?php echo $function ?>')"><?php echo $text?></a></li>
										  <li><a href="#" onclick="a2020_hide_single_card('<?php echo $function ?>')"><?php _e('Hide','admin2020')?></a></li>
									  </ul>
								  </div>
							  </div>
							  
							 <?php $object->$function($startdate,$enddate); ?>
							  
					  </div>
					</div>
					<?php
					
				}
				
			}
		}
		
	}
	
	
}

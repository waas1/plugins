<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_content
{
    public function __construct($version, $path, $utilities)
    {
        $this->version = $version;
        $this->path = $path;
        $this->utils = $utilities;
		$this->media_date = '';
		$this->attachment_size = '';
		$this->folders = new Admin_2020_module_admin_folders($this->version,$this->path,$this->utils);
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
		
		
		////AJAX
		add_action('wp_ajax_a2020_get_content', array($this,'a2020_get_content'));
		add_action('wp_ajax_a2020_save_view', array($this,'a2020_save_view'));
		add_action('wp_ajax_a2020_delete_selected', array($this,'a2020_delete_selected'));
		add_action('wp_ajax_a2020_duplicate_selected', array($this,'a2020_duplicate_selected'));
		add_action('wp_ajax_a2020_get_folders', array($this,'a2020_get_folders'));
		add_action('wp_ajax_a2020_create_folder', array($this,'a2020_create_folder'));
		add_action('wp_ajax_a2020_delete_folder', array($this,'a2020_delete_folder'));
		add_action('wp_ajax_a2020_update_folder', array($this,'a2020_update_folder'));
		add_action('wp_ajax_a2020_move_folder', array($this,'a2020_move_folder'));
		add_action('wp_ajax_a2020_move_content_to_folder', array($this,'a2020_move_content_to_folder'));
		add_action('wp_ajax_a2020_process_upload', array($this,'a2020_process_upload'));
		add_action('wp_ajax_a2020_open_quick_edit', array($this,'a2020_open_quick_edit'));
		add_action('wp_ajax_a2020_update_item', array($this,'a2020_update_item'));
		add_action('wp_ajax_a2020_batch_tags_cats', array($this,'a2020_batch_tags_cats'));
		add_action('wp_ajax_a2020_batch_rename_preview', array($this,'a2020_batch_rename_preview'));
		add_action('wp_ajax_a2020_process_batch_rename', array($this,'a2020_process_batch_rename'));
		add_action('wp_ajax_a2020_save_edited_image', array($this,'a2020_save_edited_image'));
		
		
		
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
		$data['title'] = __('Content','admin2020');
		$data['option_name'] = 'admin2020_admin_content';
		$data['description'] = __('Creates the content page where you can manage all of your assets, posts and pages from one place.','admin2020');
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
		$temp['name'] = __('Content Page disabled for','admin2020');
		$temp['description'] = __("Content Page will be disabled for any users or roles you select",'admin2020');
		$temp['type'] = 'user-role-select';
		$temp['optionName'] = 'disabled-for'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Post types available in content page','admin2020');
		$temp['description'] = __("Only the selected post types will be available in the content page.",'admin2020');
		$temp['type'] = 'post-type-select';
		$temp['optionName'] = 'post-types-content'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Enable private library mode','admin2020');
		$temp['description'] = __("When enabled, the content page will only show content created by or uploaded by the currently logged in user. This includes folders..",'admin2020');
		$temp['type'] = 'switch';
		$temp['optionName'] = 'private-mode'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName']);
		$settings[] = $temp;
		
		
		
		return $settings;
		
	}
	/**
	 * Returns settings for module
	 * @since 1.4
	 */
	 public function render_settings(){
		  
		  wp_enqueue_media();
		  
		  $info = $this->component_info();
		  $optionname = $info['option_name'];
		  
		  $disabled_for = $this->utils->get_option($optionname,'disabled-for');
		  $post_types_enabled = $this->utils->get_option($optionname,'post-types-content');
		  $privatemode = $this->utils->get_option($optionname,'private-mode');
		  
		  if($disabled_for == ""){
			  $disabled_for = array();
		  }
		  if($post_types_enabled == ""){
			  $post_types_enabled = array();
		  }
		  ///GET POST TYPES
		  $args = array('public'   => true);
		  $output = 'objects'; 
		  $post_types = get_post_types( $args, $output );
		  ?>
		  <div class="uk-grid" id="a2020_content_settings" uk-grid>
			  <!-- LOCKED FOR USERS / ROLES -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Admin 2020 content page disabled for','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("Admin 2020 Content Page will be disabled for any users or roles you select",'admin2020') ?></div>
			  </div>
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  
				  
				  <select class="a2020_setting" id="a2020-role-types" name="disabled-for" module-name="<?php echo $optionname?>" multiple>
					  
					<?php
					foreach($disabled_for as $disabled) {
						
						?>
						<option value="<?php echo $disabled ?>" selected><?php echo $disabled ?></option>
						<?php
						
					} 
					?>
				  </select>
				  
				  <script>
					  jQuery('#a2020_content_settings #a2020-role-types').tokenize2({
						 placeholder: '<?php _e('Select roles or users','admin2020') ?>',
						 dataSource: function (term, object) {
							 a2020_get_users_and_roles(term, object);
						 },
						 debounce: 1000,
					  });
				  </script>
				  
			  </div>	
			  <div class="uk-width-1-1@ uk-width-1-3@m">
			  </div>	
			  <!-- POST TYPES AVAILABLE IN CONTENT PAGE -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Post types available in content page','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("Only the selected post types will be available in the content page.",'admin2020') ?></div>
			  </div>
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  
				  
				  <select class="a2020_setting" id="a2020-post-types" name="post-types-content" module-name="<?php echo $optionname?>" multiple>
					  <?php
					  foreach ($post_types as $type){
						  $name = $type->name;
						  $label = $type->label;
						  $sel = '';
						  
						  if(in_array($name, $post_types_enabled)){
							  $sel = 'selected';
						  }
						  ?>
						  <option value="<?php echo $name ?>" <?php echo $sel?>><?php echo $label ?></option>
						  <?php
					  }
					  ?>
				  </select>
				  
				  <script>
					  jQuery('#a2020_content_settings #a2020-post-types').tokenize2({
						  placeholder: '<?php _e('Select Post Types','admin2020') ?>'
					  });
					  jQuery(document).ready(function ($) {
						  $('#a2020_content_settings #a2020-post-types').on('tokenize:select', function(container){
							  $(this).tokenize2().trigger('tokenize:search', [$(this).tokenize2().input.val()]);
						  });
					  })
				  </script>
				  
			  </div>	
			  <div class="uk-width-1-1@ uk-width-1-3@m">
					</div>
			  <!-- ENABLE USER ONLY CONTENT -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Enable private library mode','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("When enabled, the content page will only show content created by or uploaded by the currently logged in user. This includes folders.",'admin2020') ?></div>
			  </div>
			  <div class="uk-width-1-1@ uk-width-2-3@m">
				  
				  <?php
				  $checked = '';
				  if($privatemode == 'true'){
					  $checked = 'checked';
				  }
				  ?>
				  
				  <label class="admin2020_switch uk-margin-left">
					  <input class="a2020_setting" name="private-mode" module-name="<?php echo $optionname?>" type="checkbox" <?php echo $checked ?>>
					  <span class="admin2020_slider constant_dark"></span>
				  </label>
				  
			  </div>
		  </div>	
		  
		  <?php
	  }
    /**
     * Adds admin bar styles
     * @since 1.0
     */

    public function add_styles()
    {
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin_2020_content'){
				
				wp_enqueue_editor();
				wp_enqueue_media();
				
				//APP CSS
				wp_register_style('a2020-content-app', $this->path . 'assets/css/modules/admin-content-app.css', array(), $this->version);
				wp_enqueue_style('a2020-content-app');
				
				///FILEPOND IMAGE PREVIEW
				wp_register_style(
					'admin2020_filepond_preview',
					$this->path . 'assets/css/filepond/filepond-image-preview.css',
					array(),
					$this->version
				);
				wp_enqueue_style('admin2020_filepond_preview');
				///FILEPOND 
				wp_register_style(
					'admin2020_filepond',
					$this->path . 'assets/css/filepond/filepond.css',
					array(),
					$this->version
				);
				wp_enqueue_style('admin2020_filepond');
				
				wp_register_style('a2020-doka', $this->path . 'assets/css/doka/doka.css', array(), $this->version);
				wp_enqueue_style('a2020-doka');
				
					
			}
		}
    }
	
	/**
	* Enqueue Admin Bar 2020 scripts
	* @since 2.9
	*/
	
	public function add_scripts(){
		
		if(isset($_GET['page'])) {
		
			if($_GET['page'] == 'admin_2020_content'){
				
				$types = get_allowed_mime_types();
				$temparay = array();
				foreach($types as $type){
					array_push($temparay,$type);
				}
				
				$folderViews = $this->utils->get_user_preference('content_folder_view');
				$perpage = $this->utils->get_user_preference('content_per_page');
				$gridsize = $this->utils->get_user_preference('content_grid_size');
				$viewmode = $this->utils->get_user_preference('content_view_mode');
				
				
				
				if(!is_numeric($perpage)){
					$perpage = 20;	
				}
				
				if(!is_numeric($gridsize)){
					$gridsize = 5;	
				}
				
				if(!$viewmode){
					$viewmode = 'list';
				}
				
				$renameOptions = array(
					array (
						'name' => "Original Filename",
						'label' => __("Original Title / Value",'admin2020'),
					), 
					array (
						'name' => "Text",
						'label' => __("Text",'admin2020'),
					), 
					array (
						'name' => "Date Created",
						'label' => __("Date Created",'admin2020'),
					), 
					array (
						'name' => "File Extension",
						'label' => __("File Extension (attachments only)",'admin2020'),
					), 
					array (
						'name' => "Sequence Number",
						'label' => __("Sequence Number",'admin2020'),
					), 
					array (
						'name' => "Meta Value",
						'label' => __("Meta Value",'admin2020'),
					), 
					array (
						'name' => "Find and Replace",
						'label' => __("Find and Replace",'admin2020'),
					)
				);
				
					
					
				
				$preferences['perPage'] = $perpage;
				$preferences['folderView'] = $folderViews;
				$preferences['gridSize'] = $gridsize;
				$preferences['viewMode'] = $viewmode;
				$preferences['renameOptions'] = $renameOptions;
				
				
				
				
				////FILEPOND PLUGINS
				wp_enqueue_script('a2020_filepond_encode', $this->path . 'assets/js/filepond/filepond-file-encode.min.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_filepond_preview', $this->path . 'assets/js/filepond/filepond-image-preview.min.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_filepond_orientation', $this->path . 'assets/js/filepond/filepond-orientation.min.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_filepond_validate', $this->path . 'assets/js/filepond/filepond-validate-size.min.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_filepond_file_types', $this->path . 'assets/js/filepond/filepond-file-types.min.js', array('jquery'),$this->version);
				////FILEPOND
				wp_enqueue_script('a2020_filepond', $this->path . 'assets/js/filepond/filepond.min.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_filepond_jquery', $this->path . 'assets/js/filepond/filepond-jquery.min.js', array('jquery'),$this->version);
				////DOKA
				wp_enqueue_script('a2020_doka', $this->path . 'assets/js/doka/doka.js', array('jquery'),$this->version);
				wp_enqueue_script('a2020_doka', $this->path . 'assets/js/doka/doka.js', array('jquery'),$this->version);

				
				///LOAD CONTENT APP IN FOOTER
				wp_enqueue_script('admin-content-app', $this->path . 'assets/js/admin2020/admin-content-app.min.js', array('jquery'),$this->version, true);
				wp_localize_script('admin-content-app', 'a2020_content_ajax', array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'security' => wp_create_nonce('a2020-content-security-nonce'),
					'a2020_allowed_types' => json_encode($temparay),
					'a2020_content_prefs' => json_encode($preferences),
				));
				
			
			
			}
		}
	  
	}
	
	
	
	/**
	* Processes file upload from image editor
	* @since 1.4
	*/
	public function a2020_save_edited_image() {
		
		  if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
	
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
			$current_imageid = $this->utils->clean_ajax_input($_POST['attachmentid']);
			$new_file =  $_FILES['ammended_image'];
	
			$upload_overrides = array(
			  'test_form' => false
			);
	
	
			$movefile = wp_handle_upload( $new_file, $upload_overrides );
			////ADD Attachment
			if (is_wp_error($movefile)) {
				$message = __("Unable to save attachment",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
	
			$status = update_attached_file($current_imageid,$movefile['file']);
			////ADD Attachment
			if (!$status) {
				$message = __("Unable to save attachment",'admin2020');
				echo $this->utils->ajax_error_message($message);
				die();
			}
	
			$attach_data = wp_generate_attachment_metadata( $current_imageid, $movefile['file'] );
			$status = wp_update_attachment_metadata( $current_imageid, $attach_data );
			
			$returndata = array();
			$returndata['message'] = __('Image Saved','admin2020');
			$returndata['src'] = wp_get_attachment_url($current_imageid);
	
			////END ATTACHMENT
			echo json_encode($returndata);
		  }
		  die();
	}
	
	/**
	* Processes file upload
	* @since 1.4
	*/
	
	public function a2020_process_upload(){
	
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
			$folder = $this->utils->clean_ajax_input($_POST['folder']);
	
			  foreach ($_FILES as $file){
	
				$uploadedfile = $file;
				$upload_overrides = array(
				  'test_form' => false
				);
	
	
				$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
				
				// IF ERROR
				if (is_wp_error($movefile)) {
					http_response_code(400);
					$returndata['error'] = __('Failed to upload file','admin2020');
					echo json_encode($returndata);
					die();
				}
				////ADD Attachment
	
				$wp_upload_dir = wp_upload_dir();
				$withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $uploadedfile['name']);
	
					$attachment = array(
						"guid" => $movefile['url'],
						"post_mime_type" => $movefile['type'],
						"post_title" => $withoutExt,
						"post_content" => "",
						"post_status" => "published",
					);
	
					$id = wp_insert_attachment( $attachment, $movefile['file'],0);
	
					$attach_data = wp_generate_attachment_metadata( $id, $movefile['file'] );
					wp_update_attachment_metadata( $id, $attach_data );
					
					
					if(is_numeric($folder) && $folder > 0){
						
						update_post_meta($id,"admin2020_folder",$folder);
						
					}
	
				////END ATTACHMENT
	
	
			  }
			  //echo $this->build_media();
			  http_response_code(200);
			  $returndata['message'] = __('Items uploaded','admin2020');
			  echo json_encode($returndata);
			
		}
		die();
	
	}
	
	/**
	* Saves new view
	* @since 2.9
	*/
	
	public function a2020_save_view(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			
			$views = $this->utils->clean_ajax_input($_POST['allViews']);
			
			$a2020_options = get_option( 'admin2020_settings' ); 
			
			$a2020_options['modules']['admin2020_admin_content']['views'] = $views;
			
			update_option( 'admin2020_settings', $a2020_options);
			
			$returndata = array(
				'message' => __('Views updated','admin2020'),
			);
			
			echo json_encode($returndata);
			
			
		}
		die();
	}
	
	
	/**
	* Deletes selected items
	* @since 2.9
	*/
	
	public function a2020_duplicate_selected(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			$itemIDs = $this->utils->clean_ajax_input($_POST['selected']);
			$returndata = array();
			$returndata['totalduplicated'] = 0;
			$returndata['totalfailed'] = 0;
			
			if($itemIDs && is_array($itemIDs)){
				
				
				
					
				foreach($itemIDs as $item){
						
						
						$status = $this->a2020_duplicate_post($item);
						
						if($status){
							$returndata['totalduplicated'] += 1;
						} else {
							$returndata['totalfailed'] += 1;
						}
						
					
				}
					
				
			} else {
				
				$returndata['error'] = __("Something went wrong",'admin2020');
				echo json_encode($returndata);
				die();
				
			}
			
			
			$returndata['deleted_message'] = __("Items duplicated succesffuly",'admin2020');
			$returndata['deleted_total'] = $returndata['totalduplicated'];
			
			$returndata['failed_message'] = __("Itms couldn't be duplicated",'admin2020');
			$returndata['failed_total'] = $returndata['totalfailed'];
			echo json_encode($returndata);
			die();
			
		}
		
		die();
		
	}
	
	
	/**
	* Batch rename preview
	* @since 2.9
	*/
	
	public function a2020_batch_rename_preview(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			$itemIDs = $this->utils->clean_ajax_input($_POST['selected']);
			$batchOptions = $this->utils->clean_ajax_input($_POST['batchoptions']);
			$fieldtorename = $this->utils->clean_ajax_input($_POST['fieldToRename']);
			$metaKey = $this->utils->clean_ajax_input($_POST['metaKey']);
			
			$returndata = array();
			$returndata['newnames'] = array();
			$returndata['options'] = $batchOptions;
			
			if($itemIDs && is_array($itemIDs)){
				
				$sequence = 0;
				
					
				foreach($itemIDs as $item){
					
						$temp = array();
						
						
						if($fieldtorename == 'name'){
							$temp['current'] = get_the_title($item);
						}
						
						if($fieldtorename == 'meta'){
							if(!$metaKey || $metaKey == ''){
								$temp['current'] = __('No Meta Key provided','admin2020');
							} else {
								$temp['current'] = get_post_meta($item, $metaKey, true);
							}
						}
						
						if($fieldtorename == 'alt'){
							$temp['current'] = get_post_meta($item, '_wp_attachment_image_alt', true);
						}
						
						$temp['new'] = $this->generate_new_name($item, $batchOptions, $sequence, $fieldtorename, $metaKey);
						$sequence += 1;
						
						array_push($returndata['newnames'], $temp);
					
				}
					
				
			} else {
				
				$returndata['error'] = __("Something went wrong",'admin2020');
				echo json_encode($returndata);
				die();
				
			}
			
			echo json_encode($returndata);
			die();
			
		}
		
		die();
		
	}
	
	
	public function a2020_process_batch_rename(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			$itemIDs = $this->utils->clean_ajax_input($_POST['selected']);
			$batchOptions = $this->utils->clean_ajax_input($_POST['batchoptions']);
			$fieldtorename = $this->utils->clean_ajax_input($_POST['fieldToRename']);
			$metaKey = $this->utils->clean_ajax_input($_POST['metaKey']);
			
			
			
			
			$returndata = array();
			$returndata['newnames'] = array();
			$returndata['options'] = $batchOptions;
			
			if($fieldtorename == 'meta'){
				if(!$metaKey || $metaKey == ''){
					$returndata['error'] = __('No Meta Key provided','admin2020');
					echo json_encode($returndata);
					die();
				}
			}
			
			if($itemIDs && is_array($itemIDs)){
				
				$sequence = 0;
				
					
				foreach($itemIDs as $item){
					
						$temp = array();
						$postType = get_post_type($item);
						
						$newvalue = $this->generate_new_name($item, $batchOptions, $sequence, $fieldtorename, $metaKey);
						$sequence += 1;
						
						
						if($fieldtorename == 'name'){
							$update = array(
								'ID'           => $item,
								'post_title'   => $newvalue,
							);
							 
							wp_update_post( $update );
						}
						
						if($fieldtorename == 'meta'){
							update_post_meta($item, $metaKey, $newvalue);
						}
						
						if($fieldtorename == 'alt'){
							if($postType == 'attachment'){
								update_post_meta($item, '_wp_attachment_image_alt', $newvalue);
							}
						}
						
				}
					
				
			} else {
				
				$returndata['error'] = __("Something went wrong",'admin2020');
				echo json_encode($returndata);
				die();
				
			}
			$returndata['message'] = __('Attributes Updated','admin2020');
			echo json_encode($returndata);
			die();
			
		}
		
		die();
		
	}
	
	
	public function generate_new_name($item, $options, $sequence, $fieldtorename, $metaKey){
		
		if($fieldtorename == 'name'){
			$name = get_the_title($item);
		}
		
		if($fieldtorename == 'meta'){
			if(!$metaKey || $metaKey == ''){
				$name = '';
			} else {
				$name = get_post_meta($item, $metaKey, true);
			}
		}
		
		if($fieldtorename == 'alt'){
			$name = get_post_meta($item, '_wp_attachment_image_alt', true);
		}
		
		$postType = get_post_type($item);
		$newname = '';
		
		foreach($options as $option){
			
			$type = $option['name'];
			
			if($type == 'Text'){
				
				$textValue = $option['primaryValue'];
				$newname = $newname . $textValue;
				
			}
			
			if($type == 'Original Filename'){
				
				$newname = $newname . $name;
				
			}
			
			if($type == 'Date Created'){
				
				$format = $option['primaryValue'];
				$thedate =  get_the_date($format,$item);
				$newname = $newname . $thedate;
				
			}
			
			if($type == 'File Extension'){
				
				
				if($postType != 'attachment'){
					continue;
				}
				$attachment_url = wp_get_attachment_url($item);
				$filetype = wp_check_filetype($attachment_url);
				$extension = $filetype['ext'];
				$newname = $newname . $extension;
				
			}
			
			if($type == 'Sequence Number'){
				
				$start_number = $option['primaryValue'];
				if(!is_numeric($start_number)){
				  $start_number = 0;
				}
				$thenum = $start_number + $sequence;
				
				$newname = $newname . $thenum;
				
			}
			
			if($type == 'Meta Value'){
				
				$metakey = $option['primaryValue'];
				if(!$metakey || $metakey == ''){
					continue;
				}
				$value = get_post_meta($item, $metakey, true);
				
				if(!$value || $value == ''){
					continue;
				}
				$newname = $newname . $value;
				
			}
			
			if($type == 'Find and Replace'){
				
				$find = $option['primaryValue'];
				$replace = $option['secondaryValue'];
				$output = str_replace($find,$replace,$name);
				$newname = $newname . $output;
				
			}
			
			
			
			
		}
		
		return $newname;
		
		
	}
	
	/**
	* Duplicates a single post
	* @since 2.9
	*/
	public function a2020_duplicate_post($post_id){
			
			global $wpdb;
			$post = get_post( $post_id );
			
			$current_user = wp_get_current_user();
			$new_post_author = $current_user->ID;
			
			$args = array(
				'comment_status' => $post->comment_status, 
				'ping_status'    => $post->ping_status,
				'post_author'    => $new_post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'post_status'    => 'draft',
				'post_title'     => $post->post_title.' (copy)',
				'post_type'      => $post->post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order
			);
			
			$new_post_id = wp_insert_post( $args );
			
			if(!$new_post_id){
				return false;
			}
			
			$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
			foreach ($taxonomies as $taxonomy) {
				$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
				wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
			}
			
			$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
			if (count($post_meta_infos)!=0) {
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				foreach ($post_meta_infos as $meta_info) {
					
			    	$meta_key = $meta_info->meta_key;
			    	if( $meta_key == '_wp_old_slug' ) continue;
			  	  	$meta_value = addslashes($meta_info->meta_value);
			  		$sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
					  
				}
				
				$sql_query.= implode(" UNION ALL ", $sql_query_sel);
				$wpdb->query($sql_query);
				
			}
			
			$postobject = get_post($new_post_id);
			
			return true;
			
	}
	
	/**
	* Deletes selected items
	* @since 2.9
	*/
	
	public function a2020_delete_selected(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			$itemIDs = $this->utils->clean_ajax_input($_POST['selected']);
			$returndata = array();
			$returndata['totaldeleted'] = 0;
			$returndata['totalfailed'] = 0;
			
			if($itemIDs && is_array($itemIDs)){
				
				
				
					
				foreach($itemIDs as $item){
					
					$currentID = get_current_user_id();
					
					if(!current_user_can('delete_post', $item)){
						
						
						$returndata['totalfailed'] += 1;
						
					} else {
						
						if(get_post_type($item) == 'attachment'){
							$status = wp_delete_attachment($item);
						} else {
							$status = wp_delete_post($item);
						}
						
						
						if($status){
							
							$returndata['totaldeleted'] += 1;
							
						} else {
							
							$returndata['totalfailed'] += 1;
						}
						
					}
						
					
				}
					
				
			} else {
				
				$returndata['error'] = __("Something went wrong",'admin2020');
				echo json_encode($returndata);
				die();
				
			}
			
			
			$returndata['deleted_message'] = __("Items deleted succesffuly",'admin2020');
			$returndata['deleted_total'] = $returndata['totaldeleted'];
			
			$returndata['failed_message'] = __("Itms couldn't be deleted",'admin2020');
			$returndata['failed_total'] = $returndata['totalfailed'];
			echo json_encode($returndata);
			die();
			
		}
		
		die();
		
	}
	
	/**
	* Build content for front end app
	* @since 2.9
	*/
	
	public function a2020_get_folders(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			$args = array(
			  'numberposts' => -1,
			  'post_type'   => 'admin2020folders',
			  'orderby' => 'title',
			  'order'   => 'ASC',
			);
			
			$info = $this->component_info();
			$optionname = $info['option_name'];
			$privatemode = $this->utils->get_option($optionname,'private-mode');
			
			if($privatemode == 'true'){
				$args['author'] = get_current_user_id();
			}
			
			$folders = get_posts( $args );
			$structure = array();
			$folderIDS = array();
			
			foreach ($folders as $folder){
				
				array_push($folderIDS, $folder->ID);
				
			}
			
			$args = array('public'   => true);
			$output = 'objects'; 
			$post_types = get_post_types( $args, $output );
			$types = array();
			
			
			foreach($post_types as $posttype){
				array_push($types,$posttype->name);
			}
			
			///QUERY CONTENT
			$args = array(
				'post_type'=> $types,
				'fields' => 'ids',
				'posts_per_page' => -1,
				'post_status' => 'any',
				'meta_query' => array(
					 array(
						'key' => 'admin2020_folder',
						'value' => $folderIDS,
						'compare' => 'IN'
					)
				),
			);
			
			$query = new WP_Query($args);
			$contentWithFolder = $query->get_posts();
			$contentCount = array();
			
			foreach($contentWithFolder as $item){
				
				$folderid = get_post_meta($item, 'admin2020_folder', true);
				
				if(isset($contentCount[$folderid])){
					$contentCount[$folderid] += 1;
				} else {
					$contentCount[$folderid] = 1;
				}
				
			}
			
			
			foreach ($folders as $folder){
				
				  $parent_folder = get_post_meta($folder->ID, "parent_folder",true);
				  
				  if(!$parent_folder){
					$structure[] =  $this->build_folder_structure($folder,$folders,$contentCount);
				  }
				
			}
			
			
			
			
			echo json_encode($structure);
		}
		die();
	}
	
	
	
	/**
	* Build content for front end app
	* @since 2.9
	*/
	
	public function a2020_create_folder(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			
			$folders = $this->utils->clean_ajax_input($_POST['folders']);
			$name = $folders['name'];
			$color = $folders['color'];
			$parent = $folders['parent'];
			
			if(!$name){
				$returndata['error'] = __("Title is required",'admin2020');
				echo json_encode($returndata);
				die();
			}
			
			if(!$color){
				$returndata['error'] = __("Colour is required",'admin2020');
				echo json_encode($returndata);
				die();
			}
			
			
			$my_post = array(
			  'post_title'    => $name,
			  'post_status'   => 'publish',
			  'post_type'     => 'admin2020folders'
			);
			
			// Insert the post into the database.
			$thefolder = wp_insert_post( $my_post );
			
			if(!$thefolder){
				$returndata['error'] = __("Something went wrong",'admin2020');
				echo json_encode($returndata);
				die();
			}
			
			update_post_meta($thefolder,"color_tag",$color);
			
			if(is_numeric($parent) && $parent > 0){
				update_post_meta($thefolder,"parent_folder",$parent);
			}
			
			$returndata['message'] = __("Folder created",'admin2020');
			echo json_encode($returndata);
			die();
			

		}
		die();
	}
	
	/**
	* Updates folder
	* @since 2.9
	*/
	
	public function a2020_update_folder(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			
			$folder = $this->utils->clean_ajax_input($_POST['thefolder']);
			
			
			$foldername = $folder['name'];
			$folderid = $folder['id'];
			$foldertag = $folder['color'];
			
			$my_post = array(
			  'post_title'    => $foldername,
			  'post_status'   => 'publish',
			  'ID'            => $folderid,
			);
			
			// Insert the post into the database.
			$thefolder = wp_update_post( $my_post );
			
			if(!$thefolder){
				$returndata = array();
				$returndata['error'] = __('Something went wrong','admin2020');
				echo json_encode($returndata);
				die();
			}
			
			update_post_meta($folderid,"color_tag",$foldertag);
			
			$returndata = array();
			$returndata['message'] = __('Folder updated','admin2020');
			echo json_encode($returndata);
			
		}
		die();
		
	}
	
	
	/**
	* Moves Folder
	* @since 2.9
	*/
	public function a2020_move_folder(){
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			$folderToMove = $this->utils->clean_ajax_input($_POST['folderiD']);
			$destination = $this->utils->clean_ajax_input($_POST['destinationId']);
			
			if($folderToMove == $destination){
				$returndata['error'] = __('Unable to move folder into itself','admin2020');
				echo json_encode($returndata);
				die();
			}
			
			$currentParent = get_post_meta($folderToMove,"parent_folder",true);
			
			if($destination == "toplevel"){
				$status = delete_post_meta($folderToMove,"parent_folder");
			} else {
				$status = update_post_meta($folderToMove,"parent_folder",$destination);
			}
			
			
			
			if($status != true){
				
				$returndata['error'] = __('Unable to move folder','admin2020');
				echo json_encode($returndata);
				die();
				
			}
			
			
			///CHECK IF WE NEED TO MAKE SUB FOLDERS TOP LEVEL
			if(!$currentParent || $currentParent == ''){
					
					
					$args = array(
					  'numberposts' => -1,
					  'post_type'   => 'admin2020folders',
					  'orderby' => 'title',
					  'order'   => 'ASC',
					  'meta_query' => array(
						  array(
							  'key' => 'parent_folder',
							  'value' => $folderToMove,
							  'compare' => '=',
						  )
					  )
					);
					
					$folders = get_posts( $args );
					
					foreach ($folders as $folder){
						
						delete_post_meta($folder->ID,"parent_folder");
						
					}
					
			}
			
			$returndata['message'] = __('Folder moved','admin2020');
			echo json_encode($returndata);
			die();
			
		}
		die();
	}
	
	
	/**
	* Moves content to Folder
	* @since 2.9
	*/
	public function a2020_move_content_to_folder(){
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			$contentIds = $this->utils->clean_ajax_input($_POST['contentID']);
			$destination = $this->utils->clean_ajax_input($_POST['destinationId']);
			
			$contentIds = json_decode($contentIds);
			
			if(!is_array($contentIds)){
				$returndata['error'] = __('No content to move','admin2020');
				echo json_encode($returndata);
				die();
			}
			
			foreach($contentIds as $contentId){
			
				if($destination == "toplevel"){
					$status = delete_post_meta($contentId,"admin2020_folder");
				} else {
					$status = update_post_meta($contentId,"admin2020_folder",$destination);
				}
				
			}
			
			$returndata['message'] = __('Content moved','admin2020');
			echo json_encode($returndata);
			die();
			
		}
		die();
	}
	
	/**
	* Deletes folder
	* @since 2.9
	*/
	
	public function a2020_delete_folder(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			
			$folderID = $this->utils->clean_ajax_input($_POST['activeFolder']);
			
			if(!is_numeric($folderID) && !$folderID > 0){
				$returndata['error'] = __("No folder to delete",'admin2020');
				echo json_encode($returndata);
				die();
			}
			
			$currentParent = get_post_meta($folderID, "parent_folder",true);
			
			$status = wp_delete_post($folderID);
			
			if(!$status){
				$returndata['error'] = __("Unable to delete the folder",'admin2020');
				echo json_encode($returndata);
				die();
			}
			
			$args = array(
			  'numberposts' => -1,
			  'post_type'   => 'admin2020folders',
			  'orderby' => 'title',
			  'order'   => 'ASC',
			  'meta_query' => array(
				  array(
					  'key' => 'parent_folder',
					  'value' => $folderID,
					  'compare' => '=',
				  )
			  )
			);
			
			$folders = get_posts( $args );
			
			foreach ($folders as $folder){
				
				
				if($currentParent) {
					update_post_meta($folder->ID,"parent_folder",$currentParent);
				} else {
					delete_post_meta($folder->ID,"parent_folder");
				}
			}
			
			$returndata['message'] = __('Folder deleted','admin2020');
			echo json_encode($returndata);
			die();
			

		}
		die();
	}
	
	
	public function build_folder_structure($folder, $folders, $contentcount){
		
		
		$temp = array();
		$foldercolor = get_post_meta($folder->ID, "color_tag",true);
		$top_level = get_post_meta($folder->ID, "parent_folder",true);
		$title = $folder->post_title;
		
		$temp['title'] = $title;
		$temp['color'] = $foldercolor;
		$temp['id'] = $folder->ID;
		$temp['count'] = 0;
		
		if(isset($contentcount[$folder->ID])){
			$temp['count'] = $contentcount[$folder->ID];
		}
		
		
		foreach ($folders as $aFolder) {
			
			$folderParent = get_post_meta($aFolder->ID, "parent_folder",true);
			
			if($folderParent == $folder->ID){
				
				$temp['subs'][] = $this->build_folder_structure($aFolder,$folders, $contentcount);
				
			}
			
		}
		
		return $temp;
		
	}
	
	/**
	* Builds posts object for quick edits.
	* @since 2.9
	*/
	
	public function a2020_open_quick_edit(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			$itemId = $this->utils->clean_ajax_input($_POST['itemid']);
			$object = get_post($itemId);
			$author = $object->post_author;
			$authorData = get_userdata($author);
			$posttype = get_post_type($itemId);
			
			$statusobject = get_post_status_object($object->post_status);
			$niceStatus = $statusobject->label;
			
			$alltags =  wp_get_post_tags($itemId);
			$selectedTags = array();
			
			foreach($alltags as $tag){
				$selectedTags[] = $tag->term_id;
			}
		
			
			$quickedit['id'] = $itemId;
			$quickedit['title'] = $object->post_title;
			$quickedit['status'] = $niceStatus;
			$quickedit['author'] = $authorData->user_login;
			$quickedit['created'] = get_the_date( get_option('date_format'), $itemId );
			$quickedit['modified'] = get_the_modified_date( get_option('date_format'), $itemId );
			$quickedit['postType'] = $posttype;
			$quickedit['url'] = get_post_permalink($itemId);
			
			if($posttype == 'attachment'){
				
				$meta = wp_get_attachment_metadata($itemId);
				$mime = get_post_mime_type($itemId);
				$actualMime = explode("/", $mime);
				$actualMime = $actualMime[0];
				
				$quickedit['fileSize'] = $this->utils->formatBytes(filesize( get_attached_file( $itemId ) ));
				$quickedit['dimensions'] = $meta['width'] . 'px ' . $meta['height'] . 'px';
				$quickedit['serverName'] = $meta['file'];
				$quickedit['photoMeta'] = $meta['image_meta'];
				$quickedit['shortMime'] = $actualMime;
				$quickedit['src'] = wp_get_attachment_url($itemId);
				$quickedit['alt'] = get_post_meta($itemId , '_wp_attachment_image_alt', true);
				$quickedit['description'] = $object->post_content;
				$quickedit['caption'] = $object->post_excerpt;
				
				if (strpos($mime, '/zip') !== false) {
					$quickedit['icontype'] = 'icon';
					$quickedit['icon'] = 'inventory_2';
				}
				
				if (strpos($mime, '/pdf') !== false) {
					$quickedit['icontype'] = 'icon';
					$quickedit['icon'] = 'picture_as_pdf';
					$quickedit['pdf'] = true;
				}
				
				
				
				if (strpos($mime, 'text') !== false) {
					$quickedit['icontype'] = 'icon';
					$quickedit['icon'] = 'description';
				}
				
				if (strpos($mime, '/csv') !== false) {
					$quickedit['icontype'] = 'icon';
					$quickedit['icon'] = 'view_list';
				}
				
				
			} else {
				
				$quickedit['selectedStatus'] = array($object->post_status);
				$quickedit['selectedCategories'] = wp_get_post_categories($itemId);
				$quickedit['selectedTags'] = $selectedTags;
				
			}
			
			echo json_encode($quickedit); 
			
		}
		die();
	}
	
	
	/**
	* Update item from quick edit
	* @since 2.9
	*/
	
	public function a2020_update_item(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			$itemObject = $this->utils->clean_ajax_input($_POST['options']);
			$itemId = $itemObject['id'];
			$posttype = get_post_type($itemId);
			
			if($posttype != 'attachment'){
			
				$updatePost = array(
				  'ID'           => $itemId,
				  'post_title'   => $itemObject['title'],
				);
				
				if(isset($itemObject['selectedStatus'])){
					$updatePost['post_status'] = $itemObject['selectedStatus'][0];
				}
				
				$status = wp_update_post( $updatePost );
				
				if(isset($itemObject['selectedCategories'])){
					wp_set_post_categories($itemId, $itemObject['selectedCategories']);
				}
				
				if(isset($itemObject['selectedTags'])){
					
					foreach ($itemObject['selectedTags'] as $tag){
						$alltags[] = (int)$tag;
					}
					wp_set_post_tags($itemId, $alltags, false);
				}
				
				if($status == 0){
					$returndata['error'] = __("Unable to update item",'admin2020');
					echo json_encode($returndata);
					die();
				}
				
				$statusobject = get_post_status_object($itemObject['selectedStatus'][0]);
				$niceStatus = $statusobject->label;
				
				
				
			} else {
				
				
				$attachment = array(
					'ID' => strip_tags($itemId),
					'post_title' => strip_tags($itemObject['title']),
					'post_content' => strip_tags($itemObject['description']),
					'post_excerpt' => strip_tags($itemObject['caption']),
				);
				
				
				update_post_meta($itemId , '_wp_attachment_image_alt', strip_tags($itemObject['alt']));
				$status = wp_update_post( $attachment);
				
				if(!$status){
					$message = __("Unable to save attachment",'admin2020');
					echo $this->utils->ajax_error_message($message);
					die();
				}
				
				$postObj = get_post($itemId);
				$status = $postObj->post_status;
				$statusobject = get_post_status_object($status);
				$niceStatus = $statusobject->label;
				
				
				
			}
			
			
			
			$returndata['message'] = __('Item updated','admin2020');
			$returndata['status'] = $niceStatus;
			echo json_encode($returndata);
			
		}
		die();
	}
	
	
	/**
	* Update item from quick edit
	* @since 2.9
	*/
	
	public function a2020_batch_tags_cats(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			$selected = $this->utils->clean_ajax_input($_POST['selected']);
			$data = $this->utils->clean_ajax_input($_POST['theTags']);
			$replaceTags = $data['replaceTags'] == 'true';
			$replaceCats = $data['replaceCats'] == 'true';
			$alltags = array();
			
			
			foreach($selected as $itemId){
				
				$posttype = get_post_type($itemId);
			
				if($posttype != 'attachment'){
				
					if(isset($data['tags'])){
						
						foreach ($data['tags'] as $tag){
							$alltags[] = (int)$tag;
						}
						
						wp_set_post_tags($itemId, $alltags, $replaceTags);
						
					}
					
					if(isset($data['categories'])){
							
						wp_set_post_categories($itemId, $data['categories'], $replaceCats);
							
					}
				}
			}
			
			$returndata['message'] = __('Items updated','admin2020');
			$returndata['status'] = $niceStatus;
			echo json_encode($returndata);
			
		}
		die();
	}
	
	/**
	* Build content for front end app
	* @since 2.9
	*/
	
	public function a2020_get_content(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('a2020-content-security-nonce', 'security') > 0) {
			
			
			
			////CATEGORIES
			$args = array(
				'hide_empty' => false,
			);
			
			$allCategories = get_categories( $args );
			$categories = array();
			
			foreach($allCategories as $category) {
				$temp = array();
				$temp['name'] =  strval($category->term_id);
				$temp['label'] = $category->name;
				$categories[] = $temp;
			}
			
			//TAGS
			$alltags = get_tags();
			$tags = array();
			
			foreach($alltags as $tag){
				$temp = array();
				$temp['name'] =  strval($tag->term_id);
				$temp['label'] = $tag->name;
				$tags[] = $temp;
			}
			
			$searchString = $this->utils->clean_ajax_input($_POST['searchString']);
			$page = $this->utils->clean_ajax_input($_POST['page']);
			$filters = $this->utils->clean_ajax_input($_POST['filters']);
			
			
			
			$date = $filters['date'];
			$dateComparison = $filters['dateComparison'];
			
			$queryStatus = 'any';
			
			$postStatuses = get_post_statuses();
			$statuses = array();
			
			$temp = array();
			$temp['label'] = 'Inherit';
			$temp['name'] = 'inherit';
			$statuses[] = $temp;
			
			foreach($postStatuses as $key => $value) {
				$temp = array();
				$temp['name'] = $key;
				$temp['label'] = $value;
				$statuses[] = $temp;
			}
			
			if(isset($_POST['statuses'])){
				$queryStatus = $this->utils->clean_ajax_input($_POST['statuses']);
			} 
			
			////QUERY POSTS
			$types = array();
			$args = array('public'   => true);
			$output = 'objects'; 
			$post_types = get_post_types( $args, $output );
			$filterPostTypes = array();
			
			$temp = [];
			
			
			foreach($post_types as $posttype){
				array_push($types,$posttype->name);
				$temp = [];
				$temp['label'] = $posttype->label;
				$temp['name'] = $posttype->name;
				$filterPostTypes[] = $temp;
			}
			
			$info = $this->component_info();
			$optionname = $info['option_name'];
			$post_types_enabled = $this->utils->get_option($optionname,'post-types-content');
			$privatemode = $this->utils->get_option($optionname,'private-mode');
			
			if($post_types_enabled && is_array($post_types_enabled)){
				
				$types = $post_types_enabled;
				$filterPostTypes = array();
				
				foreach($types as $atype){
					$typeObject = get_post_type_object($atype);
					$temp = [];
					$temp['label'] = $typeObject->label;
					$temp['name'] = $typeObject->name;
					$filterPostTypes[] = $temp;
				}
				
			} 
			
			if(isset($_POST['types'])){
				$types = $this->utils->clean_ajax_input($_POST['types']);
			} 
			
			
			
			
			$args = array(
			  'post_type' => $types,
			  'post_status' => $queryStatus,
			  'posts_per_page' => $filters['perPage'],
			  'paged' => $page,
			  's' => $searchString,
			);
			
			if($privatemode == 'true'){
				$args['author'] = get_current_user_id();
			}
			
			if($filters['selectedFileTypes']){
				$args['post_mime_type'] = $filters['selectedFileTypes'];
			}
			
			
			if(isset($filters['selectedCategories'])){
				
				$args['category__in'] = $filters['selectedCategories'];
			}
			
			if(isset($filters['selectedTags'])){
				
				$args['tag__in'] = $filters['selectedTags'];
			}
			
			
			if($date && $dateComparison){
				
				if($dateComparison == 'on'){
					
					$year = date('Y',strtotime($date));
					$month = date('m',strtotime($date));
					$day = date('d',strtotime($date));
					
					$args['date_query'] = array(
							'year'  => $year,
							'month' => $month,
							'day' => $day,
					);
					
				} else {  
			
					if($dateComparison == 'before'){
						$args['date_query'] = array(
							array(
								'before' => date('Y-m-d',strtotime($date)),
								'inclusive' => true,
							)
						);
					} else if ($dateComparison == 'after'){
						$args['date_query'] = array(
							array(
								'after' => date('Y-m-d',strtotime($date)),
								'inclusive' => true,
							)
						);
					}
					
				} 
			}
			
			if($filters['activeFolder'] != ''){
				
				if($filters['activeFolder'] == 'uncat'){
					$args['meta_query'] = array(
						array(
							'key' => 'admin2020_folder',
							'compare' => 'NOT EXISTS'
						)
					);
				} else {
			
					$args['meta_query'] = array(
						array(
							'key' => 'admin2020_folder',
							'value' => $filters['activeFolder'],
							'compare' => '='
						)
					);
					
				}
				
			}
				
			wp_reset_query();
			$attachments = new WP_Query($args);
			$totalFound = $attachments->found_posts;
			$foundPosts = $attachments->get_posts();
			$totalPages = $attachments->max_num_pages;
			
			///BUILD RETURN DATA
			$postData = array();
			
			foreach($foundPosts as $item){
				
				$postAuthorId = $item->post_author;
				$authorData = get_userdata($postAuthorId);
				$authorLink = get_edit_profile_url($postAuthorId);
				$postType = get_post_type($item->ID);
				
				$statusObj = get_post_status_object($item->post_status);
				$postStatus = $statusObj->label;
				
				$temp = array();
				$temp['name'] = $item->post_title;
				$temp['type'] = $postType;
				$temp['date'] = get_the_date(get_option('date_format'), $item);
				$temp['author'] = $authorData->user_login;
				$temp['authorLink'] = $authorLink;
				$temp['status'] = $postStatus;
				$temp['id'] = $item->ID;
				$temp['url'] = get_the_permalink($item->ID);
				$temp['editurl'] = get_edit_post_link($item->ID,'&');
				
				
				if($postType == 'attachment'){
					
					$mime = get_post_mime_type($item);
					$actualMime = explode("/", $mime);
					$actualMime = $actualMime[0];
					
					$attachment_info = wp_get_attachment_image_src($item->ID,'thumbnail',true);
					$imageMed = wp_get_attachment_image_src($item->ID,'medium',true);
					$small_src = $attachment_info[0];
					$temp['icontype'] = 'image';
					$temp['icon'] = $small_src;
					$temp['iconLarge'] = $imageMed[0];
					$temp['mime'] = $mime;
					$temp['fileUrl'] = wp_get_attachment_url($item->ID);
					
					if($actualMime == 'audio'){
						$temp['icontype'] = 'icon';
						$temp['icon'] = 'audiotrack';
					}
					
					if($actualMime == 'video'){
						$temp['icontype'] = 'icon';
						$temp['icon'] = 'smart_display';
					}
					
					if (strpos($mime, '/zip') !== false) {
						$temp['icontype'] = 'icon';
						$temp['icon'] = 'inventory_2';
					}
					
					if (strpos($mime, '/pdf') !== false) {
						$temp['icontype'] = 'icon';
						$temp['icon'] = 'picture_as_pdf';
						$temp['pdf'] = true;
					}
					
					if (strpos($mime, 'text') !== false) {
						$temp['icontype'] = 'icon';
						$temp['icon'] = 'description';
					}
					
					
					
				} else {
					
					$image = get_the_post_thumbnail_url($item->ID,'thumbnail');
					$imageMed = wp_get_attachment_url( get_post_thumbnail_id($item->ID) );
					
					
					if($image){
						$temp['icontype'] = 'image';
						$temp['icon'] = $image;
						$temp['iconLarge'] = $imageMed;
					} else {
						$temp['icontype'] = 'icon';
						$temp['icon'] = 'library_books';
					}
					
				}
				
				
				
				$postData[] = $temp;
				
			}
			
			
			
			/////VIEWS 
			$currentviews = array();
			$a2020_options = get_option( 'admin2020_settings' );
			
			if( isset( $a2020_options['modules']['admin2020_admin_content']['views'] ) ) {
				
				$currentviews = $a2020_options['modules']['admin2020_admin_content']['views'];
				
			}
			
			$count = 0;
			$allViews = array();
			
			if($currentviews && is_array($currentviews)){
				foreach($currentviews as $view){
					$view['id'] = $count;
					$count += 1;
					$allViews[] = $view;
				}
			}
			
			$filetypes[] = array('name' => 'image','label' => 'Image');
			$filetypes[] = array('name' => 'video','label' => 'Video');
			$filetypes[] = array('name' => 'application','label' => 'Zip');
			$filetypes[] = array('name' => 'text','label' => 'Text File');
			$filetypes[] = array('name' => 'audio','label' => 'Audio');
			
			$returndata = array();
			$returndata['content'] = $postData;
			$returndata['total'] = $totalFound;
			$returndata['totalPages'] = $totalPages;
			$returndata['postTypes'] = $filterPostTypes;
			$returndata['postStatuses'] = $statuses;
			$returndata['fileTypes'] = $filetypes;
			$returndata['categories'] = $categories;
			$returndata['views'] = $allViews;
			$returndata['tags'] = $tags;
			
			
			echo json_encode($returndata);
			
		}
		die();
		
	}
	
	
	/**
	* Adds media menu item
	* @since 2.9
	*/
	
	public function add_menu_item() {
		
		add_menu_page( '2020_content', __('Content',"admin2020"), 'read', 'admin_2020_content', array($this,'build_content_page'),'dashicons-database', 4 );
		return;
	
	}
	
	/**
	* Build content page
	* @since 2.9
	*/
	
	public function build_content_page(){
		
		?>
		
		<div id="a2020-content-app" class="uk-padding" v-if="masterLoader">
			<template v-if="masterLoader">
				<?php
				$this->build_header();
				$this->build_toolbar();
				$this->active_filters();
				
				?>
				
				<div class="uk-grid uk-grid" >
					
					<div class="uk-width-1-1@s uk-width-1-4@m " v-if="contentTable.folderPanel">
				    <?php $this->build_folders(); ?>
					</div>
					
					<div class="uk-width-expand">	
					<?php $this->build_table(); ?>
					</div>
				</div>
				<?php
				$this->build_batch_options();
				$this->build_quick_edit_modal();
				$this->save_view_options();
				$this->build_batch_tags_and_categories();
				$this->build_batch_rename();
				?>
			</template>
			<?php $this->build_upload_modal(); ?>
		</div>
		<?php
	}
	
	/**
	* Build batch tags and cats modal
	* @since 2.9
	*/
	
	public function build_batch_tags_and_categories(){
		
		?>
		<div id="tags-cats-modal" class="uk-flex-top" uk-modal>
		  <div class="uk-modal-dialog uk-modal-body uk-margin-auto-vertical uk-padding-remove">
			<div class="uk-padding" style="padding-top: 15px; padding-bottom: 15px;">
			  <h4 class="uk-margin-remove">
				  <span class="material-icons-outlined" style="font-size: 20px; position: relative; top: 3px;">category</span> 
			  		<?php _e('Update Tags and Categories','admin2020')?></h4>
			  <button class="uk-modal-close-default uk-position-small uk-position-top-right uk-icon uk-close" type="button" uk-close=""></button>
			</div>
			<div class="uk-padding a2020-border bottom top">
			
				<div class="uk-margin-bottom">
					<multi-select :options="contentTable.categories" :selected="batchUpdate.categories"
					:name="'<?php _e('Categories','admin2020')?>'"
					:placeholder="'<?php _e('Search categories...','admin2020')?>'"></multi-select>
					
					<div class="uk-margin-small-top">
						<label class="uk-text-meta uk-margin-small-top">
							<input type="checkbox" class="uk-checkbox uk-margin-small-right" v-model="batchUpdate.replaceCats"> 
							<?php _e('Keep existing categories','admin2020') ?>
						</label>
					</div>
				</div>	
				
				
				<div class="">
				  <!--CONTAINER -->
					<multi-select :options="contentTable.tags" :selected="batchUpdate.tags"
					:name="'<?php _e('Tags','admin2020')?>'"
					:placeholder="'<?php _e('Search tags...','admin2020')?>'"></multi-select>
					
					<div class="uk-margin-small-top">
						<label class="uk-text-meta ">
						  <input type="checkbox" class="uk-checkbox uk-margin-small-right" v-model="batchUpdate.replaceTags"> 
						  <?php _e('Keep existing tags','admin2020') ?>
						</label>
					</div>
				</div>
			  
			  
			</div>
			<div class="uk-padding" style="padding-top: 15px; padding-bottom: 15px;">
				<button class="uk-button uk-button-secondary" @click="batchUpdateTagsCats()"> <?php _e('Update','admin2020') ?> </button>
			</div>
		  </div>
		</div>
		<?php
	}
	
	/**
	* Build batch rename modal
	* @since 2.9
	*/
	
	public function build_batch_rename(){
		
		?>
		<div id="batch-rename-modal" class="uk-flex-top" uk-modal>
		  <div class="uk-modal-dialog uk-modal-body uk-margin-auto-vertical uk-padding-remove">
			<div class="uk-padding" style="padding-top: 15px; padding-bottom: 15px;">
			  <h4 class="uk-margin-remove">
				  <span class="material-icons-outlined" style="font-size: 20px; position: relative; top: 3px;">edit</span> 
					  <?php _e('Batch Rename','admin2020')?></h4>
			  <button class="uk-modal-close-default uk-position-small uk-position-top-right uk-icon uk-close" type="button" uk-close=""></button>
			</div>
			<div class="uk-padding a2020-border bottom top">
				
				
				<div class="uk-margin-bottom">
					<div class="uk-grid-small" uk-grid>
						
						<div class="uk-width-1-1">
							<div class="uk-h5"><?php _e('Attribute to rename','admin2020')?></div>
						</div>
						
						<div class="uk-width-1-3">
							<select class="uk-select" v-model="batchRename.selectedAttribute">
									<option value="name"><?php _e('Name','admin2020') ?></option>
									<option value="meta"><?php _e('Meta Key','admin2020') ?></option>
									<option value="alt"><?php _e('Alt Tag (Attachments only)','admin2020') ?></option>
							</select>
						</div>
						
						<div class="uk-width-1-3" v-if="batchRename.selectedAttribute == 'meta'">
							<input  class="uk-input" v-model="batchRename.metaKey" type="text" placeholder="<?php _e('Meta Key Name')?>">
						</div>
						
						
					</div>
				</div>
				
				<div class="uk-margin-bottom">
					<div class="uk-grid-small" uk-grid=>
						
						<div class="uk-width-1-1">
							<div class="uk-h5"><?php _e('Filename Structure','admin2020')?></div>
						</div>
						
						<div class="uk-width-1-3">
							<select class="uk-select" v-model="batchRename.selectedOption">
								<option disabled selected value="0"><?php _e('Rename Item','admin2020')?></option>
								<template v-for="item in batchRename.renameTypes">
									<option :value="item.name">{{item.label}}</option>
								</template>
							</select>
						</div>
						
						<div class="uk-width-1-3">
							<button v-if="batchRename.selectedOption" @click="addBatchNameOption()" class="uk-button uk-button-default"><?php _e('Add','admin2020')?></button>
						</div>
						
						
					</div>
				</div>
			
				<div class="name-items uk-margin-bottom">
					
					
					
					<div v-for="(item, index) in batchRename.selectedTypes">
						
						<div class="uk-grid-small uk-margin" uk-grid>
							
							<div class="uk-width-1-4">
								<select class="uk-select" v-model="item.name">
									<template v-for="type in batchRename.renameTypes">
										<option :value="type.name">{{type.label}}</option>
									</template>
								</select>
							</div>
							
							<div class="uk-width-1-4">
								<input v-if="item.name == 'Text'" v-model="item.primaryValue" class="uk-input" type="text" placeholder="<?php _e('New Text','admin2020')?>">
								
								<input v-if="item.name == 'Date Created'" v-model="item.primaryValue" class="uk-input" type="text" placeholder="<?php _e('Date Format','admin2020')?>">
								
								<input v-if="item.name == 'Sequence Number'" v-model="item.primaryValue" class="uk-input" type="number" placeholder="<?php _e('Start Number','admin2020')?>">
								
								<input v-if="item.name == 'Meta Value'" v-model="item.primaryValue" class="uk-input" type="text" placeholder="<?php _e('Meta key Name','admin2020')?>">
								
								<input v-if="item.name == 'Find and Replace'" v-model="item.primaryValue" class="uk-input" type="text" placeholder="<?php _e('Find','admin2020')?>">
							</div>
							
							<div class="uk-width-1-4">
								<input v-if="item.name == 'Find and Replace'" v-model="item.secondaryValue" class="uk-input" type="text" placeholder="<?php _e('Replace','admin2020')?>">	
							</div>
							
							<div class="uk-width-1-4 uk-flex uk-flex-middle uk-flex-right">
								<span v-if="batchRename.selectedTypes.length > 1" class="uk-margin-right">
									<a href="#" v-if="(index + 1) < batchRename.selectedTypes.length" class="uk-link-muted" @click="moveBatchOptionDown(index)">
										<span class="material-icons-outlined">expand_more</span>
									</a>
									<a href="#" v-if="index > 0" class="uk-link-muted " @click="moveBatchOptionUp(index)">
										<span class="material-icons-outlined">expand_less</span>
									</a>
								</span>
								<a href="#" class="uk-link-muted" @click="removeBatchOption(index)">
									<span class="material-icons-outlined">remove_circle_outline</span>
								</a>
							</div>
							
						</div>
					</div>
					
					
				</div>	
				
				<div v-if="batchRename.preview.length > 0"class="uk-margin">
					<div class="uk-h5"><?php _e('Preview','admin2020')?></div>
					<div class="uk-background-muted uk-padding-small uk-border-rounded a2020-border all" style="max-height: 200px; overflow: auto;">
						<div class="uk-grid uk-grid-small" uk-grid>
							<div class="uk-width-1-2 uk-text-bold"><?php _e('Current','admin2020')?> {{batchRename.selectedAttribute}}</div>
							<div class="uk-width-1-2 uk-text-bold"><?php _e('New','admin2020')?> {{batchRename.selectedAttribute}}</div>
							
							<template v-for="preview in batchRename.preview">
								<div class="uk-width-1-2 ">{{preview.current}}</div>
								<div class="uk-width-1-2 ">{{preview.new}}</div>
							</template>
							
						</div>
					</div>
				</div>
			  
			</div>
			<div class="uk-padding" style="padding-top: 15px; padding-bottom: 15px;">
				<div class="uk-flex uk-flex-between">
					<button class="uk-button uk-button-default" @click="batchRenamePreview()"> <?php _e('Preview','admin2020') ?> </button>
					<button class="uk-button uk-button-secondary" @click="batchRenameProcess()"> <?php _e('Rename','admin2020') ?> </button>
				</div>
			</div>
		  </div>
		</div>
		<?php
	}
	
	/**
	* Build content page header
	* @since 2.9
	*/
	
	public function build_header(){
		
		?>
		<div class="uk-grid uk-grid-small " style="margin-bottom: 30px;">
			<div class="uk-width-expand">
				<div class="uk-h2"><?php _e('Content','admin2020')?></div>
			</div>
			
		</div>
		<div class="uk-grid uk-grid-small uk-margin-bottom" v-if="contentTable.views.allViews.length > 0">
			
			<div class="uk-width-auto">
				<ul class="uk-tab a2020-views" uk-tab  >
					<li :class="{'uk-active' : contentTable.views.currentView == []}">
						<a href="#" @click="resetFilters()"><?php _e('All','admin2020')?></a>
					</li>
					<template v-for="view in contentTable.views.allViews">
						<li >
							<a href="#" @click="setView(view)">{{view.name}}</a>
							<div uk-drop="delay-show:800;pos:top-justify;offset:10">
								<span class='a2020-post-label private'>
									<a href="#" class="uk-text-danger" @click="removeView(view)"><?php _e('Remove','admin2020') ?></a>
								</span>
							</div>
						</li>
					</template>
				</ul>
			</div>
			
		</div>
		<?php
		
	}
	
	/**
	* Build upload modal
	* @since 2.9
	*/
	
	public function build_upload_modal(){
		
		
		$maxupload = $this->utils->formatBytes(wp_max_upload_size());
		$maxupload = str_replace(" ", "", $maxupload);
		?>
			
		
		<div id="a2020-upload-modal" uk-modal>
			<div class="uk-modal-dialog uk-modal-body uk-padding-remove" style="">
				
				
				
				<div class="a2020-border bottom uk-padding-medium" style="padding-bottom: 15px;padding-top:15px;">
					<div class="uk-h4 uk-margin-remove"><?php _e('Upload','admin2020')?></div>
					<button class="uk-modal-close-default" type="button" uk-close></button>
				</div>
				
				<div class="uk-padding-medium">
					
					<input type="file" 
					class="filepond"
					name="filepond" 
					multiple 
					id="a2020_file_upload"
					data-allow-reorder="true"
					data-max-file-size="<?php echo $maxupload?>"
					data-max-files="30">
					
				</div>
			</div>
		</div>
		<?php
	}
	
	
	/**
	* Builds quick edit overlay
	* @since 2.9
	*/
	
	public function build_quick_edit_modal(){
		
		
		
		?>
		
		
		<div id="a2020-quick-edit-modal" class="uk-modal-full" uk-modal style="z-index: 999999" >
			
			<div class="uk-modal-dialog">
				
				
				
				<div class="uk-grid-collapse uk-height-viewport uk-overflow-hidden" uk-grid>
					
					<div class="uk-width-expand a2020-border right uk-height-viewport uk-position-relative uk-overflow-hidden" style="max-height: 100vh;padding-bottom: 100px">
						
						<div class="uk-padding a2020-border bottom" style="padding-top: 10px;padding-bottom:10px;">
							<a href="#" class="uk-link-muted"  onclick="UIkit.modal('#a2020-quick-edit-modal').hide()">
								<span class="material-icons-outlined" style="font-size: 20px; position: relative; top: 6px; left: -4px;">chevron_left</span>
								<span class="uk-text-bold"><?php _e('Back to content','admin2020')?></span>
							</a>
						</div>
						<div class="uk-padding uk-position-relative uk-overflow-auto" style="height: calc(100vh - 45px);padding-bottom: 100px;box-sizing: border-box;">
							
							<div class="" style="margin-bottom: 40px;">
								<div class="uk-h3 uk-margin-small-bottom">{{quickEdit.title}}</div>
								
								<div class="uk-margin-top">
									<span class="a2020-post-label uk-margin-small-right">{{quickEdit.postType}}</span>
									<span class="a2020-post-label" :class="quickEdit.status">{{quickEdit.status}}</span>
								</div>
							</div>
							
							
							
							<div class="uk-text-muted uk-text-bold uk-margin-small-bottom"><?php _e('Details','admin2020')?></div>
							
							<div class="uk-margin-bottom uk-background-muted uk-padding-small uk-border-rounded a2020-border all uk-height-small uk-overflow-auto">
								
								<div class="uk-text-meta uk-margin-small-bottom" uk-tooltip="title:<?php _e('Author','admin2020') ?>;delay:300;pos: top-left" >
									<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px;">person</span>
									<span>{{quickEdit.author}}</span>
								</div>
								<div class="uk-text-meta uk-margin-small-bottom" uk-tooltip="title:<?php _e('Created On','admin2020') ?>;delay:300;pos: top-left">
									<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px;">calendar_today</span>
									<span>{{quickEdit.created}}</span>
								</div>
								<div class="uk-text-meta uk-margin-small-bottom" uk-tooltip="title:<?php _e('Last modified on','admin2020') ?>;delay:300;pos: top-left">
									<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px;">edit</span>
									<span>{{quickEdit.modified}}</span>
								</div>
								
								<div class="uk-text-meta uk-margin-small-bottom">
									<span class="material-icons-outlined uk-margin-small-right uk-margin-small-right" 
									style="font-size: 15px; position: relative; top: 2px; ">link</span>
									<a :href="quickEdit.url">{{quickEdit.url}}</a>
								</div>
								
								<template v-if="quickEdit.postType == 'attachment'">
								
									<div v-if="quickEdit.shortMime == 'image' || quickEdit.shortMime == 'video'" 
									class="uk-text-meta uk-margin-small-bottom" uk-tooltip="title:<?php _e('Dimensions','admin2020') ?>;delay:300;pos: top-left">
										<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px;">photo_size_select_large</span>
										<span>{{quickEdit.dimensions}}</span>
									</div>
									
									<div class="uk-text-meta uk-margin-small-bottom" uk-tooltip="title:<?php _e('File Size','admin2020') ?>;delay:300;pos: top-left">
										<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px;">description</span>
										<span>{{quickEdit.fileSize}}</span>
									</div>
									
									<div class="uk-text-meta uk-margin-small-bottom" uk-tooltip="title:<?php _e('File Name','admin2020') ?>;delay:300;pos: top-left">
										<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px;">dns</span>
										<span>{{quickEdit.serverName}}</span>
									</div>
								
								</template>
								
							</div>
							
							
							
							
							
							<div class="uk-margin">
								<div class="uk-text-muted uk-text-bold uk-margin-small-bottom"><?php _e('Title','admin2020')?></div>
								<input class="uk-input" type="text" v-model="quickEdit.title"  placeholder="<?php _e('Title','admin2020')?>">
							</div>
							
							<template v-if="quickEdit.postType == 'attachment'">
									
									<div class="uk-margin">
										<div class="uk-text-muted uk-text-bold uk-margin-small-bottom"><?php _e('Alt Text','admin2020')?></div>
										<input class="uk-input" type="text" v-model="quickEdit.alt"  placeholder="<?php _e('Alt','admin2020')?>">
									</div>
									
									<div class="uk-margin">
										<div class="uk-text-muted uk-text-bold uk-margin-small-bottom"><?php _e('Caption','admin2020')?></div>
										<textarea cols="5" class="uk-textarea uk-border-rounded" v-model="quickEdit.caption"  placeholder="<?php _e('Caption','admin2020')?>"></textarea>
									</div>
									
									<div class="uk-margin">
										<div class="uk-text-muted uk-text-bold uk-margin-small-bottom"><?php _e('Description','admin2020')?></div>
										<textarea style="height: 75px" class="uk-textarea uk-border-rounded" v-model="quickEdit.description"  placeholder="<?php _e('Description','admin2020')?>"></textarea>
									</div>
									
									
							</template>
							
							<!-- IMAGE META -->
							<template v-if="quickEdit.shortMime == 'image' || quickEdit.shortMime == 'video' || quickEdit.shortMime == 'audio'" >
								
								<template v-if="quickEdit.photoMeta">
									<div class="uk-text-muted uk-text-bold uk-margin-small-bottom"><?php _e('Meta Data','admin2020')?></div>
								
									<div class="uk-margin-bottom uk-background-muted uk-padding-small uk-border-rounded a2020-border all uk-height-small uk-overflow-auto">
										
										<template  v-for="(value, name) in quickEdit.photoMeta">
											  <div class="uk-text-meta uk-margin-small-bottom">
												  <span class="uk-margin-small-right uk-text-bold">{{ name }}:</span>
												  <span> {{ value }}</span>
											  </div>
										</template>
										
									</div>
								</template>
							
							</template>
							
							<template v-if="quickEdit.postType != 'attachment'">
								<div class="uk-margin">
									<div class="uk-text-muted uk-text-bold uk-margin-small-bottom">
										<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px; ">check_circle</span>
										<?php _e('Status','admin2020') ?>
									</div>
									
									<multi-select :options="contentTable.postStatuses" :selected="quickEdit.selectedStatus"
									:single="true"
									:name="'<?php _e('Status','admin2020')?>'"
									:placeholder="'<?php _e('Search status...','admin2020')?>'"></multi-select>
								</div>
								
								<div class="uk-margin">
									<div class="uk-text-muted uk-text-bold uk-margin-small-bottom">
										<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px; ">category</span>
										<?php _e('Categories','admin2020') ?>
									</div>
									
									<multi-select :options="contentTable.categories" :selected="quickEdit.selectedCategories"
									:name="'<?php _e('Categories','admin2020')?>'"
									:placeholder="'<?php _e('Search categories...','admin2020')?>'"></multi-select>
								</div>
								
								<div class="uk-margin">
									<div class="uk-text-muted uk-text-bold uk-margin-small-bottom">
										<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px; ">label</span>
										<?php _e('Tags','admin2020') ?>
									</div>
									
									<multi-select :options="contentTable.tags" :selected="quickEdit.selectedTags"
									:name="'<?php _e('Tags','admin2020')?>'"
									:placeholder="'<?php _e('Search tags...','admin2020')?>'"></multi-select>
								</div>
							</template>
							
							
						</div>
						
						<div class="uk-position-bottom uk-padding a2020-border top uk-flex uk-flex-between  uk-background-default" style="padding-top: 15px;padding-bottom: 15px">
							<button class="uk-button uk-button-danger" type="button" @click="deleteItem(quickEdit.id)" style="border-radius: 4px;"><?php _e('Delete','admin2020')?></button> 
							<button class="uk-button uk-button-secondary" type="button" @click="updateItem()"><?php _e('Update','admin2020')?></button> 
						</div>
						
					</div>
					<div class="uk-width-1-1@s uk-width-2-3@m ">
						
						
						
						<iframe v-if="quickEdit.postType != 'attachment'" :src="quickEdit.url" style="width:100%;height: 100%;"></iframe>
						
						<div v-if="quickEdit.shortMime == 'image'" class="uk-flex uk-flex-middle uk-flex-center uk-height-viewport uk-overflow-auto" style="max-height: 100vh">
							<div class="uk-grid uk-grid-small">
								<div class="uk-width-1-1 uk-flex uk-flex-center">
									<button class="uk-button uk-button-small uk-button-secondary uk-margin-small-bottom" @click="openImageEdit()"><?php _e('Edit Image','admin2020');?></button>
								</div>
								<div class="uk-width-1-1 uk-flex uk-flex-center">
									<img class="uk-border-rounded" :src="quickEdit.src" style="max-width:90%;max-height: auto;" >
								</div>
							</div>
							<!-- <div class="content-image-editor"></div>-->
						</div>
						
						<div v-if="quickEdit.shortMime == 'video' || quickEdit.shortMime == 'audio'" 
						class="uk-flex uk-flex-middle uk-flex-center uk-height-viewport uk-overflow-auto" style="max-height: 100vh">
							<video :src="quickEdit.src" controls uk-video="autoplay: false" style="width: 90%"></video>
						</div>
						
						<iframe v-if="quickEdit.pdf" :src="quickEdit.src" style="width:100%;height: 100%;"></iframe>
						
						<div v-if="quickEdit.shortMime != 'video' && quickEdit.shortMime != 'audio' && quickEdit.shortMime != 'image' && quickEdit.postType == 'attachment' && !quickEdit.pdf" 
						class="uk-flex uk-flex-middle uk-flex-center uk-height-viewport uk-overflow-auto" style="max-height: 100vh">
							
							<!-- IS ICON -->
							<span v-if="quickEdit.icontype == 'icon'" class="material-icons-outlined" style="font-size: 135px;">{{quickEdit.icon}}</span>
							
						</div>
					</div>
					
				</div>
			</div>
			
		</div>
		
		<?php
		
		
	}
	
	/**
	* Build content page header
	* @since 2.9
	*/
	
	public function build_toolbar(){
		
		?>
		
		
		<div class="uk-grid uk-grid-small uk-margin-bottom" uk-grid>
			<div class="uk-width-1-1@s uk-width-expand@m">
				<ul class="uk-iconnav">
					<li><a href="#" 
					:class="{'uk-text-primary' : contentTable.folderPanel == true}"
					@click="switchFolderPanel()"><span class="material-icons-outlined">folder</span></a></li>
					<li>
						<a href="#" uk-tooltip="title: <?php _e('Filters','admin2020')?>;delay:300"><span class="material-icons-outlined">filter_list</span></a>
						<div uk-dropdown="mode: click;pos: bottom-left;"  class="content-table-filters uk-dropdown uk-dropdown-bottom-left " style="width: 500px; max-width: 100%;padding:25px;">
							<?php $this->build_filters(); ?>
						</div>
					</li>
					<li><a href="#" uk-toggle="target:#a2020-upload-modal"><span class="material-icons-outlined">file_upload</span></a></li>
					<li>
						<a href="#"><span class="material-icons-outlined">tune</span></a>
						<div uk-dropdown="mode: click;pos: bottom-left;"  class="uk-dropdown uk-dropdown-bottom-left uk-padding-medium">
							
							<div class="a2020-switch-container uk-margin-bottom">
								  <button  
								  uk-tooltip="title:<?php _e('List Mode','admin2020') ?>;delay:300"
								  :class="{ 'active' : this.contentTable.mode == 'list'}" 
								  @click="this.contentTable.mode = 'list'">
								  	<span class="material-icons-outlined">list</span>
								  </button>
								  
								  <button  
								  uk-tooltip="title:<?php _e('Grid Mode','admin2020') ?>;delay:300"
								  :class="{ 'active' :  this.contentTable.mode == 'grid'}" 
								  @click="this.contentTable.mode = 'grid'">
									  <span class="material-icons-outlined">auto_awesome_mosaic</span>
								  </button>
							</div>
							
							<div class="uk-margin-bottom">
								<div class="uk-text-bold uk-margin-small-bottom">
									<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px; ">article</span>
									<?php _e('Items Per Page','admin2020')?>
								</div>
								<div class="uk-width-1-1">
									<input class="uk-input uk-form-small" type="number" min="1" :max="contentTable.total" v-model="contentTable.filters.perPage">
								</div>
							</div>
							
							<div class="uk-margin-bottom">
								<div class="uk-text-bold uk-margin-small-bottom">
									<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px;">auto_awesome_mosaic</span>
									<?php _e('Columns','admin2020')?>
								</div>
								<div class="uk-width-1-1">
									<input class="uk-range" type="range"  min="1" max="6" step="1" v-model="contentTable.gridSize">
								</div>
							</div>
						</div>
					</li>
					<li>
						<div class="uk-inline" style="top: -2px;">
							<span class="material-icons-outlined uk-form-icon">search</span>
							<input class="uk-input uk-form-small" placeholder="<?php _e('Search...','admin2020')?>" v-model="contentTable.filters.search" 
							style="width:100px;border-color: transparent">
						</div>
					</li>
				</ul>
			</div>
			<div class="uk-width-auto">
				<span class="uk-background-default uk-border-rounded a2020-border all uk-text-meta" style="padding: 5px;display: block">
					{{fileList.length}} 
					<span v-if="contentTable.total > fileList.length">
						<?php _e('of','admin2020')?> 
						{{contentTable.total}}
					</span>
					<?php _e('items','admin2020')?> 
					</span>
			</div>
			<div class="uk-width-auto"  v-if="fileList.length < contentTable.total">
						
						<?php $this->build_pagination(); ?>
					
			</div>
		</div>
		
		<?php
	}
	
	/**
	* Build folders panel
	* @since 2.9
	*/
	public function build_folders(){
		?>
		
		<div class="uk-background-default uk-border-rounded a2020-border all " uk-sticky="offset: 100;media: 1000">
			
			<div class="folder-toolbar  uk-padding-small a2020-border bottom" >
				<ul class="uk-iconnav">
					<li>
						<a href="#" uk-tooltip="delay:300;title:<?php _e('New Folder','admin2020')?>" uk-toggle="target:#newfolderpanel">
							<span class="material-icons" style="font-size: 22px">create_new_folder</span>
						</a>
					</li>
					<li  class="uk-text-right" style="flex-grow: 1;" v-if="folders.activeFolder[0] > 0">
						<a  href="#" @click="confirmDeleteFolder()" >
							<span uk-tooltip="delay:300;title:<?php _e('Delete Folder','admin2020')?>" class="material-icons uk-text-danger" style="font-size: 22px;">delete_outline</span>
						</a>
					</li>
				</ul>
			</div>
			
			<div class="new-folder uk-padding-small a2020-border bottom" id="newfolderpanel" hidden="true">
				
				<div class="uk-text-meta uk-margin-small-bottom"><?php _e('Folder Name','admin2020')?>:</div>
				<input class="uk-input uk-margin-bottom" v-model="folders.newFolder.name" placeholder="<?php _e('Name','admin2020')?>..." type="text">
				
				<div class="" style="margin-bottom: 30px;">
					
					<div class="uk-text-meta uk-margin-small-bottom"><?php _e('Folder Colour','admin2020')?>:</div>
				
					<div class="uk-child-width-auto uk-flex uk-flex-between uk-margin-small-bottom" id="admin2020_foldertag">
						
						<div>
							<a href="#" class="color_tag" style="background-color:#0c5cef" 
							:class="{'selected' : folders.newFolder.color == '#0c5cef'}" @click="folders.newFolder.color = '#0c5cef'"></a>
							<a href="#" class="color_tag" style="background-color:#32d296" 
							:class="{'selected' : folders.newFolder.color == '#32d296'}" @click="folders.newFolder.color = '#32d296'"></a>
							<a href="#" class="color_tag" style="background-color:#faa05a" 
							:class="{'selected' : folders.newFolder.color == '#faa05a'}" @click="folders.newFolder.color = '#faa05a'"></a>
							<a href="#" class="color_tag" style="background-color:#f0506e" 
							:class="{'selected' : folders.newFolder.color == '#f0506e'}" @click="folders.newFolder.color = '#f0506e'"></a>
							<a href="#" class="color_tag" style="background-color:#ff9ff3" 
							:class="{'selected' : folders.newFolder.color == '#ff9ff3'}" @click="folders.newFolder.color = '#ff9ff3'"></a>
						</div>
						<a href="#" class="uk-button uk-button-small uk-button-default " uk-toggle="target:#custom-folder-color" style="line-height: 21px;"><?php _e('Custom','admin2020')?></a>
						
						
					</div>
					
					<input class="uk-input uk-form-small" id="custom-folder-color" style="width: 100%;" 
					v-model="folders.newFolder.color" placeholder="<?php _e('Custom (hex)','admin2020')?>" hidden="true">
				
				</div>
				
				
				<div class="uk-grid uk-grid-small uk-child-width-1-2">
					<div>
						<button class="uk-button uk-button-small uk-button-default uk-width-1-1" uk-toggle="target:#newfolderpanel"><?php _e('Cancel','admin2020')?></button>
					</div>
					<div>
						<button class="uk-button uk-button-small uk-button-secondary uk-width-1-1" @click="createFolder()"><?php _e('Create','admin2020')?></button>
					</div>
				</div>
				
			</div>
			
			<!-- EDIT CURRENT FOLDER -->
			<div class="new-folder uk-padding-small a2020-border bottom" id="updatefolderpanel" style="display: none;">
				
				<div class="uk-text-meta uk-margin-small-bottom"><?php _e('Folder Name','admin2020')?>:</div>
				<input class="uk-input uk-margin-bottom" v-model="folders.editFolder.name" placeholder="<?php _e('Folder Name','admin2020')?>" type="text">
				
				<div  style="margin-bottom: 30px;">
					
					<div class="uk-text-meta uk-margin-small-bottom"><?php _e('Folder Colour','admin2020')?>:</div>
				
					<div class="uk-child-width-auto uk-flex uk-flex-between uk-margin-small-bottom" id="admin2020_foldertag">
						
						<div>
							<a href="#" class="color_tag" style="background-color:#0c5cef" 
							:class="{'selected' : folders.editFolder.color == '#0c5cef'}" @click="folders.editFolder.color = '#0c5cef'"></a>
							<a href="#" class="color_tag" style="background-color:#32d296" 
							:class="{'selected' : folders.editFolder.color == '#32d296'}" @click="folders.editFolder.color = '#32d296'"></a>
							<a href="#" class="color_tag" style="background-color:#faa05a" 
							:class="{'selected' : folders.editFolder.color == '#faa05a'}" @click="folders.editFolder.color = '#faa05a'"></a>
							<a href="#" class="color_tag" style="background-color:#f0506e" 
							:class="{'selected' : folders.editFolder.color == '#f0506e'}" @click="folders.editFolder.color = '#f0506e'"></a>
							<a href="#" class="color_tag" style="background-color:#ff9ff3" 
							:class="{'selected' : folders.editFolder.color == '#ff9ff3'}" @click="folders.editFolder.color = '#ff9ff3'"></a>
						</div>
						<a href="#" class="uk-button uk-button-small uk-button-default " uk-toggle="target:#custom-folder-color-existing" style="line-height: 21px;"><?php _e('Custom','admin2020')?></a>
						
						
					</div>
					
					<input class="uk-input uk-form-small" id="custom-folder-color-existing" style="width: 100%;" 
					v-model="folders.editFolder.color" placeholder="<?php _e('Custom (hex)','admin2020')?>" hidden="true">
				
				</div>
				
				
				<div class="uk-grid uk-grid-small uk-child-width-1-2">
					<div>
						<button class="uk-button uk-button-small uk-button-default uk-width-1-1" onClick="jQuery('#updatefolderpanel').hide();"><?php _e('Cancel','admin2020')?></button>
					</div>
					<div>
						<button class="uk-button uk-button-small uk-button-secondary  uk-width-1-1" @click="updateFolder()"><?php _e('Update','admin2020')?></button>
					</div>
				</div>
				
			</div>
			
			<div class=" uk-padding-small" id="a2020-folders-top-level" style="max-height:500px;overflow:auto;">
			
				<div class="a2020-folder-component ">
				  <div class="uk-flex uk-flex-middle folder_block" :class="[ {'active-folder' : folders.activeFolder.length == 0} ]">
					<span class="material-icons-outlined uk-text-muted folderChevron nosub"  @click="">
					  chevron_right
					</span>
					<span class="uk-flex uk-flex-middle folder-click" @click="folders.activeFolder = []">
					<span class="material-icons  uk-margin-small-right ">folder</span>
					<span class=" uk-text-bold"><?php _e('All','admin2020')?></span></span>
				  </div>
				</div>
				
				<div class="a2020-folder-component uk-margin-bottom" >
				  <div class="uk-flex uk-flex-middle folder_block" :class="[ {'active-folder' : folders.activeFolder[0] == 'uncat'} ]">
					<span class="material-icons-outlined uk-text-muted folderChevron nosub"  @click="">
					  chevron_right
					</span>
					<span class="uk-flex uk-flex-middle folder-click" @click="folders.activeFolder = ['uncat']">
					<span class="material-icons  uk-margin-small-right ">folder</span>
					<span class=" uk-text-bold"><?php _e('No folder','admin2020')?></span></span>
				  </div>
				</div>
				
				
				
				<template v-for="folder in folders.allFolders">
					<folder-template :folder="folder" :open="folders.openFolders" :current="folders.activeFolder" ></folder-template>
				</template>
				
				<div id="a2020-folder-template"
				@drop="dropInTopLevel($event)"
				@dragover.prevent
				@dragenter.prevent
				></div>
			
			</div>
		</div>
		<?php
	}
	
	
	/**
	* Build table filters
	* @since 2.9
	*/
	public function build_filters(){
		?>
		<!--POST TYPE FILTERS -->
		<div class="uk-margin-bottom">
			<div class="uk-text-bold uk-margin-small-bottom">
				<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px;">library_books</span>
				<?php _e('Post Types','admin2020')?>
			</div>
			<multi-select :options="contentTable.postTypes" :selected="contentTable.filters.selectedPostTypes"
			:name="'<?php _e('Post Types','admin2020')?>'"
			:placeholder="'<?php _e('Search Post Types...','admin2020')?>'"></multi-select>
		</div>
		
		<!--POST TYPE FILTERS -->
		<div class="uk-margin-bottom">
			<div class="uk-text-bold uk-margin-small-bottom">
				<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px; ">check_circle</span>
				<?php _e('Post Status','admin2020')?>
			</div>
			<multi-select :options="contentTable.postStatuses" :selected="contentTable.filters.selectedPostStatuses"
			:name="'<?php _e('Post Status','admin2020')?>'"
			:placeholder="'<?php _e('Search Post Statuses...','admin2020')?>'"></multi-select>
		</div>
		<!--DATE FILTERS -->
		<div class="uk-margin-bottom">
			
			<div class="uk-text-bold uk-margin-small-bottom">
				<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px; ">date_range</span>
				<?php _e('Date Created','admin2020')?>
			</div>
			
			<div class="uk-grid uk-grid-small">
				<div class="uk-width-1-3">
					<select class="uk-select" v-model="contentTable.filters.dateComparison">
						<option value="on" selected><?php _e('Posted On','admin2020')?>:</option>
						<option value="after"><?php _e('Posted After','admin2020')?>:</option>
						<option value="before"><?php _e('Posted Before','admin2020')?>:</option>
					</select>
				</div>
				<div class="uk-width-2-3">
					<input class="uk-input" type="date" v-model="contentTable.filters.date"> 
				</div>
			</div>
			
		</div>
		
		<!--MEDIA FILTERS -->
		<div class="uk-margin-bottom">
			<div class="uk-text-bold uk-margin-small-bottom">
				<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px; ">perm_media</span>
				<?php _e('Media Types','admin2020')?>
			</div>
			<multi-select :options="contentTable.fileTypes" :selected="contentTable.filters.selectedFileTypes"
			:name="'<?php _e('Media Types','admin2020')?>'"
			:placeholder="'<?php _e('Search Media Types...','admin2020')?>'"></multi-select>
		</div>
		
		<!--CATEGORIES -->
		<div class="uk-margin-bottom">
			<div class="uk-text-bold uk-margin-small-bottom">
				<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px; ">category</span>
				<?php _e('Categories','admin2020')?>
			</div>
			<multi-select :options="contentTable.categories" :selected="contentTable.filters.selectedCategories"
			:name="'<?php _e('Categories','admin2020')?>'"
			:placeholder="'<?php _e('Search categories...','admin2020')?>'"></multi-select>
		</div>
		
		<!--TAGS -->
		<div class="uk-margin-bottom">
			<div class="uk-text-bold uk-margin-small-bottom">
				<span class="material-icons-outlined uk-margin-small-right" style="font-size: 15px; position: relative; top: 2px; ">label</span>
				<?php _e('Tags','admin2020')?>
			</div>
			<multi-select :options="contentTable.tags" :selected="contentTable.filters.selectedTags"
			:name="'<?php _e('Tags','admin2020')?>'"
			:placeholder="'<?php _e('Search tags...','admin2020')?>'"></multi-select>
		</div>
		
		<div class="uk-flex uk-flex-between">
			<button class="uk-button uk-button-default uk-margin-top" @click="resetFilters()"><?php _e('Clear Filters','admin2020') ?></button>
			<button class="uk-button uk-button-secondary uk-margin-top" @click="nameNewView()"><?php _e('Save as view','admin2020') ?></button>
		</div>
		<?php
	}
	
	
	
	/**
	* Outputs active filters
	* @since 2.9
	*/
	public function active_filters(){
		
		?>
		<!--- ACTIVE FILTERS -->
		<div class="uk-grid uk-grid-small uk-margin-bottom" uk-grid v-if="totalFilters()">
			
				<div class="uk-grid uk-grid-small" uk-grid>
					
					
						<div class="uk-width-auto">
							<span class="material-icons uk-text-muted">sell</span>
						</div>
					
					
						<div class="uk-width-auto" v-for="status in contentTable.filters.selectedPostStatuses">
							
							<div class="uk-button uk-button-small a2020-filter-tag">
								{{status}}
								<a class="uk-text-muted uk-margin-small-left" @click="removeFromList(status,contentTable.filters.selectedPostStatuses)" href="#">x</a>
							</div>
							
						</div>
						
						<div class="uk-width-auto" v-for="status in contentTable.filters.selectedPostTypes">
							
							<div class="uk-button uk-button-small a2020-filter-tag">
								{{status}}
								<a class="uk-text-muted uk-margin-small-left" @click="removeFromList(status,contentTable.filters.selectedPostTypes)" href="#">x</a>
							</div>
							
						</div>
						
						<div class="" v-if="contentTable.filters.date != ''">
							
							
							<div class="uk-button uk-button-small a2020-filter-tag">
								<?php _e('Posted','admin2020')?>
								{{contentTable.filters.dateComparison}}
								{{contentTable.filters.date}}
								<a class="uk-text-muted uk-margin-small-left" @click="contentTable.filters.date = ''" href="#">x</a>
							</div>
							
						</div>
						
						<div class="uk-width-auto" v-for="status in contentTable.filters.selectedFileTypes">
							
							<div class="uk-button uk-button-small a2020-filter-tag">
								{{status}}
								<a class="uk-text-muted uk-margin-small-left" @click="removeFromList(status,contentTable.filters.selectedFileTypes)" href="#">x</a>
							</div>
							
						</div>
						
						<div class="uk-width-auto" v-for="cat in contentTable.filters.selectedCategories">
							
							<div class="uk-button uk-button-small a2020-filter-tag">
								<template v-for="fullCat in contentTable.categories">
								
								<span v-if="fullCat.name == cat">
									{{fullCat.label}}
									<a class="uk-text-muted uk-margin-small-left" @click="removeFromList(cat,contentTable.filters.selectedCategories)" href="#">x</a>
								</span>
								
								</template>
							</div>
							
						</div>
						
						<div class="uk-width-auto" v-for="cat in contentTable.filters.selectedTags">
							
							<div class="uk-button uk-button-small a2020-filter-tag">
								<template v-for="fullCat in contentTable.tags">
								
								<span v-if="fullCat.name == cat">
									{{fullCat.label}}
									<a class="uk-text-muted uk-margin-small-left" @click="removeFromList(cat,contentTable.filters.selectedTags)" href="#">x</a>
								</span>
								
								</template>
							</div>
							
						</div>
					
				</div>
				
		</div>
		<?php
	}
	
	/**
	* Build table pagination
	* @since 2.9
	*/
	public function build_pagination(){
		
		?>
		<!--- PAGINATION  -->
		<ul class="uk-iconnav a2020-user-pagination uk-flex-right uk-background-default uk-border-rounded  a2020-border all" style="padding: 6px 10px;">
			
			<li style="padding-left: 0;">
				<a href="#" style="border: none;background: none" :class="{'uk-disabled' : contentTable.currentPage == 1}"
				@click="contentTable.currentPage = contentTable.currentPage - 1">
					<span class=" dashicons dashicons-arrow-left-alt2 uk-text-primary" style="font-size: 17px;height: auto;"></span>
				</a>
			</li>
			<!-- IF PAGES LESS THAN 6 THEN SHOW ALL -->
			<li v-if="contentTable.totalPages < 6" v-for="n in contentTable.totalPages">
				<a href="#" :class="{'uk-active' : contentTable.currentPage == n}" @click="contentTable.currentPage = n">{{n}}</a>
			</li>
			
			
			
			
			<!-- IF PAGES MORE THAN 5 THEN SHOW FIRST PAGE -->
			<li v-if="contentTable.totalPages > 5" >
				<a href="#" :class="{'uk-active' : contentTable.currentPage == 1}" @click="contentTable.currentPage = 1">1</a>
			</li>
			
			
			<!-- IF CURRENT PAGE IS MORE THAN 4 THEN PAGES BETWEEN 1 AND CURRENT PAGE -->
			<li v-if="contentTable.totalPages > 5 && (contentTable.currentPage - 2) > 1" >
				<a href="#">...</a>
				<div class="uk-dropdown" uk-dropdown="pos:bottom-center;mode:click" style="	min-width: auto;width: 100px;text-align: center;">
					<div class="uk-dropdown-grid uk-grid-collapse uk-child-width-1-3" uk-grid>
						<a class="uk-link-muted" v-for="n in (contentTable.currentPage - 3) " href="#" style="" @click="contentTable.currentPage = n + 1">{{n + 1}}</a>
					</div>
				</div>
			</li>
			
			<!-- MIDDLE: CURRENT PAGE, ONE BEFORE AND ONE AFTER -->
			<li v-if="contentTable.totalPages > 5 && contentTable.currentPage - 1 > 1">
				<a href="#"  @click="contentTable.currentPage = contentTable.currentPage - 1">{{contentTable.currentPage - 1}}</a>
			</li>
			<li v-if="contentTable.totalPages > 5 && contentTable.currentPage != 1 && contentTable.currentPage != contentTable.totalPages">
				<a href="#" class="uk-active uk-disabled" >{{contentTable.currentPage}}</a>
			</li>
			<li v-if="contentTable.totalPages > 5 && contentTable.currentPage + 1 != contentTable.totalPages">
				<a href="#"  @click="contentTable.currentPage = contentTable.currentPage + 1">{{contentTable.currentPage + 1}}</a>
			</li>
			
			<!-- IF CURRENT PAGE IS MORE THAN TOTAL PAGES MINUS  THEN PAGES BETWEEN 1 AND CURRENT PAGE -->
			<li v-if="contentTable.totalPages > 5 && (contentTable.currentPage + 2) < contentTable.totalPages" >
				<a href="#">...</a>
				<div class="uk-dropdown" uk-dropdown="pos:bottom-center;mode:click" style="	min-width: auto;width: 100px;text-align: center;">
					<div class="uk-dropdown-grid uk-grid-collapse uk-child-width-1-3" uk-grid>
						<a class="uk-link-muted" v-for="n in (contentTable.totalPages - contentTable.currentPage - 2) " href="#" style="" 
						@click="contentTable.currentPage = n + 1 + contentTable.currentPage">{{n + 1 + contentTable.currentPage}}</a>
					</div>
				</div>
			</li>
			
			<!-- IF PAGES MORE THAN 5 THEN SHOW LAST PAGE -->
			<li v-if="contentTable.totalPages > 5" >
				<a href="#" :class="{'uk-active' : contentTable.currentPage == contentTable.totalPages}" @click="contentTable.currentPage = 1">{{contentTable.totalPages}}</a>
			</li>
			
			<li>
				<a href="#" style="border: none;background: none" :class="{'uk-disabled' : contentTable.currentPage == contentTable.totalPages}" @click="contentTable.currentPage = contentTable.currentPage + 1">
					<span class=" dashicons dashicons-arrow-right-alt2 uk-text-primary" style="font-size: 17px;height: auto;"></span>
				</a>
			</li>
		</ul>
		
		<?php
	}
	
	/**
	* Build content page header
	* @since 2.9
	*/
	
	public function build_table(){
		
		?>
		<!-- TABLE HEAD -->
		<template v-if="contentTable.mode == 'list'">
			<div class="uk-background-default a2020-border all uk-border-rounded uk-padding-small uk-margin-small-bottom a2020-content-table-head" uk-sticky="offset: 100">
				
				<div class="uk-grid uk-grid-small">
					
					<div class="content-checkbox" >
						<div class="a2020-checkbox uk-border-rounded a2020-border all" :class="{'checked' : contentTable.selectAll }" @click="selectAllTable" >
						  <span class="material-icons-outlined">done</span>
						  <input type="checkbox" v-model="contentTable.selectAll "  style="opacity: 0 !important;">
						</div>
					</div>
					
					
					<div class="content-table-title uk-width-medium@m uk-width-large@xl uk-text-bold">
						<?php _e('Name','admin2020') ?>
					</div>
					
					<div class="content-table-type uk-width-expand uk-text-bold">
						<?php _e('Type','admin2020') ?>
					</div>
					
					<div class="uk-visible@s uk-width-expand uk-text-bold">
						<?php _e('Author','admin2020') ?>
					</div>
					
					<div class=" uk-visible@s uk-width-expand uk-text-bold">
						<?php _e('Date','admin2020') ?>
					</div>
					
					<div class=" uk-visible@s uk-width-expand uk-text-bold">
						<?php _e('Status','admin2020') ?>
					</div>
					
					<div style="width:40px;">
					</div>
					
					
				</div>
				
			</div>
			
			
			<!-- TABLE CONTENT -->
			<template  v-for="item in fileList">
				
				<div class="a2020-table-item uk-padding-small a2020-border bottom " draggable="true"  
				@dragstart="startContentDrag($event,item)"
				@dragend="endContentDrag($event,item)"
				@dblclick="openQuickEdit(item.id)"
				>
					
						<div class="uk-grid uk-grid-small ">
							
							<div class="content-checkbox uk-flex uk-flex-middle" >
								
								<div class="a2020-checkbox uk-border-rounded a2020-border all" :class="{'checked' : isIn(item.id,contentTable.selected)}" >
								  <span class="material-icons-outlined">done</span>
								  <input type="checkbox" v-model="contentTable.selected" :value="item.id" style="opacity: 0 !important;">
								</div>
							</div>
							
							<div class="content-table-title uk-width-medium@m uk-width-large@xl">
								<div class="uk-grid uk-grid-small">
									<!-- IS ICON -->
									<div v-if="item.icontype == 'icon'" class="uk-width-auto">
										<div class="uk-background-default uk-border-rounded a2020-border all uk-text-center" style="padding: 5px;width: 30px;height: 30px;">
											<span class="material-icons-outlined" style="font-size: 25px;">{{item.icon}}</span>
										</div>
									</div>
									<!-- HAS IMAGE -->
									<div v-if="item.icontype == 'image'" class="uk-width-auto">
										<img class="uk-background-default uk-border-rounded a2020-border all " 
										:src="item.icon" style="width:40px;height:40px;">
									</div>
									<div class="uk-width-expand">
										<div class="uk-text-bold">{{item.name}}</div>
									</div>
								</div>
							</div>
							
							<div class="content-table-type uk-width-expand uk-text-bold">
								<span class="a2020-post-label">{{item.type}}</span>
							</div>
							
							<div class="uk-visible@s uk-width-expand ">
								<a :href="item.authorLink" >
								{{item.author}}
								</a>
							</div>
							
							<div class="uk-visible@s uk-width-expand">
								{{item.date}}
							</div>
							
							<div class="uk-visible@s uk-width-expand uk-text-bold">
								<span class="a2020-post-label" :class="item.status">{{item.status}}</span>
							</div>
							
							<div style="width:40px;">
								<a href="" class="uk-icon-button" uk-icon="more"></a>
								
								<div uk-dropdown="pos: bottom-right;mode:click;" class="uk-padding-remove">
									
									<div class="uk-padding-small ">
										<ul class="uk-nav uk-dropdown-nav">
											<li >
												<a :href="item.url" target="_BLANK" >
													<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">pageview</span>
													<?php _e('View','admin2020')?>
												</a>
											</li>
											<li >
												<a :href="item.editurl" >
													<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">edit</span>
													<?php _e('Edit','admin2020')?>
												</a>
											</li>
											<li >
												<a href="#" class="" @click="openQuickEdit(item.id)">
													<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">preview</span>
													<?php _e('Quick Edit','admin2020')?>
												</a>
											</li>
											<li v-if="item.type != 'attachment'" >
												<a href="#" class="" @click="duplicateItem(item.id)">
													<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">copy</span>
													<?php _e('Duplicate','admin2020')?>
												</a>
											</li>
										</ul>
									</div>
									
									<div class="uk-padding-small a2020-border top">
										<ul class="uk-nav uk-dropdown-nav">
											<li >
												<a href="#" class="uk-text-danger" @click="deleteItem(item.id)">
													<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">delete</span>
													<?php _e('Delete Item','admin2020')?>
												</a>
											</li>
										</ul>
									</div>
									
									
								</div>
							</div>
							
						</div>
					
				</div>
				
			</template>
		
		</template>
		
		
		<!-- TABLE CONTENT -->
		<template v-if="contentTable.mode == 'grid'">
			
			<div class="uk-grid " uk-grid="masonry: true">
				<template  v-for="item in fileList">
					
					<div  class="uk-width-1-1@s" :class=" 'uk-width-1-' + contentTable.gridSize + '@m'">
						
						<div class="a2020-border all uk-border-rounded uk-background-default uk-overflow-hidden uk-box-shadow-small" draggable="true"  
						@dragstart="startContentDrag($event,item)"
						@dragend="endContentDrag($event,item)"
						@dblclick="openQuickEdit(item.id)">
							
							
							<!-- IS ICON -->	
							<div v-if="item.icontype == 'icon'" class="uk-width-auto">
								<div class="uk-width-1-1 uk-flex uk-flex-center uk-flex-middle uk-height-small ">
									<span class="material-icons-outlined" style="font-size: 50px;">{{item.icon}}</span>
								</div>
							</div>
							<!-- HAS IMAGE -->
							<div v-if="item.icontype == 'image'" class="uk-width-auto">
								<img class="uk-width-1-1" 
								:src="item.iconLarge" style="width:100%">
							</div>
							
							
							<div class="uk-padding-small">
								<div class="uk-text-bold">{{item.name}}</div>
								<div class="uk-text-meta">{{item.author}} | {{item.date}}</div>
								<div class=" uk-margin-top">
									<span class="a2020-post-label uk-margin-small-right" v-if="item.type == 'attachment'">{{item.mime}}</span>
									<span class="a2020-post-label uk-margin-small-right" v-if="item.type != 'attachment'">{{item.type}}</span>
									<span class="a2020-post-label" :class="item.status">{{item.status}}</span>
								</div>
							</div>
							
							<div class="a2020-border top uk-padding-small">
								
								<ul class="uk-iconnav">
									
									<li style="flex-grow: 1;" class="uk-flex">
										<div class="a2020-checkbox uk-border-rounded a2020-border all" :class="{'checked' : isIn(item.id,contentTable.selected)}" >
										  <span class="material-icons-outlined">done</span>
										  <input type="checkbox" v-model="contentTable.selected" :value="item.id" style="opacity: 0 !important;">
										</div>
									</li>
								
									
									
									<li style="padding-left: 15px;">
										<a href="#" uk-tooltip="title:<?php _e('Quick Edit') ?>;delay:300" @click="openQuickEdit(item.id)">
											<span class="material-icons-outlined"  style="font-size: 18px;position: relative;top: 2px;">preview</span>
										</a>
									</li>
									
									<li>
										<a uk-tooltip="title:<?php _e('View') ?>;delay:300" :href="item.url" target="_BLANK">
											<span class="material-icons-outlined"  style="font-size: 18px;position: relative;top: 2px;">pageview</span>
										</a>
									</li>
									
									<li>
										<a :href="item.editurl" uk-tooltip="title:<?php _e('Edit') ?>;delay:300">
											<span class="material-icons-outlined"  style="font-size: 18px;position: relative;top: 2px;">edit</span>
										</a>
									</li>
									
									<li style="flex-grow: 1;" class="uk-flex uk-flex-right">
										<a href="#" class="uk-text-danger" uk-tooltip="title:<?php _e('Delete') ?>;delay:300" @click="deleteItem(item.id)">
											<span class="material-icons-outlined"  style="font-size: 18px;position: relative;top: 2px;">delete</span>
										</a>
									</li>
									
								
								</ul>
							</div>
						</div>
						
					</div>
					
				</template>
				
				
			</div>
			
			<div v-if="contentTable.totalPages > contentTable.currentPage && contentTable.content.length < contentTable.total">
		
				<div class="uk-width-1-1 uk-flex uk-flex-center uk-margin-top">
					<button class="uk-button uk-button-secondary" 
					@click="contentTable.filters.perPage = Math.round(contentTable.filters.perPage * 1.5)"><?php _e("Load More",'admin2020') ?></button>
				</div>
			
			</div>
			
		</template>
		
		<div v-if="fileList.length == 0" class="uk-width-1-1 uk-text-center uk-padding">
			<div class=""><span class="material-icons-outlined">sentiment_dissatisfied</span></div>
			<p class="uk-h4 uk-text-meta uk-margin-remove-top"><?php _e('No content found','admin2020')?></p>
		</div>
		
		<div class="admin2020loaderwrap" id="admincontentloader" v-if="loading === true">
			<div class="admin2020loader"></div>
		</div>
		
		<?php
		
	}
	
	
	/**
	* Builds batch options for items
	* @since 2.9
	*/
	
	public function build_batch_options(){
		
		?>
		
		<div class="uk-position-small uk-position-bottom-right uk-animation-slide-right" v-if="contentTable.selected.length > 0" style="position: fixed !important;">
			<button class="uk-button uk-button-primary uk-box-shadow-medium uk-light" style="border-radius: 4px;">
				<div class>
					<span class="uk-text-emphasis uk-margin-small-right" style="">{{contentTable.selected.length}} </span> 
					<span class=""><?php _e('items selected','admin2020') ?><span class="uk-text-primary">
				</div>
			</button>
			
			
			<div uk-dropdown="pos: top-right;mode:click;" class="uk-padding-remove">
				
				<div class="uk-padding-small a2020-border bottom">
					<ul class="uk-nav uk-dropdown-nav">
						<li >
							<a href="#" class="" uk-toggle="target: #batch-rename-modal">
								<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">drive_file_rename_outline</span>
								<?php _e('Batch Rename','admin2020')?>
							</a>
						</li>
						<li >
							<a href="#" class="" @click="openCatsTags()">
								<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">category</span>
								<?php _e('Categories & Tags','admin2020')?>
							</a>
						</li>
						<li>
							<a href="#" class="uk-text-danger" @click='deleteMultiple()'>
								<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">delete</span>
								<?php _e('Delete selected','admin2020')?>
							</a>
						</li>
					</ul>
				</div>
				
				<div class="uk-padding-small">
					<ul class="uk-nav uk-dropdown-nav">
						<li>
							<a href="#" class="" @click='contentTable.selected = []'>
								<span class="material-icons-outlined"  style="font-size: 15px;position: relative;top: 2px;">backspace</span>
								<?php _e('Clear Selection','admin2020')?>
							</a>
						</li>
					</ul>
				</div>
			</div>
			
			
		</div>
		
		<?php
		
	}
	
	
	
	/**
	* Saves New View
	* @since 2.9
	*/
	public function save_view_options(){
		
		?>
		
		<div id="new-view-modal" class="uk-flex-top" uk-modal>
			<div class="uk-modal-dialog uk-modal-body uk-margin-auto-vertical uk-padding-remove">
				<div class="uk-padding" style="padding-top: 15px;padding-bottom: 15px">
					
					<h4 class="uk-margin-remove">
						<span class="material-icons-outlined" style="font-size: 20px;position: relative;top: 3px;">auto_awesome</span>
						<?php _e('Create New View','admin2020') ?>
					</h4>
					<button class="uk-modal-close-default uk-position-small uk-position-top-right" type="button" uk-close></button>
				</div>
		
				<div class="uk-padding a2020-border bottom top">
					<div class="uk-text-bold uk-margin-small-bottom">
						 <?php _e('View name','admin2020') ?>
					 </div>
					<div class="uk-margin-bottom">
						<input class="uk-input" v-model="newView.name" type="text" placeholder="<?php _e('View Name','admin2020') ?>"> 
					</div>
					
				</div>
				
				<div class="uk-padding" style="padding-top: 15px;padding-bottom: 15px">
					
					<button class="uk-button uk-button-secondary" @click="saveView">
						<?php _e('Save','admin2020')?>
					</button>
					
				</div>
			
		
			</div>
		</div>
		
		<?php
		
	}
	
	
	
}

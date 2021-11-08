<?php
if (!defined('ABSPATH')) {
    exit();
}

class Admin_2020_module_admin_folders
{
    public function __construct($version, $path, $utilities)
    {
        $this->version = $version;
        $this->path = $path;
        $this->utils = $utilities;
		$this->view = 'panel';
		$this->folder_id = '';
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
		
		
		add_action( 'wp_enqueue_media', array($this,'add_styles'),99 );
		add_action( 'wp_enqueue_media', array($this,'add_scripts'),99 );
		add_action( 'wp_enqueue_media', array($this,'add_media_template'),99 );
		add_filter( 'wp_prepare_attachment_for_js', array($this,'pull_meta_to_attachments'), 10, 3 );
		add_action( 'init', array($this,'a2020_create_folders_cpt') );
		
		///FOLDER AJAX
		add_filter('ajax_query_attachments_args', array($this,'legacy_media_filter'));
		
		add_action('wp_ajax_a2020_get_folders_legacy', array($this,'a2020_get_folders_legacy'));
		add_action('wp_ajax_a2020_create_folder_legacy', array($this,'a2020_create_folder_legacy'));
		add_action('wp_ajax_a2020_delete_folder_legacy', array($this,'a2020_delete_folder_legacy'));
		add_action('wp_ajax_a2020_update_folder_legacy', array($this,'a2020_update_folder_legacy'));
		add_action('wp_ajax_a2020_move_folder_legacy', array($this,'a2020_move_folder_legacy'));
		add_action('wp_ajax_a2020_move_content_to_folder_legacy', array($this,'a2020_move_content_to_folder_legacy'));
		
		
    }
	
	
	/**
	* Moves content to Folder
	* @since 2.9
	*/
	public function a2020_move_content_to_folder_legacy(){
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
			
			$contentIds = $this->utils->clean_ajax_input($_POST['contentID']);
			$destination = $this->utils->clean_ajax_input($_POST['destinationId']);
			
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
	* Moves Folder
	* @since 2.9
	*/
	public function a2020_move_folder_legacy(){
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
			
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
	* Updates folder
	* @since 2.9
	*/
	
	public function a2020_update_folder_legacy(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
			
			
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
	* Deletes folder
	* @since 2.9
	*/
	
	public function a2020_delete_folder_legacy(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
			
			
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
	
	/**
	* Build content for front end app
	* @since 2.9
	*/
	
	public function a2020_create_folder_legacy(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
			
			
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
	* Build content for front end app
	* @since 2.9
	*/
	
	public function a2020_get_folders_legacy(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('admin2020-admin-folder-security-nonce', 'security') > 0) {
			
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
				'post_type'=> 'attachment',
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
	 * Filters media by folder
	 * @since 1.4
	 */
	public function legacy_media_filter($args){
		
		if(isset($_REQUEST['query']['folder_id'])){
			
			$folderid = $_REQUEST['query']['folder_id'];
			
			if ($folderid == ""){ 
				
			} else if ($folderid == "uncat"){
				
				$args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key' => 'admin2020_folder',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key' => 'admin2020_folder',
						'value' => '',
						'compare' => '=',
					)
				);
				
			} else {
		
				$args['meta_query'] = array(
					array(
						'key' => 'admin2020_folder',
						'value' => $folderid,
						'compare' => '='
					)
				);
				
			}
			
		}
		
		return $args;
		
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
		$data['title'] = __('Folders','admin2020');
		$data['option_name'] = 'admin2020_admin_folders';
		$data['description'] = __('Creates the folder system for the content page and media page / modals.','admin2020');
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
		$temp['name'] = __('Folders Disabled for','admin2020');
		$temp['description'] = __("Folder system will be disabled for any users or roles you select",'admin2020');
		$temp['type'] = 'user-role-select';
		$temp['optionName'] = 'disabled-for'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
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
		  if($disabled_for == ""){
			  $disabled_for = array();
		  }
		  ?>
		  <div class="uk-grid" id="a2020_folder_settings" uk-grid>
			  <!-- LOCKED FOR USERS / ROLES -->
			  <div class="uk-width-1-1@ uk-width-1-3@m">
				  <div class="uk-h5 "><?php _e('Folders Disabled for','admin2020')?></div>
				  <div class="uk-text-meta"><?php _e("Folders will be disabled for any users or roles you select",'admin2020') ?></div>
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
					  jQuery('#a2020_folder_settings #a2020-role-types').tokenize2({
						  	placeholder: '<?php _e('Select roles or users','admin2020') ?>',
							dataSource: function (term, object) {
								a2020_get_users_and_roles(term, object);
							},
							debounce: 1000,
					  });
				  </script>
				  
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
		
        wp_register_style(
            'admin2020_admin_folders',
            $this->path . 'assets/css/modules/admin-folders.css',
            array(),
            $this->version
        );
        wp_enqueue_style('admin2020_admin_folders');
    }
	
	/**
	* Enqueue Admin Bar 2020 scripts
	* @since 1.4
	*/
	
	public function add_scripts(){
	  
	  ///CHECK FOR CURRENT SCREEN 
	  if(function_exists('get_current_screen')){
		  $screen = get_current_screen();
		  $theid = $screen->id;
	  } else {
		  $theid = 'toplevel_page_admin_2020_content';
	  }
	  
	  if($theid != 'toplevel_page_admin_2020_content'){
		  
		  wp_enqueue_script('admin-theme-folders', $this->path . 'assets/js/admin2020/admin-folders.min.js', array('jquery'), $this->version, true);
			wp_localize_script('admin-theme-folders', 'admin2020_folder_ajax', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'security' => wp_create_nonce('admin2020-admin-folder-security-nonce'),
				'screen' => $theid,
			));
			
			$list = 'enqueued';
			
			if (!wp_script_is( 'a2020-vue-build', $list )) {
				wp_enqueue_script('a2020-vue-build', $this->path . 'assets/js/vuejs/vue-global-dev.js', array('jquery'),  $this->version);
			} 
		  
	  }
	  
	  
	  
	}
	
	/**
	* Adds media override template
	* @since 1.4
	*/
	public function add_media_template(){
		add_action( 'admin_footer', array($this,'build_media_template'));
	}
	/**
	* Creates custom folder post type
	* @since 1.4
	*/
	public function a2020_create_folders_cpt(){
	
		 $labels = array(
		  'name'               => _x( 'Folder', 'post type general name', 'admin2020' ),
		  'singular_name'      => _x( 'folder', 'post type singular name', 'admin2020' ),
		  'menu_name'          => _x( 'Folders', 'admin menu', 'admin2020' ),
		  'name_admin_bar'     => _x( 'Folder', 'add new on admin bar', 'admin2020' ),
		  'add_new'            => _x( 'Add New', 'folder', 'admin2020' ),
		  'add_new_item'       => __( 'Add New Folder', 'admin2020' ),
		  'new_item'           => __( 'New Folder', 'admin2020' ),
		  'edit_item'          => __( 'Edit Folder', 'admin2020' ),
		  'view_item'          => __( 'View Folder', 'admin2020' ),
		  'all_items'          => __( 'All Folders', 'admin2020' ),
		  'search_items'       => __( 'Search Folders', 'admin2020' ),
		  'not_found'          => __( 'No Folders found.', 'admin2020' ),
		  'not_found_in_trash' => __( 'No Folders found in Trash.', 'admin2020' )
		);
		 $args = array(
		  'labels'             => $labels,
		  'description'        => __( 'Description.', 'Add New Folder' ),
		  'public'             => false,
		  'publicly_queryable' => false,
		  'show_ui'            => false,
		  'show_in_menu'       => false,
		  'query_var'          => false,
		  'has_archive'        => false,
		  'hierarchical'       => false,
		);
		register_post_type( 'admin2020folders', $args );
	}
	
	/**
	* Adds folder id to default wp media views
	* @since 1.4
	*/
	public function pull_meta_to_attachments(  $response, $attachment, $meta ) {
		  $mimetype = get_post_mime_type($attachment->ID);
		  $pieces = explode("/", $mimetype);
		  $type = $pieces[0];
		  $folderid = get_post_meta( $attachment->ID, 'admin2020_folder', true );
		  $response[ 'properties' ]['folderid'] = $folderid;
		  $response[ 'folderid' ] = $folderid;
			 
		  return $response;
	}
	  
	  
	/**
	* Builds media template
	* @since 1.4
	*/
	public function build_media_template(){
		
		?>
		<!-- BUILD FOLDERS IN MODAL -->
			<script type="text/html" id="tmpl-media-frame_custom"> 
			  
			  <div class="uk-grid-collapse uk-height-1-1 a2020_legacy_filter" uk-grid uk-filter="target: .attachments">			
				<div class="uk-width-1-1@s uk-width-auto@m uk-position-relative">
					<div class="admin2020-folder-modal" id="admin2020_settings_column" style="width:270px">
						  <div class="a2020-folder-title uk-h4"><?php _e('Folders','admin2020')?></div>	
						  <div class="a2020_modal_folders">
							<?php
							$this->build_folder_panel();
							?>
						</div>
					</div>
				</div>
				
				<div class="uk-width-1-1@s uk-width-expand@m uk-position-relative">
				
				  <div class="media-frame-title" id="media-frame-title"></div>
				  <h2 class="media-frame-menu-heading"><?php _ex( 'Actions', 'media modal menu actions' ); ?></h2>
				  <button type="button" class="button button-link media-frame-menu-toggle" aria-expanded="false">
					  <?php _ex( 'Menu', 'media modal menu' ); ?>
					  <span class="dashicons dashicons-arrow-down" aria-hidden="true"></span>
				  </button>
				  <div class="media-frame-menu"></div>
				  
					<div class="media-frame-tab-panel">
						<div class="media-frame-router"></div>
						<div class="media-frame-content"></div>
					</div>
				</div>
				
			  </div>
			  
			  <div class="media-frame-toolbar"></div>
			  <div class="media-frame-uploader"></div>
		  </script>
		<script>
		  jQuery(document).ready( function($) {
			  
			  
			  var delayInMilliseconds = 1000; //1 second
			  var foldersMounted = false;
			  
			  setTimeout(function () {
				if (jQuery("#wpcontent #media-folder-app").length > 0) {
				  if (!foldersMounted && !a2020foldersapp._container) {
					a2020foldersapp.mount("#media-folder-app");
					foldersMounted = true;
				  }
				}
			  }, delayInMilliseconds);
			  
			  
			  window.setInterval(function(){
				  a2020_add_drag();
			  }, 1000);
			 
		
			  if( typeof wp.media.view.Attachment != 'undefined' ){
				  //console.log(wp.media.view);
				  //wp.media.view.Attachment.prototype.template = wp.media.template( 'attachment_custom' );
				  wp.media.view.MediaFrame.prototype.template = wp.media.template( 'media-frame_custom' );
		
				  wp.media.view.Attachment.Library = wp.media.view.Attachment.Library.extend({
					className: function () { return 'attachment legacy_attachment folder' + this.model.get( 'folderid' ) },
					//folderName: function () { return 'attachment ' + this.model.get( 'folderid' ); },
					//attr: 'blue',
				  });
		
				  wp.media.view.Modal.prototype.on('open', function() {
					  //MODAL OPEN
					  
					  setTimeout(function () {
						  
							  if(!a2020foldersapp._container){
							  console.log('running');
						  		if (!foldersMounted){
								  a2020foldersapp.mount(".media-modal.wp-core-ui #media-folder-app");
								  foldersMounted = true;
							    }
							}
					   }, 1000);
					  //refreshFolderCountModal();
				  });
		
		
			  }
		  });
		  
		  function a2020_add_drag(){
			  jQuery('.attachment').attr('draggable','true');
		  }
		</script>
			  <?php
	}
	
	/**
	* Build folders panel
	* @since 2.9
	*/
	public function build_folder_panel(){
		?>
		
		<div class="uk-background-default uk-border-rounded a2020-border all " uk-sticky="offset: 100;media: 1000" id="media-folder-app">
			
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
					<span class="uk-flex uk-flex-middle folder-click" @click="viewAllMedia()">
					<span class="material-icons  uk-margin-small-right ">folder</span>
					<span class=" uk-text-bold"><?php _e('All','admin2020')?></span></span>
				  </div>
				</div>
				
				<div class="a2020-folder-component uk-margin-bottom" >
				  <div class="uk-flex uk-flex-middle folder_block" :class="[ {'active-folder' : folders.activeFolder[0] == 'uncat'} ]">
					<span class="material-icons-outlined uk-text-muted folderChevron nosub"  @click="">
					  chevron_right
					</span>
					<span class="uk-flex uk-flex-middle folder-click" @click="viewAllMediaWithoutFolder()">
					<span class="material-icons  uk-margin-small-right ">folder</span>
					<span class=" uk-text-bold"><?php _e('No folder','admin2020')?></span></span>
				  </div>
				</div>
				
				
				
				<template v-for="folder in queryTheFolders">
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

	
	
	
}

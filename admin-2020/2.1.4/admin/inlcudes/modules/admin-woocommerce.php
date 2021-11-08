<?php
if (!defined('ABSPATH')) {
	exit();
}

class uipress_module_woocommerce
{
	public function __construct($version, $path, $utilities)
	{
		$this->version = $version;
		$this->path = $path;
		$this->utils = $utilities;
		$this->orders = '';
		$this->start_date = '';
		$this->end_date = '';
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
		
		
		add_filter('uipress_register_card', array($this,'register_analytics_cards'));
		
		
		//AJAX
		add_action('wp_ajax_uipress_analytics_get_total_sales', array($this,'uipress_analytics_get_total_sales'));
		add_action('wp_ajax_uipress_analytics_get_total_orders', array($this,'uipress_analytics_get_total_orders'));
		add_action('wp_ajax_uipress_analytics_get_average_order_value', array($this,'uipress_analytics_get_average_order_value'));
		add_action('wp_ajax_uipress_get_recent_orders', array($this,'uipress_get_recent_orders'));
		add_action('wp_ajax_uipress_get_popular_products', array($this,'uipress_get_popular_products'));
		
		
		
		
	}
	
	
	/**
	 * Register admin bar component
	 * @since 1.4
	 * @variable $components (array) array of registered admin 2020 components
	 */
	public function register($components){
		
		///DON'T START IF WC ISN'T INSTALLED
		
		array_push($components,$this);
		return $components;
		
	}
	
	
	
	/**
	 * Returns component info for settings page
	 * @since 1.4
	 */
	public function component_info(){
		
		$data = array();
		$data['title'] = __('WooCommerce','admin2020');
		$data['option_name'] = 'admin2020_woocommerce';
		$data['description'] = __('Creates the woocommerce cards for the overview page.','admin2020');
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
		$temp['name'] = __('Store Analytics Disabled for','admin2020');
		$temp['description'] = __("Store Analytics will be disabled for any users or roles you select",'admin2020');
		$temp['type'] = 'user-role-select';
		$temp['optionName'] = 'disabled-for'; 
		$temp['value'] = $this->utils->get_option($optionname,$temp['optionName'], true);
		$settings[] = $temp;
		
		
		
		
		return $settings;
		
	}
	
	
	public function register_analytics_cards($cards){
		
		if(!is_array($cards)){
			$cards = array();
		}
		
		$temp = array();
		$temp['name'] = __('Total Sales','admin2020');
		$temp['moduleName'] = 'total-sales';
		$temp['description'] = __('Display total sales in your store within the date range.','admin2020'); 
		$temp['category'] = __('Commerce','admin2020');
		$temp['premium'] = true;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/woocommerce/total-sales.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Total Orders','admin2020');
		$temp['moduleName'] = 'total-orders';
		$temp['description'] = __('Display total orders in your store within the date range.','admin2020'); 
		$temp['category'] = __('Commerce','admin2020');
		$temp['premium'] = true;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/woocommerce/total-orders.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Average Order Value','admin2020');
		$temp['moduleName'] = 'average-order-value';
		$temp['description'] = __('Display average order value in your store within the date range.','admin2020'); 
		$temp['category'] = __('Commerce','admin2020');
		$temp['premium'] = true;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/woocommerce/average-order-value.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Recent Orders','admin2020');
		$temp['moduleName'] = 'recent-orders';
		$temp['description'] = __('Display recent orders from your store within the date range.','admin2020'); 
		$temp['category'] = __('Commerce','admin2020');
		$temp['premium'] = true;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/woocommerce/recent-orders.min.js';
		$cards[] = $temp;
		
		$temp = array();
		$temp['name'] = __('Popular Products','admin2020');
		$temp['moduleName'] = 'popular-products';
		$temp['description'] = __('Display top selling products from your store within the date range.','admin2020'); 
		$temp['category'] = __('Commerce','admin2020');
		$temp['premium'] = false;
		$temp['componentPath'] = $this->path . 'assets/js/admin2020/overview-modules/woocommerce/popular-products.min.js';
		$cards[] = $temp;
		
		return $cards;	
	}
	
	
	public function uipress_get_recent_orders(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			if (!is_plugin_active('woocommerce/woocommerce.php')){
				$returndata['message'] = __('Woocommerce is required for this widget','admin2020'); 
				$returndata['error'] = true; 
				echo json_encode($returndata);
				die();
			}
			
			$dates = $this->utils->clean_ajax_input($_POST['dates']); 
			$page = $this->utils->clean_ajax_input($_POST['currentPage']); 
			
			$startDate = date('Y-m-d', strtotime($dates['startDate']));
			$endDate = date('Y-m-d', strtotime($dates['endDate']));
			
			$args = array(
			'post_type'   => 'shop_order',
			'post_status' => array('wc-completed','wc-pending','wc-processing', 'wc-on-hold'),
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
				
				$order_id = $apost->ID;
				$order = wc_get_order($order_id);
				
				$temp = array();
				$temp['title'] = '#' . $order->get_order_number();	
				$temp['customer'] = $author_meta;	
				$temp['status'] = $order->get_status(); ;	
				$temp['value'] = $this->format_woo_currency($order->get_total());	
				$temp['date'] = $postdate;	
				$temp['editURL'] = get_edit_post_link($apost->ID);
				$temp['userURL'] = get_edit_user_link($author_id);
				
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
	
	
	public function uipress_get_popular_products(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			if (!is_plugin_active('woocommerce/woocommerce.php')){
				$returndata['message'] = __('Woocommerce is required for this widget','admin2020'); 
				$returndata['error'] = true; 
				echo json_encode($returndata);
				die();
			}
			
			$dates = $this->utils->clean_ajax_input($_POST['dates']); 
			$page = $this->utils->clean_ajax_input($_POST['currentPage']); 
			
			$startDate = date('Y-m-d', strtotime($dates['startDate']));
			$endDate = date('Y-m-d', strtotime($dates['endDate']));
			
			//$products = $this->best_selling_products(8,$startDate,$endDate);
			
			$args = array(
			'post_type'   => 'product',
			'post_status' => 'any',
			'posts_per_page' => 5,
			'paged' => $page,
			'orderby' => 'total_sales',
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
			
			//$formatted = array();
			
			foreach($foundPosts as $prouct){
				
				$productID = $prouct->ID;
				
				$temp = array();
				$temp['title'] = get_the_title($productID);	
				$temp['salesCount'] = get_post_meta( $productID, 'total_sales', true);
				$temp['link'] = get_edit_post_link($productID);
				$img = get_the_post_thumbnail_url($productID);
				
				if($img){
					$temp['img'] = $img;
				}
				
				$product = wc_get_product( $productID );
				$price = $product->get_price();
				$total_price = $price * $temp['salesCount'];
				
				$temp['totalValue'] = $this->format_woo_currency($total_price);
				
				$formatted[] = $temp;
				
			}
			
			$returndata = array();
			
			
			$returndata['message'] = __("Posts fetched",'admin2020');
			$returndata['posts'] = $formatted;
			$returndata['test'] = $products;
			$returndata['totalFound'] = $theposts->found_posts;
			
			$returndata['nocontent'] = 'false';
			if($theposts->found_posts < 1){
				$returndata['nocontent'] = __('No products sold during the date range.','admin2020'); 
			}
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
		
	}
	
	
	public function uipress_analytics_get_total_sales(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			if (!is_plugin_active('woocommerce/woocommerce.php')){
				$returndata['message'] = __('Woocommerce is required for this widget','admin2020'); 
				$returndata['error'] = true; 
				echo json_encode($returndata);
				die();
			}
			
			$dates = $this->utils->clean_ajax_input($_POST['dates']); 
			$startdate = date('Y-m-d', strtotime($dates['startDate']));
			$enddate = date('Y-m-d', strtotime($dates['endDate']));
			
			//$analyticsData = $this->get_analytics_data($startDate, $endDate);
			///GET ARRAY OF DATES
			$dates = $this->utils->date_array($startdate,$enddate);
			$json_dates = json_encode($dates);
		
			global  $woocommerce;
			
			$allorders = $this->get_orders($startdate,$enddate);
		
			$total = 0;
		
			$orders = $allorders['now']->posts;
			$total_orders = $allorders['now']->post_count;
			$array_orders_totals = array();
		
			foreach ($dates as $date){
			  $array_orders_totals[$date] = 0;
			}
			
			if($total_orders > 1){
				
				foreach ($orders as $ctr => $value)
				{
					$order_id = $value->ID;
			
					$order = wc_get_order($order_id);
					
					$order_total = $order->get_total();
					$order_date = date("d/m/Y",strtotime($order->get_date_created()));
			
					$array_orders_totals[$order_date] += $order_total;
			
					$total_sales += $order_total;
				}
			}
			
			
			$temparray = array();
			foreach($array_orders_totals as $item){
			  array_push($temparray,$item);
			}
			$array_orders_totals = $temparray;
			
			
			////COMPARISON
			$array_orders_totals_comp = array();
			
			$total_orders_comp = $allorders['comparison']->post_count;
			$orders_comp = $allorders['comparison']->posts;
			
			$earlier = new DateTime($startdate);
			$later = new DateTime($enddate);
			$days = $later->diff($earlier)->format("%a");
			
			$comparisonSD = date('Y-m-d', strtotime($startdate . ' -' . $days . ' day') );
			$comparisonED = date('Y-m-d', strtotime($startdate));
			
			$compdates = $this->utils->date_array($comparisonSD,$comparisonED);
			foreach ($compdates as $date){
				  $array_orders_totals_comp[$date] = 0;
			}
			
			$total_sales_comp = 0;
			
			if($total_orders_comp > 1){
				
				foreach ($orders_comp as $ctr => $value)
				{
					error_log('this far');
					$order_id = $value->ID;
			
					$order = wc_get_order($order_id);
					
					$order_total = $order->get_total();
					$order_date = date("d/m/Y",strtotime($order->get_date_created()));
			
					$array_orders_totals_comp[$order_date] += $order_total;
			
					$total_sales_comp += $order_total;
				}
				
			}
			
			$temparray = array();
			$holder = $array_orders_totals_comp;
			foreach($array_orders_totals_comp as $item){
			  array_push($temparray,$item);
			}
			$array_orders_totals_comp = $temparray;
			
			
			
			
			
			$dataSet = array( 
				'labels' => $dates,
				'datasets' => array(
					array(
						'label' => __("Total Sales",'admin2020'),
						  'fill' =>  true,
						  'data' =>  $array_orders_totals,
						  'backgroundColor' =>  array("rgba(12, 92, 239, 0.05)"),
						  'borderColor' =>  array("rgba(12, 92, 239, 1)"),
						  'borderWidth' =>  2,
						  
					),
					array(
						'label' => __("Total Sales (Comparison)",'admin2020'),
						  'fill' =>  true,
						  'data' =>  $array_orders_totals_comp,
						  'backgroundColor' =>  array("rgba(247, 127, 212, 0)"),
						  'borderColor' =>  array("rgb(247, 127, 212)"),
						  'borderWidth' =>  2,
						  
					),
				)
			);
			
			$total = $total_sales;
			$totalC = $total_sales_comp;
			
			if($total == 0 || $totalC == 0){
				$percentChange = 0;
			} else {
				$percentChange = (($total - $totalC) / ($totalC)) * 100;
			}
			
			$returndata['dataSet'] = $dataSet;
			$returndata['numbers']['total'] = $this->format_woo_currency($total_sales); 
			$returndata['numbers']['total_comparison'] = $this->format_woo_currency($totalC);
			$returndata['numbers']['change'] = number_format($percentChange, 2);
			
			
			
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
	}
	
	
	
	public function uipress_analytics_get_total_orders(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			if (!is_plugin_active('woocommerce/woocommerce.php')){
				$returndata['message'] = __('Woocommerce is required for this widget','admin2020'); 
				$returndata['error'] = true; 
				echo json_encode($returndata);
				die();
			}
			
			$dates = $this->utils->clean_ajax_input($_POST['dates']); 
			$startdate = date('Y-m-d', strtotime($dates['startDate']));
			$enddate = date('Y-m-d', strtotime($dates['endDate']));
			
			//$analyticsData = $this->get_analytics_data($startDate, $endDate);
			///GET ARRAY OF DATES
			$dates = $this->utils->date_array($startdate,$enddate);
			$json_dates = json_encode($dates);
		
			global  $woocommerce;
			
			$allorders = $this->get_orders($startdate,$enddate);
		
			$total = 0;
		
			$orders = $allorders['now']->posts;
			$total_orders = $allorders['now']->post_count;
			$array_orders_totals = array();
		
			foreach ($dates as $date){
			  $array_orders_totals[$date] = 0;
			}
			
			if($total_orders > 1){
				
				foreach ($orders as $ctr => $value)
				{
					$order_id = $value->ID;
			
					$order = wc_get_order($order_id);
					
					$order_date = date("d/m/Y",strtotime($order->get_date_created()));
			
					$array_orders_totals[$order_date] += 1;
			
					$total_sales += 1;
				}
			
				
			
				
			}
			
			$temparray = array();
			foreach($array_orders_totals as $item){
			  array_push($temparray,$item);
			}
			$array_orders_totals = $temparray;
			
			
			////COMPARISON
			$array_orders_totals_comp = array();
			
			$total_orders_comp = $allorders['comparison']->post_count;
			$orders_comp = $allorders['comparison']->posts;
			
			$earlier = new DateTime($startdate);
			$later = new DateTime($enddate);
			$days = $later->diff($earlier)->format("%a");
			
			$comparisonSD = date('Y-m-d', strtotime($startdate . ' -' . $days . ' day') );
			$comparisonED = date('Y-m-d', strtotime($startdate));
			
			$compdates = $this->utils->date_array($comparisonSD,$comparisonED);
			foreach ($compdates as $date){
				  $array_orders_totals_comp[$date] = 0;
			}
			
			$total_sales_comp = 0;
			
			if($total_orders_comp > 1){
				
				foreach ($orders_comp as $ctr => $value)
				{
					error_log('this far');
					$order_id = $value->ID;
			
					$order = wc_get_order($order_id);
					
					$order_date = date("d/m/Y",strtotime($order->get_date_created()));
			
					$array_orders_totals_comp[$order_date] += 1;
			
					$total_sales_comp += 1;
				}
				
			}
			
			$temparray = array();
			foreach($array_orders_totals_comp as $item){
			  array_push($temparray,$item);
			}
			$comp_data = $temparray;
			
			
			
			
			
			$dataSet = array( 
				'labels' => $dates,
				'datasets' => array(
					array(
						'label' => __("Total Sales",'admin2020'),
						  'fill' =>  true,
						  'data' =>  $array_orders_totals,
						  'backgroundColor' =>  array("rgba(12, 92, 239, 0.05)"),
						  'borderColor' =>  array("rgba(12, 92, 239, 1)"),
						  'borderWidth' =>  2,
						  
					),
					array(
						'label' => __("Total Sales (Comparison)",'admin2020'),
						  'fill' =>  true,
						  'data' =>  $comp_data,
						  'backgroundColor' =>  array("rgba(247, 127, 212, 0)"),
						  'borderColor' =>  array("rgb(247, 127, 212)"),
						  'borderWidth' =>  2,
						  
					),
				)
			);
			
			$total = $total_sales;
			$totalC = $total_sales_comp;
			
			if($total == 0 || $totalC == 0){
				$percentChange = 0;
			} else {
				$percentChange = (($total - $totalC) / ($totalC)) * 100;
			}
			
			$returndata['dataSet'] = $dataSet;
			$returndata['numbers']['total'] = number_format($total_sales, 0); 
			$returndata['numbers']['total_comparison'] = number_format($totalC, 0);
			$returndata['numbers']['change'] = number_format($percentChange, 2);
			$returndata['numbers']['dates'] = $dataSet;
			
			
			
			
			echo json_encode($returndata);
			
			
		}
		die();	
		
	}
	
	public function uipress_analytics_get_average_order_value(){
		
		if (defined('DOING_AJAX') && DOING_AJAX && check_ajax_referer('uipress-overview-security-nonce', 'security') > 0) {
			
			if (!is_plugin_active('woocommerce/woocommerce.php')){
				$returndata['message'] = __('Woocommerce is required for this widget','admin2020'); 
				$returndata['error'] = true; 
				echo json_encode($returndata);
				die();
			}
			
			$dates = $this->utils->clean_ajax_input($_POST['dates']); 
			$startdate = date('Y-m-d', strtotime($dates['startDate']));
			$enddate = date('Y-m-d', strtotime($dates['endDate']));
			
			//$analyticsData = $this->get_analytics_data($startDate, $endDate);
			///GET ARRAY OF DATES
			$dates = $this->utils->date_array($startdate,$enddate);
			$json_dates = json_encode($dates);
		
			global  $woocommerce;
			
			$allorders = $this->get_orders($startdate,$enddate);
		
			$total = 0;
		
			$orders = $allorders['now']->posts;
			$total_orders = $allorders['now']->post_count;
			$array_orders_totals = array();
			$orders_count = 0;
			$total_sales = 0;
			
			if($total_orders > 1){
				
				foreach ($orders as $ctr => $value)
				{
					$order_id = $value->ID;
					$order = wc_get_order($order_id);
					
					$order_total = $order->get_total();
			
					$total_sales += $order_total;
					$orders_count += 1;
				}
			}
			
			$averageOrder = $total_sales / $orders_count;
			
			
			
			////COMPARISON
			$array_orders_totals_comp = array();
			
			$total_orders_comp = $allorders['comparison']->post_count;
			$orders_comp = $allorders['comparison']->posts;
			
			$total_sales_comp = 0;
			$orders_count_comp = 0;
			
			if($total_orders_comp > 1){
				
				foreach ($orders_comp as $ctr => $value)
				{
					error_log('this far');
					$order_id = $value->ID;
			
					$order = wc_get_order($order_id);
					$order_total = $order->get_total();
			
			
					$total_sales_comp += $order_total;
					$orders_count_comp += 1;
				}
				
			}
			
			$averageOrderComp = $total_sales_comp / $orders_count_comp;
			
			
			
			$total = $averageOrder;
			$totalC = $averageOrderComp;
			
			if($total == 0 || $totalC == 0){
				$percentChange = 0;
			} else {
				$percentChange = (($total - $totalC) / ($totalC)) * 100;
			}
			
			$returndata['dataSet'] = $dataSet;
			$returndata['numbers']['total'] = $this->format_woo_currency($averageOrder); 
			$returndata['numbers']['total_comparison'] = $this->format_woo_currency($averageOrderComp);
			$returndata['numbers']['change'] = number_format($percentChange, 2);
			
			
			
			
			echo json_encode($returndata);
			
			
		}
		die();	
	}
	
	public function format_woo_currency($number){
		
		$curreny_symbol = get_woocommerce_currency_symbol();
		$currency_pos = get_option( 'woocommerce_currency_pos' );
		
		if($currency_pos == 'left'){
			return html_entity_decode($curreny_symbol . number_format($number,2));
		}
		
		if($currency_pos == 'right'){
			return html_entity_decode(number_format($number,2) . $curreny_symbol);
		}
		
		if($currency_pos == 'left_space'){
			return html_entity_decode($curreny_symbol . ' ' . number_format($number,2));
		}
		
		if($currency_pos == 'right_space'){
			return html_entity_decode(number_format($number,2) . ' ' . $curreny_symbol);
		}
		
		
	}
	
	
	/**
	* Fetches orders  / returns current query
	* @since 1.4
	*/
	
	public function get_orders($startdate = null, $enddate = null){
		
		
		if(is_object($this->orders) && $this->start_date == $startdate && $this->end_date == $enddate ) {
			return $this->orders;
		} else {
			$this->start_date = $startdate;
			$this->end_date = $enddate;
			
			$earlier = new DateTime($startdate);
			$later = new DateTime($enddate);
			$days = $later->diff($earlier)->format("%a");
			
			$comparisonSD = date('Y-m-d', strtotime($startdate . ' -' . $days . ' day') );
			$comparisonED = date('Y-m-d', strtotime($startdate) );
			
			
			
			$args = [
				'post_type' => 'shop_order',
				'posts_per_page' => '-1',
				'post_status' => array('wc-completed','wc-pending','wc-processing', 'wc-on-hold'),
				'date_query' => array(
					array(
						'after'     => $startdate,
						'before'    => $enddate,
						'inclusive' => true,
						),
					  ),
			];
			
			wp_reset_query();
			$currentOrders = new WP_Query($args);
			
			$args = [
				'post_type' => 'shop_order',
				'posts_per_page' => '-1',
				'post_status' => array('wc-completed','wc-pending','wc-processing', 'wc-on-hold'),
				'date_query' => array(
					array(
						'after'     => $comparisonSD,
						'before'    => $comparisonED,
						'inclusive' => true,
						),
					  ),
			];
			
			wp_reset_query();
			$comparisonOrders = new WP_Query($args);
			
			
			
			$allOrders = array();
			$allOrders['now'] = $currentOrders;
			$allOrders['comparison'] = $comparisonOrders;
			
			$this->orders = $allOrders;
			return $allOrders;
		}
	}
	
	
	public function best_selling_products( $limit = '-1', $startdate, $enddate){
		global $wpdb;
	
		$limit_clause = intval($limit) <= 0 ? '' : 'LIMIT '. intval($limit);
		
	
		return (array) $wpdb->get_results("
			SELECT p.ID as id, COUNT(oim2.meta_value) as count
			FROM {$wpdb->prefix}posts p
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
				ON p.ID = oim.meta_value
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim2
				ON oim.order_item_id = oim2.order_item_id
			INNER JOIN {$wpdb->prefix}woocommerce_order_items oi
				ON oim.order_item_id = oi.order_item_id
			INNER JOIN {$wpdb->prefix}posts as o
				ON o.ID = oi.order_id
			WHERE p.post_type = 'product'
			AND p.post_status = 'publish'
			AND o.post_status IN ('wc-processing','wc-completed','wc-pending','wc-on-hold')
			AND o.post_date >= '$startdate'
			AND o.post_date <= '$enddate'
			AND oim.meta_key = '_product_id'
			AND oim2.meta_key = '_qty'
			GROUP BY p.ID
			ORDER BY COUNT(oim2.meta_value) + 0 DESC
			$limit_clause
		");
	}
	
}

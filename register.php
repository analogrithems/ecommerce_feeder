<?php
/*
Plugin Name: WordPress Ecommerce Data Feeder
Plugin URI: http://www.analogrithems.com/rant/2010/12/17/wordpress-data-feeder-plugin/
Description: A utility to import and update the wp-e-commerce product catalog from another backend server
Version: 0.4
Author: Analogrithems
Author URI: http://www.analogrithems.com
*/

/*
 * @package Wordpress eCommerce Datafeeder
 * @author Analogrithems
 * @version 0.4
 * @license http://www.analogrithems.com/rant/portfolio/project-licensing/
 */

global $logger, $ecom_plugin;
define('ECOMMERCE_FEEDER', '2012032901');

//Only uncomment if you want to do debugging
define('ECOMMFEEDER_DEBUG', 0);



$ecom_plugin = WP_PLUGIN_DIR . '/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));


include_once('classes/WPEC_ecommerce_feeder.class.php');
include_once('classes/xml.class.php');
include_once('classes/jobs.class.php');
do_action('ecommerce-feeder-init');
//Implement a real debugging system
require_once ($ecom_plugin.'classes/WP_Logger.class.php');
$logger = new WP_Logger($ecom_plugin.'/ecommerce-feeder.log');

/**
* This is where we hook in our XML-RPC service,  got claim our xml methods
*/
global $ef_errors;
function init_EF_XMLRPC($methods){
	include_once('classes/WPSC_Ecommerce_Feeder_XML.class.php');
	$xmlService = new WPSC_Ecommerce_Feeder_XML();
	return($xmlService->registerMethods($methods));
}
function ef_foo($args){
	return('1.1.1.0');
}
add_filter('xmlrpc_methods', 'init_EF_XMLRPC');

if (is_admin()){
	function wpsc_add_data_feeder_page($page_hooks, $base_page) {
		global $logger;
		global $data_feed_page;
		$logger->debug("Init arguments:".print_r($page_hooks,true).':'.print_r($base_page,true));
		$data_feed_page =add_submenu_page('tools.php',__('eCommerce Feeder','wpsc'), __('eCommerce Feeder','wpsc'), 'manage_options', 'wpsc_module_data_feeder','display_wpe_data_feeder');	
		$page_hooks[] =	$data_feed_page;
		add_action('admin_init','wpsc_data_feeder_init');		
		return $page_hooks;
	}
	function ecommfeeder_ajax_job(){
		global $job, $logger;
                unset($_SESSION['status_msg']);
                unset($_SESSION['error_msg']);
		if(ECOMMFEEDER_DEBUG < 1) @define('WP_DEBUG', false);
		$logger->info("Ajax Job");
		$job = new WPEC_Jobs();
		@$job->ajax_job();
	}
	add_action('wp_ajax_ecomm_feeder_task', 'ecommfeeder_ajax_job');
}
add_filter('wpsc_additional_pages', 'wpsc_add_data_feeder_page',10, 2);

function wpsc_data_feeder_init(){
	wp_register_style('ecomm_data', WP_PLUGIN_URL . '/ecommerce-feeder/views/css/ecommerce_data.css');
	add_action( 'admin_print_styles', 'wpec_data_feed_styles' );
	register_setting( 'wpe_data_feed', 'wpe_data_feed');
}

function wpec_data_feed_styles(){
	global $wp_version;
	wp_enqueue_style( 'ecomm_data');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-widget');
// WordPress 3.1 vs older version compatibility
	if($wp_version > 3.2){
		if ( wp_script_is( 'jquery-ui-widget', 'registered' ) )
			wp_enqueue_script( 'jquery-ui-progressbar', plugins_url( 'jquery-ui/jquery.ui.progressbar.min.js', __FILE__ ), array( 'jquery-ui-core', 'jquery-ui-widget' ), '1.8.6' );
		else
			wp_enqueue_script( 'jquery-ui-progressbar', plugins_url( 'jquery-ui/jquery.ui.progressbar.min.1.7.2.js', __FILE__ ), array( 'jquery-ui-core' ), '1.7.2' );
	}else{
		wp_enqueue_script('jquery-ui-progressbar');
	}

	wp_enqueue_style( 'jquery-ui-regenthumbs', plugins_url( 'jquery-ui/redmond/jquery-ui-1.7.2.custom.css', __FILE__ ), array(), '1.7.2' );
}

function exportData(){
	global $logger;
	if(isset($_REQUEST['wpec_data_feeder']) && isset($_REQUEST['submit'])){
		switch($_REQUEST['submit']){
			case 'Export Data':
				$job = new WPEC_Jobs();
				$job->init();
				if(isset($_REQUEST['wpec_data_feeder']) ) $result = $job->runJob($_REQUEST['wpec_data_feeder']);
				break;
			default:
				break;
		}
	}
}
add_action('admin_menu', 'exportData');
	
function display_wpe_data_feeder(){
	global $data_feed_page, $data, $tab, $logger, $scheduledJobs, $job, $task, $count;
	//Trying out Smarty
	$tab = isset($_REQUEST['wpec_data_feeder']['direction'])? $_REQUEST['wpec_data_feeder']['direction'] : 'import';
	$result = false;
	$job = new WPEC_Jobs();
	$job->init();

	$logger->debug("Running ".print_r($_REQUEST,true));
	//load css
	if(!isset($_REQUEST['wpec_data_feeder']['direction'])){
		//Get the list of already saved import jobs to show the user
		wpec_data_feed_styles();
		include('views/tab.menu.php');
		$scheduledJobs = $job->getScheudledJobs(array('direction'=>'import'));
		include('views/import.php');
	}else{
		//This is were the business logic actually happens
		if(isset($_REQUEST['submit'])){
			switch($_REQUEST['submit']){
				case 'Run Now':
					$logger->debug("Running Debug");
					if(isset($_REQUEST['wpec_data_feeder']) ) $count = $job->getCount($_REQUEST['wpec_data_feeder']);
					$task = $_REQUEST['wpec_data_feeder'];
					if(isset($_SESSION['source_file'])){
						$task['source'] = $_SESSION['source_file'];
					}
					include('views/runJob.php');
					return true;
					break;
				case 'Save Job':
					if(isset($_REQUEST['wpec_data_feeder'])){
						$result = $job->saveJob($_REQUEST['wpec_data_feeder']);
					}
					break;
				case 'Delete':
					if(isset($_REQUEST['id'])){
						 $result = $job->deleteJob($_REQUEST['id']);
					}
					break;
				case 'Edit':
					if(isset($_REQUEST['id'])){
						$data = $job->getScheudledJobs(array('id'=>$_REQUEST['id']));
					}
					break;
				default:
					print "Test nothing";	
					return false;
			}
		}
		wpec_data_feed_styles();
		//If it added ok, remove the posted config, so a new one can be added
		if($result){
			unset($_REQUEST['wpec_data_feeder']);
		}
		//Unless some previous business stopped us, lets display the page we're looking for
		include('views/tab.menu.php');
		
		switch($tab){
			case 'import':
				//Get the list of already saved import jobs to show the user
				$scheduledJobs = $job->getScheudledJobs(array('direction'=>$tab));
				include('views/import.php');
				break;
			case 'export';
				$scheduledJobs = $job->getScheudledJobs(array('direction'=>$tab));
				include('views/export.php');
				break;
			case 'schedule';
				$scheduledJobs = $job->getScheudledJobs();
				include('views/scheduleDisplay.php');
				break;
			default:
				include('views/import.php');
				break;
		}
	}
}

//Load the built in scripts
function load_feeder_scripts(){
	global $ecom_plugin;
	//Register my XML script
	include_once($ecom_plugin.'/classes/xmlJobs.class.php');
	$xmlJob = new xmlJobs();
	$xmlJob->init();

	//Register my CSV script
	include_once($ecom_plugin.'/classes/csvJobs.class.php');
	$csvJob = new csvJobs();
	$csvJob->init();

	//Register my SQL script
	include_once($ecom_plugin.'/classes/sqlJobs.class.php');
	$sqlJob = new sqlJobs();
	$sqlJob->init();
}
add_filter('wpsc_init', 'load_feeder_scripts');
/**
* This next section just loads the info for admin help context menus
*
*/
function data_feeder_help($contextual_help, $screen_id, $screen) {

	global $ecom_plugin, $data_feed_page;
	if ($screen_id == $data_feed_page) {
		$contextual_help = file_get_contents($ecom_plugin.'/views/help.php');
	}
	return $contextual_help;
}
add_filter('contextual_help', 'data_feeder_help', 10, 3);


function htmlOptions($options, $selected = null){
	$str = '';
	foreach($options as $k=>$v){
		if($k == $selected) $sel = ' selected ';
		else $sel = '';
		$str .= "<option value='{$k}' {$sel}>{$v}</option>\n";
	}
	return $str;
}

function fromRequest($var){
	global $data;
	if(isset($_REQUEST['wpec_data_feeder'][$var])){
		return $_REQUEST['wpec_data_feeder'][$var];
	}elseif(isset($data[$var])){
		return $data[$var];
	}else{
		return '';
	}
}
?>

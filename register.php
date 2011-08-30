<?php
/*
Plugin Name: WordPress Ecommerce Data Feeder
Plugin URI: http://www.analogrithems.com/rant/2010/12/17/wordpress-data-feeder-plugin/
Description: A utility to import and update the wp-e-commerce product catalog from another backend server
Version: 0.1
Author: Analogrithems
Author URI: http://www.analogrithems.com
*/

/*
 * @package Wordpress eCommerce Datafeeder
 * @author Analogrithems
 * @version 0.1-Dev
 * @license http://www.analogrithems.com/rant/portfolio/project-licensing/
 */

global $logger, $ecom_plugin;
define('ECOMMERCE_FEEDER', '20110701');
$ecom_plugin = WP_PLUGIN_DIR . '/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
//Implement a real debugging system
require_once ($ecom_plugin.'classes/WP_Logger.class.php');
$logger = new WP_Logger($ecom_plugin.'/ecommerce_feeder.log');


include_once('classes/WPEC_ecommerce_feeder.class.php');
include_once('classes/xml.class.php');
include_once('classes/jobs.class.php');

//This registers the password migration functions 
include_once('classes/password.migration.class.php');
new WPSC_EC_Password_Migrator();


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
		$data_feed_page =add_submenu_page($base_page,__('-Data Feed','wpsc'), __('-Data Feeds','wpsc'), 'manage_options', 'wpsc_module_data_feeder','display_wpe_data_feeder');	
		$page_hooks[] =	$data_feed_page;
		add_action('admin_init','wpsc_data_feeder_init');		
		return $page_hooks;
	}
}
add_filter('wpsc_additional_pages', 'wpsc_add_data_feeder_page',10, 2);

function wpsc_data_feeder_init(){
	wp_register_style('ecomm_data', WP_PLUGIN_URL . '/ecommerce_feeder/views/css/ecommerce_data.css');
	add_action( 'admin_print_styles', 'wpec_data_feed_styles' );
	register_setting( 'wpe_data_feed', 'wpe_data_feed');
}

function wpec_data_feed_styles(){
	wp_enqueue_style( 'ecomm_data');
}

function exportData(){
	global $logger;
	switch($_REQUEST['submit']){
		case 'Export Data':
		case 'Run Now':
			$logger->debug("Running Debug");
			$job = new WPEC_Jobs();
			$job->init();
			if(isset($_REQUEST['wpec_data_feeder']) ) $result = $job->runJob($_REQUEST['wpec_data_feeder']);
			break;
	}
}
add_action('admin_menu', exportData);
	
function display_wpe_data_feeder(){
	global $data_feed_page, $tab, $logger, $scheduledJobs, $job;
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
		$job->prepareImportForm($data);
		include('views/import.php');
	}else{
		//This is were the business logic actually happens
		if(isset($_REQUEST['submit'])){
			switch($_REQUEST['submit']){
				case 'Export Data':
				case 'Run Now':
					$logger->debug("Running Debug");
					if(isset($_REQUEST['wpec_data_feeder']) ) $result = $job->runJob($_REQUEST['wpec_data_feeder']);
					break;
				case 'Save Job':
					if(isset($_REQUEST['wpec_data_feeder'])){
						$result = $job->saveJob($_REQUEST['wpec_data_feeder']);
					}
					break;
				case 'Export':
					if(isset($_REQUEST['wpec_data_feeder'])){
						 $result = $job->exportJob($_REQUEST['wpec_data_feeder']);
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
		if(!isset($data) && isset($_REQUEST['wpec_data_feeder'])){
			$data = $_REQUEST['wpec_data_feeder'];
		}else if(isset($data)){
			$_REQUEST['wpec_data_feeder'] = array_merge($_REQUEST['wpec_data_feeder'],$data);	
		}else if(!isset($data)){
			$data = '';
		}
		switch($tab){
			case 'import':
				//Get the list of already saved import jobs to show the user
				$scheduledJobs = $job->getScheudledJobs(array('direction'=>$tab));
				$job->prepareImportForm($data);
				include('views/import.php');
				break;
			case 'export';
				$scheduledJobs = $job->getScheudledJobs(array('direction'=>$tab));
				$job->prepareExportForm($data);
				include('views/export.php');
				break;
			case 'schedule';
				$scheduledJobs = $job->getScheudledJobs();
				include('views/scheduleDisplay.php');
				break;
			default:
				$job->prepareImportForm($data);
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
	if(isset($_REQUEST['wpec_data_feeder'][$var])){
		return $_REQUEST['wpec_data_feeder'][$var];
	}else{
		return '';
	}
}
?>

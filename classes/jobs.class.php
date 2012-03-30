<?php
/**
 * jobs.class.php
 *
 * @author Analogrithems <Analogrithems@gmail.com>
 * @version 0.1-Dev
 * @license http://www.analogrithems.com/rant/portfolio/project-licensing/
 */


/**
 *
 * This is the jobs class that is used create and run various jobs for the wordpress ecommerce data feeder plugin.
 * It does the basic input validation when creating records in the data_feeder table.
 *
 *
 *
 * @package Wordpress eCommerce Datafeeder
 * @subpackage WPEC_Jobs
 */

class WPEC_Jobs extends WPEC_ecommerce_feeder{

	var $customers;
	var $profiles;
	var $savedJobs;
	
	function __construct(){
                global $wpdb;
		parent::__construct();
	}

	function init(){
		if ( ! function_exists( 'admin_url' ) )
			return false;
		$this->savedJobs = get_option('WPEC_Jobs');
	}

	/**
	 * runSave($data)
	 *
	 * Last step before we save the job to the data_feeder prefix
	 *
	 * @param mixed $data to save to the table
	 * @return boolean
	 */
	function runSave($data){
		$slug = sanitize_title($data['name']);
		if(isset($this->savedJobs) && !empty($this->savedJobs)){
			$this->savedJobs[$slug] = $data;
			update_option('WPEC_Jobs', $this->savedJobs);
		}else{
			$this->savedJobs[$slug] = $data;
			add_option('WPEC_Jobs', $this->savedJobs,'','no');
		}
		$_SESSION['status_msg'] = __("You're Job Was Saved",'ecommerce_feeder');
	}

	function validateJob($data=false){
		return true;
	}

	/**
	 * saveJob([mixed $data = false])
	 *
	 * The call to save the submitted data.  If no array is passed it will try to grab it from the _REQUEST
	 * It will validate the data against the scripts hooks
	 *
	 * @param mixed $data
	 * @return boolean
	 */
	function saveJob($data=false){
		$valid=true;
		if(!$data || empty($data) ){
			if($this->isGood($_REQUEST['submit']) && $this->isGood($_REQUEST['action']) && $_REQUEST['action'] == 'Save Job'){
				$data = $_REQUEST['wpec_data_feeder'];
			}else{
				return false;
			}
		}
		if(!$this->isGood($data['direction']) && $_REQUEST['direction']) $data['direction'] = $_REQUEST['direction'];
		$valid = apply_filters('ecommerce_feeder_validateJob_'.$data['type'],$data);
		if($valid) $this->runSave($data);
	}

	/**
	 *
	 * getScheudledJobs([mixed $options = false])
	 *
	 * get a array of scheudled jobs.  Can be used to either display a list of the currently scheduled jobs,
	 * or used to get all the specific jobs in a certain scheudled.
	 *
	 * <code>
	 * $options = array('schedule'=>'hourly', 'direction'=>'import')
	 * $jobs = new WPEC_Jobs();
	 * $scheduled_jobs = $jobs->getScheudledJobs($options);
	 * </code>
	 *
	 * @param $options filtering options, if nothing is given it returns all jobs
	 * @returns mixed 
	 */
	function getScheudledJobs($options=false){
		if($this->isGood($options['id']) && $this->isGood($this->savedJobs[$options['id']]) ){
			return $this->savedJobs[$options['id']];
		}else{
			if($this->isGood($options['direction']) && $this->isGood($this->savedJobs) &&  in_array($options['direction'],array('import','export'))){
				foreach($this->savedJobs as $name=>$job){
					//Find all the job types you want to display
					if($job['direction'] == $options['direction']){
						$results[$name] = $job;
					}
				}
			}elseif($this->isGood($this->savedJobs)){
				return $this->savedJobs;
			}else{
				return false;
			}
		}
		return isset($results) ? $results : false;
	}

	/**
	 * runJobs([mixed $options=false])
	 *
	 * Run some jobs.  You can either give a specific id or a scheudle and it will locate the jobs and run it
	 *
	 * <code>
	 * $jobs = new WPEC_Jobs();
	 * $result = $jobs->runJobs(array('id'=>14));
	 * </code>
	 * or you could give it a scheudle like this
	 * <code>
	 * $jobs = new WPEC_Jobs();
	 * $result = $jobs->runJobs(array('schedule'=>'hourly'));
	 * </code>
	 *
	 * @param mixed $options
	 * @return boolean (true|false) for if it completed successfully or not
	 */
	function runJobs($options=false){
		if($this->isGood($options['id']) && $this->isGood($this->savedJobs[$options['id']]) ){
			$jobs = $this->getScheudledJobs(array('id'=>$options['id']));
		}elseif($this->isGood($options['schedule'])){
			$jobs = $this->getScheudledJobs(array('schedule'=>$options['schedule']));
		}elseif($this->isGood($options['direction'])){
			$jobs = $this->getScheudledJobs(array('direction'=>$options['direction']));
		}
		$this->logger->info("WPEC_Jobs::runJobs(".print_r($options,true).") running jobs ".print_r($jobs,true));
		//figure out how to run actual job here.
		foreach($jobs as $job){
			$this->executeJob($job);
		}
	}
			
	/**
	* runJob($job)
	*
	* @param mixed $job
	*
	*/
	function runJob($job){
		$this->logger->info("WPEC_Jobs::runJob(".print_r($job,true),")");
		$this->executeJob($job);
	}


	function getCount(&$instructions=false){
		extract($instructions);
		$scripts = apply_filters('ecommerce_feeder_register_script',array());
		if(isset($direction) && $direction == 'import' && isset($type) &&  array_key_exists($type, $scripts[$direction])){
                        $valid = apply_filters('ecommerce_feeder_validateJob_'.$type,$instructions);
                        if($valid) $count = apply_filters('ecommerce_feeder_import_get_count_'.$type,$object,$instructions);
                }elseif(isset($direction) && $direction == 'export' && isset($type) &&  array_key_exists($type, $scripts[$direction])){
                        $dataSets = $this->runDataTypeExport($object);
                        $count = apply_filters('ecommerce_feeder_export_get_count_'.$type,$object,$dataSets);
                }
		if(isset($count) && $count > 0) return $count;
		else{
			$_SESSION['error_msg'] = __("Record count invalid, bailing!",'ecommerce_feeder');
			return false;
		}
	}

	/**
	* executeJob($instructions)
	* This executes the job
	*
	* @param mixed $instructions
	*
	*/
	function executeJob(&$instructions){
		$this->logger->debug("executeJob:".print_r($instructions,1));
		extract($instructions);	
		set_time_limit(0);
		$scripts = apply_filters('ecommerce_feeder_register_script',array());
		if(isset($direction) && $direction == 'import' && isset($type) &&  array_key_exists($type, $scripts[$direction])){
			$valid = apply_filters('ecommerce_feeder_validateJob_'.$type,$instructions);
			if($valid) do_action('ecommerce_feeder_run_import_'.$type,$object,&$instructions);
		}elseif(isset($direction) && $direction == 'export' && isset($type) &&  array_key_exists($type, $scripts[$direction])){
			$dataSets = $this->runDataTypeExport($object);
			do_action('ecommerce_feeder_run_export_'.$type,$object,&$dataSets,&$instructions);
		}
	}	

	function getDSN($opts){
		extract($opts);
		//I want to use something to will db independant for max fleaxility.
		//It appears php is going towards pdo, so that is the choice for now
		try {
		    $pdo = new PDO("{$driver}:host={$host};{$dbname}",$user,$password);
		    if($driver == 'mysql'){
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->exec("use {$dbname};");
		    }
		} catch (PDOException $e) {
		    $this->logger->error("Failed to connetect:".$e->getMessage()."Host:{$host}\tDriver:{$driver}\tDatabase Name:{$dbname}\tUser:{$user}\tPassword:{$password}");
		}
		return $pdo;
	}

	/**
	* deleteJob($id)
	*
	* Delete Job run schedules tables
	*
	* @param int $id id of job to delete
	*/
	function deleteJob($id){
		if(isset($this->savedJobs[$id])){
			unset($this->savedJobs[$id]);
			update_option('WPEC_Jobs',$this->savedJobs);
		}
	}

	function runDataTypeImport($dataType,$dataSet){
		switch($dataType){
			case 'orders':
				//We are importing orders, run it
				include_once('orders.class.php');
				$this->logger->info("Trying tp update orders:".print_r($dataSet,true));
				$orders = new WPEC_Orders();
				$results = $orders->updateOrders($dataSet);
				break;
			case 'customers':
				//We are importing customers, run it
				include_once('users.class.php');
				global $usersAdded, $usersUpdated;
				$this->logger->info("Trying to update customers:".print_r($dataSet,true));
				$customers = new WPEC_Users();
				$results = $customers->updateUsers($dataSet);
				break;
			case 'products':
				//We are importing products, run it
				include_once('products.class.php');
				global $productUpdates, $variantUpdates;
				$this->logger->info("Trying tp update products:".print_r($dataSet,true));
				$products = new WPEC_Products();
				$products->updateProduct($dataSet);
				break;
			default:
				$this->logger->error("Failed to Run Job, incorrect object.:".print_r($instructions,true));
				break;
		}
	}

	
	function runDataTypeExport($dataType){
		switch($dataType){
			case 'orders':
				//We are exporting the orders
				include_once('orders.class.php');
				$orders = new WPEC_Orders();
				$dataSets = $orders->exportOrders();
				//$this->logger->info("Trying to export orders:".print_r($dataSets,true));
				break;
			case 'customers':
				//We are exporting the customers
				include_once('users.class.php');
				$customers = new WPEC_Users();
				$dataSets = $customers->exportUsers();
				//$this->logger->info("Trying to export customers:".print_r($dataSets,true));
				break;
			case 'products':
				//We are exporting the products
				include_once('products.class.php');
				$products = new WPEC_Products();
				$dataSets = $products->exportProducts();
				//$this->logger->info("Trying to export products:".print_r($dataSets,true));
				break;
			default:
				$this->logger->error("Failed to Run Job, incorrect object.:".print_r($instructions,true));
				$_SESSION['error_msg'] = __("Failed to Run Job, incorrect object.:",'ecommerce_feeder').print_r($instructions,true);
				break;
		}
		return($dataSets);
	}

	function ajax_job(){
		global $user_ID;
		@error_reporting( 0 ); // Don't break the JSON result
		@define('WP_DEBUG',false);
		@define('WP_DEBUG_DISPLAY', false);
		@set_time_limit( 900 ); // 5 minutes per image should be PLENTY
		header( 'Content-type: application/json' );
	
		if($this->isGood($_REQUEST['task']) && isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) ){
			$id = $_REQUEST['id'];
			$task = $_REQUEST['task'];
			$task['limit'] = $id;
			$this->logger->info("Running job with:".print_r($task,1));
			$this->executeJob($task);
			if($this->isGood($_SESSION['status_msg'])){
				die(json_encode(array('success'=>$this->esc_quotes($_SESSION['status_msg']))));
			}elseif($this->isGood($_SESSION['error_msg'])){
				die(json_encode(array('error'=>$this->esc_quotes($_SESSION['error_msg']))));
			}
		}
	}
	
	// Helper function to escape quotes in strings for use in Javascript
	function esc_quotes( $string ) {
		return str_replace( '"', '\"', $string );
	}
}

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
	var $scripts;
	var $savedJobs;
	
	function __construct(){
                global $wpdb;
                define('WPEC_ECOMM_FEEDER', $wpdb->prefix.'data_feeder');
                $this->mydb = WPEC_ECOMM_FEEDER;
		parent::__construct();
	}

	function init(){
		$this->savedJobs = get_option('WPEC_Jobs');
		$this->scripts = apply_filters('ecommerce_feeder_register_script',$this->scripts);
		return($this->scripts);
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
	 * prepareImportForm([mixed $data=false])
	 *
	 * Get any template variables ready that will be needed for the admin import form.  $data can be passed to prepopulate a form for updates or re-submits
	 *
	 * @params mixed $data
	 */
	function prepareImportForm($data=false){
		global $objects, $types, $db_drivers;
		$objects = array('customers'=>'Customer Accounts', 'products'=>'Products','orders'=>'Order History');
	}

	/**
	 * prepareExportForm([mixed $data=false])
	 *
	 * Get any template variables ready that will be needed for the admin export form.  $data can be passed to prepopulate a form for updates or re-submits
	 *
	 * @params mixed $data
	 */
	function prepareExportForm($data=false){
		$this->prepareImportForm($data);
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
			if($this->isGood($options['direction']) && in_array($options['direction'],array('import','export'))){
				foreach($this->savedJobs as $name=>$job){
					//Find all the job types you want to display
					if($job['direction'] == $options['direction']){
						$results[$name] = $job;
					}
				}
			}else{
				return $this->savedJobs;
			}
		}
		return $results;
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

	/**
	* executeJob($instructions)
	* This executes the job
	*
	* @param mixed $instructions
	*
	*/
	function executeJob($instructions){
		extract($instructions);	
		set_time_limit(0);
		if(isset($direction) && $direction == 'import' && isset($type) &&  array_key_exists($type, $this->scripts[$direction])){
			do_action('ecommerce_feeder_run_import_'.$type,$object,$instructions);
		}elseif(isset($direction) && $direction == 'export' && isset($type) &&  array_key_exists($type, $this->scripts[$direction])){
			$dataSets = $this->runDataTypeExport($object);
			do_action('ecommerce_feeder_run_export_'.$type,$object,$dataSets);
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
				$this->logger->info("Trying tp update orders:".print_r($dataSet[1],true));
				$orders = new WPEC_Orders();
				$orders->updateOrders($dataSet);
				break;
			case 'customers':
				//We are importing customers, run it
				include_once('users.class.php');
				global $usersAdded, $usersUpdated;
				$this->logger->info("Trying to update customers:".print_r($dataSet,true));
				$customers = new WPEC_Users();
				$results = $customers->updateUsers($dataSet);
				$_SESSION['status_msg'] = "{$results['added']}/{$results['updated']} ".__('Users Added/Updated','wpsc');
				break;
			case 'products':
				//We are importing products, run it
				include_once('products.class.php');
				global $productUpdates, $variantUpdates;
				$this->logger->info("Trying tp update products:".print_r($dataSet,true));
				$products = new WPEC_Products();
				$products->updateProduct($dataSet);
				$_SESSION['status_msg'] = "{$productUpdates}/{$variantUpdates} ".__('Products/Variants updated','wpsc');
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
				//$this->logger->info("Trying to export products:".print_r($dataSet,true));
				break;
			default:
				$this->logger->error("Failed to Run Job, incorrect object.:".print_r($instructions,true));
				break;
		}
		return($dataSets);
	}
}

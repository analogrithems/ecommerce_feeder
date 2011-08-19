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
	
	function __construct(){
                global $wpdb;
                define('WPEC_ECOMM_FEEDER', $wpdb->prefix.'data_feeder');
                $this->mydb = WPEC_ECOMM_FEEDER;
		parent::__construct();
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
		$result = $this->save($data);
		return $result;
	}

	/**
	 * saveJob([mixed $data = false])
	 *
	 * The call to save the submitted data.  If no array is passed it will try to grab it from the _REQUEST
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
		switch($data['type']){
			case 'db':
				if(!$this->isGood($data['name']) ){
					$this->setError("Whatever name you give this job must be unique!");
					$valid=false;
				}
				if(!$this->isGood($data['db_driver'])){
					$this->setError("You Must Select a Database Driver!");
					$valid=false;
				}
				if(!$this->isGood($data['dbhost'])){
					$this->setError("You Must Specify the Database Server!");
					$valid=false;
				}
				if(!$this->isGood($data['dbuser'])){
					$this->setError("You Must Specify the Database User to Connect As!");
					$valid=false;
				}
				if(!$this->isGood($data['dbpassword'])){
					$this->setError("You Must Specify the Database Password to Connect With!");
					$valid=false;
				}
				if(!$this->isGood($data['dbname'])){
					$this->setError("You Must Specify The Database to Connect to!");
					$valid=false;
				}
				if(!$this->isGood($data['source']['sql'])){
					$this->setError("What am I supposed to do with this?  Give me a SQL Query!");
					$valid=false;
				}else{
					$source = $data['source']['sql'];
					unset($data['source']);
					$data['source'] = $source;
				}
				$this->logger->debug(print_r($data,true));
				if($valid){
					$result = $this->runSave($data);
					if($result){
						unset($_REQUEST['wpec_data_feeder']['action']);
						return true;
					}
				}else{
					return false;
				}

				break;
			case 'xml':
				if(!$this->isGood($data['source_xml'])){
					$this->setError("Must Give A URL to Download XML From!");
					return false;
				}else{
					$source = $data['source_xml'];
					unset($data['source_xml']);
					$data['source'] = $source;
				}
				unset($data['db_driver']);
				unset($data['dbhost']);
				unset($data['dbuser']);
				unset($data['dbpassword']);
				unset($data['dbname']);
				$result = $this->runSave($data);
				break;
			case 'csv':
				if(!$this->isGood($data['source_csv'])){
					$this->setError("Must Give A URL to Download CSV From!");
					return false;
				}else{
					$source = $data['source_csv'];
					unset($data['source_csv']);
					$data['source'] = $source;
				}
				unset($data['db_driver']);
				unset($data['dbhost']);
				unset($data['dbuser']);
				unset($data['dbpassword']);
				unset($data['dbname']);
				$result = $this->runSave($data);
				break;
			default:
				$this->setError("You Must Choose A Source!");
				return false;
		}

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
		$types = array('none'=>'Select Type', 'db'=>'SQL DB', 'xml'=>'XML', 'csv'=>'CSV');
		$db_drivers = array('Select a Driver','mysql'=>'MySQL','mssql'=>'MS SQL', 'oracle'=>'Oracle');
		$this->logger->info("Preping:".print_r($data,1).print_r($_REQUEST['wpec_data_feeder'],1));
		if($this->isGood($data)  && isset($data['source'])){
			$_REQUEST['wpec_data_feeder'] = $data;
			switch($data['type']){
				case 'db':
				case 'sql':
					unset($_REQUEST['wpec_data_feeder']['source']);
					$_REQUEST['wpec_data_feeder']['source_sql'] = $data['source'];
					break;
				case 'csv':
					unset($_REQUEST['wpec_data_feeder']['source']);
					$_REQUEST['wpec_data_feeder']['source_csv'] = $data['source'];
					break;
				case 'xml':
					unset($_REQUEST['wpec_data_feeder']['source']);
					$_REQUEST['wpec_data_feeder']['source_xml'] = $data['source'];
					break;
			}
		}
				
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
	 * prepareScheudler([mixed $data=false])
	 *
	 * Get any template variables ready that will be needed for the admin forms.  $data can be passed to prepopulate a form for updates or re-submits
	 *
	 * @params mixed $data
	 */
	function prepareScheudler($data=false){
		global $schedules, $times;
		if(!$data && $this->isGood($_REQUEST['wpec_data_feeder'])){
			$data = $_REQUEST['wpec_data_feeder'];
		}
		foreach($schedules as $key=>$value){
			$times[$key] = $schedules[$key]['display'];
		}
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
		$opts = false;
		if($this->isGood($options['id']) && is_numeric($options['id'])){
			$opts['filter'] = 'id='.$options['id'];
		}else{
			if($this->isGood($options['direction']) && in_array($options['direction'],array('import','export'))){
				$opts['filter'][]='direction=\''.$options['direction'].'\'';
			}elseif($this->isGood($options['direction'])){
				$this->logger->error("WPEC_Jobs::getScheudledJobs(".print_r($options,true).") bad direction specified, should be import or export");
			}
			if($this->isGood($options['schedule'])){
				$opts['filter'][]='schedule=\''.$options['schedule'].'\'';
			}
		}
		$results = $this->read($opts);
		$this->logger->info("WPEC_Jobs::getScheudledJobs(".print_r($options,true).") returned ".print_r($results,true));
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
		if($this->isGood($options['id']) && is_numeric($options['id'])){
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
		switch($direction){
			case 'import':
				switch($type){
					case 'csv':
						//If you uploaded a file via browser, use that otherwise use whatever url was passed
						if(file_exists($_FILES['source']['tmp_name'])){
							 $file = $_FILES['source']['tmp_name'];
							$this->logger->info("Using Uploaded file:".print_r($_FILES,true));
						}else{
							//TODO clean this up to
							if(isset($source_csv) && parse_url($source_csv)) {
								$result = $this->getFile($source_csv);
								$this->logger->info("GetFile:".print_r($result,true));
								if(isset($result['tmp_name']) && 
									file_exists($result['tmp_name'])){
									$file = $result['tmp_name'];
								}
							}
						}
						$dataSet = $this->csv2array($file);
						break;
					case 'xml':
						if(file_exists($_FILES['source']['tmp_name'])){
							 $file = $_FILES['source']['tmp_name'];
							$this->logger->info("Using Uploaded file:".print_r($_FILES,true));
						}else{
							if(isset($source_xml) && parse_url($source_xml)) {
								$result = $this->getFile($source_xml);
								$this->logger->info("GetFile from URL:".print_r($result,true));
								if(isset($result['tmp_name']) && 
									file_exists($result['tmp_name'])){
									$file = $result['tmp_name'];
								}
							}
						}
						
						//get xml into array
						include_once('xml.class.php');
						$xml = new EF_XML_Helper();
						$dataSet = $xml->toArray(file_get_contents($file));
						break;
					case 'sql':
					case 'db':
						//connect to sql
						$db_handler = $this->getDSN(array('driver'=>$db_driver,'host'=>$dbhost,'dbname'=>$dbname,'user'=>$dbuser,'password'=>$dbpassword));
						//get sql into array
						$source = $this->isGood($source['sql']) ? $source['sql'] : $source;
						//$sth = $db_handler->prepare($source);
						//$sth->execute();

						$result = $db_handler->query($source);
						$dataSet = $result->fetchAll(PDO::FETCH_ASSOC);
						$this->logger->info("Result of connecting to db:".print_r($db_handler,1));
						$this->logger->info("Here is what the custom SQL returned:".print_r($dataSet,1));
						break;
					default:
						$this->logger->error("Failed to Run Job, Can't find Type:".print_r($instructions,true));
						break;
				}
				switch($object){
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
				break;
			case 'export':
				switch($object){
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
				switch($type){
					case 'csv':
						//get csv into array
						header("Content-Type: application/csv");
						header ("Content-disposition: attachment; filename=wpec_export.csv") ;
						echo $this->array2csv($dataSets);
						exit();
						break;
					case 'xml':
						//get xml into array
						include_once('xml.class.php');
						$xml = new EF_XML_Helper();
						header ("Content-Type:text/xml");
						echo $xml->toXML($dataSets,'Order');
						exit();
						break;
					case 'sql':
					case 'db':
						//connect to sql
						$db_handler = $this->getDSN(array('driver'=>$db_driver,'host'=>$dbhost,'dbname'=>$dbname,'user'=>$dbuser,'password'=>$dbpassword));
						//get sql into array
						break;
					default:
						$this->logger->error("Failed to Run Job, Can't Find Export Type:".print_r($instructions,true));
						break;
				}
				break;
			default:
				$this->logger->error("Failed to Run Job, Not sure if it's import or export:".print_r($direction,true));
				break;
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
		$this->delete($id);
	}

}

<?php

class sqlJobs extends WPEC_Jobs{

	var $script = 'sqlJobs';

	function init(){
                add_filter('ecommerce_feeder_import_form', array($this, 'importForm'));
                add_filter('ecommerce_feeder_validateJob_'.$this->script, array($this, 'validate'));
		add_action('ecommerce_feeder_run_import_'.$this->script, array($this, 'import'),10,2);
		add_filter('ecommerce_feeder_register_script', array($this, 'registerScript'));
                add_filter('ecommerce_feeder_import_get_count_'.$this->script, array($this, 'importCount'),10,2);
                add_filter('ecommerce_feeder_export_get_count_'.$this->script, array($this, 'exportCount'),10,2);
	}

	function registerScript($scripts){
		//So far I only know how to do imports for sql
		if(isset($scripts['import'][$this->script]) ){
			//Already registered, bail
			return $scripts;
		}else{
			//Which data will support?  
			//Users, Products, Orders
			$scripts['import'][$this->script] = array('name'=>'SQL', 'options'=>array('customers'=>'Customer Accounts', 'products'=>'Products','orders'=>'Order History'));
			return $scripts;
		}
	}

        function validate($data){
                if(!isset($data['baseURL'])){
                        $this->setError(__("Must have base URL for image import!, If image import is not needed set it to anything.",'oscom_import'));
                        return false;
                }
                if(!$this->isGood($data['db_driver'])){
                        $this->setError(__("You Must Select a Database Driver!",'oscom_import'));
                        return false;
                }
                if(!$this->isGood($data['dbhost'])){
                        $this->setError(__("You Must Specify the Database Server!",'oscom_import'));
                        return false;
                }
                if(!$this->isGood($data['dbuser'])){
                        $this->setError(__("You Must Specify the Database User to Connect As!",'oscom_import'));
                        return false;
                }
                if(!$this->isGood($data['dbpassword'])){
                        $this->setError(__("You Must Specify the Database Password to Connect With!",'oscom_import'));
                        return false;
                }
                if(!$this->isGood($data['dbname'])){
                        $this->setError(__("You Must Specify The Database to Connect to!",'oscom_import'));
                        return false;
                }
                if(!$this->isGood($data['source_sql'])){
                        $this->setError(__("What am I supposed to do with this?  Give me a SQL Query!",'oscom_import'));
                        return false;
                }
                return true;
        }


	function importCount($data_type,$formFields){
		extract($formFields);
		//connect to sql
		$db_handler = $this->getDSN(array('driver'=>$db_driver,'host'=>$dbhost,'dbname'=>$dbname,'user'=>$dbuser,'password'=>$dbpassword));
		//get sql into array
		if($this->isGood($source_sql)){
			$result = $db_handler->query($source_sql);
			return count($result->fetch(PDO::FETCH_ASSOC));
		}else{
			return 1;
		}

	}

	function importForm($presets=false){
		if(is_array($presets) && !$presets) extract($presets);
		?>
		<div id='<?php echo $this->script; ?>' class='hideonstart'>
			<span class='inputLine'><strong>Select DB Type: </strong>
			    <select name='wpec_data_feeder[db_driver]'>
					<?php echo htmlOptions(array('Select a Driver','mysql'=>'MySQL','mssql'=>'MS SQL', 'oracle'=>'Oracle'), fromRequest('db_driver')); ?>
			    </select>
			</span>
			<span class='inputLine'>
				<strong>DB Server: </strong><input type='text' name='wpec_data_feeder[dbhost]' value='<?php echo fromRequest('dbhost'); ?>'>
				<strong>DB Name: </strong><input type='text' name='wpec_data_feeder[dbname]' value='<?php echo fromRequest('dbname'); ?>'>
			</span>
			<span class='inputLine'>
				<strong>DB User: </strong><input type='text' name='wpec_data_feeder[dbuser]' value='<?php echo fromRequest('dbuser'); ?>'>
				<strong>DB Password: </strong><input type='password' name='wpec_data_feeder[dbpassword]' value='<?php echo fromRequest('dbpassword'); ?>'>
			</span>
			<span class='inputLine'>
				<strong>Query: </strong>
				<textarea name="wpec_data_feeder[source_sql]" rows="20" cols="70"><?php echo fromRequest('source_sql');?></textarea>
			</span>
		</div>
		<?php

	}
	
	function import($data_type,$formFields){
		extract($formFields);

                if(isset($limit) && is_array($limit) && is_numeric($limit['x'])){
                        $this->logger->debug("Limit Range had been defined: {$limit['x']} - {$limit['y']}");
                        $limit = " LIMIT {$limit['x']}, {$limit['y']}";
                }elseif(is_numeric($limit)){
                        $this->logger->debug("Limit, return row {$limit}");
                        $x = $limit; 
                        $y = 1;
                        $limit = " LIMIT {$x}, {$y}";
                }else{
                        $this->logger->debug("No Limit set, return all");
                        $limit = '';
                }

		//connect to sql
		$db_handler = $this->getDSN(array('driver'=>$db_driver,'host'=>$dbhost,'dbname'=>$dbname,'user'=>$dbuser,'password'=>$dbpassword));
		//get sql into array
		if($this->isGood($source_sql)){
			$result = $db_handler->query($source_sql.$limit);
		}else{
			return false;
		}
		$dataSet = $result->fetchAll(PDO::FETCH_ASSOC);
		$this->logger->info("Result of connecting to db:".print_r($db_handler,1));
		$this->logger->info("Here is what the custom SQL returned:".print_r($dataSet,1));
		$this->runDataTypeImport($data_type,$dataSet);
	}

}
?>

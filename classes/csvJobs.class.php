<?php
include_once('parsecsv.lib.php');

class csvJobs extends WPEC_Jobs{

	var $script = 'csvJobs';

	function init(){
                add_filter('ecommerce_feeder_import_form', array($this, 'importForm'));
                add_filter('ecommerce_feeder_export_form', array($this, 'exportForm'));
		add_filter('ecommerce_feeder_validateJob_'.$this->script, array($this, 'validate'));
		add_action('ecommerce_feeder_run_import_'.$this->script, array($this, 'import'),10,2);
		add_filter('ecommerce_feeder_import_get_count_'.$this->script, array($this, 'importCount'),10,2);
		add_filter('ecommerce_feeder_export_get_count_'.$this->script, array($this, 'exportCount'),10,2);
		add_action('ecommerce_feeder_run_export_'.$this->script, array($this, 'export'),10,2);
		add_filter('ecommerce_feeder_register_script', array($this, 'registerScript'));
	}

	function registerScript($scripts){
		if(isset($scripts['import'][$this->script]) || isset($scripts['import'][$this->script])){
			//Already registered, bail
			return $scripts;
		}else{
			//Which data will support?  
			//Users, Products, Orders
			$scripts['import'][$this->script] = array('name'=>'CSV', 'options'=>array('customers'=>'Customer Accounts', 'products'=>'Products','orders'=>'Order History'));
			$scripts['export'][$this->script] = array('name'=>'CSV', 'options'=>array('customers'=>'Customer Accounts', 'products'=>'Products','orders'=>'Order History'));
			return $scripts;
		}
	}

	function validate($data){
		$this->logger->debug("CSV validation");
		if(!$this->isGood($_FILES['source']) && !$this->isGood($data['source'])){
			$this->setError("Must Upload a File!");
			return false;
		}
		return true;
	}


	function importForm($preset=false){
		?>
		<div id='<?php echo $this->script; ?>' class='hideonstart'>
			<span class='inputLine'>
				<strong>CSV URL: </strong> <input type='file' name='source'>
			</span>
		</div>
		<?php
	}
	
	function exportForm($preset=false){
		?>
		<div id='<?php echo $this->script; ?>' class='hideonstart'>
			<span class='inputLine'>
			
			</span>
		</div>
		<?php

	}

	function importCount($data_type,$formFields){
		global $wpdb, $dbprefix;
		extract($formFields);

                if(file_exists($_FILES['source']['tmp_name'])){
                        $file = $_FILES['source']['tmp_name'];
                        $name = $_FILES['source']['name'];
			$uploads = wp_upload_dir();
			$npath = $uploads['path'].'/'.$name;
			$_SESSION['source_file'] = $npath;
                        $this->logger->info("Using Uploaded file:".print_r($_FILES,true));
			$uploads = wp_upload_dir();
			move_uploaded_file($file,$npath);
                }else{
			$this->logger->error("No file uploaded for csv import");
		}

		$count = $this->countLines($npath);
		//First line is field definitions, reduce by one
		$count--;
		$this->logger->debug("CSVImport file has {$count} lines");
		return $count;
	}
	function import($data_type,$formFields){
		extract($formFields);
		//If you uploaded a file via browser, use that otherwise use whatever url was passed
		$file = $formFields['source'];
		$csv = new parseCSV($file);
		$csv->auto();
			
		$this->logger->debug("CSVImport: ".print_r($formFields,1));
		if(isset($limit) && is_array($limit) && is_numeric($limit['x'])){
			$this->logger->debug("Limit Range had been defined: {$limit['x']} - {$limit['y']}");
			$dataSet = array_slice((array)$csv->parse_file(), $limit['x'], $limit['y']);
		}elseif(is_numeric($limit)){
			$this->logger->debug("Limit, return row {$limit}");
			$dataSet = array_slice((array)$csv->parse_file(), $limit,1);
		}else{
			$this->logger->debug("No Limit set, return all");
			$dataSet = (array)$csv->parse_file();
		}

		$this->logger->debug('CSVImport::Importing:'.print_r($dataSet,1));
		$this->runDataTypeImport($data_type,$dataSet);
	}

	function export($data_type=false,$dataSets=false){
		//get csv into array
		if($dataSets){
			header("Content-Type: application/csv");
			header ("Content-disposition: attachment; filename={$data_type}_wpec_export.csv") ;
			echo $this->array2csv($dataSets);
			exit();
		}else{
			return false;
		}
	}
	
}
?>

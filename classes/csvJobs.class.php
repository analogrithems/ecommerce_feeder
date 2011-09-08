<?php

class csvJobs extends WPEC_Jobs{

	var $script = 'csvJobs';

	function init(){
                add_filter('ecommerce_feeder_import_form', array($this, 'importForm'));
                add_filter('ecommerce_feeder_export_form', array($this, 'exportForm'));
		add_filter('ecommerce_feeder_validateJob_'.$this->script, array($this, 'validate'));
		add_action('ecommerce_feeder_run_import_'.$this->script, array($this, 'import'),10,2);
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
		if(!$this->isGood($data['source_csv'])){
			$this->setError("Must Give A URL to Download CSV From!");
			return false;
		}
		return true;
	}


	function importForm($preset=false){
		if(is_array($presets) && !$presets) extract($presets);
		?>
		<div id='<?php echo $this->script; ?>' class='hideonstart'>
			<span class='inputLine'>
				<strong>CSV URL: </strong><input type='text' name='wpec_data_feeder[source_csv]' value='<?php echo fromRequest('source_csv'); ?>'> or <input type='file' name='source'>
			</span>
		</div>
		<?php
	}
	
	function exportForm($preset=false){
		if(is_array($presets) && !$presets) extract($presets);
		?>
		<div id='<?php echo $this->script; ?>' class='hideonstart'>
			<span class='inputLine'>
			
			</span>
		</div>
		<?php

	}

	function import($data_type,$formFields){
		extract($formFields);
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

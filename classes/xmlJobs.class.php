<?php

class xmlJobs extends WPEC_Jobs{

	var $script = 'xmlJobs';

	function init(){
		add_filter('ecommerce_feeder_import_form', array($this, 'importForm'));
		add_filter('ecommerce_feeder_export_form', array($this, 'exportForm'));
		add_filter('ecommerce_feeder_register_script', array($this, 'registerScript'));
		add_action('ecommerce_feeder_run_import_'.$this->script, array($this, 'import'),10,2);
		add_action('ecommerce_feeder_run_export_'.$this->script, array($this, 'export'),10,2);
	}

	function registerScript($scripts){
		if(isset($scripts['import'][$this->script]) || isset($scripts['import'][$this->script])){
			//Already registered, bail
			return $scripts;
		}else{
			//Which data will support?  
			//Users, Products, Orders
			$scripts['import'][$this->script] = array('XML'=>array('users','products','orders'));
			$scripts['export'][$this->script] = array('XML'=>array('users','products','orders'));
			return $scripts;
		}
	}


	function importForm($preset=false){
		if(is_array($presets) && !$presets) extract($presets);
		?>
		<div id='<?php echo $this->script; ?>' class='hideonstart'>
			<span class='inputLine'>
				<strong>XML URL: </strong><input type='text' name='wpec_data_feeder[source_xml]' value='<?php echo fromRequest('source_xml'); ?>'> or <input type='file' name='source'>
			</span>
		</div>
		<?php
	}
	
	function exportForm($presets){
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
		$this->runDataTypeImport($data_type,$dataSet);
	}

        function export($data_type=false,$dataSets=false){
                //get csv into array
                if($dataSets){
			include_once('xml.class.php');
			$xml = new EF_XML_Helper();
			header ("Content-Type:text/xml");
			echo $xml->toXML($dataSets,$data_type);
			exit();
                }else{
                        return false;
                }
        }
}
?>

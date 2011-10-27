<?php

class xmlJobs extends WPEC_Jobs{

	var $script = 'xmlJobs';

	function init(){
		add_filter('ecommerce_feeder_import_form', array($this, 'importForm'));
		add_filter('ecommerce_feeder_export_form', array($this, 'exportForm'));
		add_filter('ecommerce_feeder_validateJob_'.$this->script, array($this, 'validate'));
		add_filter('ecommerce_feeder_register_script', array($this, 'registerScript'));
		add_action('ecommerce_feeder_run_import_'.$this->script, array($this, 'import'),10,2);
		add_action('ecommerce_feeder_run_export_'.$this->script, array($this, 'export'),10,2);
		add_filter('ecommerce_feeder_import_get_count_'.$this->script, array($this, 'importCount'),10,2);
		add_filter('ecommerce_feeder_export_get_count_'.$this->script, array($this, 'exportCount'),10,2);
	}

	function registerScript($scripts){
		if(isset($scripts['import'][$this->script]) || isset($scripts['import'][$this->script])){
			//Already registered, bail
			return $scripts;
		}else{
			//Which data will support?  
			//Users, Products, Orders
			$scripts['import'][$this->script] = array('name'=>'XML', 'options'=>array('customers'=>'Customer Accounts', 'products'=>'Products','orders'=>'Order History'));
			$scripts['export'][$this->script] = array('name'=>'XML', 'options'=>array('customers'=>'Customer Accounts', 'products'=>'Products','orders'=>'Order History'));
			return $scripts;
		}
	}

        function validate($data){
                $this->logger->debug("XML validation");
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
			<input type='file' name='source'>
			</span>
		</div>
		<?php
	}
	
	function exportForm($presets){
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
		$this->logger->debug("XMLImport file has {$count} lines");
		return $count;
	}

	function import($data_type,$formFields){
		extract($formFields);
		$this->logger->debug("XMLImport: ".print_r($formFields,1));
		$file = $formFields['source'];
		include_once('xml.class.php');
		$xml = new EF_XML_Helper();
		$d = $xml->toArray(file_get_contents($file));
		$na = substr($data_type, 0,-1);
		$d = $d[$na];
		$this->logger->debug("XML Array to import from:".print_r($d,1));

		if(isset($limit) && is_array($limit) && is_numeric($limit['x'])){
			$this->logger->debug("Limit Range had been defined: {$limit['x']} - {$limit['y']}");
			for($i=0;$i<$limit['y'];$i++){
				$dataSet[] = $d[$limit['x'] + $i];
			}
		}elseif(is_numeric($limit)){
			$this->logger->debug("Limit, return row {$limit}");
			$dataSet[] = $d[$limit];
		}else{
			$this->logger->debug("No Limit set, return all");
			$dataSet = &$d;
		}


		//get xml into array
		$this->runDataTypeImport($data_type,$dataSet);
	}

        function export($data_type=false,$dataSets=false){
                //get xml into array
                if($dataSets){
			include_once('xml.class.php');
			$xml = new EF_XML_Helper();
			header('Content-Type: application/octet-stream');
			echo $xml->toXML($dataSets,$data_type);
			exit();
                }else{
                        return false;
                }
        }
}
?>

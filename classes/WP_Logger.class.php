<?php

class WP_Logger{

	var $file;
	var $strdate;
	var $level = array('debug'=>7,'info'=>6,'notice'=>5,'warn'=>4,'critical'=>3,'error'=>2);

	function __construct($file = false){
		if($file){
			$this->file = $file;
		}
		$this->strdate = 'Y/m/d';
		$this->debug('Start Session:'.print_r($_SERVER,1));
	}

	function log($level=debug,$msg=false){
		//If debug is not on, then don't log
		if(defined('ECOMMFEEDER_DEBUG')){
			if(ECOMMFEEDER_DEBUG >= $this->level[$level]){
				return error_log('['.date($this->strdate).'] '.strtoupper($level).' '.$msg."\n",3,$this->file);
			}
		}
	}

	function debug($msg=false){
		return $this->log('debug',$msg);
	}

	function info($msg=false){
		return $this->log('info',$msg);
	}

	function notice($msg=false){
		return $this->log('notice',$msg);
	}

	function warn($msg=false){
		return $this->log('warn',$msg);
	}

	function critical($msg=false){
		return $this->log('critical',$msg);
	}

	function error($msg=false){
		return $this->log('error',$msg);
	}
}
?>

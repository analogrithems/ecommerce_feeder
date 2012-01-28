<?php
/**
 * Package: Wordpress eCommerce Data Feeder
 * WPEC_ecommerce_feeder.class.php
 *
 * @author Analogrithems <Analogrithems@gmail.com>
 * @version 0.1-Dev
 * @license http://www.analogrithems.com/rant/portfolio/project-licensing/
 */


/**
 *
 * This is the base class used by the Wordpress eCommerce Data Feeder in 
 * all of it's class to manipulate data.  It tries to opersate like an MVC ORM
 * it gives the classes basic CRUD ability
 *
 *
 * @package Wordpress eCommerce Datafeeder
 * @subpackage WPEC_ecommerce_feeder
 */


class WPEC_ecommerce_feeder{
	var $logger;
	var $mydb;
	var $filesUploaded;

	public function __construct() {
                global $logger;
                $this->logger = &$logger;
		if(isset($_SESSION['error_msg'])) unset($_SESSION['error_msg']);
		if(isset($_SESSION['status_msg'])) unset($_SESSION['status_msg']);
		$filesUploaded = array();
	}

	/**
	* save($newData, $options)
	* This is a universal funtion to insert or update records in the database of the extending class.
	*
        * The different options available for the options hash array
        * 'primaryKey'=>'user_id'  (Defaults: 'id') this defines what the primary key is for this table
	* 'id'=>21 Used when updating an exsisting record, the id references the column id
	*
        * @param array $newData the data to be saved to the database
        * @param array $options various key=>value references to set the primaryKey for setting the primaryKey
        * @return boolean
	*/
        function save($new=false, $options=array('primaryKey'=>'id')){
		global $wpdb;
		$table = $this->mydb;
		if(isset($options['primaryKey'])) $pkey = $options['primaryKey'];
		else $pkey = 'id';

		if(isset($new['id']) || !empty($new['id']) ){
			$id = $new['id'];
			$cur = $wpdb->get_row("SELECT * FROM {$table} WHERE ".$wpdb->escape($pkey)."=".$wpdb->escape($id),ARRAY_A);
		}
                $format = array();
		//We Should only mess with the columns that are in our table.
		$tbl_desc = $wpdb->get_results("DESC {$table}",ARRAY_A);

		//OK, if this isn't a valid table column, unset it
		$new = $this->cleanInPut($new,$tbl_desc);
		
                if(!isset($cur) || empty($cur)){
                        //treat as new record
			//make sure any new data meets unique rules
			if(!$this->isUnique($table, $tbl_desc, $new)){
				return false;
			}
			$format = $this->getFormat($new, $tbl_desc);
			$this->logger->info("Creating New Record in {$table}:".print_r($new,true));
                        $result = $wpdb->insert($table,$new,$format);
			$this->logger->info("Result for newlly created record in {$table}:".print_r($result,true));
			return $result;
                }else{
                        //treat as update then, find out whats different between the updated data set and the one currently in the db 
                        //and update it
                        $dif = array_diff_assoc($new,$cur);
			if(!isset($dif) || empty($dif)){
				$this->logger->warn("Their is no difference in the update");
				return false;
			}
			$format = $this->getFormat($dif, $tbl_desc);
			$where = array($pkey=>$id);
			$where_format = $this->getFormat($where, $tbl_desc);
			$this->logger->debug("Updating Record in {$table}:Dif".print_r($dif,true)."\nWhere:".print_r($where,true)."\nFormat:".print_r($format,true)."\tWhere Format:".print_r($where_format,true));
			$result = $wpdb->update($table,$dif,$where,$format,$where_format);
			$this->logger->info("Result for updated record in {$table}:".print_r($result,true));
			return $result;
                }
        }

	/**
	* delete($id)
	*
	* Delete record from table
	*
	* @param int $id
	* @return boolean
	*/
	function delete($id,$field='id'){
		global $wpdb;
		$sql = "DELETE FROM ".$this->mydb." WHERE {$field}={$id}";
		$this->logger->debug("WPEC_ecommerce_feeder::delete(".print_r($id,true).",".print_r($field,true).")\n{$sql}");
		return $wpdb->query($sql);
	}

	/**
	* read($data)
	*
	* Generic function to read records from database, you can use the options array to filter what data you want back
	*
	* <code>
	* $data = array('cols'=>'name')
	* </code>
	* or
	* <code>
	* $data = array('cols'=>array('name', 'price', 'id')
	* </code>
	*
	* <code>
	* $data = array('filter'=>"name='bob'")
	* </code>
	* or
	* <code>
	* $data = array('filter'=>array('id=21',"name='bob'")
	* </code>
	*
	* <code>
	* $data = array('sort'=>'ORDER by name')
	* </code>
	*
        * @param array $data conditions
        * @return string
	*/
	function read($data=false){
		global $wpdb;
		if(isset($data['cols']) && is_array($data['cols'])){
			$cols = implode(", ", $data['cols']);
		}elseif(isset($data['cols']) && is_string($data['cols'])){
			$cols = $data['cols'];
		}else{
			$cols = '*';
		}
		
		if(isset($data['filter']) && is_array($data['filter'])){
			$caluse = 'WHERE '.implode(" and ", $data['filter']);
		}elseif(isset($data['filter']) && is_string($data['filter'])){
			$caluse = 'WHERE '.$data['filter'];
		}else{
			$caluse = '';
		}

		if(isset($data['sort'])){
			$sort = $data['sort'];
		}else{
			$sort = '';
		}
		$sql = "SELECT {$cols} FROM {$this->mydb} {$caluse} {$sort}";

		$result = $wpdb->get_results($sql, ARRAY_A);

		$this->logger->info("Result for read on {$this->mydb}:".print_r($result,true));
		return $result;
	}

	/**
	 * getRows($key, $name, $rows)
	 *
	 * Searches through $rows (a multidimensional array usually being a table description) to fine a specific set (IE row) 
	 * that has a key (IE column) that matches a certain value (name)
	 * it's how we look through the table description to find the information about a specific column name, type etc.
	 *
	 * returns $ros on success or false if not found
	 *
	 * 
	 * @param string $key column to look for
	 * @param string $name name to look for
	 * @param mixed  $rows rows to look through
	 * @return mixed
	 */
	function getRow($key, $name, $rows){
		//die("Name: ".print_r($name,true)."\nKey:".print_r($key,true)."\nRows:".print_r($rows,true));
		foreach($rows as $row){
			foreach($row as $col=>$value){
				if($col == $key && $name == $value) return $row;
			}
		}
		return false;
	}

	/**
	 * getformat($data,$desc)
	 *
	 * the Wordpress db class wants to know the data format so it can insert it nicely.  We find out what the format is by just seeing what the
	 * column type is supposed to be.
	 *
	 * returns the format the data types to use in wpdb classes, loosely based off printf libs
	 *
	 * @param mixed $data data to save
	 * @param mixed $desc database description (desc $table)
	 * @return array
	 */
	function getFormat($data, $desc){
		$format = array();
		foreach($data as $col=>$value){
			$col_desc = $this->getRow('Field',$col,$desc);
			if(preg_match('/float/i',$col_desc['Type'])>0 || preg_match('/double/i',$col_desc['Type'])>0 ){
				$format[] = '%f';
			}else if(preg_match('/int/i',$col_desc['Type'])>0){
				$format[] = '%d';
			}else {
				$format[] = '%s';
			}	
		}
		return $format;
	}

	/** 
	 * cleanInPut($data, $desc)
	 *
	 * Simple quick function that just removes anything that shouldn't be in this array for the insert.
	 * It checks the table definition to know what columns should be in this array
	 *
	 * returns cleaned data
	 *
	 * @param mixed $data data to clean
	 * @param mixed $desc database description
	 * @return mixed
	 */
	function cleanInPut($data, $desc){
		$this->logger->info("WPEC_ecommerce_feeder::cleanInPut(".print_r($data,true).','.print_r($desc,true).')');
		foreach($data as $col=>$value){
                        if(! $this->getRow('Field',$col,$desc)){
				unset($data[$col]);
			}
		}
		return $data;
	}
	/**
	 * isUnique($table, $desc, $data)
	 * 
	 * This function helps along in the validation of data when saving NEW records.
	 * It checks the database definition and makes sure that any column that requires 
	 * unique data is uniqe.  If not it sets the session error message and returns false
	 *
	 *
	 * @param string $table to check
	 * @param mixed $desc array of the table description
	 * @param mixed $data data to interrate through and check.
	 * @return boolean 
	 */
	function isUnique($table, $desc, $data){
		global $wpdb;
		if(!isset($table) || !isset($desc) || !isset($data)) return false;
		foreach($data as $col=>$value){
			$col_desc = $this->getRow('Field',$col,$desc);
			if(preg_match('/UNI/i',$col_desc['Key'])>0 || preg_match('/PRI/i',$col_desc['Key'])>0 ){
				if(preg_match('/float/i',$col_desc['Type'])>0 || preg_match('/double/i',$col_desc['Type'])>0 ){
					$d1 = "'$value'";
				}else if(preg_match('/int/i',$col_desc['Type'])>0){
					$d1 = "$value";
				}else {
					$d1 = "'$value'";
				}	
				$sql = "SELECT COUNT({$col}) FROM {$table} WHERE ".$wpdb->escape($col)."=".$wpdb->escape($d1);
				$result = $wpdb->get_var($sql);
				$this->logger->info("isUnique: ".$sql.":".print_r($result,true));
				if(is_null($result) || $result == 0) return true;
				else {
					$this->setError("$value is not Unique in $col");
					return false;
				}
                        }
                }
	}

		
	/**
	 * setError($msg)
	 *
	 * This sets an error message to display in the admin forms.
	 *
	 * @param string $msg msg to set in the session error_msg
	 */
	function setError($msg){
		$this->logger->error("Sent $msg to user");
		if($this->isGood($_SESSION['error_msg'])){
			$_SESSION['error_msg'] .= $msg;
		}else{
			$_SESSION['error_msg'] = $msg;
		}
	}

	/** 
	* read a csv file and return an indexed array. 
	* @param string $cvsfile path to csv file 
	* @param array $fldnames array of fields names. Leave this to null to use the first row values as fields names. 
	* @param string $sep string used as a field separator (default ';') 
	* @param string $protect char used to protect field (generally single or double quote) 
	* @param array  $filters array of regular expression that row must match to be in the returned result. 
	*                        ie: array('fldname'=>'/pcre_regexp/') 
	* @return array 
	*/ 
	function csv2array($csvfile,$fldnames=null,$sep=',',$protect='"',$filters=null,$line=null,$count=null){
	    ini_set('auto_detect_line_endings', true);
	    if(! $csv = file($csvfile) ) 
		return FALSE; 

	    # use the first line as fields names 
	    if( is_null($fldnames) ){ 
		    //if first row is column names make sure we offset line limit if in use
		    if(!is_null($line)) $line++;
		    $fldnames = array_shift($csv); 
		    $fldnames = explode($sep,$fldnames); 
		    $fldnames = array_map('trim',$fldnames); 
		    if($protect){ 
			foreach($fldnames as $k=>$v) 
			    $fldnames[$k] = preg_replace(array("/(?<!\\\\)$protect/","!\\\\($protect)!"),'\\1',$v); 
		    }             
	    }elseif( is_string($fldnames) ){ 
		    $fldnames = explode($sep,$fldnames); 
		    $fldnames = array_map('trim',$fldnames); 
	    } 
	     
	    if(!is_null($line)){
		if(!is_null($count)){
			$tmp = array();
			for($i=0;$i<$count;$i++){
				$tmp[] = $csv[$line + $i];
			}
			$csv = $tmp;
		}else{
			$c[] = $csv[$line];
			$csv = $c;
		}
	    }

	    $i=0; 
	    foreach($csv as $row){ 
		    if($protect){ 
			$row = preg_replace(array("/(?<!\\\\)$protect/","!\\\\($protect)!"),'\\1',$row); 
		    } 
		    $row = explode($sep,trim($row)); 
		     
		    foreach($row as $fldnb=>$fldval) 
			$res[$i][(isset($fldnames[$fldnb])?$fldnames[$fldnb]:$fldnb)] = $fldval; 
		     
		    if( is_array($filters) ){ 
			foreach($filters as $k=>$exp){ 
			    if(! preg_match($exp,$res[$i][$k]) ) 
				unset($res[$i]); 
			} 
		    } 
		    $i++; 
	    } 
	     
	    return $res; 
	}  

	/**
	* take a array and convert it to csv
	* @param array  $dataset with data to be converted, should only be one dimensional, if not it gets flattened group.subgroup
	* @param char $sep field delimiter
	* @param boolean  $eol MS Excel Friendly (default: false)
	* @return string the csv as one big string
	*/
	function array2csv($data=false, $sep = ',', $msformat=false){
		if(!$data) return false;
		if($msformat) $eol = "\r\n";
		else $eol = "\n";

		

		//Get aray keys to use as column headers from first row/element
		$headers = array();
		foreach($data as $row){
			$headers += array_keys($row);
		}
		$csv = implode($sep, $headers).$eol;
		foreach($data as $row){
			$tmp = array();
			foreach($headers as $col){
				if(isset($row[$col])) {
					if(is_string($row[$col])){
						$tmp[] = '"'.$row[$col].'"';
					}else{
						$tmp[] = $row[$col];
					}
				}
				else $tmp[] = 0;
			}
			$csv .= implode($sep,$tmp) . $eol;
		}

		return $csv;
	}

        /**
	* getFile($url)
        * Downloads a file  to the wpsc image file dir
        * if already downloaded, dont do it again.
        *
        */
        function getFile($url){
                //if we already downloaded this image in this session, bail, don't waste bandwith
		$this->logger->debug("WPEC_ecommerce_feeder::getFile(".$url.")");
                if($this->isGood($this->filesUploaded[$url])){
			$this->logger->debug("File Already Downloaded, skipping download");
                        return $this->filesUploaded[$url];
                }else{
                        //get new filename from url filename will include path
                        $new_filename = preg_replace("/[^a-z0-9\.\_]/i",'_', basename($url));
                        $new_filepath = sys_get_temp_dir(). "/" . $new_filename;
                        set_time_limit(0);
                        $fp = fopen ($new_filepath, 'w+');//This is the file where we save the information
                        $ch = curl_init(str_replace(' ', '%20', $url));//Here is the file we are downloading
                        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
                        curl_setopt($ch, CURLOPT_FILE, $fp);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_exec($ch);
                        curl_close($ch);
                        fclose($fp);
			//Check for best method to get mime type
			if(function_exists('finfo_open')){
				$finfo = finfo_open(FILEINFO_MIME);
				$tmp = explode(';',finfo_file($finfo, $new_filepath));
				finfo_close($finfo);
			}else{
				$tmp = explode(';',exec("/usr/bin/file -i -b {$new_filepath}"));
			}
                        $type = $tmp[0];
                        $size = filesize($new_filepath);
                        $file =  array('name'=>$new_filename, 'tmp_name'=>$new_filepath, 'error'=>UPLOAD_ERR_OK, 'type'=>$type, 'size'=>$size);
                        $this->filesUploaded[$url] = $file;
			$this->logger->debug("Downloaded:".print_r($file,true));
                        return $file;
                }
        }

	/**
	* $this->isGood($var)
	* this is an improved version of the isset.  also makes sure that the data in the variable is usable data
	*
	* @param $var reference to variable to check
	* @returns boolean 
	*/
	function isGood(&$var){
		if(isset($var) && !empty($var) && $var != 'NA' && $var != '<null>'){
			return true;
		}else{ return false; }
	}


	function countLines($filepath) {
		ini_set('auto_detect_line_endings', true);
		$handle = fopen( $filepath, "r" );
		$count = 0;
		while( fgets($handle) ) {
			$count++;
		}
		fclose($handle);
		return $count;
	}

	/*
	 * This Function doesn't work the same pre php 5.3
	 *
	 */
	function strstr($haystack, $needle, $before_needle=FALSE) {
		//Find position of $needle or abort
		if(($pos=strpos($haystack,$needle))===FALSE) return FALSE;

		if($before_needle) return substr($haystack,0,$pos+strlen($needle));
		else return substr($haystack,$pos);
	}
}
?>

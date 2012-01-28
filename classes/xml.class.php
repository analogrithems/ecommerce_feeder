<?php
/**
 * xml.class.php
 *
 * @author Analogrithems <Analogrithems@gmail.com>
 * @version 0.1-Dev
 * @license http://www.analogrithems.com/rant/portfolio/project-licensing/
 */


/**
 *
 *
 * @package Wordpress eCommerce Datafeeder
 * @subpackage WPSC_Ecommerce_Feeder_XML
 */


class EF_XML_Helper extends WPEC_ecommerce_feeder{


	/** 
	* The main function for converting to an XML document. 
	* Pass in a multi dimensional array and this recrusively loops through and builds up http://snipplr.com/view/3491/convert-php-array-to-xml-or-simple-xml-object-if-you-wish/an XML document. 
	* 
	* @param array $data 
	* @param string $rootNodeName - what you want the root node to be - defaultsto data. 
	* @param SimpleXMLElement $xml - should only be used recursively 
	* @return string
	*/ 
	public function toXML( $data, $rootNodeName = 'ResultSet', &$xml=null ) {

	    // turn off compatibility mode as simple xml throws a wobbly if you don't.
	    if ( ini_get('zend.ze1_compatibility_mode') == 1 ) ini_set ( 'zend.ze1_compatibility_mode', 0 );
	    if ( is_null( $xml ) ){ 	
		$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
	    }
	    // loop through the data passed in.
	    foreach( $data as $key => $value ) {

		// no numeric keys in our xml please!
		if ( is_numeric( $key ) ) {
		    $numeric = 1;
		    $key = substr($rootNodeName, 0,-1);
		}

		// delete any char not allowed in XML element names
		$key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

		// if there is another array found recursively call this function
		if ( is_array( $value ) ) {

		    if ( $this->is_assoc( $value ) || $numeric ) {

			// older SimplXMLElement Libraries do not have the addChild Method
			if (method_exists('SimpleXMLElement','addChild'))
			{
			    $node = $xml->addChild( $key );
			}
			else
			{// alternative / dirty method for adding a child
			 $domchild = new DOMElement($key,$value);
			 $dom= new DOMDocument;
			 $dom = dom_import_simplexml($xml);
			 $dom->appendChild($domchild);
			 $xml = simplexml_import_dom($dom);
			 $node = $xml;
			}

		    }else{
			$node =$xml;
		    }

		    // recrusive call.
		    if ( $numeric ) $key = 'anon';
		    $this->toXml( $value, $key, $node );
		} else {

			// older SimplXMLElement Libraries do not have the addChild Method
			if (method_exists('SimpleXMLElement','addChild'))
			{
			    $xml->addChild( $key,htmlentities($value,ENT_QUOTES) );
			}
			else
			{   // alternative / dirty method for adding a child
			     $domchild = new DOMElement($key,$value);
			     $dom= new DOMDocument;
			     $dom = dom_import_simplexml($xml);
			     $dom->appendChild($domchild);
			     $xml = simplexml_import_dom($dom);
			}
		}
	    }

	    // pass back as XML
	    //return $xml->asXML();

		// if you want the XML to be formatted, use the below instead to return the XML
		$doc = new DOMDocument('1.0');
		$doc->preserveWhiteSpace = false;
		$doc->loadXML( $xml->asXML() );
		$doc->formatOutput = true;
		return $doc->saveXML();
	}


	/**
	* Convert an XML document to a multi dimensional array
	* Pass in an XML document (or SimpleXMLElement object) and this recrusively loops through and builds a representative array
	*
	* @param string $xml - XML document - can optionally be a SimpleXMLElement object
	* @return array ARRAY
	*/
	public static function toArray( $xml ) {
		if ( is_string( $xml ) ) $xml = new SimpleXMLElement( $xml );
		$children = $xml->children();
		if ( !$children ) return (string) $xml;
		$arr = array();
		foreach ( $children as $key => $node ) {
		    $node = EF_XML_Helper::toArray( $node );

		    if(is_string($node)) $node = html_entity_decode($node,ENT_QUOTES);

		    // support for 'anon' non-associative arrays
		    if ( $key == 'anon' ) $key = count( $arr );

		    // if the node is already set, put it into an array
		    if ( isset( $arr[$key] ) ) {
			if ( !is_array( $arr[$key] ) || !isset($arr[$key][0]) || $arr[$key][0] == null ) $arr[$key] = array( $arr[$key] );
			$arr[$key][] = $node;
		    } else {
			$arr[$key] = $node;
		    }
		}
		global $logger;
		return $arr;
	}



	/**
	 * function is_assoc( $array )
	 * 
	 * determine if a variable is an associative array
	 * @param mixed $array array to check if is an associated array
	 * @return boolean
	 */
	public static function is_assoc( $array ) {
	    return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
	}
}

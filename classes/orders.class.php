<?php
/**
 * orders.class.php
 *
 * @author Analogrithems <Analogrithems@gmail.com>
 * @version 0.1-Dev
 * @license http://www.analogrithems.com/rant/portfolio/project-licensing/
 */


/**
 *
 * Simple class to aid in working with orders.  Weather you are trying to display the order to the user, 
 * email the order/ship confirmation or displaying order History this class will save you lots of time.
 *
 * @package Wordpress eCommerce Datafeeder
 * @subpackage WPEC_Orders
 */

class WPEC_Orders extends WPEC_ecommerce_feeder{

	var $orders;
	var $purchase_logs;

	function __construct(){
		parent::__construct();
	}

	function updateOrders($orders=false){
		global $wpdb;
		if($orders == false) return false;
		$plFields = array('totalprice','statusno','sessionid','transactid','authcode','processed','user_ID','date','gateway','billing_country',
				'shipping_country','base_shipping','email_sent','stock_adjusted','discount_value','discount_data','track_id','billing_region',
				'shipping_region','find_us','engravetext','shipping_method','shipping_option','affiliate_id','plugin_version','notes',
				'wpec_taxes_total','wpec_taxes_rate');
				
		$ciFields = array('prodid','name','price','pnp','tax_charged','gst','quantity','donation','no_shipping','custom_message','files','meta');
		$formResults = $wpdb->get_results("SELECT id,unique_name FROM ".WPSC_TABLE_CHECKOUT_FORMS." WHERE unique_name!=''",ARRAY_A);
		foreach($formResults as $f){
			$formFields[$f['unique_name']] = $f['id'];
		}
		unset($formResults);
		$results = array('added'=>0,'updated'=>0);
		foreach($orders as $order){
			$this->logger->info('Inserting Order:'.print_r($order,1));
			foreach($plFields as $field){	
				if($this->isGood($order[$field])) $rec[$field] = $order[$field];
			}
			$wpdb->insert(WPSC_TABLE_PURCHASE_LOGS,$rec);
			$purchase_id = $wpdb->insert_id;
			if($this->isGood($purchase_id)){
				$results['added']++;
			}

			//Add Items to order
			if(isset($order['items'])){
				foreach($order['items'] as $item){
					foreach($ciFields as $field){
						if($this->isGood($item[$field])) $ritm[$field] = $item[$field];
					}
					if(isset($purchase_id)) $ritm['purchaseid'] = $purchase_id;
					$wpdb->insert(WPSC_TABLE_CART_CONTENTS,$ritm);
				}
			}
			foreach($formFields as $sf=>$id){
				$this->logger->info("checking if order has for {$sf}:");
				if(isset($order[$sf])){
					$wpdb->insert(WPSC_TABLE_SUBMITED_FORM_DATA,array('log_id'=>$purchase_id,'form_id'=>$id,'value'=>$order[$sf]));
				}
			}
		}
		return $results;
	}

	function exportOrders(){
		$this->loadOrders(array('any_status'=>true));
		//$this->logger->debug("WPEC_Orders::exportOrders exporting:".print_r($this->orders,true));
		return $this->orders;
	}

	/** 
	 * loadOrders([mixed $options = false])
	 *
	 * This function recursively grabs all the orders and their contents
	 * With this one function you can get the order info, cart contents,
	 * customer info & product info for the items ordered
	 *
	 * The different options
	 * 'any_status'  Show Orders that are in any stage of ther order process.  Default is to only show open orders I.E. Order Status 2-5
	 * <code> WPEC_Orders::loadOrders(array('any_status'=>true));</code>
	 * 'status' if you only want to see a certain status level set it here.  Note can only use status or any_status, not both
	 * <code> WPEC_Orders::loadOrders(array('status'=>2));</code>
	 * 'orderby' if you want to order the results go ahead
	 * <code> WPEC__Orders::loadOrders(array('orderby'=>'date'));</code>
	 * 'filter' lets you add a little extra refinement, perfect if you only want a certain users logs
	 * <code> WPEC__Orders::loadOrders(array('filter'=>'user_ID=3'));</code>
	 * @global object $wpdb for speaking that sweet wp info
	 * @param mixed $options
	 * @return mixed 
	 *
	 */
	function loadOrders($options = false){
		global $wpdb;
		if($options != false) extract($options);
		$order_status = (isset($options['any_status']) && $options['any_status'] == true) ?  'WHERE processed BETWEEN 0 AND 6' : 'WHERE processed BETWEEN 2 AND 5';
		if(!isset($options['any_status']) && isset($options['status'])){
			$order_status = 'WHERE statusno='.addslashes($options['status']);
		}
		if(isset($filter)) $filter = " AND ".addslashes($filter);
		else $filter = '';
		if(isset($orderby)) $orderby = " ORDER BY ".addslashes($filter);
		else $orderby = '';
		

                $purchase_logs = "SELECT *  FROM ".WPSC_TABLE_PURCHASE_LOGS.' '.$order_status.$filter.$orderby;
                $this->purchase_logs = $wpdb->get_results( $purchase_logs, ARRAY_A );
		
		foreach($this->purchase_logs as $pl){
			$i = $pl['id'];
			$this->orders[$i] = $pl;

			//Pack in the cart contents so we know what was orderd and at what price
			$cart_contents_sql = "SELECT * FROM ".WPSC_TABLE_CART_CONTENTS." WHERE purchaseid='".$pl['id']."'";
			$this->orders[$i]['items'] = $wpdb->get_results($cart_contents_sql,ARRAY_A);

			//Now Make sure to grab the customer info
			$submitted_form_sql = "SELECT * FROM ".WPSC_TABLE_SUBMITED_FORM_DATA." WHERE log_id='".$pl['id']."'";
			$submitted_form = $wpdb->get_results($submitted_form_sql,ARRAY_A);
			foreach($submitted_form as $field){ $user_form[$field['form_id']] = $field['value']; }
			unset($submitted_form);
			unset($field);

			//Silly Hack to get the field names for the previously fetched customer_info
			$forms_sql = "SELECT * FROM ".WPSC_TABLE_CHECKOUT_FORMS." checkout_f WHERE active=1 order by checkout_f.id";
			$forms = $wpdb->get_results($forms_sql,ARRAY_A);
			foreach($forms as $field){
				if(!$this->isGood($field['unique_name'])) continue;
				if(isset($user_form[$field['id']])) $value = $user_form[$field['id']];
				else $value = '';
				$this->orders[$i][$field['unique_name']] = $value;
			}
			
		}
		return $this->orders;
	}

	/**
	 * getOpenOrdersViaJSON([mixed $options = false])
	 * 
	 * This is really just a simple wrapper to the loadOrders function that then places the data in JSON format
	 * @param mixed $options
	 * @return mixed return the open orders in JSON format
	 */
	function getOpenOrdersViaJSON($options = false){
		
		$orders = $this->loadOrders($options);
		return json_encode($formatted_orders);
	}
}

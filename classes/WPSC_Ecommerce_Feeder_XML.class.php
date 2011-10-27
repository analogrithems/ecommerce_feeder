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
//class WPSC_Ecommerce_Feeder_XML extends wp_xmlrpc_server{
class WPSC_Ecommerce_Feeder_XML{

	var $blog_id;
	var $username;
	var $password;

	function registerMethods($methods){
	
		$methods['wpsc.getOrders'] 	= array($this,'getOrders'); //get all orders
		$methods['wpsc.getOrdersSince']	= array($this,'getOrdersSince'); //get all orders since a specific time requires date in mysql date time format
		$methods['wpsc.getOrdersByStatus'] 	= array($this,'getOrdersByStatus'); //get orders by status
		$methods['wpsc.getOrder'] 	= array($this,'getOrder'); //get specific order, requires orderId
		$methods['wpsc.updateOrder'] 	= array($this,'updateOrder'); //update the status of an order.  requires orderId & statusId be set
		$methods['wpsc.getUsers'] 	= array($this,'getUsers'); //returns all users
		$methods['wpsc.getUserByEmail'] 	= array($this,'getUserbyEmail'); //get specific user, requires  email
		$methods['wpsc.getUserByLogin'] 	= array($this,'getUserByLogin'); //get specific user, requires username
		$methods['wpsc.getProducts'] 	= array($this,'getProducts'); //get all products, careful, this can take a long time
		$methods['wpsc.getProduct']	= array($this,'getProduct'); //get specific order by id
		$methods['wpsc.getProductVariants'] = array($this,'getProductVariants'); // return all the variants of a product
		$methods['wpsc.version']	= array($this,'efVersion'); //return version and test our plugins are loaded
		return $methods;
	}

	/** 
	* efVersion xml-rpc test function and return the version number
	*/
	function efVersion(){
		return(ECOMMERCE_FEEDER);
	}

	/**
	* getOrders return all orders in the system
	*
	* @param int blogid
	* @param string username
	* @param string password
	*/
        function getOrders($args) {
		$this->adminInit($args);
                do_action('wpsc_xmlrpc_call', 'wpsc.getOrders');

		include_once('orders.class.php');
		$order = new WPEC_Orders();
		$orders = $order->exportOrders();
		return($orders);
        }

	/**
	* getOrderSince get orders since a specific point in time
	*
	* @param int blogid
	* @param string username
	* @param string password
	* @param date A specific date get order since.
	*/
	function getOrdersSince($args){
		$this->adminInit($args);
		$date					= strtotime($args[3]);
                do_action('wpsc_xmlrpc_call', 'wpsc.getOrdersSince');

		include_once('orders.class.php');
		$order = new WPEC_Orders();
		$orders = $order->loadOrders(array('filter'=>'date > '.$date));
		return($orders);
	}

        /**
        * getOrder a specific order by order id number
        *
	* @param int blogid
	* @param string username
	* @param string password
	* @param int order ID Number
        */
        function getOrder($args){
		$this->adminInit($args);
                $orderid                                = $args[3];
                do_action('wpsc_xmlrpc_call', 'wpsc.getOrder');

                include_once('orders.class.php');
                $order = new WPEC_Orders();
                $orders = $order->loadOrders(array('filter'=>'id='.$orderid));
                return($orders);
        }

        /**
        * getOrdersByStatus Get the order by the status.  This is usefull to get all orders in a specific state
	* See http://getshopped.org/resources/docs/building-your-store/sales/ for an explanation of what each level means
	*
	* The default order status levels are
	* 1 = Incomplete Sale
	* 2 = Order Received
	* 3 = Accepted Payment
	* 4 = Job Dispatched
	* 5 = Closed Order
	* 6 = Payment Declined
        *
	* @param int blogid
	* @param string username
	* @param string password
	* @param int statusNumber
        */
        function getOrdersByStatus($args){
		$this->adminInit($args);
                $status                                 = $args[3];
                do_action('wpsc_xmlrpc_call', 'wpsc.getOrdersByStatus');

                include_once('orders.class.php');
                $order = new WPEC_Orders();
                $orders = $order->loadOrders(array('filter'=>'statusno='.$status));
                return($orders);
        }

	/**
	* updateOrder
	*
	* @param int blogid
	* @param string username
	* @param string password
	* @param int statusNumber
	*/
	function updateOrder($args){
		$this->adminInit($args);
                $status                                 = $args[3];
                do_action('wpsc_xmlrpc_call', 'wpsc.getOrdersByStatus');

                include_once('orders.class.php');
                $order = new WPEC_Orders();
                $orders = $order->loadOrders(array('filter'=>'statusno='.$status));
                return($orders);
	}

	/**
	* wpsc.getUsers export all the user information.
	*
	* @param int blogid
	* @param string username
	* @param string password
	*/
	function getUsers($args){
		$this->adminInit($args);
                do_action('wpsc_xmlrpc_call', 'wpsc.getUsers');

                include_once('orders.class.php');
                $user = new WPEC_Users();
                $users = $user->exportUsers()
                return($users);
	}

        /**
        * wpsc.getUserByEmail export all the user information.
        *
        * @param int blogid
        * @param string username
        * @param string password
	* @param string email the users information you want to return
        */
        function getUserByEmail($args){
                $this->adminInit($args);
		$email		=	$args[3];
                do_action('wpsc_xmlrpc_call', 'wpsc.getUserByEmail');

                include_once('orders.class.php');
                $user = new WPEC_Users();
                $users = $user->loadUser(array('filter'=>"user_email='{$email}'"));
                return($users);
        }
	
        /**
        * wpsc.getUserByLogin export all the user information.
        *
        * @param int blogid
        * @param string username
        * @param string password
        * @param string email the users information you want to return
        */
        function getUserByLogin($args){
                $this->adminInit($args);
                $login          =       $args[3];
                do_action('wpsc_xmlrpc_call', 'wpsc.getUserByLogin');

                include_once('orders.class.php');
                $user = new WPEC_Users();
                $users = $user->loadUser(array('filter'=>"user_login='{$login}'"));
                return($users);
        }

        /**
        * getProducts
        *
        * @param int blogid
        * @param string username
        * @param string password
        */
        function getProducts($args){
                $this->adminInit($args);
                do_action('wpsc_xmlrpc_call', 'wpsc.getProducts');

                include_once('products.class.php');
		$product = WPEC_Products();
                $products = $product->exportProducts();
                return($products);
	}

        /**
        * getProduct
        *
        * @param int blogid
        * @param string username
        * @param string password
        */
        function getProducts($args){
                $this->adminInit($args);
                do_action('wpsc_xmlrpc_call', 'wpsc.getProducts');

                include_once('products.class.php');
                $product = WPEC_Products();
                $products = $product->exportProducts();
                return($products);
        }

        /**
         * Sanitize string or array of strings for database.
         *
         * @since 1.5.2
         *
         * @param string|array $array Sanitize single string or array of strings.
         * @return string|array Type matches $array and sanitized for the database.
         */
        function escape(&$array) {
                global $wpdb;

                if (!is_array($array)) {
                        return($wpdb->escape($array));
                } else {
                        foreach ( (array) $array as $k => $v ) {
                                if ( is_array($v) ) {
                                        $this->escape($array[$k]);
                                } else if ( is_object($v) ) {
                                        //skip
                                } else {
                                        $array[$k] = $wpdb->escape($v);
                                }
                        }
                }
        }

        /**
         * Check user's credentials.
         *
         * @since 1.5.0
         *
         * @param string $user_login User's username.
         * @param string $user_pass User's password.
         * @return bool Whether authentication passed.
         * @deprecated use wp_xmlrpc_server::login
         * @see wp_xmlrpc_server::login
         */
        function login_pass_ok($user_login, $user_pass) {
                if ( !get_option( 'enable_xmlrpc' ) ) {
                        $this->error = new IXR_Error( 405, sprintf( __( 'XML-RPC services are disabled on this site.  An admin user can enable them at %s'),  admin_url('options-writing.php') ) );
                        return false;
                }

                if (!user_pass_ok($user_login, $user_pass)) {
                        $this->error = new IXR_Error(403, __('Bad login/pass combination.'));
                        return false;
                }
                return true;
        }

        /**
         * Log user in.
         *
         * @since 2.8
         *
         * @param string $username User's username.
         * @param string $password User's password.
         * @return mixed WP_User object if authentication passed, false otherwise
         */
        function login($username, $password) {
                if ( !get_option( 'enable_xmlrpc' ) ) {
                        $this->error = new IXR_Error( 405, sprintf( __( 'XML-RPC services are disabled on this site.  An admin user can enable them at %s'),  admin_url('options-writing.php') ) );
                        return false;
                }

                $user = wp_authenticate($username, $password);

                if (is_wp_error($user)) {
                        $this->error = new IXR_Error(403, __('Bad login/pass combination.'));
                        return false;
                }

                wp_set_current_user( $user->ID );
                return $user;
        }

	/**
	* adminInit
	*
	* @param mixed args from rpc-xml
	*/
	function adminInit($args){
                $this->escape($args);

                $this->blog_id                                = (int) $args[0];
                $this->username                               = $args[1];
                $this->password                               = $args[2];

                if ( !$user = $this->login($this->username, $this->password) )
                        return $this->error;

                if ( !current_user_can( 'administrator' ) )
                        return new IXR_Error( 403, __( 'You must be an administrator to pull these orders.' ) );

	}

}
?>

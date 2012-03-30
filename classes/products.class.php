<?php
/**
 * products.class.php
 *
 * @author Analogrithems <Analogrithems@gmail.com>
 * @version 0.1-Dev
 * @license http://www.analogrithems.com/rant/portfolio/project-licensing/
 */


/**
 *
 * This is a class to work with the Wordpress products.  It gives an easy to use interface to import and export uer records 
 *
 *
 *
 * @package Wordpress eCommerce Datafeeder
 * @subpackage WPEC_Products
 */


class WPEC_Products extends WPEC_ecommerce_feeder{

	var $products;
	
	var $itemsAdded;
	var $wpsc_options;
	var $post_type;

	function __construct(){
		$this->post_type = 'wpsc-product';
		parent::__construct();
		$this->wpsc_options = get_option('wpsc_options');
		$this->itemsAdded = array();
	}

	function updateProduct(&$products){
		wp_reset_query();
		global $wpdb, $productUpdates, $variantUpdates;
		$productUpdates = 0;
		$variantUpdates = 0;
		set_time_limit(0); 

		$r = 0;
		foreach($products as $row){
			$r++;
			if($this->isGood($row)){

				foreach($row as $key=>$val){
					$key = mb_convert_encoding($key, 'UTF-8');
					$row[$key] = mb_convert_encoding($val, 'UTF-8');
				}

				//search products by style, don't waste time with name as it can be to hard to keep exact match
				$this->logger->info("Row #{$r}");
				if($this->isGood($row['style'])) $product = query_posts( array( 'post_type' => 'wpsc-product', 'meta_key'=>'style', 'meta_value'=>$row['style'] ) );
				elseif($this->isGood($row['name'])) $product = query_posts( array( 'post_type' => 'wpsc-product', 'post_title'=>$row['name']));
				else $product = false;

				//Meta the meta info ready
				$row['meta']['_wpsc_price'] = abs((float)str_replace( ',','',$this->isGood($row['price']) ? $row['price'] : '' ));

				$row['meta']['_wpsc_product_metadata']['display_weight_as'] = 'pound';
				$row['meta']['_wpsc_product_metadata']['weight_unit'] = 'pound';
				//This sets product dimensions for shipping calculators
				if($this->isGood($row['width']) && is_numeric($row['width']) ){
					$row['meta']['_wpsc_product_metadata']['dimensions']['width'] = $row['width'];
					unset($row['width']);
				}
				if($this->isGood($row['length']) && is_numeric($row['length']) ){
					$row['meta']['_wpsc_product_metadata']['dimensions']['length'] = $row['length'];
					unset($row['length']);
				}
				if($this->isGood($row['height']) && is_numeric($row['height']) ){
					$row['meta']['_wpsc_product_metadata']['dimensions']['height'] = $row['height'];
					unset($row['height']);
				}

				//This is for setting the weight
				if($this->isGood($row['weight']) && is_numeric($row['weight']) ){
					$row['meta']['_wpsc_product_metadata']['weight'] = $row['weight'];
					unset($row['weight']);
				}elseif($this->isGood($row['weight']) && $weight = $this->strstr($row['weight'], 'kilogram',true)){
					$row['meta']['_wpsc_product_metadata']['display_weight_as'] = 'kilogram';
					$row['meta']['_wpsc_product_metadata']['weight_unit'] = 'kilogram';
					$row['meta']['_wpsc_product_metadata']['weight'] = $weight;
					unset($row['weight']);
				}elseif($this->isGood($row['weight']) && $weight = $this->strstr($row['weight'], 'gram',true)){
					$row['meta']['_wpsc_product_metadata']['display_weight_as'] = 'gram';
					$row['meta']['_wpsc_product_metadata']['weight_unit'] = 'gram';
					$row['meta']['_wpsc_product_metadata']['weight'] = $weight;
					unset($row['weight']);
				}elseif($this->isGood($row['weight']) && $weight = $this->strstr($row['weight'], 'once',true)){
					$row['meta']['_wpsc_product_metadata']['display_weight_as'] = 'once';
					$row['meta']['_wpsc_product_metadata']['weight_unit'] = 'once';
					$row['meta']['_wpsc_product_metadata']['weight'] = $weight;
					unset($row['weight']);
				}elseif($this->isGood($row['weight']) && $weight = $this->strstr($row['weight'], 'pound',true)){
					$row['meta']['_wpsc_product_metadata']['display_weight_as'] = 'pound';
					$row['meta']['_wpsc_product_metadata']['weight_unit'] = 'pond';
					$row['meta']['_wpsc_product_metadata']['weight'] = $weight;
					unset($row['weight']);
				}else{
					$row['meta']['_wpsc_product_metadata']['weight'] = 1;
				}


				if($this->isGood($row['quantity']) && is_numeric($row['quantity']) ){
					$row['meta']['_wpsc_stock']=$row['quantity'];
					$row['meta']['quantity_limited']=1;
					$row['meta']['_wpsc_limited_stock']=1;
					$row['quantity_limited']=1;
					$row['_wpsc_limited_stock']=1;
					unset($row['quantity']);
				}else{
					$row['meta']['_wpsc_stock']='';
				}

				//Include a product sku
				if($this->isGood($row['sku'])){
					$row['meta']['_wpsc_sku'] = $row['sku'];
					unset($row['sku']);
				}


				//Setup The custom meta info
				foreach($row as $k=>$v){
					if( is_string($k) && $this->isGood($v) && (preg_match('/^meta_/',$k) > 0) ){	
						$row['new_custom_meta']['name'][] =  substr($k,5);
						$row['new_custom_meta']['value'][] = $v;
					}
				}
				

				if($this->isGood($row['active']) && $row['active'] == 1) $row['post_status'] = 'publish';
				if($this->isGood($product)){
					$product_id = $product[0]->ID;
					//looks like either an update or variant adjustment
					$row['product_id'] = $product[0]->ID;
					$row['ID'] = $product[0]->ID;
					//if the product is no longer active, unpublish it.
					if($this->isGood($row['active']) && $row['active'] == 0 ) $row['post_status'] = 'draft';
					if($this->isGood($row['image'])){
						$images = explode(' | ', $row['image']);
						foreach($images as $image){
							$image_id = $this->image_handle_upload($image,$row['product_id']);
						}
					}
					if(isset($_SESSION['status_msg'])) $_SESSION['status_msg'] .= __("Updating Product:",'ecommerce_feeder').$row['name'].'. ';
					else $_SESSION['status_msg'] = __("Updating Product:",'ecommerce_feeder').$row['name'].'. ';
					$result = $this->updateVariant($row['product_id'], $row);
					$variantUpdates++;
				}
				else{
					//Add inital product
					$row['new_custom_meta']['name'][] = 'style';
					$row['new_custom_meta']['value'][] = $row['style'];
					if(!isset($row['description']) || empty($row['description']) ){
						$row['description'] = ' ';
					}
					$product_id = wpsc_insert_product($row);
					$productUpdates++;
					if($this->isGood($row['image'])){
						$images = explode(' | ', $row['image']);
						foreach($images as $image){
							$image_id = $this->image_handle_upload($image,$product_id);
						}
					}
					if(isset($_SESSION['status_msg'])) $_SESSION['status_msg'] .= __("Adding Product:",'ecommerce_feeder').$row['name'].'. ';
					else $_SESSION['status_msg'] = __("Adding Product:",'ecommerce_feeder').$row['name'].'. ';
					$result = $this->updateVariant($product_id,$row);
				}
				//Products tags
				if($this->isGood($row['tags'])){
					//Makes the tags a santizied array product_tag
					$row['tags'] = explode('|',preg_replace('/\s+\|\s+/','|',$row['tags']));
					wp_set_object_terms($product_id,$row['tags'],'product_tag');
				}
				//This sets the category, if category doesn't exists it makes it.
				if($this->isGood($row['category'])){
					$categories = explode('|',preg_replace('/\s+\|\s+/','|',$row['category']));
					foreach($categories as $cat){
						$categoryPath = explode('->',$cat);
						if(count($categoryPath) > 1){
							$pid = $this->getVariant($categoryPath[0],'wpsc_product_category');
							for($i=1;count($categoryPath) > $i;$i++){
								$pid = $this->getVariant($categoryPath[$i],'wpsc_product_category',$pid);
							}
							$row['category'] = (int)$pid;
						}else{
							$row['category'] = (int)$this->getVariant($categoryPath[0],'wpsc_product_category');
						}
						$this->logger->info("Adding {$row['category']} to productid {$product_id}");
						wp_set_object_terms($product_id,$row['category'],'wpsc_product_category',true);
					}
				}
				//Update the Meta
				foreach ($row['meta'] as $meta_key => $meta_value ){
					if($meta_key =='_wpsc_sku') $this->logger->debug("Trying to add a sku to {$product_id} of {$meta_value}");
					if ($meta_key == "_wpsc_product_metadata") {
						update_post_meta($product_id, $meta_key, $meta_value);
					} else {
						update_post_meta($product_id, $meta_key, $meta_value);
					}
				}
			}
				
		}

	}



	/*
	* Need to get current Varaiants before adding a new one, otherwise it may overwrite
	*
	*/
	function updateVariant($product_id, $data){
		$hasVariant=false;
		foreach($data as $k=>$v){
			if( is_string($k) && $this->isGood($v) && (preg_match('/^variant_/',$k) > 0) ){
				$hasVariant=true;
				$varName = substr($k,8);
				$_SESSION['status_msg'] .= __('With Variant:','ecommerce_feeder').$varName.'='.$v.'.';
				$var_pid = $this->getVariant($varName,'wpsc-variation');
				$var_id = $this->getVariant($v,'wpsc-variation',$var_pid);
				$variantCombo[$var_pid][$var_id] = 1;
			}
		}
		//Find out what the current combos are and remove them if we don't need them anymore.
		//Prepare variant info (If any)
		if($this->isGood($variantCombo) && $hasVariant){
			$this->itemsAdded[$data['style']]['edit_var_val'] = $variantCombo;
			$data['edit_var_val'] = $variantCombo;
			return $this->data_feed_variants_manage($product_id, $data);
		}elseif(isset($data['product_id'])){
			$product_id = $data['product_id'];
			$_product_meta = get_post_custom($product_id);
			if($this->isGood($data['meta'])){
				if($this->isGood($data['meta']['_wpsc_price']) && $_product_meta['_wpsc_price'] != $data['meta']['_wpsc_price']){
					$_product_meta['_wpsc_price'] = array( $data['meta']['_wpsc_price']);
				}
				if($this->isGood($data['meta']['_wpsc_stock']) && $this->isGood($_product_meta['_wpsc_stock']) && $_product_meta['_wpsc_stock'] != $data['meta']['_wpsc_stock']){
					$_product_meta['_wpsc_stock'] = array( $data['meta']['_wpsc_stock']);
				}
				if($this->isGood($data['meta']['_wpsc_product_metadata']['weight'])  ){
					$tempMeta = unserialize($_product_meta['_wpsc_product_metadata'][0]);
					$tempMeta['weight'] = $data['meta']['_wpsc_product_metadata']['weight'];
					$_product_meta['_wpsc_product_metadata'] = array( serialize($tempMeta));
				}
			}
			foreach ($_product_meta as $meta_key => $meta_value ){
				if ($meta_key == "_wpsc_product_metadata") {
					update_post_meta($product_id, $meta_key, unserialize($meta_value[0]));
				} else {
					update_post_meta($product_id, $meta_key, $meta_value[0]);
				}
			}
			return wp_update_post($data);
		}

	}

	/* This function gets the term_id of the named term in the named taxonomy.
	*  if the the term n the taxonomy doesn't exsists it is added.  
	*  It returns the term_id of the given term
	*/
	function getVariant($term, $taxonomy, $parent_id = null){
		$this->logger->debug("Checking {$taxonomy} for term:".print_r($term,1)." parent:".print_r($parent_id,1));
		if(($_term = term_exists($term, $taxonomy, $parent_id)) == 0){
			//Parent Doesn't Exsits, add it
			$slug = preg_replace('/[\`\~\!\@\#\$\%\^\*\(\)\; \,\.\'\/\-]/i','_',$term);
			$newTerm = wp_insert_term( $term, $taxonomy, array('slug'=>$slug, 'parent'=>$parent_id));
			if(get_class($newTerm) == 'WP_Error'){
				$this->logger->error('getVariant import error:'.print_r($newTerm,1),'error');
				return false;
			}else{
				return $newTerm['term_id'];
			}
		}else{
			return $_term['term_id'];
		}
	}


	/*
	* Custom Array Merge
	*
	*/
	function variant_combine($one, $two){

		foreach($two as $key => $value){
			if(isset($one[$key]) && is_array($value)){
				$lkey = key($value);
				$lvalue = $value[$lkey];
				$one[$key][$lkey] = $lvalue;
			}else{
				$one[$key] = $value;
			}
		}
		return $one;
	}

	/**
	 * wpsc_edit_product_variations function.
	 * this is the function to make child products using variations 
	 *
	 * @access public
	 * @param mixed $product_id
	 * @param mixed $post_data
	 * @return void
	 */
	function data_feed_variants_manage($product_id, $post_data) {
		global $wpdb, $user_ID;
		$variations = array();
		if (!isset($post_data['edit_var_val'])) $post_data['edit_var_val'] = '';
		
		$variations = (array)$post_data['edit_var_val'];
		// bail if the array is empty
		if(count($variations) < 1) {
			return false;
		}
		
		// Generate the arrays for variation sets, values and combinations
		$wpsc_combinator = new wpsc_variation_combinator($variations);
		// Retrieve the array containing the variation set IDs
		$variation_sets = $wpsc_combinator->return_variation_sets();
		
		// Retrieve the array containing the combinations of each variation set to be associated with this product.
		$variation_values = $wpsc_combinator->return_variation_values();
		
		// Retrieve the array containing the combinations of each variation set to be associated with this product.
		$combinations = $wpsc_combinator->return_combinations();
		
		$variation_sets_and_values = array_merge($variation_sets, $variation_values);
		wp_set_object_terms($product_id, $variation_sets_and_values, 'wpsc-variation', true);
		
		$child_product_template = array(
			'post_author' => $user_ID,
			'post_content' => (isset($post_data['description'])) ? $post_data['description'] : '',
			'post_excerpt' => (isset($post_data['additional_description'])) ? $post_data['additional_description'] : '',
			'post_title' => (isset($post_data['name'])) ? $post_data['name'] : '',
			'post_status' => 'inherit',
			'post_type' => 'wpsc-product',
			'post_name' => (isset($post_data['name'])) ? sanitize_title($post_data['name']) : '',
			'post_parent' => $product_id
		);
					
		$child_product_meta = get_post_custom($product_id);
		if($this->isGood($post_data['meta'])){
			if($this->isGood($post_data['meta']['_wpsc_price']) && $child_product_meta['_wpsc_price'] != $post_data['meta']['_wpsc_price']){
				$child_product_meta['_wpsc_price'] = array( $post_data['meta']['_wpsc_price']);		
			}
			if($this->isGood($post_data['meta']['_wpsc_stock']) && $this->isGood($child_product_meta['_wpsc_stock']) && $child_product_meta['_wpsc_stock'] != $post_data['meta']['_wpsc_stock']){
				$child_product_meta['_wpsc_stock'] = array( $post_data['meta']['_wpsc_stock']);
			}
			if($this->isGood($post_data['meta']['_wpsc_product_metadata']['weight'])  ){
				$tempMeta = unserialize($child_product_meta['_wpsc_product_metadata'][0]);
				$tempMeta['weight'] = $post_data['meta']['_wpsc_product_metadata']['weight'];
				$child_product_meta['_wpsc_product_metadata'] = array( serialize($tempMeta));
			}
		}
		// here we loop through the combinations, get the term data and generate custom product names
		$child_ids = array();

		foreach($combinations as $combination) {
			$term_names = array();
			$term_ids = array();
			$term_slugs = array();
			$product_values = $child_product_template;

			$combination_terms = get_terms('wpsc-variation', array(
				'hide_empty' => 0,
				'include' => implode(",", $combination),
				'orderby' => 'parent',
			));
			foreach($combination_terms as $term) {
				$term_ids[] = $term->term_id;
				$term_slugs[] = $term->slug;
				$term_names[] = $term->name;
			}

			$product_values['post_title'] .= " (".implode(", ", $term_names).")";
			$product_values['post_name'] = sanitize_title($product_values['post_title']);
			
			/*
			* wp_set_object_terms($product_id, $variation_sets_and_values, 'wpsc-variation');
			* Once we create a new variant, store it's variantion sets and values to a global
			* array that keeps track by style, at the end of the total update, send the list to
			* wp_set_object_terms, this should will other variantions.
			*/
			if(!isset($this->itemsAdded[$product_id])) $this->itemsAdded[$product_id] = array();
			$this->itemsAdded[$product_id] = array_merge($this->itemsAdded[$product_id],array_merge($variation_sets, $variation_values));
			//This clears all previous variants
			wp_set_object_terms($product_id, $this->itemsAdded[$product_id], 'wpsc-variation',true);


			$selected_post = get_posts(array(
				//'numberposts' => 1,
				'name' => $product_values['post_name'],
				'post_parent' => $product_id,
				'post_type' => "wpsc-product",
				'post_status' => 'all',
				'suppress_filters' => true
			));
			$selected_post = array_shift($selected_post);
			$child_product_id = wpsc_get_child_object_in_terms($product_id, $term_ids, 'wpsc-variation');
			$already_a_variation = true;
			if($child_product_id == false) {
				$already_a_variation = false;
				if($selected_post != null) {
					$child_product_id = $selected_post->ID;	
				} else {
					$child_product_id = wp_update_post($product_values);
				}
			} else {
				// sometimes there have been problems saving the variations, this gets the correct product ID
				if(($selected_post != null) && ($selected_post->ID != $child_product_id)) {
					$child_product_id = $selected_post->ID;
				}
			}
			if($child_product_id > 0) {
				array_push($child_ids,$child_product_id);
				wp_set_object_terms($child_product_id, $term_slugs, 'wpsc-variation');
			}
			//JS - 7.9 - Adding loop to include meta data in child product.
			foreach ($child_product_meta as $meta_key => $meta_value ) :
				if ($meta_key == "_wpsc_product_metadata") {
					update_post_meta($child_product_id, $meta_key, unserialize($meta_value[0]));
				} else {
					update_post_meta($child_product_id, $meta_key, $meta_value[0]);
				}
				
			endforeach;
			
			//Adding this to check for a price on variations.  Applying the highest price, seems to make the most sense.		
			if ( is_array ($term_ids) ) {
				$price = array();
				foreach ($term_ids as $term_id_price) {
					$price[] = term_id_price($term_id_price, $child_product_meta["_wpsc_price"][0]);
					//$price[] = $term_id_price;
				}
				rsort($price);
				$price = $price[0];	
			
				if($price > 0) {
					update_post_meta($child_product_id, "_wpsc_price", $price);
				}
			}
		}
		return($child_ids);
		
	}
	/* Taken from the actual wordpress, slightly hacked for my dirty needs
	 * @param array $post_data allows you to overwrite some of the attachment
	 * @param array $overrides allows you to override the {@link wp_handle_upload()} behavior
	 * @return int the ID of the attachment
	 */
	function image_handle_upload($url, $post_id, $post_data = array(), $overrides = array( 'test_form' => false )) {
		$time = current_time('mysql');
		if ( $post = get_post($post_id) ) {
			if ( substr( $post->post_date, 0, 4 ) > 0 )
				$time = $post->post_date;
		}

		if(isset($post_id) && $post_id>0){
			$currentImages = array();
			$currentImages = $this->getProductImages($post_id);
			$img = basename($url);
			foreach($currentImages as $imageRec){
				if(basename($imageRec->guid) == $img){
					//img already exists, assume this is update and delete the other one first
					wp_delete_attachment($imageRec->ID,1);
				}
			}
		}

		$file = ($this->isGood($this->filesUploaded[$url])) ? $this->filesUploaded[$url] : wp_handle_sideload($this->getFile($url), array('test_form'=>false, 'test_upload'=>false), $time);
		$this->filesUploaded[$url] = array_merge($this->filesUploaded[$url],$file);
		$name = $this->filesUploaded[$url]['name'];

		if ( isset($file['error']) )
			return new WP_Error( 'upload_error', $file['error'] );

		$name_parts = pathinfo($name);
		$name = trim( substr( $name, 0, -(1 + strlen($name_parts['extension'])) ) );

		$url = $file['url'];
		$type = $file['type'];
		$file = $file['file'];
		$title = $name;
		$content = '';

		// use image exif/iptc data for title and caption defaults if possible
		if ( $image_meta = @wp_read_image_metadata($file) ) {
			if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) )
				$title = $image_meta['title'];
			if ( trim( $image_meta['caption'] ) )
				$content = $image_meta['caption'];
		}

		// Construct the attachment array
		$attachment = array_merge( array(
			'post_mime_type' => $type,
			'guid' => $url,
			'post_parent' => $post_id,
			'post_title' => $title,
			'post_content' => $content,
		), $post_data );

		// Save the data
		$id = wp_insert_attachment($attachment, $file, $post_id);
		if ( !is_wp_error($id) ) {
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
		}

		return $id;

	}

	function exportProducts(){
		return($this->getProducts());
	}

	/**
	* getProducts
	*/
	function getProducts($options = false){
		global $wpdb, $logger;
		set_time_limit(0); 
		$order = 'ASC';
		if($options) extract($options);
		$cg = "SELECT t.term_id, t.name, x.parent FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON  x.term_id=t.term_id WHERE x.taxonomy='wpsc_product_category'";
		foreach($wpdb->get_results($cg,ARRAY_A) as $c){
			$cats[$c['term_id']] = array('pid'=>$c['parent'], 'name'=>$c['name']);
		}
		foreach($cats as $id=>$cat){
			$cid = $id;
			$pid = $cat['pid'];
			$path = $cat['name'];
			while($cid != 0){
				$pid = $cats[$cid]['pid'];
				if($pid != 0){
					$path = $cats[$pid]['name'].'->'.$path;
				}
				$cid = $cats[$cid]['pid'];
			}
			$categoryMatrix[$id] = $path;
		}
		unset($cats);

		$sql = "SELECT ID, post_content, post_title, guid from {$wpdb->posts} WHERE post_type='wpsc-product' AND post_status in ('publish', 'draft','pending')";
		//Loop Through all the 
		$posts = $wpdb->get_results($sql,OBJECT_K);
		$wpdb->flush();
		foreach($posts as $post){
			$sql = "SELECT ID, post_content, post_title, guid from {$wpdb->posts} WHERE post_type='wpsc-product' AND post_status='inherit' AND post_parent={$post->ID}";
			if($variants = $wpdb->get_results($sql,OBJECT_K)){
				$wpdb->flush();
				foreach($variants as $variant){
					$id = $variant->ID;
					$meta = $this->get_meta($id);

					$name = $post->post_title;
					$description = $post->post_content;

					$prod_tmp[$id]['name'] = $name;
					$prod_tmp[$id]['description']=$description;
					if($this->isGood($meta['_wpsc_price'])) $prod_tmp[$id]['price'] = $meta['_wpsc_price'];
					if($this->isGood($meta['_wpsc_special_price'])) $prod_tmp[$id]['special_price'] = $meta['_wpsc_special_price'];
					if($this->isGood($meta['_wpsc_stock'])) $prod_tmp[$id]['quantity'] = $meta['_wpsc_stock'];

					//if no style is stored, then use the parent id as the style for export, that way we can do a new import
					if(!$this->isGood($meta['style'])){
						$prod_tmp[$id]['style'] = $post->ID;
					}
						
					if($this->isGood($meta['_wpsc_sku'])){
						$prod_tmp[$id]['sku'] = $meta['_wpsc_sku'];
					}else{
						$prod_tmp[$id]['sku'] = $id;
					}
					if($this->isGood($meta['_wpsc_product_metadata'])){
						$prod_meta = unserialize($meta['_wpsc_product_metadata']);
						if(isset($prod_meta['weight'])){
							$prod_tmp[$id]['weight'] = $prod_meta['weight'].$prod_meta['weight_unit'];
						}else{
							$prod_tmp[$id]['weight'] = 0;
						}
					}

					foreach(array_keys($meta) as $cmeta){
						if(preg_match('/^_wpsc_/',$cmeta) == 1) continue;
						$prod_tmp[$id]['meta_'.$cmeta] = $meta[$cmeta];
					}
					unset($meta);
					unset($prod_meta);
					$prod_tmp[$id]['image'] = $this->exportImages($id);
			
					//Pack the product Tags
					//TODO look for product_tag taxonomy
					$productTags = wp_get_object_terms( $post->ID, 'product_tag', array( 'fields' => 'names' ) );
					$wpdb->flush();
					if($productTags) {
						$prod_tmp[$id]['tags'] = implode('|', $productTags);
					}
					//Get Variant info
					$variantTaxonomies = wp_get_object_terms($id,'wpsc-variation');
					$wpdb->flush();
					foreach($variantTaxonomies as $tvar){
						if($tvar->parent > 0){
							$parent = get_term($tvar->parent,'wpsc-variation');
							$wpdb->flush();
							$key = $parent->name;
						}else{
							$key = $tvar->name;
						}
						$prod_tmp[$id]['variant_'.$key] = $tvar->name;
					}

					//Get Group Relationship
					$categories = wp_get_object_terms($id,'wpsc_product_category',array('fields'=>'ids'));
					$wpdb->flush();
					$cats = array();
					foreach($categories as $cat){
						$cats[] = $categoryMatrix[$cat];
					}
					$prod_tmp[$id]['category'] = implode('|', $cats);
				}
			}else{
				if(isset($post->post_title)) $prod_tmp[$post->ID]['name'] = $post->post_title;
				if(isset($post->post_content)) $prod_tmp[$post->ID]['description'] = $post->post_content;
				if(isset($post->post_status) && $post->post_status == 'publish') $prod_tmp[$post->ID]['active'] = true;
				else $prod_tmp[$post->ID]['active'] = false;
				$id = $post->ID;
				unset($post);
				$meta = $this->get_meta($id);
				if($this->isGood($meta['_wpsc_price'])) $prod_tmp[$id]['price'] = $meta['_wpsc_price'];
				if($this->isGood($meta['_wpsc_special_price'])) $prod_tmp[$id]['special_price'] = $meta['_wpsc_special_price'];
				if($this->isGood($meta['_wpsc_stock'])) $prod_tmp[$id]['quantity'] = $meta['_wpsc_stock'];
				if($this->isGood($meta['_wpsc_sku'])){
					$prod_tmp[$id]['sku'] = $meta['_wpsc_sku'];
					$prod_tmp[$id]['upc'] = $prod_tmp[$id]['sku'];
				}else{
					$prod_tmp[$id]['sku'] = $id;
				}
				if($this->isGood($meta['_wpsc_product_metadata'])){
					$prod_meta = unserialize($meta['_wpsc_product_metadata']);
					if(isset($prod_meta['weight']) && isset($prod_meta['weight_unit'])){
						$prod_tmp[$id]['weight'] = $prod_meta['weight'].$prod_meta['weight_unit'];
					}else{
						$prod_tmp[$id]['weight'] = 0;
					}
				}

				foreach(array_keys($meta) as $cmeta){
					if(preg_match('/^_wpsc_/',$cmeta) == 1) continue;
					$prod_tmp[$id]['meta_'.$cmeta] = $meta[$cmeta];
				}
			
				$prod_tmp[$id]['image'] = $this->exportImages($id);
				//Pack the product Tags
				$productTags = wp_get_object_terms( $id, 'product_tag', array( 'fields' => 'names' ) );
				$wpdb->flush();
				if($productTags) {
					$prod_tmp[$id]['tags'] = implode('|', $productTags);
				}
				$categories = wp_get_object_terms($id,'wpsc_product_category',array('fields'=>'ids'));
				$wpdb->flush();
				$cats = array();
				foreach($categories as $cat){
					$cats[] = $categoryMatrix[$cat];
				}
				$prod_tmp[$id]['category'] = implode('|', $cats);
			}
		}
	
		wp_reset_postdata();
		return($prod_tmp);
	}

	function get_meta($id){
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->postmeta} WHERE post_id=".(int)$id;
		
		foreach($wpdb->get_results($sql, ARRAY_A) as $t){
			$results[$t['meta_key']] = $t['meta_value'];
		}
		$wpdb->flush();;
		if(isset($results)) return($results);
		else return false;
	}
		

	function exportImages($pid){
		$t = array();
		foreach($this->getProductImages($pid) as $img){
			$t[] = $img->guid;
		}
		return implode('|',$t);
	}
		
		

	/**
	* getProductImages($productID) get all the images for a specific product.
	*
	* @param int $productID
	* @return mixed
	*/
	function getProductImages($productID){
		global $wpdb;
		$images = get_children(array(
			'post_parent' => $productID,
			'post_type' => 'attachment',
			'post_mime_type' => 'image',)
		);
		$wpdb->flush();
		return $images;
	}
}

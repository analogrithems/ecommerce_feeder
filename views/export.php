<?php 

//get our global vars inported
global $objects, $types, $schedules, $db_drivers; 
?>        
	<h2>WordPress eCommerce Data Feeder Export</h2>
        <?php
                if(isset($_SESSION['status_msg'])){ ?>
                        <div id="message" class="updated">
                        <?php echo $_SESSION['status_msg']; unset($_SESSION['status_msg']); ?>
                        </div>
        <?php   
                }
                if(isset($_SESSION['error_msg'])){ ?>
                        <div id="message" class="error">
                        <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                        </div>
        <?php   }
        ?>

	<div style="width: 39%;" id="col-right">
		<?php include('schedule.php'); ?>
	</div>
	<div style="width: 59%;" id="col-left">

	  <form>
		<div id="poststuff" class="postbox">
                	<h3 class="hndle">Export Data</h3>
				<div class="inside">
                                        <script>
                                                jQuery(document).ready( function() {
                                                        <?php
								$scripts['export'][] = array('name'=>__('Select Export Method','ecomfeeder'));
								$scripts = apply_filters('ecommerce_feeder_register_script',$scripts);
                                                                echo "var scriptOptions = ".json_encode($scripts['export']).";\n";
                                                                //load all the active scripts
                                                                if($selectedScript = fromRequest('type')){
                                                                        echo "var selScript = '".$selectedScript."';\n";
                                                                }
                                                                if($selectedPurpose = fromRequest('object')){
                                                                        echo "var selPurp = '".$selectedPurpose."';\n";
                                                                }


                                                        ?>
                                                   var options = '';
                                                   jQuery('#scriptsForm div span :input').attr('disabled', true);
                                                   jQuery('.hideonstart').hide();
                                                   for(var key in scriptOptions){
                                                        options = options+"<option value='"+key+"'>"+scriptOptions[key].name+"</option>";
                                                   }
    
                                                   jQuery('select#wpec_data_feeder_type').html(options);
                                                   selected = jQuery('#wpec_data_feeder_type').children("option:selected").val();
                                                   getScriptOptions(selected);
                                                   if(typeof(selScript) !== 'undefined'){
                                                        jQuery('select#wpec_data_feeder_type').val(selScript);
                                                        getScriptOptions(selScript);
                                                        jQuery('#'+selScript).show();
                                                        jQuery('#'+selScript+' span :input').attr('disabled', false);
                                                   }
                                                   if(typeof(selPurp) !== 'undefined'){
                                                        jQuery('#wpec_data_feeder_object').val(selPurp);
                                                   }

                                                   jQuery('#wpec_data_feeder_type').change(function() {
                                                        jQuery('#scriptsForm div span :input').attr('disabled', true);
                                                        jQuery('.hideonstart').hide();
                                                        shown = jQuery(this).children("option:selected").val();
                                                        jQuery('#'+shown).show();
                                                        jQuery('#'+shown+' span :input').attr('disabled', false);
                                                        var options = getScriptOptions(shown);
                                                        //jQuery('select#'+wpec_data_feeder_object).html(options);
                                                   });

                                                   function getScriptOptions(sel){
                                                        var slist = scriptOptions[sel];
                                                        var options = '';
                                                        for(var key in slist.options){
                                                                options = options+"<option value='"+key+"'>"+slist.options[key]+"</option>";
                                                        }
                                                        jQuery('select#wpec_data_feeder_object').html(options);
                                                   }

                                                });
                                        </script>
					<table class="form-table">
                                                <tr valign="top">
                                                        <th scope="row">Name</th>
                                                        <td><input type='text' name="wpec_data_feeder[name]" value="<?php echo fromRequest('name'); ?>"></td>
                                                </tr>
                                                <tr valign="top">
                                                        <th scope="row">Choose a Source</th>
                                                        <td><select id='wpec_data_feeder_type' name='wpec_data_feeder[type]'>
                                                            </select>
                                                        </td>
                                                </tr>
                                                <tr valign="top">
                                                        <th scope="row">Select a Purpose</th>
                                                        <td>
                                                            <select id='wpec_data_feeder_object' name='wpec_data_feeder[object]'>
                                                            </select>
                                                        </td>
                                                </tr>
					</table>
				</div>
				<div id='scriptsForm'>
					<?php
						echo apply_filters('ecommerce_feeder_export_form','');
					?>
				</div>
				<table class="form-table">
					<tr valign="top">
                                                        <th scope="row">
								<?php if(fromRequest('id') != ''){ ?>
									<input type='hidden' name='wpec_data_feeder[id]' value='<?php echo fromRequest('id'); ?>' />
								<?php } ?>
								<input type='hidden' name='wpec_data_feeder[direction]' value='export'>
								<input type='submit' name='submit' value='Export Data' /></th>
								<input type='hidden' name='page' value='wpsc_module_data_feeder' />
                                                       <!-- <th scope="row"><input type='submit' name='submit' value='Save Job' /></th> -->
					</tr>
				</table>
		</div>
	  </form>
	</div>

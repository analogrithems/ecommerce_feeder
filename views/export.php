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
						    jQuery('.hideonstart').hide();
						    jQuery('select.wpec_data_feeder_destination').change(function() {
							jQuery('.hideonstart').hide();
							shown = jQuery(this).children("option:selected").val();
							jQuery('#'+shown).show();
						    });
						});
					</script>
					<table class="form-table">
                                                <tr valign="top">
                                                        <th scope="row">Name</th>
                                                        <td><input type='text' name="wpec_data_feeder[name]" value="<?php echo fromRequest('name'); ?>"></td>
                                                </tr>
                                                <tr valign="top">
                                                        <th scope="row">Choose a Destination</th>
                                                        <td><select id='wpec_data_feeder_type' name='wpec_data_feeder[type]'>
								<?php echo htmlOptions($types, $type); ?>
                                                            </select>
                                                        </td>
                                                </tr>
                                                <tr valign="top">
                                                        <th scope="row">Select a Purpose</th>
                                                        <td>
                                                            <select name='wpec_data_feeder[object]'>
								<?php echo htmlOptions($objects, $object); ?>
                                                            </select>
                                                        </td>
                                                </tr>
					</table>
					<div id='db' class='hideonstart'>
						<span class='inputLine'><strong>Select DB Type: </strong>
							<select name="wpec_data_feeder[db_driver]">
								<option value='mysql'>MySQL</option>
								<option value='mssql'>MS SQL</option>
								<option value='oracle'>Oracle</option>
							</select>
						</span>
						<span class='inputLine'>
							<strong>DB Server: </strong><input type='text' name='wpec_data_feeder[dbhost]' value='<?php echo fromRequest('dbhost'); ?>'>
							<strong>DB Name: </strong><input type='text' name='wpec_data_feeder[dbname]' value='<?php echo fromRequest('dbname'); ?>'>
						</span>
						<span class='inputLine'>
							<strong>DB User: </strong><input type='text' name='wpec_data_feeder[dbuser]' value='<?php echo fromRequest('dbuser');?>'>
							<strong>DB Password: </strong><input type='password' name='wpec_data_feed[dbpassword]' value='<?php echo fromRequest('dbpassword');?>'>
						</span>
						<span class='inputLine'>
							<strong>Query: </strong>
							<textarea name="wpec_data_feeder[source_sql]" rows="20" cols="70"><?echo fromRequest('source_sql');?></textarea>
						</span>
					</div>
					<div id='xml' class='hideonstart'>
						<span class='inputLine'>
							<strong>XML URL: </strong><input type='text' name='wpec_data_feeder[source_xml]' value='<?php echo fromRequest('source_xml');?>'>
						</span>
					</div>
					<div id='csv' class='hideonstart'>
						<span class='inputLine'>
							<strong>CSV URL: </strong><input type='text' name='wpec_data_feeder[source_csv]' value='<?php echo fromRequest('source_csv');?>'>
						</span>
					</div>
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

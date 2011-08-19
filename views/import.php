<?php 

//get our global vars inported
global $objects, $types, $times, $schedules, $db_drivers; 
?>
        <h2>WordPress eCommerce Data Feeder Import</h2>

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
	<?php	}
	?>

	<div style="width: 39%;" id="col-right">
		<?php include('schedule.php');?>
	</div>
	<div style="width: 59%;" id="col-left">

	  <form method='post' enctype="multipart/form-data">
		<div id="poststuff" class="postbox">
                	<h3 class="hndle">Auto Import Data</h3>
				<div class="inside">
					<script>
						jQuery(document).ready( function() {
						   selected = jQuery('#wpec_data_feeder_type').children("option:selected").val();
						   jQuery('.hideonstart').hide();
						   if(selected != 'none'){
							jQuery('#'+selected).show();
						   }

						   jQuery('#wpec_data_feeder_type').change(function() {
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
							<th scope="row">Choose a Source</th>
							<td><select id='wpec_data_feeder_type' name='wpec_data_feeder[type]'>
								<?php echo htmlOptions($types, fromRequest('type')); ?>
							    </select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Select a Purpose</th>
							<td>
							    <select name='wpec_data_feeder[object]'>
								<?php echo htmlOptions($objects, fromRequest('object')); ?>
							    </select>
							</td>
						</tr>
					</table>
					<div id='db' class='hideonstart'>
						<span class='inputLine'><strong>Select DB Type: </strong>
						    <select name='wpec_data_feeder[db_driver]'>
								<?php echo htmlOptions($db_drivers, fromRequest('db_driver')); ?>
						    </select>
						</span>
						<span class='inputLine'>
							<strong>DB Server: </strong><input type='text' name='wpec_data_feeder[dbhost]' value='<?php echo fromRequest('dbhost'); ?>'>
							<strong>DB Name: </strong><input type='text' name='wpec_data_feeder[dbname]' value='<?php echo fromRequest('dbname'); ?>'>
						</span>
						<span class='inputLine'>
							<strong>DB User: </strong><input type='text' name='wpec_data_feeder[dbuser]' value='<?php echo fromRequest('dbuser'); ?>'>
							<strong>DB Password: </strong><input type='password' name='wpec_data_feeder[dbpassword]' value='<?php echo fromRequest('dbpassword'); ?>'>
						</span>
						<span class='inputLine'>
							<strong>Query: </strong>
							<textarea name="wpec_data_feeder[source][sql]" rows="20" cols="70"><?php echo fromRequest('source_sql');?></textarea>
						</span>
					</div>
					<div id='xml' class='hideonstart'>
						<span class='inputLine'>
							<strong>XML URL: </strong><input type='text' name='wpec_data_feeder[source][xml]' value='<?php echo fromRequest('source_xml'); ?>'> or <input type='file' name='source'>
						</span>
					</div>
					<div id='csv' class='hideonstart'>
						<span class='inputLine'>
							<strong>CSV URL: </strong><input type='text' name='wpec_data_feeder[source_csv]' value='<?php echo fromRequest('source_csv'); ?>'> or <input type='file' name='source'>
						</span>
					</div>
				</div>
				<table class="form-table">
<!--
					<tr valign="top">
						<th cols='2' scope="row">
						    <strong>Schedule:</strong>
						    <select name='wpec_data_feeder[schedule]'>
							<?php echo htmlOptions($times, fromRequest('schedule')); ?>
						    </select>
						</th>
					</tr>
-->
					<tr valign="top">
						<th scope="row">
						<?php if(fromRequest('id') != ''){ ?>
							<input type='hidden' name='wpec_data_feeder[id]' value='<?php echo fromRequest('id'); ?>' />
						<?php } ?>
						<input type='hidden' name='wpec_data_feeder[direction]' value='import' />
						<input type='hidden' name='MAX_FILE_SIZE' value='1000000' />
						<input type='hidden' name='page' value='wpsc_module_data_feeder' />
						<input type='submit' name='submit' value='Run Now' /></th>
						<th scope="row"><input type='submit' name='submit' value='Save Job' /></th>
					</tr>
				</table>
		</div>
	  </form>
	</div>

<?php 

//get our global vars inported
global $objects, $types, $schedules, $db_drivers, $job;
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
						   var activeScripts = new Array();
							<?php
								//load all the active scripts
								$scripts = array('none'=>__('Select Script','ecommerce_feeder'));
								$i = 0;
								foreach($job->scripts['import'] as $script=>$options){
									$tmp = array_keys($options);
									$scripts[$script] = $tmp[0];
									echo "activeScripts[{$i}] = '".$script."';\n";
									$i++;
								}
							?>
						   selected = jQuery('#wpec_data_feeder_type').children("option:selected").val();
						   jQuery('#scriptsForm div span :input').attr('disabled', true);
						   jQuery('.hideonstart').hide();
						   if(selected != 'none'){
							jQuery('#'+selected).show();
							jQuery('#'+selected+' span :input').attr('disabled', false);
						   }

						   jQuery('#wpec_data_feeder_type').change(function() {
						        jQuery('#scriptsForm div span :input').attr('disabled', true);
							jQuery('.hideonstart').hide();
							shown = jQuery(this).children("option:selected").val();
							jQuery('#'+shown).show();
							jQuery('#'+shown+' span :input').attr('disabled', false);
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
								<?php echo htmlOptions($scripts, fromRequest('type')); ?>
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
					<div id='scriptsForm'>
						<?php
							
							echo apply_filters('ecommerce_feeder_import_form','');
						?>
					</div>
				</div>
				<table class="form-table">
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

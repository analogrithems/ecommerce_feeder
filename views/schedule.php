<?php 

//get our global vars inported
global $objects, $schedules;
?>
<div class="clear"></div>
<table class="widefat tag fixed" cellspacing="0">
	<thead>
	<tr>
	<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
	<th scope="col" id="name" class="manage-column column-name" style="">Name</th>
	<th scope="col" id="type" class="manage-column" style="">Type</th>
	<th scope="col" id="direction" class="manage-column" style="">Direction</th>
	<th scope="col" id="schedule" class="manage-column num" style="">Purpose</th>
	</tr>
	</thead>

	<tfoot>
	<tr>
	<th scope="col"  class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>

	<th scope="col" class="manage-column column-name" style="">Name</th>
	<th scope="col" class="manage-column" style="">Type</th>
	<th scope="col" class="manage-column" style="">Direction</th>
	<th scope="col" class="manage-column num" style="">Purpose</th>
	</tr>
	</tfoot>

        <?php
        global $scheduledJobs,$types;
	if(isset($scheduledJobs)){
	?>
        <tbody id="the-list" class="list:tag">
	<?php
		if(isset($scheduledJobs) && !empty($scheduledJobs)){
			foreach($scheduledJobs as $id=>$schedule){
		?>
			<tr id='schedule-<?php echo $id; ?>' class="alternate">
			<th scope="row" class="check-column"> <input type="checkbox" name="delete_tags[]" value="<?php echo $id; ?>" /></th>
			<td class="name column-name">
				<strong><a class='row-title' href='admin.php?page=wpsc_module_data_feeder&wpec_data_feeder[direction]=<?php echo $schedule['direction']; ?>&id=<?php echo $id; ?>&submit=Edit' title='Edit Job'><?php echo $schedule['name']; ?></a></strong>
				<br />
				<div class="row-actions">
					<span class='edit'><a href="admin.php?page=wpsc_module_data_feeder&wpec_data_feeder[direction]=<?php echo $schedule['direction']; ?>&id=<?php echo $id; ?>&submit=Edit">Edit</a> | </span>
					<span class='delete'><a class='delete-tag' href='admin.php?page=wpsc_module_data_feeder&wpec_data_feeder[direction]=<?php echo $schedule['direction']; ?>&id=<?php echo $id; ?>&submit=Delete' onclick="return confirm('Are you sure you want to delete?')">Delete</a></span>
				</div>
				<div class="hidden" id="inline_<?php echo $id; ?>">
					<div class="name"><?php echo $schedule['name']; ?></div>
					<div class="type"><?php echo $schedule['type']; ?></div>
					<div class="direction"><?php echo $schedule['direction']; ?></div>
					<div class="parent">0</div>
				</div>
			</td>
			<td class="type column-type"><?php echo $schedule['type']; ?></td>
			<td class="slug column-slug"><?php echo $schedule['direction']; ?></td>
			<td class="schedule column-schedule"><?php echo $schedule['object']; ?></td>
		<?php
			}
		}
		?>

		</tbody>
<?php	}?>
	</table>

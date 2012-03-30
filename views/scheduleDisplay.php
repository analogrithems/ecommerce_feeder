<div class="clear"></div>
<table class="widefat tag fixed" cellspacing="0">
	<thead>
	<tr>
	<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
	<th scope="col" id="name" class="manage-column column-name" style="">Name</th>
	<th scope="col" id="type" class="manage-column" style="">Type</th>
	<th scope="col" id="direction" class="manage-column" style="">Direction</th>
	<th scope="col" id="schedule" class="manage-column num" style="">Schedule</th>
	</tr>
	</thead>

	<tfoot>
	<tr>
	<th scope="col"  class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>

	<th scope="col" class="manage-column column-name" style="">Name</th>
	<th scope="col" class="manage-column" style="">Type</th>
	<th scope="col" class="manage-column" style="">Direction</th>
	<th scope="col" class="manage-column num" style="">Schedule</th>
	</tr>
	</tfoot>

	<tbody id="the-list" class="list:tag">
	<?php 
	global $scheduleJobs;
	foreach($scheduleJobs as $schedule){
	?>
		<tr id='schedule-<?php echo $schedule['id']; ?>' class="alternate">
		<th scope="row" class="check-column"> <input type="checkbox" name="delete_tags[]" value="<?php echo $schedule['id']; ?>" /></th>
		<td class="name column-name">
			<strong><a class='row-title' href='admin.php?page=wpsc_module_data_feeder&wpec_data_feeder[source]=<?php echo $schedule['direction']; ?>&id=<?php echo $schedule['id']; ?>&submit=Edit' title='Edit Job'><?php echo $schedule['name']; ?></a></strong>
			<br />
			<div class="row-actions">
				<span class='edit'><a href="admin.php?page=wpsc_module_data_feeder&wpec_data_feeder[direction]=<?php echo $schedule['direction']; ?>&id=<?php echo $schedule['id']; ?>&submit=Edit">Edit</a> | </span>
				<span class='delete'><a class='delete-tag' href='admin.php?page=wpsc_module_data_feeder&wpec_data_feeder[direction]=<?php echo $schedule['direction']; ?>&id=<?php echo $schedule['id']; ?>&submit=Delete' onclick="return confirm('Are you sure you want to delete?')">Delete</a></span>
			</div>
			<div class="hidden" id="inline_<?php echo $schedule['id']; ?>">
				<div class="name"><?php echo $schedule['name']; ?></div>
				<div class="type"><?php echo $schedule['data_type']; ?></div>
				<div class="direction"><?php echo $schedule['direction']; ?></div>
				<div class="parent">0</div>
			</div>
		</td>
		<td class="type column-type"><?php echo $schedule['data_type']; ?></td>
		<td class="slug column-slug"><?php echo $schedule['direction']; ?></td>
		<td class="schedule column-schedule"><?php echo $schedule['schedule']; ?></td>
	<?php
	}
	?>

	</tbody>

</table>

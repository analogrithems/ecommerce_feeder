		<h2>
		<?php global $tab; ?>
			<a id="Import" class="nav-tab <?php if(isset($tab) && $tab == 'import') echo 'nav-tab-active'; ?>" href="admin.php?page=wpsc_module_data_feeder&wpec_data_feeder[direction]=import">Import</a>
			<a id="Export" class="nav-tab <?php if(isset($tab) && $tab == 'export') echo 'nav-tab-active'; ?>" href="admin.php?page=wpsc_module_data_feeder&wpec_data_feeder[direction]=export">Export</a>
<!--
			<a id="Schedule" class="nav-tab <?php if(isset($tab) && $tab == 'schedule') echo 'nav-tab-active'; ?>" href="admin.php?page=wpsc_module_data_feeder&wpec_data_feeder[direction]=schedule">View Schedule</a>
-->
		</h2>


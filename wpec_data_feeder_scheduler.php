<?php
global $schedules;
  $schedules['monthly'] = array(
      'interval'=> 2592000,
      'display'=>  __('Once Every 30 Days')
  );
  $schedules['everyfive'] = array(
      'interval'=> 300,
      'display'=>  __('Once Every 5 Mintues')
  );
  $schedules['everyten'] = array(
      'interval'=> 600,
      'display'=>  __('Once Every 10 Minutes')
  );
  $schedules['everyfifteen'] = array(
      'interval'=> 900,
      'display'=>  __('Once Every 15 Minutes')
  );
  $schedules['everythirty'] = array(
      'interval'=> 1800,
      'display'=>  __('Once Every 30 Minutes')
  );
  $schedules['hourly'] = array(
      'interval'=> 21600,
      'display'=>  __('Once Every Hour')
  );
  $schedules['everytwohours'] = array(
      'interval'=> 7200,
      'display'=>  __('Once Every 2 Hours')
  );
  $schedules['everysixhours'] = array(
      'interval'=> 21600,
      'display'=>  __('Once Every 6 Hours')
  );
  $schedules['daily'] = array(
      'interval'=> 86400,
      'display'=>  __('Every Day')
  );
  $schedules['weekly'] = array(
      'interval'=> 604800,
      'display'=>  __('Every Week')
  );

/* This function is executed when the user activates the plugin */
function wpec_data_feeder_activation()
{
	global $schedules;
	include_once('installDBTables.php');
	installDBTable();
	foreach($schedules as $key=>$value){
		$func = 'wpec_data_feeder_'.$key;
		if(function_exists($func) && !wp_next_scheduled($func)){
			wp_schedule_event(time(),$key,$func);
		}
	}
}

/* This function is executed when the user deactivates the plugin */
function wpec_data_feeder_deactivation()
{
	global $schedules;
	foreach($schedules as $key=>$value){
		$func = 'wpec_data_feeder_'.$key;
		if(function_exists($func)){
			wp_clear_scheduled_hook($func);
		}
	}
}

add_filter('cron_schedules','wpec_data_feeder_cron_definer');    

//This schedules all of our cron jobs.
foreach($schedules as $key=>$value){
	$func = 'wpec_data_feeder_'.$key;
	if(function_exists($func)){
		add_filter($func,$func);
	}
}

function wpec_data_feeder_cron_definer($current_schedules)
{
  global $schedules;
  $schedules = array_merge($current_schedules,$schedules);
  return $schedules;
}

function wpec_data_feeder_everyfive(){
	$jobs = new WPEC_Jobs();
	$runJobsResult = $jobs->runJobs(array('schedule'=>'everyfive'));
}
function wpec_data_feeder_everyten(){
	$jobs = new WPEC_Jobs();
	$runJobsResult = $jobs->runJobs(array('schedule'=>'everyten'));
}
function wpec_data_feeder_everyfifteen(){
	$jobs = new WPEC_Jobs();
	$runJobsResult = $jobs->runJobs(array('schedule'=>'everyfifteen'));
}
function wpec_data_feeder_everythirty(){
	$jobs = new WPEC_Jobs();
	$runJobsResult = $jobs->runJobs(array('schedule'=>'everythirty'));
}
function wpec_data_feeder_hourly(){
	$jobs = new WPEC_Jobs();
	$runJobsResult = $jobs->runJobs(array('schedule'=>'hourly'));
}
function wpec_data_feeder_everytwohours(){
	$jobs = new WPEC_Jobs();
	$runJobsResult = $jobs->runJobs(array('schedule'=>'everytwohours'));
}
function wpec_data_feeder_everysixhours(){
	$jobs = new WPEC_Jobs();
	$runJobsResult = $jobs->runJobs(array('schedule'=>'everysixhours'));
}
function wpec_data_feeder_daily(){
	$jobs = new WPEC_Jobs();
	$runJobsResult = $jobs->runJobs(array('schedule'=>'daily'));
}
function wpec_data_feeder_weekly(){
	$jobs = new WPEC_Jobs();
	$runJobsResult = $jobs->runJobs(array('schedule'=>'weekly'));
}
function wpec_data_feeder_monthly(){
	$jobs = new WPEC_Jobs();
	$runJobsResult = $jobs->runJobs(array('schedule'=>'monthly'));
}
?>

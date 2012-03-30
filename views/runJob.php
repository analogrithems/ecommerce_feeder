<?php
/*
 * This is the script that runs during a execution of a job.  
 * It allows processes to run for long periods of time.
 *
*/

global $count, $ids, $text_failures, $text_nofailures, $task;

	$type = $task['object'];
	$text_goback = ( ! empty( $_GET['goback'] ) ) ? sprintf( __( 'To go back to the previous page, <a href="%s">click here</a>.', 'ecommerce_feeder' ), 'javascript:history.go(-1)' ) : '';

	$text_failures = sprintf( __( 'All done! %1$s %2$s were imported in %3$s seconds and there were %4$s failure(s). To try again, <a href="%5$s">click here</a>. %6$s', 
			'ecommerce_feeder' ), 
				"' + rt_successes + '", 
				$type,
				"' + rt_totaltime + '", 
				"' + rt_errors + '", 
				esc_url( wp_nonce_url( admin_url( 'tools.php?page=ecommerce_feeder&goback=1' ), 'ecommerce_feeder' ) . '&ids=' ) . "' + rt_failedlist + '", 
				$text_goback );

	$text_nofailures = sprintf( __( 'All done! %1$s %2$s were successfully imported in %3$s seconds and there were 0 failures. %4$s', 'ecommerce_feeder' ), 
			"' + rt_successes + '", 
			$type,
			"' + rt_totaltime + '", 
			$text_goback );

?>
<div id="message" class="updated fade" style="display:none"></div>

<div class="wrap ecommercefeeder">
	<h2><?php _e('Ecommerce Feeder: Task Engine', 'ecommerce_feeder'); ?></h2>


	<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'ecommerce_feeder' ) ?></em></p></noscript>

	<div id="ecomfeeder-bar" style="position:relative;height:25px;">
		<div id="ecomfeeder-bar-percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
	</div>

	<p><input type="button" class="button hide-if-no-js" name="ecomfeeder-stop" id="ecomfeeder-stop" value="<?php _e( 'Abort Job', 'ecommerce_feeder' ) ?>" /></p>

	<h3 class="title"><?php _e( 'Debugging Information', 'ecommerce_feeder' ) ?></h3>

	<p>
		<?php printf( __( 'Total Tasks: %s', 'ecommerce_feeder' ), $count ); ?><br />
		<?php printf( __( 'Success: %s', 'ecommerce_feeder' ), '<span id="ecomfeeder-debug-successcount">0</span>' ); ?><br />
		<?php printf( __( 'Failures: %s', 'ecommerce_feeder' ), '<span id="ecomfeeder-debug-failurecount">0</span>' ); ?>
	</p>

	<ol id="ecomfeeder-debuglist">
		<li style="display:none"></li>
	</ol>

	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function($){
			var i;
			var rt_total = <?php echo (int)$count;?>;
			var rt_count = 0;
			var rt_percent = 0;
			var rt_successes = 0;
			var rt_errors = 0;
			var rt_failedlist = '';
			var rt_resulttext = '';
			var rt_timestart = new Date().getTime();
			var rt_timeend = 0;
			var rt_totaltime = 0;
			var rt_continue = true;

			// Create the progress bar
			jQuery("#ecomfeeder-bar").progressbar();
			jQuery("#ecomfeeder-bar-percent").html( "0%" );

			// Stop button
			jQuery("#ecomfeeder-stop").click(function() {
				rt_continue = false;
				jQuery('#ecomfeeder-stop').val("<?php echo __( 'Stopping...', 'ecommerce_feeder'); ?>");
			});

			// Clear out the empty list element that's there for HTML validation purposes
			jQuery("#ecomfeeder-debuglist li").remove();

			// Called after each product update. Updates debug information and the progress bar.
			function EcommFeederUpdateStatus( id, success, response ) {
				jQuery("#ecomfeeder-bar").progressbar( "value", ( rt_count / rt_total ) * 100 );
				jQuery("#ecomfeeder-bar-percent").html( Math.round( ( rt_count / rt_total ) * 1000 ) / 10 + "%" );
				rt_count = rt_count + 1;

				if ( success ) {
					rt_successes = rt_successes + 1;
					jQuery("#ecomfeeder-debug-successcount").html(rt_successes);
					jQuery("#ecomfeeder-debuglist").append("<li>" + response.success + "</li>");
				}
				else {
					rt_errors = rt_errors + 1;
					rt_failedlist = rt_failedlist + ',' + id;
					jQuery("#ecomfeeder-debug-failurecount").html(rt_errors);
					console.log(response);
					if(response.error){
						jQuery("#ecomfeeder-debuglist").append("<li>" +response.error+"</li>");
					}else{
						jQuery("#ecomfeeder-debuglist").append("<li>Bad response from Server, verify Wordpress Debugging is off!</li>");
					}
				}
			}

			function IsJsonString(str) {
			    try {
				JSON.parse(str);
			    } catch (e) {
				return false;
			    }
			    return true;
			}

			// Called when all task are done
			function EcommFeederFinishUp() {
				rt_timeend = new Date().getTime();
				rt_totaltime = Math.round( ( rt_timeend - rt_timestart ) / 1000 );
                                jQuery("#ecomfeeder-bar").progressbar( "value",  100 );
                                jQuery("#ecomfeeder-bar-percent").html( "100%" );

				jQuery('#ecomfeeder-stop').hide();

				if ( rt_errors > 0 ) {
					rt_resulttext = '<?php echo $text_failures; ?>';
				} else {
					rt_resulttext = '<?php echo $text_nofailures; ?>';
				}

				jQuery("#message").html("<p><strong>" + rt_resulttext + "</strong></p>");
				jQuery("#message").show();
			}

			// Run task via ajax
			function EcommFeeder( id ) {
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					data: { action: "ecomm_feeder_task", id: id, task: <?php echo json_encode($task); ?>  },
					success: function( response ) {
						if ( response.success ) {
							EcommFeederUpdateStatus( id, true, response );
						}
						else {
							alert('Bad response from server');
							EcommFeederUpdateStatus( id, false, response );
						}
						if ( rt_count < rt_total && rt_continue){
							EcommFeeder( rt_count );
						}
						else {
							EcommFeederFinishUp();
						}
					},
					error: function( response ) {
						EcommFeederUpdateStatus( id, false, response );

						if ( rt_count < rt_total && rt_continue){
							EcommFeeder( rt_count );
						} 
						else {
							EcommFeederFinishUp();
						}
					}
				});
			}

			EcommFeeder( rt_count );
		});
	// ]]>
	</script>
</div>

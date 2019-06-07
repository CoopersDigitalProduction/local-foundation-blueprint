jQuery(document).ready(function($) {

	/**
	 * This function will start the restore over ajax for the passed in job_id.
	 *
	 * @param {string}  job_id - the restore job id
	 */
	function updraft_restore_command(job_id) {
		var xhttp = new XMLHttpRequest();

		var xhttp_data = 'action=updraft_ajaxrestore&updraftplus_ajax_restore=do_ajax_restore&job_id=' + job_id;
		var previous_data_length = 0;
		var show_alert = true;
		var debug = $('#updraftplus_ajax_restore_debug').length;

		xhttp.open("POST", ajaxurl, true);
		xhttp.onprogress = function(response) {
			if (response.currentTarget.status >= 200 && response.currentTarget.status < 300) {
				if (-1 !== response.currentTarget.responseText.indexOf('<html')) {
					if (show_alert) {
						show_alert = false;
						alert("UpdraftPlus " + updraftlion.ajax_restore_invalid_response);
					}
					$('#updraftplus_ajax_restore_output').append("UpdraftPlus " + updraftlion.ajax_restore_invalid_response);
					console.log("UpdraftPlus restore error: HTML detected in response could be a copy of the WordPress front page caused by mod_security");
					console.log(response.currentTarget.responseText);
					return;
				}
				
				if (previous_data_length == response.currentTarget.responseText.length) return;
				
				var responseText = response.currentTarget.responseText.substr(previous_data_length);
				
				previous_data_length = response.currentTarget.responseText.length;

				var end_of_json = 0;
				// Check if there is restore information json in the response if so process it and remove it from the response so that it does not make it to page
				for (var i = 0; i < responseText.length; i++) {
					var buffer = responseText.substr(i, 7);
					if ('RINFO:{' == buffer) {
						// Output what precedes the RINFO:
						$('#updraftplus_ajax_restore_output').append(responseText.substr(0, i));
						// Grab what follows RINFO:
						var analyse_it = ud_parse_json(responseText.substr(i), true);
						// In future, this is the point at which to do something with the parsed data. For now, we'll just log it. The returned object is in analyse_it.parsed.
						if (1 == debug) { console.log(analyse_it); }
						// move the for loop counter to the end of the json
						end_of_json = i + analyse_it.json_last_pos - analyse_it.json_start_pos + 7;
						// When the for loop goes round again, it will start with the end of the JSON
						i = end_of_json;
					}
				}
				$('#updraftplus_ajax_restore_output').append(responseText.substr(end_of_json));
			} else {
				$('#updraftplus_ajax_restore_output').append("UpdraftPlus restore error: " + response.currentTarget.status + ' ' + response.currentTarget.statusText);
				console.log("UpdraftPlus restore error: " + response.currentTarget.status + ' ' + response.currentTarget.statusText);
				console.log(response.currentTarget);
			}
		}
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send(xhttp_data);
	}
	
	var job_id = $('#updraftplus_ajax_restore_job_id').val();
	updraft_restore_command(job_id);
});

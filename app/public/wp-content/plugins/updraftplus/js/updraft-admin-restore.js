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
				$('#updraftplus_ajax_restore_output').append(responseText);
				previous_data_length = response.currentTarget.responseText.length;
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
function ajaxRequest() {
	$.ajax({
		type : 'post',
		url : ajaxRequest_data.ajax_url,
		data : {
			action: 'ajaxRequest',
			_ajax_nonce: ajaxRequest_data.nonce,
		},
		success: function(result) {
			$("body").prepend(result);
		}
	});
}

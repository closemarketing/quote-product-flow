
jQuery(document).ready(function($) {
	
	$('.generate-pbc-pdf').click(function(e) {
		e.preventDefault();
		post_id_pdf = $(this).attr("data-post-id");

		$.ajax({
			type: 'POST',
			url: ajaxAction.url,
			data: {
				action: 'pbc_enquiry_pdf',
				post_id: post_id_pdf,
				nonce: ajaxAction.nonce
			},
			beforeSend: function() { $("#pbc-pdf-"+post_id_pdf+".spinner").addClass("is-active"); },
			complete: function() { $("#pbc-pdf-"+post_id_pdf+".spinner").removeClass("is-active"); },
			success: function(file){
				window.open(file.data);
			},
			error: function(xhr, textStatus, error) {
				console.log(xhr.statusText);
				console.log(textStatus);
				console.log(error);
			}
		});
	});

	$('#bulk-updater-prices').click(function(e) {
		e.preventDefault();

		$.ajax({
			type: 'POST',
			url: ajaxActionPrice.url,
			data: {
				action: 'price_updater',
				percentage: $("#pbc-percentage-price").val(),
				nonce: ajaxActionPrice.nonce
			},
			beforeSend: function() { $("#pbc-price-updater-button.spinner").addClass("is-active"); },
			complete: function() { $("#pbc-price-updater-button.spinner").removeClass("is-active"); },
			success: function(result){
				$(".price-updater-result").html( result.data );
			},
			error: function(error) {
				console.log(error);
			}
		});
	});

});
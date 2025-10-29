jQuery(function($){
	console.log('PBC: JavaScript loaded and ready!');
	console.log('PBC: jQuery version:', $.fn.jquery);
	console.log('PBC: AJAX URL:', typeof PBCAjaxAction !== 'undefined' ? PBCAjaxAction.ajax_url : 'NOT DEFINED');

	// Variation selected.
	$(document).on('click', 'input[type=radio].pbc_variation', function(){
		var cPhase = $('input[name=pbc_current_phase]').val();
		var show_prices = PBCAjaxAction.show_prices;
		$('.phase_descvar .actived').addClass('hidden').removeClass('actived');
		$('.phase_descvar .descvar_' + $(this).val() ).addClass('actived').removeClass('hidden');
		$.ajax({
			url: PBCAjaxAction.ajax_url,  //server script to process data
			type: 'POST',
			data: $('#configurator-form').serialize()+'&current_phase='+cPhase+'&action=variation_selected',
			dataType: "html",
			success: function(response) {
				var resArr = response.split(';;--;;');
				var obj = jQuery.parseJSON(resArr[1]);
				if(obj.type == 'error'){
					$('.product_preview').find('.product_preview_status').html('<div>'+obj.msg+'</div>').show().delay(4000, function(){
						window.setTimeout( function(){
							$('.product_preview').find('.product_preview_status').html('').addClass('hidden');
						}, 1000 );
					});
				} else
					if ( obj.type == 'success' ) {
					$('.product_preview').find('.product_preview_status').addClass('hidden');
					if(obj.url){
						if($('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').length != 0){
							$('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').attr('src',obj.url);
						} else {
							if ( obj.flipped ) {
								var className = 'flipped';
							} else {
								className = '';
							}
							$('.product_preview').find('.image-wrap').append('<img phaseid="'+cPhase+'" class="'+className+'" src="'+obj.url+'" alt="product image"/>').show();
						}
					}
					else
						if($('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').length != 0){
								$('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').remove();
						}
					if(obj.flipped){
						$('.product_preview').find('.image-wrap img').each(function(){
								if(!$(this).hasClass('flipped'))
								$(this).addClass('flipped');
						});
					}
					else{
						$('.product_preview').find('.image-wrap img').each(function(){
								if($(this).hasClass('flipped'))
								$(this).removeClass('flipped');
						});
					}
					if(obj.option || obj.price){
						if($('.variation_selected.phase-'+cPhase).length == 0){
							html_append = '<table><tr class="variation_selected phase-'+cPhase+'"><td class="name">'+obj.option+'</td><td class="price">';
							if ( show_prices !== 'no' ) {
								html_append += obj.price;
							}
							html_append += '</td></tr></table>';
							$('.configurator_summary').append( html_append );
						} else {
								$('.variation_selected.phase-'+cPhase+' td.name').html(obj.option);
							if ( show_prices !== 'no' ) {
								$('.variation_selected.phase-'+cPhase+' td.price').html(obj.price);
							}
						}
					}

				}
			}
		});
	});

	// Price variation selected.
	$(document).on('click', 'select[class=pbc_pricevar]', function(){
		$(this).parent().parent().find('input.pbc_variation').prop("checked", true);
	});

	// Price variation changed.
	$(document).on('change', 'select[class=pbc_pricevar]', function(){
		var cPhase = $('input[name=pbc_current_phase]').val();
		var select_pricevar = $(this).parent().parent().find('input.pbc_variation');
		var show_prices = PBCAjaxAction.show_prices;
		$.ajax({
			url: PBCAjaxAction.ajax_url,  //server script to process data
			type: 'POST',
			data: $('#configurator-form').serialize()+'&current_phase='+cPhase+'&action=variation_selected',
			dataType: "html",
			success: function(response) {
					var resArr = response.split(';;--;;');
					var obj = jQuery.parseJSON(resArr[1]);
					if(obj.type == 'error'){
						$('.product_preview').find('.product_preview_status').html('<div>'+obj.msg+'</div>').show().delay(4000, function(){
							window.setTimeout( function(){
									$('.product_preview').find('.product_preview_status').html('').addClass('hidden');
							}, 1000 );
						});
					}else if(obj.type == 'success'){
						$('.product_preview').find('.product_preview_status').addClass('hidden');
						select_pricevar.prop("checked", true);
						if(obj.url){
							if($('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').length != 0){
									$('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').attr('src',obj.url);
							}else{
									if(obj.flipped)
										var className = 'flipped';
									else className = '';
									$('.product_preview').find('.image-wrap').append('<img phaseid="'+cPhase+'" class="'+className+'" src="'+obj.url+'" alt="product image"/>').show();
							}
						}
						else
							if($('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').length != 0){
									$('.product_preview').find('.image-wrap img[phaseid="'+cPhase+'"]').remove();
							}
						if(obj.flipped){
							$('.product_preview').find('.image-wrap img').each(function(){
									if(!$(this).hasClass('flipped'))
									$(this).addClass('flipped');
							});
						}
						else{
							$('.product_preview').find('.image-wrap img').each(function(){
									if($(this).hasClass('flipped'))
									$(this).removeClass('flipped');
							});
						}
						if(obj.option || obj.price){
							if($('.variation_selected.phase-'+cPhase).length == 0){
								html_price = '';
								if ( show_prices !== 'no' ) {
									html_price = '<td class="price">'+obj.price+'</td>';
								}
								$('.configurator_summary').append('<table><tr class="variation_selected phase-'+cPhase+'"><td class="name">'+obj.option+'</td>'+html_price+'</tr></table>');
							} else {
								$('.variation_selected.phase-'+cPhase+' td.name').html(obj.option);
								if ( show_prices !== 'no' ) {
									$('.variation_selected.phase-'+cPhase+' td.price').html(obj.price);
								}
							}
						}

					}
			}
		});
	});

	// Submit form.
	$(document).on('click', 'button[name=submit]', function(e){
		console.log('PBC: Button clicked');
        var thisButton = $(this);
		var submit_val = $(this).val();
		console.log('PBC: Submit value:', submit_val);
        thisButton.prop('disabled', true);
		var form_id = 'configurator-form';
		e.preventDefault();
		var next_phase = $('input[name=next_phase]').val();
		
		console.log('PBC: Form ID:', form_id);
		console.log('PBC: AJAX URL:', PBCAjaxAction.ajax_url);
		console.log('PBC: Next phase:', next_phase);

		var formData = $('#'+form_id).serialize()+'&current_phase='+$('input[name=pbc_current_phase]').val()+'&submit='+submit_val+'&action=configurator_submit&pbc_template='+$('#configurator-form').data('template');
		console.log('PBC: Form data length:', formData.length);

		$.ajax({
			url: PBCAjaxAction.ajax_url,  //server script to process data
			type: 'POST',
			data: formData,
			dataType: "html",
			beforeSend: function() {
				console.log('PBC: AJAX request starting...');
			},
			success: function(response) {
				console.log('PBC: AJAX success! Response length:', response.length);
                thisButton.prop('disabled', false);
				
				// Extract PDF URL from response if generating PDF.
				if (submit_val === 'generate_pdf' || submit_val === 'email_send') {
					console.log('PBC: Looking for PDF URL in response...');
					var scriptMatch = response.match(/<script[^>]*>window\.open\(['"]([^'"]+)['"]/);
					console.log('PBC: Script match:', scriptMatch);
					if (scriptMatch && scriptMatch[1]) {
						console.log('PBC: Opening PDF URL:', scriptMatch[1]);
						// Open PDF in new window.
						window.open(scriptMatch[1], '_blank');
					} else {
						console.log('PBC: No PDF URL found in response');
						console.log('PBC: Response preview:', response.substring(0, 500));
					}
				}
				
				$('.page-configurator').html(response);
				if (
					next_phase != 'calculate' &&
					(submit_val == 'prev' || submit_val == 'next') && 
					( $(document).find('input.pbc_variation').length == 0 && $(document).find('select.pbc_variation option').length == 0 )
				)
				{
					$(document).find('button[name=submit][value='+submit_val+']').trigger('click');
				}else{
					$(document).find('.status_loader.phase_detail_loader').html('').addClass('hidden');
					//$('.page-configurator').html(response);
					if($(document).find('.result_submit_action').length > 0){
						$(document).find('.result_submit_action').show().delay(3000).fadeOut(400);
					}
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error('PBC: AJAX Error!');
				console.error('PBC: Status:', textStatus);
				console.error('PBC: Error:', errorThrown);
				console.error('PBC: Status Code:', jqXHR.status);
				console.error('PBC: Response Text:', jqXHR.responseText);
				thisButton.prop('disabled', false);
				alert('Error en la petición AJAX: ' + textStatus + '\nCódigo: ' + jqXHR.status + '\nPor favor, abre la consola del navegador (F12) para más detalles.');
			}
		});
	});

	// Login form submit.
	$(document).on('submit', '#configurator_login_form', function(e){
		e.preventDefault();
		var form_id = 'configurator_login_form';

		$.ajax({
			url: PBCAjaxAction.ajax_url,  //server script to process data
			type: 'POST',
			data: $('#'+form_id).serialize()+'&current_phase='+$('input[name=pbc_current_phase]').val()+'&action=configurator_login',
			dataType: "html",
			success: function(response) {
					var arr = response.split(';;-;;');
					if(arr[1]=='error'){
						$(document).find('.status_loader.phase_detail_loader').html('').addClass('hidden');
						$('#'+form_id).find('.message').html(arr[2]).show().delay(3000).fadeOut(400);
					}else{
						location.reload(true);
						$('.page-configurator').html(response);
						$(document).find('.status_loader.phase_detail_loader').html('').addClass('hidden');
					}
			}
		});
	});
});
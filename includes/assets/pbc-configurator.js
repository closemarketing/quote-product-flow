jQuery(function($){
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
        var thisButton = $(this);
		var submit_val = $(this).val();
        thisButton.prop('disabled', true);
		var form_id = 'configurator-form';
		e.preventDefault();
		var next_phase = $('input[name=next_phase]').val();

		var formData = $('#'+form_id).serialize()+'&current_phase='+$('input[name=pbc_current_phase]').val()+'&submit='+submit_val+'&action=configurator_submit&pbc_template='+$('#configurator-form').data('template')+'&nonce='+PBCAjaxAction.nonce;

		console.log('PBC Debug: Submit button clicked:', submit_val);
		console.log('PBC Debug: Next phase:', next_phase);
		console.log('PBC Debug: Current phase:', $('input[name=pbc_current_phase]').val());

		$.ajax({
			url: PBCAjaxAction.ajax_url,  //server script to process data
			type: 'POST',
			data: formData,
			dataType: "html",
			success: function(response) {
                thisButton.prop('disabled', false);
			
			console.log('PBC Debug: Response length:', response.length);
			console.log('PBC Debug: Response preview:', response.substring(0, 500));
			
			// Note: PDF opens automatically via script tag in response.
			// No need to manually open it here as it would create duplicate tabs.
			
			$('.page-configurator').html(response);
			
			// Wait for DOM to be ready before checking for variations.
			setTimeout(function() {
				var hasVariations = $('.page-configurator').find('input.pbc_variation').length > 0 || 
				                     $('.page-configurator').find('select.pbc_variation option').length > 0;
				var newNextPhase = $('.page-configurator').find('input[name=next_phase]').val();
				
				console.log('PBC Debug: Has variations:', hasVariations);
				console.log('PBC Debug: New next phase:', newNextPhase);
				
				if (
					newNextPhase && 
					newNextPhase != 'calculate' &&
					(submit_val == 'prev' || submit_val == 'next') && 
					!hasVariations
				)
				{
					console.log('PBC Debug: Auto-skipping empty step');
					$('.page-configurator').find('button[name=submit][value='+submit_val+']').trigger('click');
				} else {
					$('.page-configurator').find('.status_loader.phase_detail_loader').html('').addClass('hidden');
					if($('.page-configurator').find('.result_submit_action').length > 0){
						$('.page-configurator').find('.result_submit_action').show().delay(3000).fadeOut(400);
					}
				}
			}, 100);
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

	// Share via WhatsApp.
	$(document).on('click', '#pbc-share-whatsapp', function(e){
		e.preventDefault();
		var sessionKey = $('input[name=pbc_session_key]').val();
		var parentPhase = $('input[name=pbc_parent_phase]').val();
		var template = $('#configurator-form').data('template');
		var currentUrl = window.location.href.split('#')[0];

		$.ajax({
			url: PBCAjaxAction.ajax_url,
			type: 'POST',
			data: {
				action: 'get_shareable_config',
				session_key: sessionKey,
				parent_phase: parentPhase,
				template: template,
				current_url: currentUrl
			},
			success: function(response) {
				if (response.success && response.data.url) {
					console.log('PBC: Generated URL:', response.data.url);
					if (response.data.variations_count) {
						console.log('PBC: Variations in URL:', response.data.variations_count);
					}
					var message = 'Mira esta configuración: ' + response.data.url;
					var text = encodeURIComponent(message);
					var whatsappUrl = 'https://wa.me/?text=' + text;
					window.open(whatsappUrl, '_blank');
				} else {
					var errMsg = (response.data && response.data.message) ? response.data.message : 'No se pudo generar el enlace de configuración.';
					alert(errMsg);
				}
			},
			error: function() {
				alert('Error al procesar la solicitud');
			}
		});
	});

	// Share via Email.
	$(document).on('click', '#pbc-share-email', function(e){
		e.preventDefault();
		e.stopPropagation();

		var $button = $(this);
		if ($button.hasClass('processing')) {
			return false;
		}

		// Ask for recipient email.
		var recipientEmail = prompt('Introduce el email del destinatario:');

		// Validate email.
		if (!recipientEmail) {
			return false; // User cancelled.
		}

		recipientEmail = recipientEmail.trim();
		var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

		if (!emailPattern.test(recipientEmail)) {
			alert('Por favor, introduce un email válido.');
			return false;
		}

		$button.addClass('processing');

		var sessionKey = $('input[name=pbc_session_key]').val();
		var parentPhase = $('input[name=pbc_parent_phase]').val();
		var template = $('#configurator-form').data('template');
		var currentUrl = window.location.href.split('#')[0];

		$.ajax({
			url: PBCAjaxAction.ajax_url,
			type: 'POST',
			data: {
				action: 'send_config_email',
				session_key: sessionKey,
				parent_phase: parentPhase,
				template: template,
				current_url: currentUrl,
				recipient_email: recipientEmail
			},
			success: function(response) {
				$button.removeClass('processing');
				if (response.success) {
					alert('✓ Email enviado correctamente a ' + recipientEmail);
				} else {
					var errMsg = (response.data && response.data.message) ? response.data.message : 'No se pudo enviar el email.';
					alert(errMsg);
				}
			},
			error: function() {
				$button.removeClass('processing');
				alert('Error al procesar la solicitud');
			}
		});

		return false;
	});

	// Restart process button.
	$(document).on('click', '#pbc-restart-process', function(e){
		e.preventDefault();
		
		if (!confirm('¿Estás seguro de que quieres reiniciar el proceso? Se perderán todas las configuraciones actuales.')) {
			return;
		}
		
		var button = $(this);
		var originalText = button.text();
		
		// Disable button.
		button.prop('disabled', true).text('Reiniciando...');
		
		$.ajax({
			url: PBCAjaxAction.ajax_url,
			type: 'POST',
			data: {
				action: 'pbc_restart_process',
				nonce: PBCAjaxAction.nonce
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					// Reload the page to start from step 1.
					window.location.reload();
				} else {
					alert(response.data.message || 'Error al reiniciar el proceso.');
					button.prop('disabled', false).text(originalText);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error('PBC Restart Error:', {
					status: jqXHR.status,
					statusText: jqXHR.statusText,
					textStatus: textStatus,
					errorThrown: errorThrown
				});
				alert('Error al reiniciar el proceso.');
				button.prop('disabled', false).text(originalText);
			}
		});
	});
});

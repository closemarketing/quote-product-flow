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
		var submit_val = $(this).val();
		var form_id = 'configurator-form';
		e.preventDefault();
		var next_phase = $('input[name=next_phase]').val();

		$.ajax({
			url: PBCAjaxAction.ajax_url,  //server script to process data
			type: 'POST',
			data: $('#'+form_id).serialize()+'&current_phase='+$('input[name=pbc_current_phase]').val()+'&submit='+submit_val+'&action=configurator_submit&pbc_template='+$('#configurator-form').data('template'),
			dataType: "html",
			success: function(response) {
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
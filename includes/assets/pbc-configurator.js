jQuery(function($){
	// Function to toggle custom input fields based on selected variation.
	function toggleCustomInputs() {
		// Hide all custom input wrappers first.
		$('.pbc-custom-input-wrapper').hide();
		
		// Show custom input for selected variation.
		var selectedVariationId = $('input[type=radio].pbc_variation:checked').val();
		if (selectedVariationId) {
			// Find wrapper that contains this variation ID in its data attribute.
			$('.pbc-custom-input-wrapper[data-variation-ids]').each(function() {
				var variationIds = $(this).attr('data-variation-ids').split(',');
				if ($.inArray(selectedVariationId, variationIds) !== -1) {
					var $inputWrapper = $(this);
					$inputWrapper.show();
					// Update the name attribute to match the selected variation.
					var currentStep = $('input[name=pbc_current_phase]').val();
					$inputWrapper.find('textarea').attr('name', 'pbc_custom_input[' + currentStep + '][' + selectedVariationId + ']');
				}
			});
		}
	}

	// Function to initialize number input controls.
	function initNumberInputs() {
		// Remove any existing handlers to prevent duplicates.
		$(document).off('click', '.pbc-number-decrease');
		$(document).off('click', '.pbc-number-increase');
		
		// Handle decrease button click.
		$(document).on('click', '.pbc-number-decrease', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var $input = $(this).siblings('.pbc-direct-input-number');
			var currentValue = parseInt($input.val(), 10) || 0;
			var newValue = Math.max(0, currentValue - 1);
			$input.val(newValue);
		});
		
		// Handle increase button click.
		$(document).on('click', '.pbc-number-increase', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var $input = $(this).siblings('.pbc-direct-input-number');
			var currentValue = parseInt($input.val(), 10) || 0;
			var newValue = currentValue + 1;
			$input.val(newValue);
		});
		
		// Ensure default value is 0 if empty.
		$('.pbc-direct-input-number').each(function() {
			if ($(this).val() === '' || $(this).val() === null || $(this).val() === undefined) {
				$(this).val('0');
			}
		});
	}

	// Toggle custom inputs on page load.
	toggleCustomInputs();
	
	// Initialize number input controls.
	initNumberInputs();

	window.pbcSyncVerticalMultipleCards = function () {
		$('.pbc-multiple-selections .pbc-checkbox-option').each(function () {
			var $lb = $(this);
			$lb.toggleClass('pbc-option-active', $lb.find('.pbc-option-native').prop('checked'));
		});
	};
	window.pbcSyncVerticalMultipleCards();
	$(document).on('change', '.pbc-multiple-selections .pbc-option-native', window.pbcSyncVerticalMultipleCards);

	// Variation selected (radio buttons - single selection).
	$(document).on('click', 'input[type=radio].pbc_variation', function(){
		var cPhase = $('input[name=pbc_current_phase]').val();
		
		// Show recommendation button if we're on step 1 and a variation is selected.
		if (parseInt(cPhase, 10) === 1) {
			$('.recommendation').show();
		}
		
		// Toggle custom input fields.
		toggleCustomInputs();
		
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

	// Variation selected (checkboxes - multiple selection).
	$(document).on('change', 'input[type=checkbox].pbc_variation_multiple', function(){
		var cPhase = $('input[name=pbc_current_phase]').val();
		var show_prices = PBCAjaxAction.show_prices;
		
		// Show recommendation button if we're on step 1 and at least one variation is selected.
		if (parseInt(cPhase, 10) === 1) {
			var hasSelection = $('input[type=checkbox].pbc_variation_multiple:checked').length > 0;
			if (hasSelection) {
				$('.recommendation').show();
			} else {
				$('.recommendation').hide();
			}
		}
		
		// Update description visibility for checked variations.
		var variationId = $(this).val();
		if ($(this).is(':checked')) {
			$('.phase_descvar .descvar_' + variationId).addClass('actived').removeClass('hidden');
		} else {
			$('.phase_descvar .descvar_' + variationId).removeClass('actived').addClass('hidden');
		}
		
		// Update summary via AJAX.
		$.ajax({
			url: PBCAjaxAction.ajax_url,
			type: 'POST',
			data: $('#configurator-form').serialize() + '&current_phase=' + cPhase + '&action=variation_selected',
			dataType: "html",
			success: function(response) {
				var resArr = response.split(';;--;;');
				var obj = jQuery.parseJSON(resArr[1]);
				if (obj.type == 'error') {
					$('.product_preview').find('.product_preview_status').html('<div>' + obj.msg + '</div>').show().delay(4000, function(){
						window.setTimeout(function(){
							$('.product_preview').find('.product_preview_status').html('').addClass('hidden');
						}, 1000);
					});
				} else if (obj.type == 'success') {
					$('.product_preview').find('.product_preview_status').addClass('hidden');
					
					// Update image if provided.
					if (obj.url) {
						if ($('.product_preview').find('.image-wrap img[phaseid="' + cPhase + '"]').length != 0) {
							$('.product_preview').find('.image-wrap img[phaseid="' + cPhase + '"]').attr('src', obj.url);
						} else {
							var className = obj.flipped ? 'flipped' : '';
							$('.product_preview').find('.image-wrap').append('<img phaseid="' + cPhase + '" class="' + className + '" src="' + obj.url + '" alt="product image"/>').show();
						}
					} else {
						if ($('.product_preview').find('.image-wrap img[phaseid="' + cPhase + '"]').length != 0) {
							$('.product_preview').find('.image-wrap img[phaseid="' + cPhase + '"]').remove();
						}
					}
					
					// Handle image flipping.
					if (obj.flipped) {
						$('.product_preview').find('.image-wrap img').each(function(){
							if (!$(this).hasClass('flipped')) {
								$(this).addClass('flipped');
							}
						});
					} else {
						$('.product_preview').find('.image-wrap img').each(function(){
							if ($(this).hasClass('flipped')) {
								$(this).removeClass('flipped');
							}
						});
					}
					
					// Update summary table with multiple selections.
					if (obj.option || obj.price) {
						if ($('.variation_selected.phase-' + cPhase).length == 0) {
							var html_append = '<table><tr class="variation_selected phase-' + cPhase + '"><td class="name">' + obj.option + '</td><td class="price">';
							if (show_prices !== 'no') {
								html_append += obj.price;
							}
							html_append += '</td></tr></table>';
							$('.configurator_summary').append(html_append);
						} else {
							$('.variation_selected.phase-' + cPhase + ' td.name').html(obj.option);
							if (show_prices !== 'no') {
								$('.variation_selected.phase-' + cPhase + ' td.price').html(obj.price);
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

	// Load recommendation.
	$(document).on('click', '#pbc-load-recommendation', function(e){
		e.preventDefault();
		var button = $(this);
		var originalText = button.text();
		
		// Get selected first variation (if any).
		var $firstVariationRadio = $('input[type=radio].pbc_variation:checked');
		
		// Check if first variation is selected.
		if ($firstVariationRadio.length === 0) {
			alert('Por favor, selecciona una opción en la primera fase antes de cargar la recomendación.');
			return;
		}
		
		var firstVariation = $firstVariationRadio.val();
		
		// Disable button and show loading state.
		button.prop('disabled', true).text('Cargando...');
		
		// Get parent phase.
		var parentPhase = $('input[name="pbc_parent_phase"]').val();
		
		$.ajax({
			url: PBCAjaxAction.ajax_url,
			type: 'POST',
			data: {
				action: 'pbc_get_recommendations',
				parent_phase: parentPhase,
				first_variation: firstVariation,
				nonce: PBCAjaxAction.recommendation_nonce
			},
			dataType: 'json',
			success: function(response) {
				if (response.success && response.data.recommendations) {
					var recommendations = response.data.recommendations;
					
					// Get current step from the form.
					var currentStep = parseInt($('input[name="pbc_current_phase"]').val(), 10) || 1;
					
					// Start the process by clicking next and then applying recommendations.
					startRecommendationProcess(recommendations, currentStep, button, originalText);
					
				} else {
					// Show error message.
					var errorMessage = response.data && response.data.message 
						? response.data.message 
						: 'Esta fase no tiene configuración recomendada.';
					
					// Replace \n with actual line breaks for better display.
					errorMessage = errorMessage.replace(/\\n/g, '\n');
					
					alert(errorMessage);
					
					// Re-enable button.
					button.prop('disabled', false).text(originalText);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error('PBC Recommendation AJAX Error:', {
					status: jqXHR.status,
					statusText: jqXHR.statusText,
					responseText: jqXHR.responseText,
					textStatus: textStatus,
					errorThrown: errorThrown
				});
				
				// Try to show more specific error.
				var errorMsg = 'Error al cargar las recomendaciones.';
				if (jqXHR.responseText) {
					try {
						var responseData = JSON.parse(jqXHR.responseText);
						if (responseData.data && responseData.data.message) {
							errorMsg = responseData.data.message;
						}
					} catch(e) {
						// If it's not JSON, check if it's a PHP error.
						if (jqXHR.responseText.indexOf('Fatal error') !== -1 || 
						    jqXHR.responseText.indexOf('Warning') !== -1) {
							errorMsg += '\n\nError de servidor. Por favor, revisa la consola del navegador para más detalles.';
						}
					}
				}
				
				alert(errorMsg);
				
				// Re-enable button.
				button.prop('disabled', false).text(originalText);
			}
		});
	});
	
	// Start recommendation process by advancing to next step first.
	function startRecommendationProcess(recommendations, currentStep, button, originalText) {
		// Get all recommendation steps sorted.
		var allSteps = Object.keys(recommendations).map(function(k) {
			return parseInt(k, 10);
		}).sort(function(a, b) {
			return a - b;
		});
		
		// Find the next step that has a recommendation.
		var nextStep = null;
		for (var i = 0; i < allSteps.length; i++) {
			if (allSteps[i] > currentStep) {
				nextStep = allSteps[i];
				break;
			}
		}
		
		if (!nextStep) {
			if (button && originalText) {
				button.prop('disabled', false).text(originalText);
			}
			return;
		}
		
		// Click next button to advance.
		var $nextButton = $('button[name=submit][value=next]');
		
		if ($nextButton.length > 0) {
			// Set up a listener for when the next step loads.
			// We'll wait for the AJAX form submission to complete.
			var checkInterval = setInterval(function() {
				var newStep = parseInt($('input[name="pbc_current_phase"]').val(), 10);
				
				if (newStep === nextStep) {
					// We reached the target step with recommendation.
					clearInterval(checkInterval);
					
					// Now apply the recommendation for this step.
					var recData = recommendations[nextStep];
					applyRecommendationForCurrentStep(recData, recommendations, nextStep, allSteps, button, originalText);
				} else if (newStep > currentStep && newStep < nextStep) {
					// We advanced to an intermediate step without recommendation.
					// Keep clicking next.
					currentStep = newStep; // Update current step.
					
					var $nextBtn = $('button[name=submit][value=next]');
					if ($nextBtn.length > 0) {
						setTimeout(function() {
							$nextBtn.trigger('click');
						}, 400);
					} else {
						clearInterval(checkInterval);
						if (button && originalText) {
							button.prop('disabled', false).text(originalText);
						}
					}
				} else if (newStep > nextStep) {
					// We somehow skipped the target step.
					clearInterval(checkInterval);
					// Continue with next recommendation.
					startRecommendationProcess(recommendations, newStep, button, originalText);
				}
			}, 200);
			
			// Click next button.
			$nextButton.trigger('click');
			
			// Safety timeout: if step doesn't change properly in 10 seconds, stop.
			setTimeout(function() {
				clearInterval(checkInterval);
				var finalStep = parseInt($('input[name="pbc_current_phase"]').val(), 10);
				if (finalStep !== nextStep) {
					if (button && originalText) {
						button.prop('disabled', false).text(originalText);
					}
				}
			}, 10000);
		} else {
			if (button && originalText) {
				button.prop('disabled', false).text(originalText);
			}
		}
	}
	
	// Apply recommendation for the current step and continue to next.
	function applyRecommendationForCurrentStep(recData, recommendations, currentStep, allSteps, button, originalText) {
		if (!recData) {
			// Continue to next step anyway.
			startRecommendationProcess(recommendations, currentStep, button, originalText);
			return;
		}
		
		var variationId = recData.variation_id;
		var priceVar = recData.price_var;
		
		// Select the radio button.
		var radioSelector = 'input[type=radio].pbc_variation[value="' + variationId + '"]';
		var $radio = $(radioSelector);
		
		if ($radio.length > 0) {
			$radio.prop('checked', true);
			
			// If there's a price variation, select it.
			if (priceVar) {
				var priceVarSelector = 'select.pbc_pricevar[name="pbc_pricevar_' + variationId + '"]';
				$(priceVarSelector).val(priceVar);
			}
			
			// Trigger click to update the preview.
			$radio.trigger('click');
			
			// Toggle custom inputs after selecting variation.
			setTimeout(function() {
				toggleCustomInputs();
			}, 100);
			
			// Check if this is the last recommendation step.
			var isLastStep = (currentStep >= Math.max.apply(null, allSteps));
			
			if (isLastStep) {
				// Last recommendation applied, now click "Calculate" button.
				setTimeout(function() {
					var $nextButton = $('button[name=submit][value=next]');
					
					if ($nextButton.length > 0) {
						// Click next/calculate button to go to summary.
						$nextButton.trigger('click');
					} else {
						// If no next button found, just re-enable recommendation button.
						if (button && originalText) {
							button.prop('disabled', false).text(originalText);
						}
					}
				}, 500);
			} else {
				// Wait a bit for the UI to update, then continue to next step.
				setTimeout(function() {
					startRecommendationProcess(recommendations, currentStep, button, originalText);
				}, 600);
			}
		} else {
			// Continue to next step anyway.
			setTimeout(function() {
				startRecommendationProcess(recommendations, currentStep, button, originalText);
			}, 300);
		}
	}
	
	// Recursive function to apply recommendations and advance through steps (OLD - NOT USED).
	function applyRecommendationsRecursively(recommendations, currentStep, button, originalText) {
		// Get recommendation for current step.
		var recData = recommendations[currentStep];
		
		if (!recData) {
			// No more recommendations, we're done.
			// Re-enable button if provided.
			if (button && originalText) {
				button.prop('disabled', false).text(originalText);
			}
			return;
		}
		
		var variationId = recData.variation_id;
		var priceVar = recData.price_var;
		
		// Select the radio button.
		var radioSelector = 'input[type=radio].pbc_variation[value="' + variationId + '"]';
		var $radio = $(radioSelector);
		
		if ($radio.length > 0) {
			$radio.prop('checked', true);
			
			// If there's a price variation, select it.
			if (priceVar) {
				var priceVarSelector = 'select.pbc_pricevar[name="pbc_pricevar_' + variationId + '"]';
				$(priceVarSelector).val(priceVar);
			}
			
			// Trigger click to update the preview.
			$radio.trigger('click');
			
			// Check if there are more recommendations after this one.
			var allSteps = Object.keys(recommendations).map(function(key) {
				return parseInt(key, 10);
			}).sort(function(a, b) {
				return a - b;
			});
			
			var maxStep = Math.max.apply(null, allSteps);
			var isLastStep = (currentStep >= maxStep);
			
			// Wait a bit for the UI to update, then click next button.
			setTimeout(function() {
				var $nextButton = $('button[name=submit][value=next]');
				
				if ($nextButton.length > 0 && !isLastStep) {
					// Click next and continue with next recommendation.
					$nextButton.trigger('click');
					
					// Wait for next step to load, then apply next recommendation.
					setTimeout(function() {
						applyRecommendationsRecursively(recommendations, currentStep + 1, button, originalText);
					}, 400);
				} else {
					// Last step or no next button, we're done.
					// Re-enable button.
					if (button && originalText) {
						button.prop('disabled', false).text(originalText);
					}
				}
			}, 300);
		} else {
			// Try next recommendation anyway.
			setTimeout(function() {
				applyRecommendationsRecursively(recommendations, currentStep + 1, button, originalText);
			}, 100);
		}
	}

	// Submit form.
	$(document).on('click', 'button[name=submit]', function(e){
        var thisButton = $(this);
		var submit_val = $(this).val();
        thisButton.prop('disabled', true);
		var form_id = 'configurator-form';
		e.preventDefault();
		var next_phase = $('input[name=next_phase]').val();

		var formData = $('#'+form_id).serialize()+'&current_phase='+$('input[name=pbc_current_phase]').val()+'&submit='+submit_val+'&action=configurator_submit&pbc_template='+$('#configurator-form').data('template')+'&nonce='+PBCAjaxAction.nonce;

		if (!thisButton.data('pbc-programmatic-next')) {
			window.pbcAutoSkipCount = 0;
		}
		thisButton.removeData('pbc-programmatic-next');

		$.ajax({
			url: PBCAjaxAction.ajax_url,  //server script to process data
			type: 'POST',
			data: formData,
			dataType: "html",
			success: function(response) {
                thisButton.prop('disabled', false);

			$('.page-configurator').html(response);

			setTimeout(function() {
				var $pc = $('.page-configurator');
				var hasVariations = $pc.find('input.pbc_variation').length > 0 ||
					$pc.find('input.pbc_variation_multiple').length > 0 ||
					$pc.find('select.pbc_variation option').length > 0;
				var hasQuestions = $pc.find('.pbc_question_input').length > 0;
				var hasDirectInput = $pc.find('.pbc-direct-input-wrapper').length > 0;
				var hasPhaseNote = $pc.find('.phase_note_top').length > 0 && $.trim($pc.find('.phase_note_top').text()) !== '';
				var newNextPhase = $pc.find('input[name=next_phase]').val();

				toggleCustomInputs();
				initNumberInputs();
				if (typeof window.pbcSyncVerticalMultipleCards === 'function') {
					window.pbcSyncVerticalMultipleCards();
				}

				var canAutoSkip = newNextPhase &&
					newNextPhase !== 'calculate' &&
					(submit_val === 'prev' || submit_val === 'next') &&
					!hasVariations &&
					!hasQuestions &&
					!hasDirectInput &&
					!hasPhaseNote &&
					(typeof window.pbcAutoSkipCount === 'number' && window.pbcAutoSkipCount < 30);

				if (canAutoSkip) {
					window.pbcAutoSkipCount = (window.pbcAutoSkipCount || 0) + 1;
					var $go = $pc.find('button[name=submit][value=' + submit_val + ']');
					if ($go.length) {
						$go.data('pbc-programmatic-next', 1);
						$go.trigger('click');
					} else {
						$pc.find('.status_loader.phase_detail_loader').html('').addClass('hidden');
					}
				} else {
					window.pbcAutoSkipCount = 0;
					$pc.find('.status_loader.phase_detail_loader').html('').addClass('hidden');
					if ($pc.find('.result_submit_action').length > 0) {
						$pc.find('.result_submit_action').show().delay(3000).fadeOut(400);
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

	// Share via WhatsApp: generates PDF and shares its download link (WhatsApp cannot attach files from browser).
	$(document).on('click', '#pbc-share-whatsapp', function(e){
		e.preventDefault();
		var $btn = $(this);
		if ($btn.hasClass('processing')) {
			return;
		}
		var sessionKey = $('input[name=pbc_session_key]').val();
		var parentPhase = $('input[name=pbc_parent_phase]').val();
		var shareData = {
			action: 'pbc_share_budget_pdf',
			nonce: PBCAjaxAction.nonce,
			session_key: sessionKey,
			parent_phase: parentPhase
		};
		$('#configurator-form').find('.email_submit_fields input[name], .email_submit_fields textarea[name]').each(function() {
			var n = $(this).attr('name');
			if (n) {
				shareData[n] = $(this).val();
			}
		});

		$btn.addClass('processing').prop('disabled', true);

		$.ajax({
			url: PBCAjaxAction.ajax_url,
			type: 'POST',
			data: shareData,
			dataType: 'json',
			success: function(response) {
				$btn.removeClass('processing').prop('disabled', false);
				if (response.success && response.data && response.data.whatsapp_text) {
					var text = encodeURIComponent(response.data.whatsapp_text);
					window.open('https://wa.me/?text=' + text, '_blank');
				} else {
					var errMsg = (response.data && response.data.message) ? response.data.message : 'No se pudo generar el PDF del presupuesto.';
					alert(errMsg);
				}
			},
			error: function() {
				$btn.removeClass('processing').prop('disabled', false);
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
		var emailData = {
			action: 'send_config_email',
			nonce: PBCAjaxAction.nonce,
			session_key: sessionKey,
			parent_phase: parentPhase,
			recipient_email: recipientEmail
		};
		$('#configurator-form').find('.email_submit_fields input[name], .email_submit_fields textarea[name]').each(function() {
			var n = $(this).attr('name');
			if (n) {
				emailData[n] = $(this).val();
			}
		});

		$.ajax({
			url: PBCAjaxAction.ajax_url,
			type: 'POST',
			data: emailData,
			dataType: 'json',
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

	// Prevent question inputs from submitting form on Enter or arrow keys.
	$(document).on('keydown', '.pbc_question_input', function(e) {
		// Prevent Enter key from submitting the form.
		if (e.keyCode === 13 || e.which === 13) {
			e.preventDefault();
			return false;
		}
		
		// For number inputs, arrow keys should only change value, not trigger submit.
		if ($(this).attr('type') === 'number') {
			// Arrow up (38) or arrow down (40).
			if (e.keyCode === 38 || e.keyCode === 40) {
				// Let the default behavior happen (increment/decrement).
				// But prevent any bubbling that might trigger form submit.
				e.stopPropagation();
			}
		}
	});

	// Prevent blur/change events on question inputs from triggering anything.
	$(document).on('change blur', '.pbc_question_input', function(e) {
		e.stopPropagation();
	});

	// Prevent clicking inside question input from triggering anything.
	$(document).on('click focus', '.pbc_question_input', function(e) {
		e.stopPropagation();
	});

	// Question validation - ensure questions are answered before proceeding.
	$(document).on('submit', '#configurator-form', function(e) {
		// Only validate on "next" button click.
		var submitType = $(document.activeElement).attr('value');
		if (submitType !== 'next') {
			return true;
		}
		
		// Check if there are any question inputs in the current phase.
		var questionInputs = $('.pbc_question_input');
		
		if (questionInputs.length > 0) {
			var allAnswered = true;
			var missingRequired = [];
			
			questionInputs.each(function() {
				var $input = $(this);
				var isRequired = $input.prop('required') || $input.attr('required') === 'required';
				var value = $.trim($input.val());
				var label = $input.closest('li, .variation-question-item').find('.variation-question-label').text().trim();
				
				if (!label || label === '') {
					// Try to get from closest label element.
					var $parentLabel = $input.closest('label');
					if ($parentLabel.length) {
						label = $parentLabel.clone().children().remove().end().text().trim();
					}
				}
				
				if (isRequired && value === '') {
					allAnswered = false;
					missingRequired.push(label || 'Campo requerido');
					// Add visual feedback.
					$input.addClass('error-field');
				} else {
					$input.removeClass('error-field');
				}
			});
			
			if (!allAnswered) {
				e.preventDefault();
				e.stopPropagation();
				alert('Por favor, responde todas las preguntas requeridas:\n- ' + missingRequired.join('\n- '));
				// Focus on first empty required field.
				$('.pbc_question_input.error-field').first().focus();
				return false;
			}
		}
		
		// Check if there are any non-question variations that need selection.
		var normalVariations = $('input[type=radio].pbc_variation');
		if (normalVariations.length > 0) {
			var questionInputsCount = questionInputs.length;
			// Only validate variations if there are no questions or if questions are optional.
			if (questionInputsCount === 0) {
				var anySelected = normalVariations.filter(':checked').length > 0;
				if (!anySelected) {
					e.preventDefault();
					e.stopPropagation();
					alert('Por favor, selecciona una opción antes de continuar.');
					return false;
				}
			}
		}
	});

	// Auto-focus first question input.
	$(document).ready(function() {
		var firstQuestion = $('.pbc_question_input:first');
		if (firstQuestion.length > 0) {
			setTimeout(function() {
				firstQuestion.focus();
			}, 300);
		}
	});

	// Global variable to track target step for navigation.
	window.pbcNavigationTarget = null;

	// Handle navigation steps clicks.
	$(document).on('click', '.configurator_steps.clickable', function(e){
		e.preventDefault();
		e.stopPropagation();
		
		var targetStep = parseInt($(this).data('step'), 10);
		var currentStep = parseInt($('input[name=pbc_current_phase]').val(), 10);
		
		// Only allow navigation to previous steps.
		if (targetStep >= currentStep) {
			return;
		}
		
		// Set the target step globally.
		window.pbcNavigationTarget = targetStep;
		
		// Start navigation by clicking prev button.
		var $prevButton = $('button[name=submit][value=prev]');
		if ($prevButton.length > 0) {
			$prevButton.trigger('click');
		}
	});
	
	// Override the submit button handler to check if we need to continue navigation.
	var originalSubmitHandler = $(document).find('button[name=submit]');
	
	// Intercept the AJAX success to check navigation target.
	$(document).ajaxSuccess(function(event, xhr, settings) {
		// Only handle configurator submit actions.
		if (settings.data && settings.data.indexOf('action=configurator_submit') !== -1) {
			// Wait a bit for the DOM to update.
			setTimeout(function() {
				checkNavigationTarget();
			}, 150);
		}
	});
	
	// Function to check if we reached the target step.
	function checkNavigationTarget() {
		if (window.pbcNavigationTarget === null) {
			return;
		}
		
		var currentStep = parseInt($('input[name=pbc_current_phase]').val(), 10);
		
		// Check if we reached the target.
		if (currentStep === window.pbcNavigationTarget) {
			// We reached the target, clear it.
			window.pbcNavigationTarget = null;
			return;
		}
		
		// Check if we went past the target (shouldn't happen, but just in case).
		if (currentStep < window.pbcNavigationTarget) {
			window.pbcNavigationTarget = null;
			return;
		}
		
		// We still need to go back more.
		if (currentStep > window.pbcNavigationTarget) {
			var $prevButton = $('button[name=submit][value=prev]');
			if ($prevButton.length > 0) {
				// Continue navigating back.
				setTimeout(function() {
					$prevButton.trigger('click');
				}, 100);
			} else {
				// No prev button found, stop.
				window.pbcNavigationTarget = null;
			}
		}
	}
});

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
				nonce: ajaxAction.pdf_nonce
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
			beforeSend: function() { 
				$("#pbc-price-updater-button.spinner").addClass("is-active");
				$("#bulk-updater-prices").prop('disabled', true);
			},
			complete: function() { 
				$("#pbc-price-updater-button.spinner").removeClass("is-active");
				$("#bulk-updater-prices").prop('disabled', false);
			},
			success: function(result){
				$(".price-updater-result").html( result.data );
			},
			error: function(error) {
				console.log(error);
			}
		});
	});

	// Recommendations configuration: Add/Remove recommendations dynamically.
	
	// Toggle recommendation sections (expand/collapse).
	$(document).on('click', '.pbc-recommendation-toggle', function(e) {
		e.preventDefault();
		var $header = $(this);
		var $content = $header.next('.pbc-recommendation-content');
		var $icon = $header.find('.dashicons');
		
		if ($content.is(':visible')) {
			// Collapse.
			$content.slideUp(300);
			$icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
		} else {
			// Expand.
			$content.slideDown(300);
			$icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
		}
	});
	
	// Dynamic filtering of recommendation selects based on dependencies.
	$(document).on('change', '.pbc-rec-select', function() {
		var $changedSelect = $(this);
		
		// Remove warning border when user selects something.
		if ($changedSelect.val()) {
			$changedSelect.css('border', '');
		}
		
		var $group = $changedSelect.closest('.recommendation-group');
		var firstVarId = $group.data('var-id');
		var changedStep = parseInt($changedSelect.data('phase-step'), 10);
		
		// Get all selects in this group.
		var $allSelects = $group.find('.pbc-rec-select');
		
		// Build array of selected variations by step.
		var selectedByStep = {};
		selectedByStep[0] = firstVarId; // First variation.
		
		$allSelects.each(function() {
			var $sel = $(this);
			var step = parseInt($sel.data('phase-step'), 10);
			var value = $sel.val();
			if (value) {
				selectedByStep[step - 1] = parseInt(value, 10);
			}
		});
		
		var clearedInvalidSelections = false;
		
		// Update all subsequent selects.
		$allSelects.each(function() {
			var $select = $(this);
			var step = parseInt($select.data('phase-step'), 10);
			
			// Only update selects after the changed one.
			if (step <= changedStep) {
				return;
			}
			
			// Filter options based on dependencies.
			var hiddenCount = 0;
			var totalCount = 0;
			
			$select.find('option').each(function() {
				var $option = $(this);
				
				// Skip empty option.
				if (!$option.val()) {
					return;
				}
				
				totalCount++;
				
				var depends = $option.data('depends');
				
				if (!depends || Object.keys(depends).length === 0) {
					// No dependencies, always show.
					$option.show().prop('disabled', false);
					return;
				}
				
				// Check if dependencies are met.
				var isValid = true;
				for (var depStep in depends) {
					depStep = parseInt(depStep, 10);
					var requiredVars = depends[depStep];
					
					if (selectedByStep[depStep] !== undefined && requiredVars.length > 0) {
						if (requiredVars.indexOf(selectedByStep[depStep]) === -1) {
							isValid = false;
							break;
						}
					}
				}
				
				if (isValid) {
					$option.show().prop('disabled', false);
				} else {
					$option.hide().prop('disabled', true);
					hiddenCount++;
					
					// If this option was selected, clear it.
					if ($option.is(':selected')) {
						$select.val('');
						$select.css('border', '2px solid #ff9800');
						clearedInvalidSelections = true;
					}
				}
			});
			
			// Update info message.
			var $info = $select.next('.pbc-filtered-info');
			if (hiddenCount > 0) {
				$info.text('(' + hiddenCount + ' variaciones ocultas por dependencias)');
			} else {
				$info.text('');
			}
		});
		
		// Show warning if invalid selections were cleared.
		if (clearedInvalidSelections) {
			if (!$group.find('.pbc-invalid-warning').length) {
				$group.find('h3').after(
					'<div class="notice notice-warning inline pbc-invalid-warning" style="margin:10px 0; padding:10px;">' +
					'<strong>⚠️ Atención:</strong> Se han encontrado y limpiado selecciones inválidas debido a dependencias. ' +
					'Por favor, revisa las opciones marcadas en naranja y guarda de nuevo.' +
					'</div>'
				);
			}
		}
	});
	
	// Trigger initial filtering on page load for existing groups.
	function initializeRecommendationFiltering($group) {
		var $selects = $group.find('.pbc-rec-select').sort(function(a, b) {
			return parseInt($(a).data('phase-step'), 10) - parseInt($(b).data('phase-step'), 10);
		});
		
		// Force trigger on all selects in sequence to apply filtering.
		$selects.each(function(index) {
			var $sel = $(this);
			
			// Trigger change to apply filtering.
			setTimeout(function() {
				$sel.trigger('change');
			}, index * 50);
		});
	}
	
	// Wait for DOM to be ready.
	setTimeout(function() {
		$('.recommendation-group').each(function() {
			initializeRecommendationFiltering($(this));
		});
	}, 100);
	
	// Add new recommendation configuration.
	$('#pbc-add-recommendation-btn').on('click', function() {
		var $button = $(this);
		var $select = $('#pbc-add-recommendation-select');
		var varId = $select.val();
		var varName = $select.find('option:selected').data('name');
		
		if (!varId) {
			alert('Por favor, selecciona una opción primero.');
			return;
		}
		
		// Disable button while loading.
		$button.prop('disabled', true).text('Cargando...');
		
		// Get rendered HTML via AJAX (with dependency filtering).
		$.ajax({
			url: ajaxAction.url,
			type: 'POST',
			data: {
				action: 'pbc_render_recommendation_group',
				var_id: varId,
				var_name: varName,
				nonce: ajaxAction.nonce
			},
			success: function(response) {
				if (response.success && response.data.html) {
					// Add to container.
					$('#pbc-recommendations-container').append(response.data.html);
					
					// Trigger initial filtering for the new group.
					var $newGroup = $('#pbc-recommendations-container .recommendation-group').last();
					initializeRecommendationFiltering($newGroup);
					
					// Remove from select dropdown.
					$select.find('option[value="' + varId + '"]').remove();
					$select.val('');
					
					// Show warning about dependencies.
					showDependencyWarning();
				} else {
					alert('Error al cargar la configuración: ' + (response.data.message || 'Error desconocido'));
				}
			},
			error: function() {
				alert('Error de conexión al cargar la configuración.');
			},
			complete: function() {
				// Re-enable button.
				$button.prop('disabled', false).text('Añadir Configuración');
			}
		});
	});
	
	// Remove recommendation configuration.
	$(document).on('click', '.pbc-remove-recommendation', function() {
		var $group = $(this).closest('.recommendation-group');
		var varId = $group.data('var-id');
		var varName = $group.find('h3').text().replace('Recomendaciones para: ', '').trim();
		
		if (confirm('¿Estás seguro de que quieres eliminar esta configuración de recomendaciones?')) {
			// Add back to select dropdown.
			var $select = $('#pbc-add-recommendation-select');
			$select.append('<option value="' + varId + '" data-name="' + varName + '">' + varName + '</option>');
			
			// Sort options alphabetically.
			var options = $select.find('option').not(':first');
			options.sort(function(a, b) {
				return $(a).text().localeCompare($(b).text());
			});
			$select.find('option:first').after(options);
			
			// Remove group.
			$group.remove();
		}
	});
	
	// Show dependency warning when changing recommendations.
	$(document).on('change', '.recommended-variations-table select', function() {
		showDependencyWarning();
	});
	
	function showDependencyWarning() {
		var $warningDiv = $('.recommendation-dependency-warning');
		if ($warningDiv.length === 0) {
			$warningDiv = $('<div class="notice notice-warning recommendation-dependency-warning" style="margin-top:10px;"><p></p></div>');
			$('.pbc-recommendations-manager').after($warningDiv);
		}
		$warningDiv.find('p').html('<strong>Aviso:</strong> Asegúrate de que las variaciones seleccionadas sean compatibles entre sí según sus dependencias. El sistema validará automáticamente al cargar las recomendaciones.');
		$warningDiv.show();
	}

	// ---- PBC repeatable groups (variation metabox) ----

	// Generic add-row for tables (pricegroup, question_depends).
	$(document).on('click', '.pbc-add-row', function() {
		var tableId = $(this).data('table');
		var tplId   = $(this).data('tpl');
		var tpl     = $('#' + tplId).html();
		$('#' + tableId + ' tbody').append(tpl);
	});

	$(document).on('click', '.pbc-remove-row', function() {
		$(this).closest('tr').remove();
	});

	// Depends grid: add/remove.
	$(document).on('click', '#pbc-add-dep-row', function() {
		var tpl = $('#pbc-depends-tpl').html();
		$('#pbc-depends-table').append(tpl);
	});

	$(document).on('click', '.pbc-remove-dep', function() {
		$(this).closest('.pbc-depends-item').remove();
	});

	// Add imgprodgroup row.
	$(document).on('click', '#pbc-add-imgprodgroup-row', function() {
		var tbody   = $('#pbc-imgprodgroup-table tbody');
		var idx     = tbody.find('tr').length;
		var options = tbody.find('tr:first-child td:first-child select').html() || '';
		var row     = '<tr data-index="' + idx + '">' +
			'<td style="width:70%;max-width:0;"><select name="pbc_imgprodgroup[' + idx + '][pbc_depvarimgprod][]" multiple style="width:100%;height:80px;box-sizing:border-box;">' + options + '</select>' +
			'<p class="description">Hold Ctrl/Cmd to select multiple</p></td>' +
			'<td><div class="pbc-image-field">' +
				'<input type="hidden" name="pbc_imgprodgroup[' + idx + '][pbc_imgprod]" class="pbc-imgprod-id" value="" />' +
				'<img src="" style="max-width:80px;max-height:64px;display:none;margin-bottom:4px;" class="pbc-img-preview" />' +
				'<button type="button" class="button pbc-upload-imgprod">Select image</button>' +
				'<button type="button" class="button pbc-remove-imgprod" style="display:none;">Remove</button>' +
			'</div></td>' +
			'<td><button type="button" class="button-link-delete pbc-remove-row">Remove</button></td>' +
		'</tr>';
		tbody.append(row);
	});

	// Icon image upload (single field).
	$(document).on('click', '.pbc-upload-image', function(e) {
		e.preventDefault();
		var targetId = $(this).data('target');
		var $btn     = $(this);
		var frame    = wp.media({
			title:    'Select image',
			button:   { text: 'Use this image' },
			multiple: false
		});
		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			$('#' + targetId).val(attachment.id);
			var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
			$btn.siblings('.pbc-img-preview').attr('src', url).show();
			$btn.siblings('.pbc-remove-image').show();
		});
		frame.open();
	});

	$(document).on('click', '.pbc-remove-image', function(e) {
		e.preventDefault();
		var targetId = $(this).data('target');
		$('#' + targetId).val('');
		$(this).siblings('.pbc-img-preview').attr('src', '').hide();
		$(this).hide();
	});

	// Imgprodgroup image upload.
	$(document).on('click', '.pbc-upload-imgprod', function(e) {
		e.preventDefault();
		var $btn  = $(this);
		var frame = wp.media({
			title:    'Select product image',
			button:   { text: 'Use this image' },
			multiple: false
		});
		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
			$btn.closest('.pbc-image-field').find('.pbc-imgprod-id').val(attachment.id);
			$btn.closest('.pbc-image-field').find('.pbc-img-preview').attr('src', url).show();
			$btn.closest('.pbc-image-field').find('.pbc-remove-imgprod').show();
		});
		frame.open();
	});

	$(document).on('click', '.pbc-remove-imgprod', function(e) {
		e.preventDefault();
		var $field = $(this).closest('.pbc-image-field');
		$field.find('.pbc-imgprod-id').val('');
		$field.find('.pbc-img-preview').attr('src', '').hide();
		$(this).hide();
	});

	// Toggle question fields visibility.
	$('#pbc_is_question').on('change', function() {
		$('.pbc-question-field').toggle(this.checked);
	});

});
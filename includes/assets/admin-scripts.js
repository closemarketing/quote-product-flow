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

	// Helper function to add log message.
	function addExportLog(message, type) {
		type = type || 'info';
		var logContainer = $('#pbc-export-log');
		logContainer.show();
		
		var icon = '●';
		var color = '#2271b1';
		if (type === 'success') {
			icon = '✓';
			color = '#00a32a';
		} else if (type === 'error') {
			icon = '✗';
			color = '#d63638';
		} else if (type === 'warning') {
			icon = '⚠';
			color = '#dba617';
		}
		
		var timestamp = new Date().toLocaleTimeString();
		var logLine = '<div style="margin: 3px 0; color: ' + color + ';">';
		logLine += '<span style="opacity: 0.6;">[' + timestamp + ']</span> ';
		logLine += '<strong>' + icon + '</strong> ' + message;
		logLine += '</div>';
		
		logContainer.append(logLine);
		logContainer.scrollTop(logContainer[0].scrollHeight);
	}

	function addImportLog(message, type) {
		type = type || 'info';
		var logContainer = $('#pbc-import-log');
		logContainer.show();
		
		var icon = '●';
		var color = '#2271b1';
		if (type === 'success') {
			icon = '✓';
			color = '#00a32a';
		} else if (type === 'error') {
			icon = '✗';
			color = '#d63638';
		} else if (type === 'warning') {
			icon = '⚠';
			color = '#dba617';
		}
		
		var timestamp = new Date().toLocaleTimeString();
		var logLine = '<div style="margin: 3px 0; color: ' + color + ';">';
		logLine += '<span style="opacity: 0.6;">[' + timestamp + ']</span> ';
		logLine += '<strong>' + icon + '</strong> ' + message;
		logLine += '</div>';
		
		logContainer.append(logLine);
		logContainer.scrollTop(logContainer[0].scrollHeight);
	}

	// Export functionality.
	$('#pbc-export-button').click(function(e) {
		e.preventDefault();

		// Clear previous logs.
		$('#pbc-export-log').html('').hide();
		
		addExportLog('Starting export process...', 'info');

		$.ajax({
			type: 'POST',
			url: ajaxActionExportImport.url,
			data: {
				action: 'pbc_export_data',
				nonce: ajaxActionExportImport.nonce
			},
			beforeSend: function() { 
				$("#pbc-export-spinner").addClass("is-active");
				$("#pbc-export-button").prop('disabled', true);
				addExportLog('Connecting to server...', 'info');
			},
			complete: function() { 
				$("#pbc-export-spinner").removeClass("is-active");
				$("#pbc-export-button").prop('disabled', false);
			},
			success: function(result) {
				if (result.success) {
					addExportLog('Data received from server', 'success');
					
					var exportData = result.data.data;
					var phasesCount = exportData.phases ? exportData.phases.length : 0;
					var variationsCount = exportData.variations ? exportData.variations.length : 0;
					
					addExportLog('Total phases found: ' + phasesCount, 'info');
					addExportLog('Total variations found: ' + variationsCount, 'info');
					
					// Log phase details.
					if (phasesCount > 0) {
						addExportLog('Processing phases...', 'info');
						exportData.phases.forEach(function(phase, index) {
							addExportLog('  → Phase ' + (index + 1) + ': ' + phase.title + ' (slug: ' + phase.slug + ')', 'info');
						});
					}
					
					// Log variation details.
					if (variationsCount > 0) {
						addExportLog('Processing variations...', 'info');
						var displayLimit = 10;
						exportData.variations.slice(0, displayLimit).forEach(function(variation, index) {
							addExportLog('  → Variation ' + (index + 1) + ': ' + variation.title + ' (slug: ' + variation.slug + ')', 'info');
						});
						if (variationsCount > displayLimit) {
							addExportLog('  ... and ' + (variationsCount - displayLimit) + ' more variations', 'info');
						}
					}
					
					addExportLog('Generating CSV files...', 'info');
					
					// Download phases CSV.
					var csvPhasesContent = result.data.csv_phases;
					var dataStr1 = "data:text/csv;charset=utf-8," + encodeURIComponent(csvPhasesContent);
					var downloadAnchorNode1 = document.createElement('a');
					downloadAnchorNode1.setAttribute("href", dataStr1);
					downloadAnchorNode1.setAttribute("download", result.data.filename_phases);
					document.body.appendChild(downloadAnchorNode1);
					downloadAnchorNode1.click();
					downloadAnchorNode1.remove();
					
					addExportLog('File downloaded: ' + result.data.filename_phases, 'success');
					
					// Download variations CSV.
					var csvVariationsContent = result.data.csv_variations;
					var dataStr2 = "data:text/csv;charset=utf-8," + encodeURIComponent(csvVariationsContent);
					var downloadAnchorNode2 = document.createElement('a');
					downloadAnchorNode2.setAttribute("href", dataStr2);
					downloadAnchorNode2.setAttribute("download", result.data.filename_variations);
					document.body.appendChild(downloadAnchorNode2);
					downloadAnchorNode2.click();
					downloadAnchorNode2.remove();
					
					addExportLog('File downloaded: ' + result.data.filename_variations, 'success');
					addExportLog('Export completed successfully! (2 files downloaded)', 'success');
				} else {
					addExportLog('Export error: ' + result.data.message, 'error');
				}
			},
			error: function(xhr, textStatus, error) {
				console.log(error);
				addExportLog('Connection error: ' + textStatus, 'error');
				addExportLog('Please check the console for more details', 'error');
			}
		});
	});

	// Import file selection.
	var importPhasesData = null;
	var importVariationsData = null;

	$('#pbc-import-file-phases').change(function(e) {
		var file = e.target.files[0];
		if (!file) {
			return;
		}

		$('#pbc-import-filename-phases').text(file.name);
		
		var reader = new FileReader();
		reader.onload = function(e) {
			importPhasesData = e.target.result;
			checkImportReady();
		};
		reader.readAsText(file);
	});

	$('#pbc-import-file-variations').change(function(e) {
		var file = e.target.files[0];
		if (!file) {
			return;
		}

		$('#pbc-import-filename-variations').text(file.name);
		
		var reader = new FileReader();
		reader.onload = function(e) {
			importVariationsData = e.target.result;
			checkImportReady();
		};
		reader.readAsText(file);
	});

	function checkImportReady() {
		// Enable import button if at least one file is loaded.
		if (importPhasesData || importVariationsData) {
			$('#pbc-import-button').prop('disabled', false);
		}
	}

	// Import functionality.
	$('#pbc-import-button').click(function(e) {
		e.preventDefault();

		if (!importPhasesData && !importVariationsData) {
			alert('Please select at least one CSV file to import');
			return;
		}

		if (!confirm('Are you sure you want to import this data? New phases and variations will be created.')) {
			return;
		}

		// Clear previous logs.
		$('#pbc-import-log').html('').hide();
		
		addImportLog('Starting import process...', 'info');
		addImportLog('Validating CSV files...', 'info');
		
		// Validate files.
		var filesValidated = 0;
		
		if (importPhasesData) {
			if (importPhasesData.length < 10) {
				addImportLog('Error: Phases CSV file is empty or too small', 'error');
				return;
			}
			addImportLog('Phases CSV is valid ✓', 'success');
			filesValidated++;
		}
		
		if (importVariationsData) {
			if (importVariationsData.length < 10) {
				addImportLog('Error: Variations CSV file is empty or too small', 'error');
				return;
			}
			addImportLog('Variations CSV is valid ✓', 'success');
			filesValidated++;
		}
		
		addImportLog('Validated ' + filesValidated + ' file(s)', 'success');

		$.ajax({
			type: 'POST',
			url: ajaxActionExportImport.url,
			data: {
				action: 'pbc_import_data',
				nonce: ajaxActionExportImport.nonce,
				import_phases: importPhasesData || '',
				import_variations: importVariationsData || ''
			},
			beforeSend: function() { 
				$("#pbc-import-spinner").addClass("is-active");
				$("#pbc-import-button").prop('disabled', true);
				addImportLog('Sending data to server...', 'info');
				addImportLog('This process may take a few seconds...', 'warning');
			},
			complete: function() { 
				$("#pbc-import-spinner").removeClass("is-active");
				$("#pbc-import-button").prop('disabled', false);
			},
			success: function(result) {
				if (result.success) {
					addImportLog('Server response received', 'success');
					addImportLog('─────────────────────────────', 'info');
					addImportLog('IMPORT RESULTS', 'info');
					addImportLog('─────────────────────────────', 'info');
					addImportLog('✓ Phases created: ' + result.data.phases_created, 'success');
					addImportLog('✓ Variations created: ' + result.data.variations_created, 'success');
					
					if (result.data.errors && result.data.errors.length > 0) {
						addImportLog('─────────────────────────────', 'warning');
						addImportLog('ERRORS FOUND:', 'warning');
						result.data.errors.forEach(function(error) {
							addImportLog('  ⚠ ' + error, 'warning');
						});
					}
					
					addImportLog('─────────────────────────────', 'success');
					addImportLog('Import completed successfully!', 'success');
					addImportLog('Page will reload in 3 seconds...', 'info');
					
					// Clear the file inputs.
					$('#pbc-import-file-phases').val('');
					$('#pbc-import-filename-phases').text('');
					$('#pbc-import-file-variations').val('');
					$('#pbc-import-filename-variations').text('');
					importPhasesData = null;
					importVariationsData = null;
					
					// Reload page after 3 seconds.
					setTimeout(function() {
						location.reload();
					}, 3000);
				} else {
					addImportLog('─────────────────────────────', 'error');
					addImportLog('IMPORT ERROR', 'error');
					addImportLog('─────────────────────────────', 'error');
					addImportLog(result.data.message, 'error');
					
					if (result.data.errors && result.data.errors.length > 0) {
						result.data.errors.forEach(function(error) {
							addImportLog('  → ' + error, 'error');
						});
					}
				}
			},
			error: function(xhr, textStatus, error) {
				console.log(error);
				addImportLog('─────────────────────────────', 'error');
				addImportLog('CONNECTION ERROR', 'error');
				addImportLog('─────────────────────────────', 'error');
				addImportLog('Error: ' + textStatus, 'error');
				addImportLog('Please check the browser console for more details', 'error');
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

});
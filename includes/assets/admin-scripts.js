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

});
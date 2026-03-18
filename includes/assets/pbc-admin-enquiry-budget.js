/**
 * Admin: editable budget lines on enquiry post.
 */
(function ($) {
	'use strict';

	var cfg = window.pbcEnquiryBudget || {};
	var phaseVariations = cfg.phaseVariations || {};

	function refreshCatalogVariations() {
		var pid = $('#pbc-catalog-phase').val();
		var $v = $('#pbc-catalog-variation');
		$v.empty();
		$v.append($('<option></option>').val('').text(cfg.i18n.selectVariation));
		if (!pid || !phaseVariations[pid]) {
			$('#pbc-catalog-price-hint').text('');
			return;
		}
		phaseVariations[pid].forEach(function (item) {
			$v.append(
				$('<option></option>')
					.val(String(item.id))
					.text(item.lineLabel)
					.attr('data-price', item.price || '')
			);
		});
	}

	$(function () {
		var $tbody = $('#pbc-budget-rows');
		var tpl = document.getElementById('pbc-budget-row-template');
		if (!tpl) {
			return;
		}

		$(document).on('click', '.pbc-add-budget-row', function (e) {
			e.preventDefault();
			$tbody.append($(tpl.innerHTML));
		});

		$(document).on('click', '.pbc-remove-budget-row', function (e) {
			e.preventDefault();
			var $tr = $(this).closest('tr');
			if ($tbody.find('tr').length < 2) {
				$tr.find('input').val('');
				return;
			}
			$tr.remove();
		});

		$('#pbc-catalog-phase').on('change', function () {
			refreshCatalogVariations();
		});

		$('#pbc-catalog-variation').on('change', function () {
			var $opt = $(this).find('option:selected');
			var p = $opt.attr('data-price') || '';
			$('#pbc-catalog-price-hint').text(
				p ? cfg.i18n.suggestedPrice + ' ' + p : ''
			);
		});

		$('#pbc-insert-catalog-row').on('click', function (e) {
			e.preventDefault();
			var $cv = $('#pbc-catalog-variation');
			if (!$cv.val()) {
				return;
			}
			var $opt = $cv.find('option:selected');
			var $row = $(tpl.innerHTML);
			$row.find('.pbc-row-desc').val($opt.text().trim());
			$row.find('.pbc-row-price').val($opt.attr('data-price') || '');
			$tbody.append($row);
		});
	});
})(jQuery);

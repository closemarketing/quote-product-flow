/**
 * Admin: budget lines respect question / qty / multiple-selection phases.
 */
(function ($) {
	'use strict';

	var cfg = window.pbcEnquiryBudget || {};
	var phaseVariations = cfg.phaseVariations || {};
	var phaseFlags = cfg.phaseFlags || {};

	function formatEsPrice(num) {
		var n = Math.round(num * 100) / 100;
		var s = n.toFixed(2);
		return s.replace('.', ',');
	}

	function getPhaseFlag(pid) {
		return phaseFlags[String(pid)] || phaseFlags[pid] || {};
	}

	function updatePhaseMode() {
		var pid = $('#pbc-catalog-phase').val();
		var flags = getPhaseFlag(pid);
		$('#pbc-catalog-phase-hint').text(
			flags.allowMultiple ? cfg.i18n.phaseMulti : ''
		);
		if (flags.allowMultiple) {
			$('#pbc-catalog-multi-mode').show();
			renderMultiList(pid);
		} else {
			$('#pbc-catalog-multi-mode').hide();
		}
		refreshCatalogVariations(pid);
		resetSingleExtras();
	}

	function resetSingleExtras() {
		$('#pbc-catalog-answer-wrap').hide();
		$('#pbc-catalog-qty-wrap').hide();
		$('#pbc-catalog-answer').val('');
		$('#pbc-catalog-qty').val('1');
		$('#pbc-catalog-price-hint').text('');
	}

	function refreshCatalogVariations(pid) {
		var $v = $('#pbc-catalog-variation');
		$v.empty();
		$v.append($('<option></option>').val('').text(cfg.i18n.selectVariation));
		if (!pid || !phaseVariations[pid]) {
			return;
		}
		phaseVariations[pid].forEach(function (item) {
			var $o = $('<option></option>')
				.val(String(item.id))
				.text(item.lineLabel)
				.attr('data-price', item.price || '-')
				.attr('data-is-question', item.isQuestion ? '1' : '0')
				.attr('data-field-type', item.fieldType || '')
				.attr('data-unit-price', String(item.unitPrice != null ? item.unitPrice : 0))
				.attr('data-qtitle', item.questionTitle || '')
				.attr('data-short-label', item.shortLabel || '');
			$v.append($o);
		});
	}

	function renderMultiList(pid) {
		var $list = $('#pbc-catalog-multi-list');
		$list.empty();
		if (!pid || !phaseVariations[pid]) {
			$list.append($('<p>').text(cfg.i18n.noOptionsMulti));
			return;
		}
		var count = 0;
		phaseVariations[pid].forEach(function (item) {
			if (item.isQuestion) {
				return;
			}
			count += 1;
			var id = 'pbc-mvar-' + item.id;
			var $lab = $('<label></label>').css({ display: 'block', margin: '4px 0' });
			$lab.append(
				$('<input type="checkbox" class="pbc-multi-var" />').attr({
					id: id,
					'data-short-label': item.shortLabel,
					'data-unit': String(item.unitPrice != null ? item.unitPrice : 0),
				})
			);
			$lab.append(' ');
			$lab.append($('<span></span>').text(item.lineLabel));
			$list.append($lab);
		});
		if (count === 0) {
			$list.append($('<p>').text(cfg.i18n.noOptionsMulti));
		}
	}

	function onVariationChange() {
		resetSingleExtras();
		var $opt = $('#pbc-catalog-variation option:selected');
		if (!$opt.val()) {
			return;
		}
		var isQ = $opt.attr('data-is-question') === '1';
		var isQty = $opt.attr('data-field-type') === 'qty';
		if (isQ) {
			$('#pbc-catalog-answer-wrap').show();
			$('#pbc-catalog-price-hint').text('');
		} else if (isQty) {
			$('#pbc-catalog-qty-wrap').show();
			var unit = parseFloat($opt.attr('data-unit-price')) || 0;
			$('#pbc-catalog-price-hint').text(
				unit
					? cfg.i18n.suggestedPrice + ' ' + formatEsPrice(unit) + ' ' + cfg.i18n.perUnit
					: ''
			);
		} else {
			var p = $opt.attr('data-price') || '';
			$('#pbc-catalog-price-hint').text(
				p && p !== '-' ? cfg.i18n.suggestedPrice + ' ' + p : ''
			);
		}
	}

	function appendBudgetRow(desc, price, lineType, phaseName) {
		var tpl = document.getElementById('pbc-budget-row-template');
		if (!tpl) {
			return;
		}
		var $row = $(tpl.innerHTML);
		$row.find('.pbc-row-desc').val(desc);
		$row.find('.pbc-row-price').val(price);
		$row.find('.pbc-row-phase-name').val(phaseName || '');
		if (lineType) {
			$row.find('.pbc-row-type').val(lineType);
		}
		$('#pbc-budget-rows').append($row);
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
				$tr.find('input[type=text],input[type=hidden]').val('');
				$tr.find('select.pbc-row-type').prop('selectedIndex', 0);
				return;
			}
			$tr.remove();
		});

		$('#pbc-catalog-phase').on('change', updatePhaseMode);
		$('#pbc-catalog-variation').on('change', onVariationChange);

		$('#pbc-insert-catalog-row').on('click', function (e) {
			e.preventDefault();
			var pid = $('#pbc-catalog-phase').val();
			var $opt = $('#pbc-catalog-variation option:selected');
			if (!$opt.val()) {
				return;
			}
			var flags = getPhaseFlag(pid);
			var phaseName = flags.phaseName || '';
			var isQ = $opt.attr('data-is-question') === '1';
			var isQty = $opt.attr('data-field-type') === 'qty';
			var desc;
			var price;
			var lineType;

			if (isQ) {
				var ans = $.trim($('#pbc-catalog-answer').val());
				if (!ans) {
					window.alert(cfg.i18n.needAnswer);
					return;
				}
				desc = ($opt.attr('data-qtitle') || '').trim() + ': ' + ans;
				price = '-';
				lineType = 'question';
			} else if (isQty) {
				var q = parseInt($('#pbc-catalog-qty').val(), 10) || 1;
				var unit = parseFloat($opt.attr('data-unit-price')) || 0;
				desc = $opt.attr('data-short-label') || $opt.text().trim();
				price = formatEsPrice(q * unit);
				lineType = 'qty';
			} else {
				desc = $opt.attr('data-short-label') || $opt.text().trim();
				price = $opt.attr('data-price') || '-';
				lineType = 'price';
			}
			appendBudgetRow(desc, price, lineType, phaseName);
		});

		$('#pbc-insert-multi-row').on('click', function (e) {
			e.preventDefault();
			var pid = $('#pbc-catalog-phase').val();
			var flags = getPhaseFlag(pid);
			var phaseName = flags.phaseName || '';
			var names = [];
			var total = 0;
			$('.pbc-multi-var:checked').each(function () {
				var $cb = $(this);
				names.push($cb.attr('data-short-label') || '');
				total += parseFloat($cb.attr('data-unit')) || 0;
			});
			if (!names.length) {
				window.alert(cfg.i18n.pickMulti);
				return;
			}
			appendBudgetRow(names.join(', '), formatEsPrice(total), 'multiple', phaseName);
		});
	});
})(jQuery);

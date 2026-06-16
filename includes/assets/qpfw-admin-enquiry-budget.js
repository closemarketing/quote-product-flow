/**
 * Admin: budget lines respect question / qty / multiple-selection phases.
 */
(function ($) {
	'use strict';

	var cfg = window.qpfwEnquiryBudget || {};
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
		var pid = $('#qpfw-catalog-phase').val();
		var flags = getPhaseFlag(pid);
		$('#qpfw-catalog-phase-hint').text(
			flags.allowMultiple ? cfg.i18n.phaseMulti : ''
		);
		if (flags.allowMultiple) {
			$('#qpfw-catalog-multi-mode').show();
			renderMultiList(pid);
		} else {
			$('#qpfw-catalog-multi-mode').hide();
		}
		refreshCatalogVariations(pid);
		resetSingleExtras();
	}

	function resetSingleExtras() {
		$('#qpfw-catalog-answer-wrap').hide();
		$('#qpfw-catalog-qty-wrap').hide();
		$('#qpfw-catalog-answer').val('');
		$('#qpfw-catalog-qty').val('1');
		$('#qpfw-catalog-price-hint').text('');
	}

	function refreshCatalogVariations(pid) {
		var $v = $('#qpfw-catalog-variation');
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
		var $list = $('#qpfw-catalog-multi-list');
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
			var id = 'qpfw-mvar-' + item.id;
			var $lab = $('<label></label>').css({ display: 'block', margin: '4px 0' });
			$lab.append(
				$('<input type="checkbox" class="qpfw-multi-var" />').attr({
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
		var $opt = $('#qpfw-catalog-variation option:selected');
		if (!$opt.val()) {
			return;
		}
		var isQ = $opt.attr('data-is-question') === '1';
		var isQty = $opt.attr('data-field-type') === 'qty';
		if (isQ) {
			$('#qpfw-catalog-answer-wrap').show();
			$('#qpfw-catalog-price-hint').text('');
		} else if (isQty) {
			$('#qpfw-catalog-qty-wrap').show();
			var unit = parseFloat($opt.attr('data-unit-price')) || 0;
			$('#qpfw-catalog-price-hint').text(
				unit
					? cfg.i18n.suggestedPrice + ' ' + formatEsPrice(unit) + ' ' + cfg.i18n.perUnit
					: ''
			);
		} else {
			var p = $opt.attr('data-price') || '';
			$('#qpfw-catalog-price-hint').text(
				p && p !== '-' ? cfg.i18n.suggestedPrice + ' ' + p : ''
			);
		}
	}

	function appendBudgetRow(desc, price, lineType, phaseName) {
		var tpl = document.getElementById('qpfw-budget-row-template');
		if (!tpl) {
			return;
		}
		var $row = $(tpl.innerHTML);
		$row.find('.qpfw-row-desc').val(desc);
		$row.find('.qpfw-row-price').val(price);
		$row.find('.qpfw-row-phase-name').val(phaseName || '');
		if (lineType) {
			$row.find('.qpfw-row-type').val(lineType);
		}
		$('#qpfw-budget-rows').append($row);
	}

	$(function () {
		var $tbody = $('#qpfw-budget-rows');
		var tpl = document.getElementById('qpfw-budget-row-template');
		if (!tpl) {
			return;
		}

		$(document).on('click', '.qpfw-add-budget-row', function (e) {
			e.preventDefault();
			$tbody.append($(tpl.innerHTML));
		});

		$(document).on('click', '.qpfw-remove-budget-row', function (e) {
			e.preventDefault();
			var $tr = $(this).closest('tr');
			if ($tbody.find('tr').length < 2) {
				$tr.find('input[type=text],input[type=hidden]').val('');
				$tr.find('select.qpfw-row-type').prop('selectedIndex', 0);
				return;
			}
			$tr.remove();
		});

		$('#qpfw-catalog-phase').on('change', updatePhaseMode);
		$('#qpfw-catalog-variation').on('change', onVariationChange);

		$('#qpfw-insert-catalog-row').on('click', function (e) {
			e.preventDefault();
			var pid = $('#qpfw-catalog-phase').val();
			var $opt = $('#qpfw-catalog-variation option:selected');
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
				var ans = $.trim($('#qpfw-catalog-answer').val());
				if (!ans) {
					window.alert(cfg.i18n.needAnswer);
					return;
				}
				desc = ($opt.attr('data-qtitle') || '').trim() + ': ' + ans;
				price = '-';
				lineType = 'question';
			} else if (isQty) {
				var q = parseInt($('#qpfw-catalog-qty').val(), 10) || 1;
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

		$('#qpfw-insert-multi-row').on('click', function (e) {
			e.preventDefault();
			var pid = $('#qpfw-catalog-phase').val();
			var flags = getPhaseFlag(pid);
			var phaseName = flags.phaseName || '';
			var names = [];
			var total = 0;
			$('.qpfw-multi-var:checked').each(function () {
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

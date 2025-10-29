/**
 * PBC Debug Script
 * 
 * Ejecuta este script en la consola del navegador (F12) para diagnosticar problemas
 * Copia y pega en la consola:
 */

console.log('=== PBC Diagnostic Tool ===');

// Check jQuery
if (typeof jQuery !== 'undefined') {
    console.log('✓ jQuery is loaded:', jQuery.fn.jquery);
} else {
    console.error('✗ jQuery is NOT loaded!');
}

// Check PBCAjaxAction
if (typeof PBCAjaxAction !== 'undefined') {
    console.log('✓ PBCAjaxAction is defined:', PBCAjaxAction);
} else {
    console.error('✗ PBCAjaxAction is NOT defined!');
}

// Check form
var form = jQuery('#configurator-form');
if (form.length > 0) {
    console.log('✓ Form found:', form.length, 'form(s)');
    console.log('  - Form template:', form.data('template'));
    console.log('  - Current phase:', jQuery('input[name=pbc_current_phase]').val());
    console.log('  - Parent phase:', jQuery('input[name=pbc_parent_phase]').val());
    console.log('  - Nonce field exists:', jQuery('input[name=pbc_template_wizard_nonce]').length > 0);
} else {
    console.error('✗ Form #configurator-form NOT found!');
}

// Check buttons
var submitButtons = jQuery('button[name=submit]');
console.log('Submit buttons found:', submitButtons.length);
submitButtons.each(function(i, btn) {
    console.log('  - Button', i+1, ':', jQuery(btn).val(), '(visible:', jQuery(btn).is(':visible'), ')');
});

// Check page configurator
var pageConfig = jQuery('.page-configurator');
if (pageConfig.length > 0) {
    console.log('✓ Page configurator found');
} else {
    console.error('✗ Page configurator NOT found!');
}

// Check if on calculate step
var calcStep = jQuery('.configurator_result_share');
if (calcStep.length > 0) {
    console.log('✓ On calculate/results step');
} else {
    console.log('ℹ Not on calculate step (this is OK if you\'re still configuring)');
}

console.log('=== End Diagnostic ===');
console.log('Copy the output above and share it for debugging.');


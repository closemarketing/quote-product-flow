import { test, expect } from '@playwright/test';
import { createPost, deletePost, updatePostMeta, wp } from './wp-cli';

/**
 * Highest-value E2E test: proves the full chain from the phase's "Old style
 * options" checkbox (qpfw_legacy_style post meta) through Template.php and
 * ShowParts.php::variations_content() to actual rendered HTML on the public
 * wizard page.
 *
 * Builds a throwaway, isolated phases tree + page (never touching real
 * catalog content) and tears it all down again in afterEach.
 */
test.describe( 'Wizard step reflects the phase legacy-style setting', () => {
	let topPhaseId: number;
	let childPhaseId: number;
	let variationId: number;
	let pageId: number;

	test.beforeEach( async () => {
		topPhaseId = await createPost( 'qpfw_phases', 'E2E Wizard Top Phase' );
		childPhaseId = await createPost( 'qpfw_phases', 'E2E Wizard Child Phase', [
			`--post_parent=${ topPhaseId }`,
			'--menu_order=1',
		] );
		variationId = await createPost( 'qpfw_variation', 'E2E Wizard Variation' );
		await updatePostMeta( variationId, 'qpfw_phase', String( childPhaseId ) );

		pageId = await createPost( 'page', 'E2E Wizard Test Page', [
			`--post_content=[quote-product-flow pid=${ topPhaseId }]`,
		] );
	} );

	test.afterEach( async () => {
		await deletePost( pageId );
		await deletePost( variationId );
		await deletePost( childPhaseId );
		await deletePost( topPhaseId );
	} );

	test( 'renders without qpfw-choice-row when legacy style is off, with it when on', async ( { page } ) => {
		const pageUrl = await wp( [ 'post', 'get', String( pageId ), '--field=url' ] );

		// -- Legacy style OFF (default) --
		await page.goto( pageUrl );
		const li = page.locator( 'li.variation_list', {
			has: page.locator( `input.qpfw-option-native[value="${ variationId }"]` ),
		} );
		await expect( li ).toHaveCount( 1 );
		await expect( li ).toHaveClass( /qpfw-choice-row/ );
		await expect( li.locator( 'label' ) ).toHaveClass( /qpfw-choice-label/ );
		// These are NOT gated by legacy_style and should always be present.
		await expect( li.locator( 'input.qpfw-option-native' ) ).toHaveCount( 1 );
		await expect( li.locator( 'span.qpfw-option-card' ) ).toHaveCount( 1 );

		// -- Legacy style ON --
		await updatePostMeta( childPhaseId, 'qpfw_legacy_style', '1' );

		await page.goto( pageUrl );
		const liLegacy = page.locator( 'li.variation_list', {
			has: page.locator( `input.qpfw-option-native[value="${ variationId }"]` ),
		} );
		await expect( liLegacy ).toHaveCount( 1 );
		await expect( liLegacy ).not.toHaveClass( /qpfw-choice-row/ );
		await expect( liLegacy.locator( 'label' ) ).not.toHaveClass( /qpfw-choice-label/ );
		// Still not gated by legacy_style.
		await expect( liLegacy.locator( 'input.qpfw-option-native' ) ).toHaveCount( 1 );
		await expect( liLegacy.locator( 'span.qpfw-option-card' ) ).toHaveCount( 1 );
	} );
} );

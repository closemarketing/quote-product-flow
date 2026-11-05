import { test, expect, type Page } from '@playwright/test';
import {
	createPost,
	deletePost,
	deleteOption,
	updateOptionJson,
	updatePostMeta,
	wp,
} from './wp-cli';

/**
 * Proves the variation display-order feature end to end: the per-phase
 * "Variations order" numbers (qpfw_display_order meta) control the order
 * variations render in on the public wizard, alphabetical as a tie-break,
 * and the global "Order by title" setting overrides both the alphabetical
 * default AND a variation's own per-phase order when its title matches.
 *
 * Builds a throwaway phases/variations/page fixture (never touching real
 * catalog content) and tears it all down again in afterEach.
 */
test.describe( 'Variation display order', () => {
	let topPhaseId: number;
	let childPhaseId: number;
	let alphaVariationId: number;
	let betaVariationId: number;
	let pageId: number;

	test.beforeEach( async () => {
		topPhaseId = await createPost( 'qpfw_phases', 'E2E Order Top Phase' );
		childPhaseId = await createPost( 'qpfw_phases', 'E2E Order Child Phase', [
			`--post_parent=${ topPhaseId }`,
			'--menu_order=1',
		] );
		// Named so plain alphabetical order would put "Alpha" before "Beta".
		alphaVariationId = await createPost( 'qpfw_variation', 'Alpha Variation' );
		await updatePostMeta( alphaVariationId, 'qpfw_phase', String( childPhaseId ) );
		betaVariationId = await createPost( 'qpfw_variation', 'Beta Variation' );
		await updatePostMeta( betaVariationId, 'qpfw_phase', String( childPhaseId ) );

		pageId = await createPost( 'page', 'E2E Order Test Page', [
			`--post_content=[quote-product-flow pid=${ topPhaseId }]`,
		] );
	} );

	test.afterEach( async () => {
		await deletePost( pageId );
		await deletePost( betaVariationId );
		await deletePost( alphaVariationId );
		await deletePost( childPhaseId );
		await deletePost( topPhaseId );
		await deleteOption( 'qpfw_order_by_title' );
	} );

	async function optionTitlesInOrder( page: Page, pageUrl: string ) {
		await page.goto( pageUrl );
		return page.locator( '.phase_variations .qpfw-option-title' ).allInnerTexts();
	}

	test( 'own qpfw_display_order overrides the alphabetical default, alphabetical wins on ties', async ( { page } ) => {
		const pageUrl = await wp( [ 'post', 'get', String( pageId ), '--field=url' ] );

		// Baseline: no order set on either variation -> alphabetical (Alpha, Beta).
		expect( await optionTitlesInOrder( page, pageUrl ) ).toEqual( [
			'Alpha Variation',
			'Beta Variation',
		] );

		// Give Beta a lower order number than Alpha's default (0) -> Beta must move first.
		await updatePostMeta( betaVariationId, 'qpfw_display_order', '-1' );
		expect( await optionTitlesInOrder( page, pageUrl ) ).toEqual( [
			'Beta Variation',
			'Alpha Variation',
		] );

		// Same order on both -> falls back to alphabetical again.
		await updatePostMeta( alphaVariationId, 'qpfw_display_order', '-1' );
		expect( await optionTitlesInOrder( page, pageUrl ) ).toEqual( [
			'Alpha Variation',
			'Beta Variation',
		] );
	} );

	test( 'global order-by-title overrides both the alphabetical default and a conflicting own order', async ( { page } ) => {
		const pageUrl = await wp( [ 'post', 'get', String( pageId ), '--field=url' ] );

		// Give "Beta Variation" a high own-meta order, so on its own it would sort last.
		await updatePostMeta( betaVariationId, 'qpfw_display_order', '99' );
		expect( await optionTitlesInOrder( page, pageUrl ) ).toEqual( [
			'Alpha Variation',
			'Beta Variation',
		] );

		// A global title match for "Beta Variation" must win over its own 99 AND
		// over Alpha's default of 0, moving Beta to the front.
		await updateOptionJson( 'qpfw_order_by_title', [
			{ qpfw_order_title: 'Beta Variation', qpfw_order_value: -1 },
		] );
		expect( await optionTitlesInOrder( page, pageUrl ) ).toEqual( [
			'Beta Variation',
			'Alpha Variation',
		] );
	} );
} );

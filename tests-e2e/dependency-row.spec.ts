import { test, expect } from '@playwright/test';
import { createPost, deletePost, updatePostMeta } from './wp-cli';

/**
 * E2E coverage for the "Depends of" repeatable row on a qpfw_variation
 * edit screen (includes/HelperPostTypes.php render_depend_row(), and the
 * client-side toggle/prefix logic in includes/assets/admin-scripts.js).
 */
test.describe( 'Variation depends-of row', () => {
	let variationId: number;

	test.beforeEach( async () => {
		variationId = await createPost( 'qpfw_variation', 'E2E Dep Var' );
	} );

	test.afterEach( async () => {
		await deletePost( variationId );
	} );

	test( 'saves and reloads a "by title" dependency row', async ( { page } ) => {
		await page.goto( `/wp-admin/post.php?post=${ variationId }&action=edit` );

		// Start from a clean slate: remove any pre-existing default row(s).
		while ( await page.locator( '.qpfw-depends-item' ).count() > 0 ) {
			await page.locator( '.qpfw-remove-dep' ).first().click();
		}

		await page.click( '#qpfw-add-dep-row' );
		const row = page.locator( '.qpfw-depends-item' ).first();
		await expect( row ).toBeVisible();

		await row.locator( '.qpfw-dep-mode' ).selectOption( 'title' );

		const titleInput = row.locator( '.qpfw-dep-title' );
		const idSelect = row.locator( '.qpfw-dep-id' );
		await expect( titleInput ).toBeVisible();
		await expect( titleInput ).toBeEnabled();
		await expect( idSelect ).toBeHidden();

		await titleInput.fill( '130x150' );

		await page.click( '#publish' );
		await page.waitForURL( /post\.php\?post=\d+&action=edit/ );

		// Reload to prove the save/reload roundtrip, not just an in-page state.
		await page.goto( `/wp-admin/post.php?post=${ variationId }&action=edit` );

		const reloadedRow = page.locator( '.qpfw-depends-item' ).first();
		await expect( page.locator( '.qpfw-depends-item' ) ).toHaveCount( 1 );
		await expect( reloadedRow.locator( '.qpfw-dep-mode' ) ).toHaveValue( 'title' );
		await expect( reloadedRow.locator( '.qpfw-dep-title' ) ).toHaveValue( '130x150' );
		await expect( reloadedRow.locator( '.qpfw-dep-title' ) ).toBeVisible();
		await expect( reloadedRow.locator( '.qpfw-dep-id' ) ).toBeHidden();
	} );

	test( 'saves and reloads a "specific variation" dependency row', async ( { page } ) => {
		// A target variation that will show up in the "specific variation" select
		// (get_var_options() only lists variations whose qpfw_phase resolves to a
		// real phase post).
		const phaseId = await createPost( 'qpfw_phases', 'E2E Dep Phase' );
		const targetVariationId = await createPost( 'qpfw_variation', 'E2E Target Var' );
		await updatePostMeta( targetVariationId, 'qpfw_phase', String( phaseId ) );

		try {
			await page.goto( `/wp-admin/post.php?post=${ variationId }&action=edit` );

			while ( await page.locator( '.qpfw-depends-item' ).count() > 0 ) {
				await page.locator( '.qpfw-remove-dep' ).first().click();
			}

			await page.click( '#qpfw-add-dep-row' );
			const row = page.locator( '.qpfw-depends-item' ).first();
			await expect( row ).toBeVisible();

			// Default mode is "Specific variation" already; confirm the controls
			// are in the expected initial state before picking a value.
			await expect( row.locator( '.qpfw-dep-mode' ) ).toHaveValue( 'id' );
			const idSelect = row.locator( '.qpfw-dep-id' );
			const titleInput = row.locator( '.qpfw-dep-title' );
			await expect( idSelect ).toBeVisible();
			await expect( titleInput ).toBeHidden();

			const option = idSelect.locator( 'option', { hasText: 'E2E Target Var' } );
			await expect( option ).toHaveCount( 1 );
			const optionValue = await option.getAttribute( 'value' );
			await idSelect.selectOption( optionValue as string );

			await page.click( '#publish' );
			await page.waitForURL( /post\.php\?post=\d+&action=edit/ );

			await page.goto( `/wp-admin/post.php?post=${ variationId }&action=edit` );

			const reloadedRow = page.locator( '.qpfw-depends-item' ).first();
			await expect( page.locator( '.qpfw-depends-item' ) ).toHaveCount( 1 );
			await expect( reloadedRow.locator( '.qpfw-dep-mode' ) ).toHaveValue( 'id' );
			await expect( reloadedRow.locator( '.qpfw-dep-id' ) ).toHaveValue( optionValue as string );
			await expect( reloadedRow.locator( '.qpfw-dep-id' ) ).toBeVisible();
			await expect( reloadedRow.locator( '.qpfw-dep-title' ) ).toBeHidden();
		} finally {
			await deletePost( targetVariationId );
			await deletePost( phaseId );
		}
	} );

	test( '"Add dependency" and "Remove" buttons add/remove rows in the DOM', async ( { page } ) => {
		await page.goto( `/wp-admin/post.php?post=${ variationId }&action=edit` );

		const rows = page.locator( '.qpfw-depends-item' );
		const initialCount = await rows.count();

		await page.click( '#qpfw-add-dep-row' );
		await expect( rows ).toHaveCount( initialCount + 1 );

		await page.click( '#qpfw-add-dep-row' );
		await expect( rows ).toHaveCount( initialCount + 2 );

		await rows.last().locator( '.qpfw-remove-dep' ).click();
		await expect( rows ).toHaveCount( initialCount + 1 );

		await rows.last().locator( '.qpfw-remove-dep' ).click();
		await expect( rows ).toHaveCount( initialCount );
	} );
} );

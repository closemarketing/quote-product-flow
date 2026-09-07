import { test, expect } from '@playwright/test';
import { createPost, deletePost } from './wp-cli';

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
} );

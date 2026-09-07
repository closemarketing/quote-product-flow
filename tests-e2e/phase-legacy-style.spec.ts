import { test, expect } from '@playwright/test';
import { createPost, deletePost } from './wp-cli';

/**
 * E2E coverage for the "Old style options" checkbox on a qpfw_phases edit
 * screen (includes/HelperPostTypes.php render_phase_metabox() /
 * save_phase_meta(), persisting the qpfw_legacy_style post meta).
 */
test.describe( 'Phase "Old style options" checkbox', () => {
	let phaseId: number;

	test.beforeEach( async () => {
		phaseId = await createPost( 'qpfw_phases', 'E2E Legacy Style Phase' );
	} );

	test.afterEach( async () => {
		await deletePost( phaseId );
	} );

	const checkbox = ( page: import( '@playwright/test' ).Page ) =>
		page.locator( 'input[name="qpfw_legacy_style"]' );

	test( 'persists checked state across save + reload', async ( { page } ) => {
		await page.goto( `/wp-admin/post.php?post=${ phaseId }&action=edit` );
		await expect( checkbox( page ) ).not.toBeChecked();

		await checkbox( page ).check();
		await page.click( '#publish' );
		await page.waitForURL( /post\.php\?post=\d+&action=edit/ );

		await page.goto( `/wp-admin/post.php?post=${ phaseId }&action=edit` );
		await expect( checkbox( page ) ).toBeChecked();
	} );
} );

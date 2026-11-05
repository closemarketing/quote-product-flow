import { test as setup, expect } from '@playwright/test';

const authFile = './tests-e2e/.auth/admin.json';

/**
 * Logs in once as the dedicated qpfw_e2e_test admin user (created via WP-CLI,
 * not the site's real admin account) and reuses the session for every test.
 *
 * Submits the form directly (rather than clicking #wp-submit) to avoid a
 * flaky race where the click sometimes doesn't trigger the navigation in
 * time.
 */
setup( 'authenticate', async ( { page } ) => {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', 'qpfw_e2e_test' );
	await page.fill( '#user_pass', 'Qpfw!E2eTest#2026' );
	await Promise.all( [
		page.waitForURL( /wp-admin/, { timeout: 20000 } ),
		page.locator( '#loginform' ).evaluate( ( form: HTMLFormElement ) => form.submit() ),
	] );
	await expect( page ).toHaveURL( /wp-admin/ );
	await page.context().storageState( { path: authFile } );
} );

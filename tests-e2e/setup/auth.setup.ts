import { test as setup } from '@playwright/test';

const authFile = './tests-e2e/.auth/admin.json';

/**
 * Logs in once as the dedicated qpfw_e2e_test admin user (created via WP-CLI,
 * not the site's real admin account) and reuses the session for every test.
 */
setup( 'authenticate', async ( { page } ) => {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', 'qpfw_e2e_test' );
	await page.fill( '#user_pass', 'Qpfw!E2eTest#2026' );
	await page.click( '#wp-submit' );
	await page.waitForURL( /wp-admin/ );
	await page.context().storageState( { path: authFile } );
} );

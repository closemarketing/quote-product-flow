import { defineConfig, devices } from '@playwright/test';

/**
 * E2E config for quote-product-flow.
 *
 * Targets the local ALPU WordPress dev install (Local by Flywheel), which
 * uses a self-signed cert, hence ignoreHTTPSErrors.
 */
export default defineConfig({
	testDir: './tests-e2e',
	fullyParallel: false,
	workers: 1,
	retries: 0,
	reporter: [ [ 'list' ] ],
	use: {
		baseURL: 'https://alpu.local',
		ignoreHTTPSErrors: true,
		trace: 'retain-on-failure',
	},
	projects: [
		{
			name: 'setup',
			testMatch: /.*\.setup\.ts/,
		},
		{
			name: 'chromium',
			use: {
				...devices[ 'Desktop Chrome' ],
				storageState: './tests-e2e/.auth/admin.json',
			},
			dependencies: [ 'setup' ],
		},
	],
});

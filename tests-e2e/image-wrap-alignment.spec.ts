import { test, expect } from '@playwright/test';
import path from 'node:path';

/**
 * Regression test for the composite preview image bug: `.image-wrap` had no
 * `position` set, so the absolutely-positioned overlay layers anchored to
 * `.product_preview` instead (64px higher than where the base image
 * actually sits, because of that container's padding-top spacer) — a
 * mismatch that let a taller overlay spill past the bottom of the base
 * image and read as a cut/duplicated slice.
 *
 * Loads a static fixture using the real, currently-deployed
 * qpfw-configurator.css (fetched straight from the running site) with two
 * stacked images of deliberately different pixel dimensions, and asserts
 * both occupy the exact same box — proving every layer aligns regardless
 * of its own source photo's aspect ratio.
 */
test( 'stacked preview images share the same box regardless of their own aspect ratio', async ( { page } ) => {
	const fixtureUrl = 'file://' + path.resolve( __dirname, 'fixtures/image-wrap.html' );
	await page.goto( fixtureUrl );

	const images = page.locator( '.product_preview .image-wrap img' );
	await expect( images ).toHaveCount( 2 );

	const firstBox = await images.nth( 0 ).boundingBox();
	const secondBox = await images.nth( 1 ).boundingBox();

	expect( firstBox ).not.toBeNull();
	expect( secondBox ).not.toBeNull();
	expect( secondBox ).toEqual( firstBox );
} );

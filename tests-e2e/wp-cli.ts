import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify( execFile );

const PHP_BIN =
	'/Users/close/Library/Application Support/Local/lightning-services/php-8.2.27+1/bin/darwin-arm64/bin/php';
const WP_CLI_PHAR = `${ __dirname }/../.tools/wp-cli.phar`;
const WP_PATH = '/Users/close/Web/alpu/app/public';
const DB_SOCKET =
	'/Users/close/Library/Application Support/Local/run/wwMuc8ZX0/mysql/mysqld.sock';

/**
 * Runs a WP-CLI command against the local ALPU site and returns trimmed stdout.
 * Used by E2E tests to create/clean up disposable fixtures (posts, meta) so
 * each test is self-contained and re-runnable.
 */
export async function wp( args: string[] ): Promise< string > {
	const { stdout } = await execFileAsync(
		PHP_BIN,
		[
			`-d`,
			`mysqli.default_socket=${ DB_SOCKET }`,
			`-d`,
			`display_errors=0`,
			WP_CLI_PHAR,
			`--path=${ WP_PATH }`,
			`--url=https://alpu.local`,
			...args,
		],
		{ maxBuffer: 10 * 1024 * 1024 }
	);
	return stdout.trim();
}

export async function createPost(
	postType: string,
	title: string,
	extraArgs: string[] = []
): Promise< number > {
	const out = await wp( [
		'post',
		'create',
		'--post_type=' + postType,
		'--post_title=' + title,
		'--post_status=publish',
		'--porcelain',
		...extraArgs,
	] );
	return parseInt( out, 10 );
}

export async function deletePost( id: number ): Promise< void > {
	await wp( [ 'post', 'delete', String( id ), '--force' ] );
}

export async function updatePostMeta(
	id: number,
	key: string,
	value: string
): Promise< void > {
	await wp( [ 'post', 'meta', 'update', String( id ), key, value ] );
}

/**
 * Updates a WP option to an arbitrary JSON-serializable value (array/object
 * included), via WP-CLI's --format=json.
 */
export async function updateOptionJson( key: string, value: unknown ): Promise< void > {
	await wp( [ 'option', 'update', key, JSON.stringify( value ), '--format=json' ] );
}

export async function deleteOption( key: string ): Promise< void > {
	await wp( [ 'option', 'delete', key ] );
}

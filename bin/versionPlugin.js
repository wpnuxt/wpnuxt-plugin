/**
 * Copies the version that `changeset version` wrote to package.json into the
 * plugin header, the version constant and plugin.json (the update checker's metadata).
 * Each replacement must match exactly once, so a missed spot fails the release PR
 * instead of shipping a zip with the wrong version.
 */
const { readFileSync, writeFileSync } = require( 'node:fs' );
const { join } = require( 'node:path' );

const root = join( __dirname, '..' );
const { version } = JSON.parse( readFileSync( join( root, 'package.json' ), 'utf8' ) );

function replaceOnce( file, pattern, replacement ) {
	const path = join( root, file );
	const content = readFileSync( path, 'utf8' );
	const matches = content.match( new RegExp( pattern.source, pattern.flags + 'g' ) ) || [];
	if ( matches.length !== 1 ) {
		throw new Error( `${ file }: expected 1 match for ${ pattern }, found ${ matches.length }` );
	}
	writeFileSync( path, content.replace( pattern, replacement ) );
}

replaceOnce( 'wpnuxt-plugin.php', /^( \* Version:\s+).+$/m, `$1${ version }` );
replaceOnce( 'wpnuxt-plugin.php', /(define\('WPNUXT_PLUGIN_VERSION', ')[^']+('\);)/, `$1${ version }$2` );
replaceOnce( 'plugin.json', /("version": ")[^"]+(")/, `$1${ version }$2` );
replaceOnce( 'plugin.json', /(\/releases\/download\/v)[^/]+(\/wpnuxt-plugin\.zip)/, `$1${ version }$2` );

const changelog = JSON.parse( readFileSync( join( root, 'plugin.json' ), 'utf8' ) ).sections.changelog;
if ( ! changelog.includes( `<h2>${ version }</h2>` ) ) {
	console.warn( `plugin.json has no changelog entry for ${ version } yet: add it to the release PR.` );
}

console.log( `Plugin version set to ${ version }.` );

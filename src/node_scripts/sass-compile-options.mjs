import path from 'node:path'
import * as sass from 'sass'

const LEGACY_DEPRECATIONS = ['import']

export function createSassCompileOptions({ cwd, isProduction }) {
    const sourceMap = !isProduction

    return {
        style: isProduction ? 'compressed' : 'expanded',
        sourceMap,
        sourceMapIncludeSources: sourceMap,
        loadPaths: [cwd, path.join(cwd, 'node_modules')],
        quietDeps: true,
        silenceDeprecations: LEGACY_DEPRECATIONS,
        logger: sass.Logger.silent,
    }
}

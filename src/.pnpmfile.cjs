function hasLegacyMixScript(pkg) {
    if (!pkg?.scripts) {
        return false
    }

    return Object.values(pkg.scripts).some((script) => {
        return typeof script === 'string' && script.includes('mix --mix-config')
    })
}

module.exports = {
    hooks: {
        readPackage(pkg) {
            if (!hasLegacyMixScript(pkg)) {
                return pkg
            }

            pkg.devDependencies = pkg.devDependencies || {}
            pkg.devDependencies['@mapas/scripts'] = pkg.devDependencies['@mapas/scripts'] || 'workspace:*'

            // Prevent laravel-mix from shadowing the compatibility "mix" binary from @mapas/scripts.
            delete pkg.devDependencies['laravel-mix']
            delete pkg.devDependencies['webpack']
            delete pkg.devDependencies['webpack-cli']
            delete pkg.devDependencies['sass-loader']
            delete pkg.devDependencies['resolve-url-loader']

            return pkg
        },
    },
}

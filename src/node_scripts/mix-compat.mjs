export function resolveLegacyMixMode(argv) {
    const hasWatchMode = argv.includes('watch') || argv.includes('--watch')
    if (hasWatchMode) {
        return 'watch'
    }

    const hasProductionMode = argv.includes('--production') || argv.includes('-p')
    if (hasProductionMode) {
        return 'build'
    }

    return 'dev'
}

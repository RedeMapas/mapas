#!/usr/bin/env node

import { spawnSync } from 'node:child_process'
import path from 'node:path'
import process from 'node:process'
import { fileURLToPath } from 'node:url'

import { resolveLegacyMixMode } from './mix-compat.mjs'

const argv = process.argv.slice(2)
const mode = resolveLegacyMixMode(argv)

const currentFile = fileURLToPath(import.meta.url)
const currentDir = path.dirname(currentFile)
const buildScriptPath = path.join(currentDir, 'esbuild-build.mjs')

const result = spawnSync(process.execPath, [buildScriptPath, mode], {
    stdio: 'inherit',
})

if (result.error) {
    console.error('[mix-compat] Falha ao executar mapas-build:', result.error)
    process.exit(1)
}

process.exit(result.status ?? 1)

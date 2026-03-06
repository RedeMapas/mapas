#!/usr/bin/env node

import { mkdir, readFile, readdir, watch, writeFile } from 'node:fs/promises'
import { existsSync } from 'node:fs'
import path from 'node:path'
import process from 'node:process'
import { build as esbuildBuild, context as esbuildContext } from 'esbuild'
import fg from 'fast-glob'
import * as sass from 'sass'
import { createSassCompileOptions } from './sass-compile-options.mjs'

const cwd = process.cwd()
const mode = process.argv[2] ?? 'dev'
const isProduction = mode === 'build'
const isWatch = mode === 'watch'

if (!['dev', 'build', 'watch'].includes(mode)) {
    console.error(`Modo invalido: ${mode}. Use dev, build ou watch.`)
    process.exit(1)
}

const pkg = JSON.parse(await readFile(path.join(cwd, 'package.json'), 'utf8'))
const jsEntries = fg.sync('assets-src/js/*.js', { cwd, absolute: true })
const sassEntries = fg.sync(['assets-src/sass/*.scss', '!assets-src/sass/_*.scss'], { cwd, absolute: true })

function resolveDestination(source, type) {
    const sourceId = path.basename(source, path.extname(source))
    const exportName = pkg.mapas?.assets?.[type]?.[sourceId]
    return path.join(cwd, 'assets', type, `${exportName ?? sourceId}.${type}`)
}

async function ensureParentDir(filePath) {
    await mkdir(path.dirname(filePath), { recursive: true })
}

async function buildSass() {
    for (const entry of sassEntries) {
        const destination = resolveDestination(entry, 'css')
        const options = createSassCompileOptions({
            cwd,
            isProduction,
        })

        await ensureParentDir(destination)
        const result = await sass.compileAsync(entry, options)

        await writeFile(destination, result.css)

        if (options.sourceMap && result.sourceMap) {
            await writeFile(`${destination}.map`, JSON.stringify(result.sourceMap))
        }
    }
}

async function buildJsOnce() {
    for (const entry of jsEntries) {
        const destination = resolveDestination(entry, 'js')
        await ensureParentDir(destination)

        await esbuildBuild({
            entryPoints: [entry],
            outfile: destination,
            bundle: true,
            format: 'iife',
            platform: 'browser',
            sourcemap: !isProduction,
            minify: isProduction,
            target: ['es2019'],
            loader: {
                '.png': 'file',
                '.jpg': 'file',
                '.jpeg': 'file',
                '.gif': 'file',
                '.svg': 'file',
                '.woff': 'file',
                '.woff2': 'file',
                '.ttf': 'file',
                '.eot': 'file',
            },
            logLevel: 'info',
        })
    }
}

async function getSassDirs(rootDir) {
    const dirs = []
    if (!existsSync(rootDir)) {
        return dirs
    }

    const queue = [rootDir]
    while (queue.length) {
        const dir = queue.shift()
        dirs.push(dir)
        const entries = await readdir(dir, { withFileTypes: true })
        for (const entry of entries) {
            if (entry.isDirectory()) {
                queue.push(path.join(dir, entry.name))
            }
        }
    }

    return dirs
}

async function watchSass() {
    const sassRoot = path.join(cwd, 'assets-src', 'sass')
    const dirs = await getSassDirs(sassRoot)
    let timer = null

    const triggerBuild = () => {
        if (timer) {
            clearTimeout(timer)
        }
        timer = setTimeout(async () => {
            try {
                await buildSass()
                console.log('[sass] rebuild concluido')
            } catch (error) {
                console.error('[sass] falha no rebuild', error)
            }
        }, 100)
    }

    for (const dir of dirs) {
        const watcher = watch(dir)
        ;(async () => {
            for await (const _event of watcher) {
                triggerBuild()
            }
        })()
    }
}

async function watchJs() {
    for (const entry of jsEntries) {
        const destination = resolveDestination(entry, 'js')
        await ensureParentDir(destination)
        const ctx = await esbuildContext({
            entryPoints: [entry],
            outfile: destination,
            bundle: true,
            format: 'iife',
            platform: 'browser',
            sourcemap: true,
            minify: false,
            target: ['es2019'],
            loader: {
                '.png': 'file',
                '.jpg': 'file',
                '.jpeg': 'file',
                '.gif': 'file',
                '.svg': 'file',
                '.woff': 'file',
                '.woff2': 'file',
                '.ttf': 'file',
                '.eot': 'file',
            },
            logLevel: 'info',
        })
        await ctx.watch()
    }
}

async function run() {
    if (!jsEntries.length && !sassEntries.length) {
        console.log('Nenhum entrypoint em assets-src/js ou assets-src/sass. Nada para compilar.')
        return
    }

    await buildSass()

    if (isWatch) {
        await watchJs()
        await watchSass()
        console.log('Watch ativo (JS + SASS).')
        await new Promise(() => {})
        return
    }

    await buildJsOnce()
}

run().catch((error) => {
    console.error(error)
    process.exit(1)
})

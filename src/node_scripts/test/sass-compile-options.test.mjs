import test from 'node:test'
import assert from 'node:assert/strict'

import { createSassCompileOptions } from '../sass-compile-options.mjs'

test('enables import deprecation silencing for compatibility by default', () => {
  const options = createSassCompileOptions({
    cwd: '/tmp/project',
    isProduction: false,
  })

  assert.deepEqual(options.silenceDeprecations, ['import'])
  assert.equal(options.quietDeps, true)
  assert.ok(options.logger)
  assert.equal(options.sourceMap, true)
  assert.equal(options.style, 'expanded')
})

test('keeps production profile when building', () => {
  const options = createSassCompileOptions({
    cwd: '/tmp/project',
    isProduction: true,
  })

  assert.equal(options.sourceMap, false)
  assert.equal(options.style, 'compressed')
})

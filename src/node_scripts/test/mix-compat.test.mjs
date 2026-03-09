import test from 'node:test'
import assert from 'node:assert/strict'

import { resolveLegacyMixMode } from '../mix-compat.mjs'

test('defaults to dev for bare mix invocation', () => {
  assert.equal(resolveLegacyMixMode([]), 'dev')
})

test('maps --production to build', () => {
  assert.equal(resolveLegacyMixMode(['--mix-config=node_modules/@mapas/scripts/webpack.mix.js', '--production']), 'build')
})

test('maps watch command to watch mode', () => {
  assert.equal(resolveLegacyMixMode(['watch', '--mix-config=node_modules/@mapas/scripts/webpack.mix.js']), 'watch')
})

test('watch takes precedence over --production when both appear', () => {
  assert.equal(resolveLegacyMixMode(['watch', '--production']), 'watch')
})

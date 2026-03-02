import { describe, expect, it } from 'vitest'
import { useIdempotencyKey } from './useIdempotencyKey'

describe('useIdempotencyKey', () => {
  it('returns a UUID key', () => {
    const { idempotencyKey } = useIdempotencyKey()

    expect(idempotencyKey.value).toBeDefined()
    expect(typeof idempotencyKey.value).toBe('string')
    expect(idempotencyKey.value).toMatch(
      /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/,
    )
  })

  it('regenerateKey produces a new key', () => {
    const { idempotencyKey, regenerateKey } = useIdempotencyKey()

    const firstKey = idempotencyKey.value
    regenerateKey()

    expect(idempotencyKey.value).not.toBe(firstKey)
    expect(idempotencyKey.value).toMatch(
      /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/,
    )
  })
})

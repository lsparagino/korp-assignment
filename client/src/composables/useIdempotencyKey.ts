import { ref } from 'vue'

export function useIdempotencyKey () {
  const idempotencyKey = ref(crypto.randomUUID())

  function regenerateKey (): void {
    idempotencyKey.value = crypto.randomUUID()
  }

  return { idempotencyKey, regenerateKey }
}

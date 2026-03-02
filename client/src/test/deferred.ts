/**
 * Creates a deferred promise — resolve it externally to control async flow in tests.
 */
export function createDeferred<T = void> () {
  let resolve!: (value: T | PromiseLike<T>) => void
  const promise = new Promise<T>(r => {
    resolve = r
  })
  return { promise, resolve }
}

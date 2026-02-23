/**
 * Global polyfills for jsdom test environment.
 * Loaded via vitest.config.ts setupFiles — runs before every test file.
 *
 * See: https://vuetifyjs.com/en/getting-started/unit-testing/#setup-vitest
 */

// Polyfill ResizeObserver for jsdom (required by Vuetify components)
import ResizeObserver from 'resize-observer-polyfill'
globalThis.ResizeObserver = ResizeObserver

// Polyfill visualViewport for jsdom (required by Vuetify's VOverlay)
if (!globalThis.visualViewport) {
    Object.defineProperty(globalThis, 'visualViewport', {
        value: {
            width: 1024,
            height: 768,
            offsetLeft: 0,
            offsetTop: 0,
            pageLeft: 0,
            pageTop: 0,
            scale: 1,
            addEventListener: () => { },
            removeEventListener: () => { },
        },
        writable: true,
    })
}

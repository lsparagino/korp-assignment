import { DOMWrapper } from '@vue/test-utils'

export function findByTestId(testId: string) {
    const el = document.body.querySelector(`[data-testid="${testId}"]`)
    return el ? new DOMWrapper(el as HTMLElement) : null
}

export function findAllByTestId(testId: string) {
    return Array.from(document.body.querySelectorAll(`[data-testid="${testId}"]`))
        .map(el => new DOMWrapper(el as HTMLElement))
}

export function bodyText() {
    return document.body.textContent || ''
}

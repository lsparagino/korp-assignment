import { describe, expect, it, afterEach } from 'vitest'
import ConfirmDialog from './ConfirmDialog.vue'
import { mountWithPlugins } from '@/test/setup'
import { DOMWrapper } from '@vue/test-utils'

describe('ConfirmDialog.vue', () => {
    let wrapper: ReturnType<typeof mountWithPlugins>

    afterEach(() => {
        wrapper?.unmount()
        document.body.innerHTML = ''
    })

    function mountDialog(props: Record<string, unknown> = {}) {
        return mountWithPlugins(ConfirmDialog, {
            props: {
                modelValue: true,
                ...props,
            },
            attachTo: document.body,
        })
    }

    function findByTestId(testId: string) {
        const el = document.body.querySelector(`[data-testid="${testId}"]`)
        return el ? new DOMWrapper(el as HTMLElement) : null
    }

    it('renders with default props', async () => {
        wrapper = mountDialog()
        await wrapper.vm.$nextTick()

        expect(findByTestId('dialog-title')?.text()).toContain('Confirm Action')
        expect(findByTestId('dialog-message')?.text()).toContain('Are you sure you want to proceed?')
    })

    it('renders custom title and message', async () => {
        wrapper = mountDialog({
            title: 'Custom Title',
            message: 'Custom Message',
        })
        await wrapper.vm.$nextTick()

        expect(findByTestId('dialog-title')?.text()).toContain('Custom Title')
        expect(findByTestId('dialog-message')?.text()).toContain('Custom Message')
    })

    it('emits update:modelValue with false and cancel on cancel button click', async () => {
        wrapper = mountDialog()
        await wrapper.vm.$nextTick()

        const cancelBtn = findByTestId('cancel-btn')
        expect(cancelBtn).not.toBeNull()
        await cancelBtn!.trigger('click')

        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
        expect(wrapper.emitted('cancel')).toBeTruthy()
    })

    it('emits confirm and closes dialog on confirm button click without pin', async () => {
        wrapper = mountDialog()
        await wrapper.vm.$nextTick()

        const confirmBtn = findByTestId('confirm-btn')
        expect(confirmBtn).not.toBeNull()
        await confirmBtn!.trigger('click')

        expect(wrapper.emitted('confirm')).toBeTruthy()
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
    })

    it('disables confirm button when pin is required', async () => {
        wrapper = mountDialog({ requiresPin: true })
        await wrapper.vm.$nextTick()

        expect(findByTestId('pin-section')).not.toBeNull()

        const confirmBtn = findByTestId('confirm-btn')
        expect(confirmBtn).not.toBeNull()
        expect(
            confirmBtn?.element.hasAttribute('disabled')
            || confirmBtn?.element.getAttribute('aria-disabled') === 'true',
        ).toBe(true)
    })
})

import { describe, expect, it, afterEach } from 'vitest'
import ConfirmDialog from './ConfirmDialog.vue'
import { mountWithPlugins } from '@/test/setup'
import { DOMWrapper } from '@vue/test-utils'

describe('ConfirmDialog.vue', () => {
    let wrapper: ReturnType<typeof mountWithPlugins>

    afterEach(() => {
        wrapper?.unmount()
        // Clean up teleported dialog content
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

    // VDialog teleports content to body, so we query document.body for rendered dialog content
    function getDialogContent() {
        return document.body.textContent || ''
    }

    function findButtonInBody(text: string) {
        const buttons = Array.from(document.body.querySelectorAll('button'))
        return buttons.find(btn => btn.textContent?.includes(text))
    }

    it('renders with default props', async () => {
        wrapper = mountDialog()
        await wrapper.vm.$nextTick()

        const content = getDialogContent()
        expect(content).toContain('Confirm Action')
        expect(content).toContain('Are you sure you want to proceed?')
    })

    it('renders custom title and message', async () => {
        wrapper = mountDialog({
            title: 'Custom Title',
            message: 'Custom Message',
        })
        await wrapper.vm.$nextTick()

        const content = getDialogContent()
        expect(content).toContain('Custom Title')
        expect(content).toContain('Custom Message')
    })

    it('emits update:modelValue with false and cancel on cancel button click', async () => {
        wrapper = mountDialog()
        await wrapper.vm.$nextTick()

        const cancelBtn = findButtonInBody('Cancel')
        expect(cancelBtn).toBeDefined()
        await new DOMWrapper(cancelBtn!).trigger('click')

        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
        expect(wrapper.emitted('cancel')).toBeTruthy()
    })

    it('emits confirm and closes dialog on confirm button click without pin', async () => {
        wrapper = mountDialog()
        await wrapper.vm.$nextTick()

        const confirmBtn = findButtonInBody('Yes, Proceed')
        expect(confirmBtn).toBeDefined()
        await new DOMWrapper(confirmBtn!).trigger('click')

        expect(wrapper.emitted('confirm')).toBeTruthy()
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false])
    })

    it('disables confirm button when pin is required', async () => {
        wrapper = mountDialog({ requiresPin: true })
        await wrapper.vm.$nextTick()

        const content = getDialogContent()
        expect(content).toContain('Verification Required')

        const confirmBtn = findButtonInBody('Yes, Proceed')
        expect(confirmBtn).toBeDefined()
        expect(
            confirmBtn?.hasAttribute('disabled')
            || confirmBtn?.getAttribute('aria-disabled') === 'true',
        ).toBe(true)
    })
})

import { describe, expect, it, vi } from 'vitest'
import { createDeferred } from '@/test/deferred'
import { useConfirmDialog } from './useConfirmDialog'

describe('useConfirmDialog', () => {
  it('initializes with dialog closed', () => {
    const { confirmDialog } = useConfirmDialog()
    expect(confirmDialog.value.show).toBe(false)
    expect(confirmDialog.value.title).toBe('')
    expect(confirmDialog.value.message).toBe('')
    expect(confirmDialog.value.processing).toBe(false)
  })

  it('opens the dialog with provided options', () => {
    const { confirmDialog, openConfirmDialog } = useConfirmDialog()

    openConfirmDialog({
      title: 'Delete Item',
      message: 'Are you sure?',
      requiresPin: false,
      onConfirm: async () => {},
    })

    expect(confirmDialog.value.show).toBe(true)
    expect(confirmDialog.value.title).toBe('Delete Item')
    expect(confirmDialog.value.message).toBe('Are you sure?')
    expect(confirmDialog.value.requiresPin).toBe(false)
    expect(confirmDialog.value.processing).toBe(false)
  })

  it('opens with pin requirement', () => {
    const { confirmDialog, openConfirmDialog } = useConfirmDialog()

    openConfirmDialog({
      title: 'Sensitive Action',
      message: 'Enter PIN',
      requiresPin: true,
      onConfirm: async () => {},
    })

    expect(confirmDialog.value.requiresPin).toBe(true)
  })

  it('closes the dialog', () => {
    const { confirmDialog, openConfirmDialog, closeConfirmDialog } = useConfirmDialog()

    openConfirmDialog({
      title: 'Test',
      message: 'Test',
      requiresPin: false,
      onConfirm: async () => {},
    })

    expect(confirmDialog.value.show).toBe(true)
    closeConfirmDialog()
    expect(confirmDialog.value.show).toBe(false)
  })

  it('sets processing to true while onConfirm is running', async () => {
    const { confirmDialog, openConfirmDialog, executeConfirm } = useConfirmDialog()
    const { promise, resolve } = createDeferred()

    openConfirmDialog({
      title: 'Delete',
      message: 'Confirm?',
      requiresPin: false,
      onConfirm: () => promise,
    })

    const execPromise = executeConfirm()
    expect(confirmDialog.value.processing).toBe(true)
    expect(confirmDialog.value.show).toBe(true)

    resolve()
    await execPromise

    expect(confirmDialog.value.processing).toBe(false)
    expect(confirmDialog.value.show).toBe(false)
  })

  it('closes dialog after executeConfirm succeeds', async () => {
    const onConfirm = vi.fn()
    const { confirmDialog, openConfirmDialog, executeConfirm } = useConfirmDialog()

    openConfirmDialog({
      title: 'Test',
      message: 'Test',
      requiresPin: false,
      onConfirm,
    })

    await executeConfirm()

    expect(onConfirm).toHaveBeenCalledOnce()
    expect(confirmDialog.value.show).toBe(false)
    expect(confirmDialog.value.processing).toBe(false)
  })

  it('resets processing when onConfirm throws', async () => {
    const { confirmDialog, openConfirmDialog, executeConfirm } = useConfirmDialog()

    openConfirmDialog({
      title: 'Fail',
      message: 'Will error',
      requiresPin: false,
      onConfirm: async () => {
        throw new Error('API Error')
      },
    })

    await expect(executeConfirm()).rejects.toThrow('API Error')
    expect(confirmDialog.value.processing).toBe(false)
  })

  it('prevents closing while processing', async () => {
    const { confirmDialog, openConfirmDialog, executeConfirm, closeConfirmDialog } = useConfirmDialog()
    const { promise, resolve } = createDeferred()

    openConfirmDialog({
      title: 'Test',
      message: 'Test',
      requiresPin: false,
      onConfirm: () => promise,
    })

    const execPromise = executeConfirm()
    expect(confirmDialog.value.processing).toBe(true)

    closeConfirmDialog()
    expect(confirmDialog.value.show).toBe(true)

    resolve()
    await execPromise
    expect(confirmDialog.value.show).toBe(false)
  })
})

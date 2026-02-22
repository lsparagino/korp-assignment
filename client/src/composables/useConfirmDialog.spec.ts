import { describe, expect, it } from 'vitest'
import { useConfirmDialog } from './useConfirmDialog'

describe('useConfirmDialog', () => {
  it('initializes with dialog closed', () => {
    const { confirmDialog } = useConfirmDialog()
    expect(confirmDialog.value.show).toBe(false)
    expect(confirmDialog.value.title).toBe('')
    expect(confirmDialog.value.message).toBe('')
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
})

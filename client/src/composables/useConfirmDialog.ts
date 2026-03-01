import { ref } from 'vue'

interface ConfirmDialogState {
  show: boolean
  title: string
  message: string
  requiresPin: boolean
  processing: boolean
  onConfirm: () => void | Promise<void>
}

export function useConfirmDialog() {
  const confirmDialog = ref<ConfirmDialogState>({
    show: false,
    title: '',
    message: '',
    requiresPin: false,
    processing: false,
    onConfirm: () => { },
  })

  function openConfirmDialog(options: Omit<ConfirmDialogState, 'show' | 'processing'>) {
    confirmDialog.value = {
      show: true,
      processing: false,
      ...options,
    }
  }

  async function executeConfirm() {
    confirmDialog.value.processing = true
    try {
      await confirmDialog.value.onConfirm()
      confirmDialog.value.show = false
    } finally {
      confirmDialog.value.processing = false
    }
  }

  function closeConfirmDialog() {
    if (!confirmDialog.value.processing) {
      confirmDialog.value.show = false
    }
  }

  return {
    confirmDialog,
    openConfirmDialog,
    executeConfirm,
    closeConfirmDialog,
  }
}

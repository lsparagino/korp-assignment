import { ref } from 'vue'

interface ConfirmDialogState {
  show: boolean
  title: string
  message: string
  requiresPin: boolean
  onConfirm: () => void
}

export function useConfirmDialog () {
  const confirmDialog = ref<ConfirmDialogState>({
    show: false,
    title: '',
    message: '',
    requiresPin: false,
    onConfirm: () => {},
  })

  function openConfirmDialog (options: Omit<ConfirmDialogState, 'show'>) {
    confirmDialog.value = {
      show: true,
      ...options,
    }
  }

  function closeConfirmDialog () {
    confirmDialog.value.show = false
  }

  return {
    confirmDialog,
    openConfirmDialog,
    closeConfirmDialog,
  }
}

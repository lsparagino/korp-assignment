import { api } from '@/plugins/api'

export interface Wallet {
    id: number
    name: string
    address: string
    balance: number
    currency: string
    status: 'active' | 'frozen'
    can_delete: boolean
}


interface WalletParams {
    page?: number
    per_page?: number
}

interface WalletForm {
    name: string
    currency: string
}

export function fetchWallets(params?: WalletParams) {
    return api.get('/wallets', { params })
}

export function fetchWallet(id: string | number) {
    return api.get(`/wallets/${id}`)
}

export function createWallet(form: WalletForm) {
    return api.post('/wallets', form)
}

export function updateWallet(id: string | number, form: WalletForm) {
    return api.put(`/wallets/${id}`, form)
}

export function toggleWalletFreeze(id: number) {
    return api.patch(`/wallets/${id}/toggle-freeze`)
}

export function deleteWallet(id: number) {
    return api.delete(`/wallets/${id}`)
}

import { api } from '@/plugins/api'

export interface RecoveryCode {
    code: string
}


interface ProfileForm {
    name: string
    email: string
}

interface PasswordForm {
    current_password: string
    password: string
    password_confirmation: string
}

export function updateProfile(form: ProfileForm) {
    return api.patch('/settings/profile', form)
}

export function deleteAccount(form: { password: string }) {
    return api.delete('/settings/profile', { data: form })
}

export function updatePassword(form: PasswordForm) {
    return api.put('/settings/password', form)
}

export function enableTwoFactor() {
    return api.post('/user/two-factor-authentication')
}

export function getTwoFactorQrCode() {
    return api.get('/user/two-factor-qr-code')
}

export function confirmTwoFactor(code: string) {
    return api.post('/user/confirmed-two-factor-authentication', { code })
}

export function disableTwoFactor() {
    return api.delete('/user/two-factor-authentication')
}

export function getRecoveryCodes() {
    return api.get('/user/two-factor-recovery-codes')
}

export function cancelPendingEmail() {
    return api.delete('/settings/pending-email')
}

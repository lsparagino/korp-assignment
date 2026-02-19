import { api } from '@/plugins/api'

interface LoginForm {
    email: string
    password: string
    remember: boolean
}

interface RegisterForm {
    name: string
    email: string
    password: string
    password_confirmation: string
}

interface ResetPasswordForm {
    token: string
    email: string
    password: string
    password_confirmation: string
}

export function login(form: LoginForm) {
    return api.post('/login', form)
}

export function register(form: RegisterForm) {
    return api.post('/register', form)
}

export function logout() {
    return api.post('/logout')
}

export function forgotPassword(form: { email: string }) {
    return api.post('/forgot-password', form)
}

export function resetPassword(form: ResetPasswordForm) {
    return api.post('/reset-password', form)
}

export function confirmPassword(form: { password: string }) {
    return api.post('/user/confirm-password', form)
}

export function twoFactorChallenge(payload: Record<string, unknown>) {
    return api.post('/two-factor-challenge', payload)
}

export function sendVerificationEmail() {
    return api.post('/email/verification-notification')
}

export function fetchUser() {
    return api.get('/user')
}

export function verifyInvitation(token: string) {
    return api.get(`/invitation/${token}`)
}

export function acceptInvitation(token: string, form: { password: string, password_confirmation: string }) {
    return api.post(`/accept-invitation/${token}`, form)
}

export function deleteUser(password: string) {
    return api.delete('/user', { data: { password } })
}

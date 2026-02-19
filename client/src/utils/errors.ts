import type { AxiosError } from 'axios'

interface ApiErrorResponse {
    errors?: Record<string, string[]>
    message?: string
}

export function isApiError(error: unknown, status: number): boolean {
    return (error as AxiosError)?.response?.status === status
}

export function getValidationErrors(error: unknown): Record<string, string[]> {
    if (!isApiError(error, 422)) {
        return {}
    }

    const data = (error as AxiosError<ApiErrorResponse>).response?.data
    return data?.errors ?? {}
}

export function getErrorMessage(error: unknown, fallback = 'Something went wrong.'): string {
    const data = (error as AxiosError<ApiErrorResponse>).response?.data
    return data?.message || fallback
}

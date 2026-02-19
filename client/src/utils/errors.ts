import type { AxiosError } from 'axios'

interface ApiErrorResponse {
    errors?: Record<string, string[]>
    message?: string
}

/**
 * Checks if an unknown error is an Axios error with the given HTTP status code.
 */
export function isApiError(error: unknown, status: number): boolean {
    return (error as AxiosError)?.response?.status === status
}

/**
 * Extracts validation errors from a 422 response, returning an empty object if
 * the error is not a validation error.
 */
export function getValidationErrors(error: unknown): Record<string, string[]> {
    if (!isApiError(error, 422)) {
        return {}
    }

    const data = (error as AxiosError<ApiErrorResponse>).response?.data
    return data?.errors ?? {}
}

/**
 * Extracts the error message from an API error response, returning the
 * fallback string if no message is present.
 */
export function getErrorMessage(error: unknown, fallback = 'Something went wrong.'): string {
    const data = (error as AxiosError<ApiErrorResponse>).response?.data
    return data?.message || fallback
}

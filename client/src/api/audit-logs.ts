import { api } from '@/plugins/api'

export interface AuditLog {
    id: string
    user_id: number | null
    user_name: string | null
    company_id: number | null
    category: string
    severity: string
    action: string
    description: string
    metadata: Record<string, unknown> | null
    ip_address: string | null
    created_at: number
}

export interface AuditLogFilters {
    category?: string
    severity?: string
    user_id?: number
    date_from?: string
    date_to?: string
    cursor?: number
    per_page?: number
}

export interface AuditLogResponse {
    data: AuditLog[]
    meta: {
        next_cursor: number | null
    }
}

export function fetchAuditLogs(params: AuditLogFilters) {
    return api.get<AuditLogResponse>('/audit-logs', { params })
}

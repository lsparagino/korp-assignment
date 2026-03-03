import { flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { setupPinia } from '@/test/pinia'

vi.mock('@/api/audit-logs', () => ({
    fetchAuditLogs: vi.fn(),
}))

function makePage(data: any[], nextCursor: number | null = null) {
    return { data: { data, meta: { next_cursor: nextCursor } } }
}

function makeLog(id: string) {
    return {
        id,
        user_id: 1,
        user_name: 'Admin',
        company_id: 1,
        category: 'auth',
        severity: 'normal',
        action: 'login',
        description: `Log ${id}`,
        metadata: null,
        ip_address: '127.0.0.1',
        created_at: 1700000000,
    }
}

describe('useAuditLogList', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        setupPinia({
            company: { currentCompany: { id: 1, name: 'Test' }, companies: [] },
        })
    })

    it('fetches initial data', async () => {
        const { fetchAuditLogs } = await import('@/api/audit-logs')
        vi.mocked(fetchAuditLogs).mockResolvedValue(
            makePage([makeLog('1'), makeLog('2')], 100) as any,
        )

        const { useAuditLogList } = await import('@/queries/audit-logs')
        const { logs, nextCursor } = useAuditLogList()
        await flushPromises()

        expect(fetchAuditLogs).toHaveBeenCalledTimes(1)
        expect(logs.value).toHaveLength(2)
        expect(nextCursor.value).toBe(100)
    })

    it('loadMore triggers a new fetch with cursor', async () => {
        const { fetchAuditLogs } = await import('@/api/audit-logs')
        vi.mocked(fetchAuditLogs)
            .mockResolvedValueOnce(makePage([makeLog('1')], 100) as any)
            .mockResolvedValueOnce(makePage([makeLog('2')], null) as any)

        const { useAuditLogList } = await import('@/queries/audit-logs')
        const { logs, nextCursor, loadMore } = useAuditLogList()
        await flushPromises()

        expect(logs.value).toHaveLength(1)
        expect(nextCursor.value).toBe(100)

        loadMore()
        await flushPromises()

        expect(fetchAuditLogs).toHaveBeenCalledTimes(2)
        // Second call should include cursor param
        expect(fetchAuditLogs).toHaveBeenLastCalledWith(
            expect.objectContaining({ cursor: 100 }),
        )
        expect(logs.value).toHaveLength(2)
        expect(nextCursor.value).toBeNull()
    })

    it('loadMore does nothing when nextCursor is null', async () => {
        const { fetchAuditLogs } = await import('@/api/audit-logs')
        vi.mocked(fetchAuditLogs).mockResolvedValue(
            makePage([makeLog('1')], null) as any,
        )

        const { useAuditLogList } = await import('@/queries/audit-logs')
        const { loadMore } = useAuditLogList()
        await flushPromises()

        loadMore()
        await flushPromises()

        // Should not have made a second call
        expect(fetchAuditLogs).toHaveBeenCalledTimes(1)
    })

    it('clearFilters resets accumulated data', async () => {
        const { fetchAuditLogs } = await import('@/api/audit-logs')
        vi.mocked(fetchAuditLogs).mockResolvedValue(
            makePage([makeLog('1')], null) as any,
        )

        const { useAuditLogList } = await import('@/queries/audit-logs')
        const { logs, clearFilters } = useAuditLogList()
        await flushPromises()

        expect(logs.value).toHaveLength(1)

        clearFilters()
        expect(logs.value).toHaveLength(0)
    })
})

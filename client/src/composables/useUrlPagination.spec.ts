import { describe, expect, it, vi } from 'vitest'
import { useUrlPagination } from './useUrlPagination'

// Mock vue-router
const mockRoute = { query: {} }
const mockRouter = { push: vi.fn() }

vi.mock('vue-router', () => ({
    useRoute: () => mockRoute,
    useRouter: () => mockRouter,
}))

describe('useUrlPagination', () => {
    it('initializes with default values when query is empty', () => {
        mockRoute.query = {}
        const { page, perPage } = useUrlPagination()

        expect(page.value).toBe(1)
        expect(perPage.value).toBe(10)
    })

    it('initializes with custom default perPage', () => {
        mockRoute.query = {}
        const { perPage } = useUrlPagination({ defaultPerPage: 25 })

        expect(perPage.value).toBe(25)
    })

    it('reads values from route query', () => {
        mockRoute.query = { page: '3', per_page: '50' }
        const { page, perPage } = useUrlPagination()

        expect(page.value).toBe(3)
        expect(perPage.value).toBe(50)
    })

    describe('handlePageChange', () => {
        it('pushes new page to router', () => {
            mockRoute.query = { per_page: '20' }
            const { handlePageChange } = useUrlPagination()

            handlePageChange(2)

            expect(mockRouter.push).toHaveBeenCalledWith({
                query: { per_page: '20', page: '2' },
            })
        })

        it('removes page query param when navigating to page 1', () => {
            mockRoute.query = { page: '2', per_page: '20' }
            const { handlePageChange } = useUrlPagination()

            handlePageChange(1)

            expect(mockRouter.push).toHaveBeenCalledWith({
                query: { per_page: '20' },
            })
        })
    })

    describe('handlePerPageChange', () => {
        it('pushes new per_page to router and resets to page 1', () => {
            mockRoute.query = { page: '3', filter: 'active' }
            const { handlePerPageChange } = useUrlPagination()

            handlePerPageChange(50)

            expect(mockRouter.push).toHaveBeenCalledWith({
                query: { filter: 'active', page: '1', per_page: '50' },
            })
        })

        it('removes per_page query param when matching default', () => {
            mockRoute.query = { page: '2', per_page: '50' }
            const { handlePerPageChange } = useUrlPagination({ defaultPerPage: 25 })

            handlePerPageChange(25)

            expect(mockRouter.push).toHaveBeenCalledWith({
                query: { page: '1' },
            })
        })
    })
})

/** Request-side params — used by API fetch functions */
export interface PaginationParams {
  page?: number
  per_page?: number
}

/** Response-side meta — returned by Laravel's paginator */
export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

import { request } from '@playwright/test'

const API_BASE = 'http://127.0.0.1:8001/api/v0'

export async function resetDatabase () {
  const ctx = await request.newContext()
  const response = await ctx.post(`${API_BASE}/test/reset-database`)
  if (!response.ok()) {
    throw new Error(`Failed to reset database: ${response.status()} ${await response.text()}`)
  }
  await ctx.dispose()
}

export async function createUser (attrs: {
  name: string
  email: string
  password?: string
  role?: 'admin' | 'member'
  company_id?: number
}) {
  const ctx = await request.newContext()
  const response = await ctx.post(`${API_BASE}/test/create-user`, { data: attrs })
  if (!response.ok()) {
    throw new Error(`Failed to create user: ${response.status()} ${await response.text()}`)
  }
  const data = await response.json()
  await ctx.dispose()
  return data as { user: Record<string, unknown>, token: string }
}

export async function loginViaApi (email: string, password: string) {
  const ctx = await request.newContext()
  const response = await ctx.post(`${API_BASE}/test/login`, {
    data: { email, password },
  })
  if (!response.ok()) {
    throw new Error(`Failed to login: ${response.status()} ${await response.text()}`)
  }
  const data = await response.json()
  await ctx.dispose()
  return data as {
    user: Record<string, unknown>
    token: string
    company: { id: number, name: string }
  }
}

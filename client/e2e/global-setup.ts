import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { test as setup } from '@playwright/test'
import { loginViaApi, resetDatabase } from './helpers/api'
import { buildStorageState } from './helpers/auth'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const authDir = path.join(__dirname, '.auth')

setup('reset database and create auth states', async () => {
  // Ensure auth directory exists
  fs.mkdirSync(authDir, { recursive: true })

  // Reset database to seeded state
  await resetDatabase()

  // Login as admin and save storage state
  const admin = await loginViaApi('admin@example.com', 'password')
  const adminState = buildStorageState(admin)
  fs.writeFileSync(path.join(authDir, 'admin.json'), JSON.stringify(adminState, null, 2))

  // Login as member and save storage state
  const member = await loginViaApi('member@example.com', 'password')
  const memberState = buildStorageState(member)
  fs.writeFileSync(path.join(authDir, 'member.json'), JSON.stringify(memberState, null, 2))
})

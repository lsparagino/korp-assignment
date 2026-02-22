import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { test as teardown } from '@playwright/test'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const authDir = path.join(__dirname, '.auth')

teardown('clean up auth states', async () => {
  if (fs.existsSync(authDir)) {
    fs.rmSync(authDir, { recursive: true })
  }
})

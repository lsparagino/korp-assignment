import { test as teardown } from '@playwright/test'
import path from 'path'
import fs from 'fs'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const authDir = path.join(__dirname, '.auth')

teardown('clean up auth states', async () => {
    if (fs.existsSync(authDir)) {
        fs.rmSync(authDir, { recursive: true })
    }
})

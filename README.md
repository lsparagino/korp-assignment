# SecureWallet

A multi-company financial wallet management system built with Laravel 12 and Vue.js 3 (Vuetify).

## 🚀 Setup Instructions

### Backend (Laravel)

1.  **Clone the repository** and navigate to the project root.
2.  **Install PHP dependencies**:
    ```bash
    composer install
    ```
3.  **Environment Setup**:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    - *Note*: Ensure `APP_URL` in `.env` matches your local server (e.g., `http://localhost:8000`).
4.  **Database Setup**:
    - Create a database if not using SQLite (SQLite is the default in `.env.example`).
    - Run migrations and seed data:
      ```bash
      php artisan migrate --seed
      ```
5.  **Serve the application**:
    - **Option A (Artisan)**: Run `php artisan serve`.
    - **Option B (Herd/Valet)**: Ensure the folder is linked or parked (accessible at `http://securewallet.test`).
    - Test the health check at `{APP_URL}/up`.

### Frontend (Vue.js)

1.  Navigate to the `client` directory:
    ```bash
    cd client
    ```
2.  **Install dependencies**:
    ```bash
    npm install
    ```
3.  **Environment Setup**:
    - Create/Edit `client/.env`.
    - Set `VITE_API_BASE_URL` to match your backend URL (e.g., `http://localhost:8000/api/v0` or `http://securewallet.test/api/v0`).
4.  **Start development server**:
    ```bash
    npm run dev
    ```

---

## 🔐 Credentials

Default accounts created during seeding:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `password` |
| Member | `member@example.com` | `password` |
| Manager | `manager@example.com` | `password` |

---

## 📊 Database Seeding

To reset and seed the database with fresh dummy data (Wallets, Transactions, and an Acme Corp company):

```bash
php artisan migrate:fresh --seed
```

### Dummy Data Summary
- **Companies**: Acme Corp
- **Users**: Admin User, Member User, Manager User
- **Wallets**: Multiple USD, EUR and GBP wallets, ready to be assigned.
- **Transactions**: Deposit transaction of 20k (currency) for each wallet

---

## ✨ New Features

- **User Preferences** — Notification opt-out, date/number locale settings, and personal security thresholds.
- **Manager Role** — A new role with visibility into all wallets and the ability to approve/reject transactions. Managers cannot access the audit log or promote/demote team members.
- **Transaction Approval Thresholds** — Configurable by admins on a per-currency basis. Transactions initiated by managers and admins are auto-approved.
- **Transaction Approval Flow** — Pending transactions can be approved or rejected by managers and admins, with support for rejection reasons.
- **Transaction Details with Context** — The transaction detail modal displays the initiator's recent transactions to aid in review decisions.
- **Team Member Detail Page** — Replaced the modal-based editing with a dedicated detail page for richer team member management.
- **Wallet Detail Page** — Replaced the modal-based editing with a dedicated detail page for wallet management.
- **Address Book** — Saved external addresses for frequently used recipients.
- **Email Notifications** — Automated email alerts for transaction events (initiation, approval, rejection, cancellation).
- **Audit Logs** — Admin-accessible activity logs for accountability and compliance.
- **Transaction Cancellation** — Users can cancel their own pending transactions while they are awaiting approval.
- **Identity Confirmation for Sensitive Operations** — Security-critical actions (e.g., modifying approval thresholds) require re-authentication via account password or 2FA code if enabled.

---

## 🔧 New Features (Technical)

- **Major Refactoring** — Codebase restructured for improved maintainability, separation of concerns, and testability.
- **Internationalization (i18n)** — Fully localization-ready frontend with all user-facing strings externalized.
- **Static Analysis** — Integrated SonarQube for continuous code quality and security analysis.
- **Audit Logs in Firestore** — Leveraging GCP Firestore as a scalable, append-only store for audit trail data.
- **CI/CD with GitHub Actions** — Automated test execution on pull requests and pushes.
- **Idempotency Keys** — All mutating financial operations support idempotency keys to prevent duplicate processing, also serving as groundwork for future queue-based architecture.
- **Frontend Testing** — Added unit tests (Vitest) and end-to-end tests (Playwright). Coverage can be further improved.
- **Double-Entry Transactions** — Each transaction records two rows for internal transfers, allowing a user specific perspective. Source and destination currencies with an exchange rate are also recorded, enabling multi-currency transfer support.

---


## 🔮 Future Improvements

- **Dedicated Transaction Detail Page** — Transactions currently open in a modal; a full detail page (similar to wallets and team members) would improve usability and allow richer interactions.
- **Asynchronous Processing via Cloud Tasks** — Transaction processing and deferred audit log writes should be offloaded to queues (e.g., GCP Cloud Tasks) to eliminate unnecessary request delays in the current synchronous implementation.
- **Push Notifications** — With queue infrastructure in place, real-time notifications (e.g., via Firebase Cloud Messaging) should be implemented for transaction updates, approval requests, and other events.

> **Note**: Queue-based processing and push notifications are not currently implemented due to cost considerations.

---

## ⚠️ Known Issues

- Newly registered users cannot interact with the application because they don't have a company. The only way to access the platform for new users is to be invited to a team.
- ~~Changing email should trigger a new email validation process.~~ **FIXED**
- ~~The email verification process is currently not implemented.~~ **FIXED**
- Fortify implementation is very basic.
- ~~Email layouts are not customized.~~ **FIXED**
- ~~There's no localization.~~ **FIXED**
- The API base path `/api/v0` is currently redundant, since server and client are each deployed on a dedicated third level domain. In this scenario, `/v0` alone could have been enough.
- Synchronous logging to Firestore introduces noticeable latency on audited operations. This should be mitigated by offloading log writes to a background queue.
- Everyone is able to see other team members' email addresses. This might not be a desired behaviour. Ideally, only admins should be able to see email addresses.
- Audit logs should have a user filter.
- Firestore is currently using a TTL policy to delete audit logs after 7 days. This is not a good practice for audit logs, as they should be kept for a longer period of time (at least 6 months).
- The audit log visible by admins is a subset of the full audit trail. User-scoped actions (e.g. login, logout, registration, 2FA setup, password changes, profile updates, account deletion) are intentionally logged without a `company_id` and therefore do not appear in the company-specific audit table.

---

## 💡 Assumptions and Interpretations

- ~~Instead of applying an "initial balance" to the wallet (I assumed that a newly created wallet should have zero balance), I introduced **external transactions**. If all transactions are between internal wallets starting at zero balance, the overall balance will always be 0. External transactions prevent this by adding or removing money from the user's wallets.~~
- It's unclear how users become admins. In this basic implementation, admin roles can only be set from the database. Regarding members, there are currently two ways to create them:
    - A user registers using the sign-up form: in this scenario, the user has no company and must be invited to a team. The address is immediately added since the user is already verified (email verification not yet implemented).
    - An admin invites an email address that is not already registered. In this scenario, I implemented an **invitation mechanism**, so that the user can accept the invite and create a password for their account.
- I assumed wallets with at least one transaction cannot be deleted. Only newly created wallets can be deleted.
- Delete operations require the user to type a "pin" shown on screen. This is mostly to showcase a delete protection for critical operations.
- The infrastructure has been deployed on **GCP (Singapore region)** with Terraform, using 2 separate Cloud Run services and a very small MySQL instance to keep costs to a minimum. Due to low performance settings and cold starts, you might experience delays. 
- This is currently a **mono repo** for convenience. Depending on company policies or personal preference, it could be split into client and server repos.
- External transactions could theoretically fail silently due to uncontrollable external factors. This edge case is acknowledged but intentionally not addressed in the current implementation.
- Address validation (e.g., wallet hash or IBAN format checks) is not implemented, as the system does not integrate with real financial networks or blockchain providers.


---

## 🛠 API Endpoints Summary

All API routes are prefixed with `/api/v0/`. All protected routes require a `Bearer` token.

> Endpoints marked with 🆕 are newly added, ✏️ indicates a changed endpoint, and 🗑️ indicates a removed endpoint.

### 🔐 Authentication
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/login` | Authenticate and receive token. |
| `POST` | `/register` | Create a new account. |
| `POST` | `/logout` | Revoke current session (Protected). |
| `POST` | `/two-factor-challenge` | Verify TOTP or recovery codes. |
| `POST` | `/forgot-password` | Request password reset link. |
| `POST` | `/reset-password` | Update password with reset token. |
| `GET` | `/invitation/{token}` | Verify invitation token. |
| `POST` | ✏️ `/invitation/{token}/accept` | Accept invitation and set password. |

### 👤 Profile & User (Protected)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/user` | Get current authenticated user details. |
| `POST` | `/user/confirm-password` | Confirm password. |
| `PATCH` | `/settings/profile` | Update profile information. |
| `DELETE` | `/settings/profile` | Delete user account. |
| `PUT` | `/settings/password` | Update account password. |
| `POST` | `/email/verification-notification` | Resend verification email. |
| `GET` | `/email/verify/{id}/{hash}` | Verify email address. |
| `DELETE` | 🆕 `/settings/pending-email` | Cancel a pending email change. |
| `GET` | 🆕 `/settings/preferences` | Get user preferences. |
| `PUT` | 🆕 `/settings/preferences` | Update user preferences. |

### 🛡️ Two-Factor Authentication (Protected)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | ✏️ `/user/two-factor/qr-code` | Get 2FA setup QR code. |
| `POST` | ✏️ `/user/two-factor/authentication` | Enable 2FA. |
| `POST` | ✏️ `/user/two-factor/confirmed-authentication` | Confirm 2FA setup. |
| `DELETE` | ✏️ `/user/two-factor/authentication` | Disable 2FA. |
| `GET` | ✏️ `/user/two-factor/recovery-codes` | List recovery codes. |
| `POST` | ✏️ `/user/two-factor/recovery-codes` | Regenerate recovery codes. |

### 💰 Financials & Management (Protected)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/dashboard` | Financial overview (balances, top wallets, recent transactions). |
| `GET` | `/companies` | List user's companies. |
| `GET` | 🆕 `/currencies` | List supported currencies. |
| `GET` | `/transactions` | List transactions (filterable by type, date, amount, reference, wallet). |
| `POST` | 🆕 `/transfers` | Initiate a new transfer (requires Idempotency-Key header). |
| `POST` | 🆕 `/transfers/{groupId}/review` | Approve or reject a pending transfer. |
| `POST` | 🆕 `/transfers/{groupId}/cancel` | Cancel own pending transfer. |
| `GET` | `/wallets` | List wallets. |
| `POST` | `/wallets` | Create a new wallet. |
| `GET` | 🆕 `/wallets/{wallet}` | Get wallet details. |
| `PUT` | `/wallets/{wallet}` | Update a wallet. |
| `DELETE` | `/wallets/{wallet}` | Delete a wallet (only if no transactions). |
| `PATCH` | `/wallets/{wallet}/toggle-freeze` | Freeze/Unfreeze a wallet. |
| `GET` | `/team-members` | List team members. |
| `POST` | `/team-members` | Invite a new team member. |
| `GET` | 🆕 `/team-members/{member}` | Get team member details. |
| `PUT` | `/team-members/{member}` | Update a team member. |
| `DELETE` | `/team-members/{member}` | Remove a team member. |
| `PATCH` | 🆕 `/team-members/{member}/promote` | Promote/demote a team member's role. |

### ⚙️ Settings — Approval Thresholds (Protected, Admin)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | 🆕 `/settings/thresholds` | List approval thresholds per currency. |
| `PUT` | 🆕 `/settings/thresholds` | Create or update an approval threshold. |
| `DELETE` | 🆕 `/settings/thresholds/{threshold}` | Remove an approval threshold. |

### 📋 Address Book (Protected)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | 🆕 `/address-book` | List saved external addresses. |
| `POST` | 🆕 `/address-book` | Save a new external address. |
| `PUT` | 🆕 `/address-book/{entry}` | Update a saved address. |
| `DELETE` | 🆕 `/address-book/{entry}` | Delete a saved address. |

### 🔍 Audit Logs (Protected, Admin)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | 🆕 `/audit-logs` | List audit log entries. |

---

## ☁️ Deployment (GCP Cloud Run)

The application deploys to Google Cloud Platform using Terraform. Infrastructure is defined in `terraform/`.

### Quick Deploy

```powershell
.\deployment\gcp\deploy.ps1 -ProjectId "your-project-id" -Apply
```

This builds and pushes Docker images, then runs `terraform apply` to update Cloud Run services.

### Database Migration

Create and run a Cloud Run job to execute migrations against the remote database:

```bash
# Create the migration job (first time only)
gcloud run jobs create migrate \
  --image {region}-docker.pkg.dev/{your-project-id}/korp-repo/api:latest \
  --command "php,artisan,migrate:fresh,--seed,--force" \
  --region {region} \
  --vpc-connector korp-connector \
  --set-env-vars "DB_CONNECTION=mysql,DB_HOST={db_ip},DB_DATABASE={db_name},DB_USERNAME={db_user},DB_PASSWORD='{db_pass}'"

# Execute the migration
gcloud run jobs execute migrate --region {region}
```

### Cost Optimization
Both Cloud Run services are configured for minimal cost:
- **CPU throttling** enabled (billed only during request processing).
- **Scale-to-zero** (minScale = 0) — no charges when idle.
- **Max instances** capped at 2 per service.
- **Database** uses the smallest Cloud SQL tier (`db-f1-micro`).

> **Note**: First request after idle may have a cold start delay (~30s).

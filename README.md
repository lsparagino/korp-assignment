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

## 🔐 Admin Credentials

A default admin account is created during seeding:

- **Email**: `admin@example.com`
- **Password**: `password`

---

## 📊 Database Seeding

To reset and seed the database with fresh dummy data (Wallets, Transactions, and an Acme Corp company):

```bash
php artisan migrate:fresh --seed
```

### Dummy Data Summary
- **Companies**: Acme Corp
- **Users**: Admin User, Member User
- **Wallets**: Multiple USD and EUR wallets assigned to users.
- **Transactions**: 50+ mixed internal and external transactions.

---

## 🛠 API Endpoints Summary

All API routes are prefixed with `/api/v0/`. All protected routes require a `Bearer` token.

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
| `POST` | `/accept-invitation/{token}` | Accept invitation and set password. |

### 👤 Profile & User (Protected)
| Method | Endpoint | Description                             |
| :--- | :--- |:----------------------------------------|
| `GET` | `/user` | Get current authenticated user details. |
| `POST` | `/user/confirm-password` | Confirm password.                       |
| `PATCH` | `/settings/profile` | Update profile information.             |
| `DELETE` | `/settings/profile` | Delete user account.                    |
| `PUT` | `/settings/password` | Update account password.                |
| `POST` | `/email/verification-notification` | Resend verification email.              |
| `GET` | `/email/verify/{id}/{hash}` | Verify email address.                   |

### 🛡️ Two-Factor Authentication (Protected)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/user/two-factor-qr-code` | Get 2FA setup QR code. |
| `POST` | `/user/two-factor-authentication` | Enable 2FA. |
| `POST` | `/user/confirmed-two-factor-authentication` | Confirm 2FA setup. |
| `DELETE` | `/user/two-factor-authentication` | Disable 2FA. |
| `GET` | `/user/two-factor-recovery-codes` | List recovery codes. |
| `POST` | `/user/two-factor-recovery-codes` | Regenerate recovery codes. |

### 💰 Financials & Management (Protected)
| Method | Endpoint | Description                           |
| :--- | :--- |:--------------------------------------|
| `GET` | `/dashboard` | Financial overview |
| `GET` | `/companies` | List user's companies.                |
| `GET` | `/transactions` | List all transactions (filterable).   |
| `GET/POST` | `/wallets` | List or create wallets.               |
| `GET/PUT/DELETE` | `/wallets/{wallet}` | Manage a specific wallet.             |
| `PATCH` | `/wallets/{wallet}/toggle-freeze` | Freeze/Unfreeze a wallet.             |
| `GET/POST` | `/team-members` | List or invite team members.          |
| `PUT/DELETE` | `/team-members/{member}` | Manage team members.                  |

---

## 🛡 Features
- **Two-Factor Authentication (TOTP)**: Secure your account with authenticator apps.
- **Multi-Currency Support**: Unified dashboard for USD and EUR tracking.
- **Role-Based Access**: Granular control for Admin vs Member roles.
- **Audit-Safe Wallet Deletion**: Wallets with transaction history cannot be deleted.

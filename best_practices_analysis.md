# Server Code Best Practices Analysis

Analysis of the Laravel server code against the practices defined in [laravel_best_practices.md](file:///d:/LaravelHerd/korp-assignment/.agent/laravel_best_practices.md).

> [!TIP]
> Items are grouped by practice area and marked with severity: 🔴 High · 🟡 Medium · 🟢 Low

---

## 1. Fat Controllers / SRP Violations

### 🔴 `TeamMemberController::index` — Query logic belongs in a Service or Model

The [index](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/TeamMemberController.php#L23-L51) method contains a complex inline query with `whereHas`, constrained eager loads, and `withCount`. This should be delegated to [TeamMemberService](file:///d:/LaravelHerd/korp-assignment/app/Services/TeamMemberService.php#14-78) (which already exists but isn't used for listing).

```php
// Current — fat controller
$users = User::query()
    ->whereHas('companies', function ($q) use ($companyId) {
        $q->where('companies.id', $companyId);
    })
    ->with(['assignedWallets' => function ($q) use ($companyId) {
        $q->where('company_id', $companyId);
    }])
    ->withCount(['assignedWallets' => ...])
    ->orderBy('name')
    ->paginate(10);
```

**Fix:** Move this query into `TeamMemberService::list(int $companyId)` or a model scope.

---

### 🔴 `TeamMemberController::store` — Authorization logic in controller

[Lines 58-61](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/TeamMemberController.php#L57-L61) perform manual company authorization inside the controller. This should be in the `StoreTeamMemberRequest` or a Policy.

```php
// Current — inline authorization
$companyId = $request->input('company_id');
if (! $companyId || ! $request->user()->companies()->where('companies.id', $companyId)->exists()) {
    abort(403, 'Unauthorized access to company.');
}
```

**Fix:** Move into the `authorize()` method of `StoreTeamMemberRequest`, or create a `TeamMemberPolicy`.

---

### 🔴 `VerificationController::verify` — Business logic in controller

The [verify](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/VerificationController.php#L25-L57) method contains 30+ lines of signature validation, hash checking, and conditional updates (pending email swap vs. first-time verification). This is business logic.

**Fix:** Extract into a `VerificationService::verifyEmail(int $id, string $hash, Request $request)` method.

---

### 🟡 [WalletController](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/WalletController.php#16-94) — Inline business logic in [store](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/TwoFactorController.php#15-21) and [toggleFreeze](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/WalletController.php#61-73)

[store](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/WalletController.php#L39-L52) constructs the model creation array directly. [toggleFreeze](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/WalletController.php#L61-L72) contains toggle logic that could live in the model or [WalletService](file:///d:/LaravelHerd/korp-assignment/app/Services/WalletService.php#7-52).

[WalletService](file:///d:/LaravelHerd/korp-assignment/app/Services/WalletService.php#7-52) exists but is only used for dashboard aggregations — not for CRUD operations.

**Fix:** Add `WalletService::create()` and `Wallet::toggleFreeze()` (model method).

---

### 🟡 `AuthController::register` — Multi-step business logic

[register](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/AuthController.php#L44-L66) handles user creation, email dispatch with error recovery (delete user on failure), and token creation. This is 20+ lines of orchestration.

**Fix:** Move into `AuthService::register()`.

---

### 🟡 `AuthController::confirmPassword` — Password verification in controller

[confirmPassword](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/AuthController.php#L82-L93) does a `Hash::check` and throws. This is simple, but it should be consistent — delegate to [AuthService](file:///d:/LaravelHerd/korp-assignment/app/Services/AuthService.php#14-143).

---

## 2. DocBlocks

### 🟢 Unnecessary DocBlocks throughout

Per the best practices: *"DocBlocks reduce readability. Use a descriptive method name and modern PHP features like return type hints instead."*

The following files contain redundant DocBlocks that only restate the obvious (e.g., `/** Display a listing of the resource. */`):

| File | Lines |
|------|-------|
| [TeamMemberController.php](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/TeamMemberController.php) | L20-22, L53-55, L73-75, L92-94 |
| [ProfileController.php](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Settings/ProfileController.php) | L15-17, L31-33, L45-47 |
| [PasswordController.php](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Settings/PasswordController.php) | L11-13 |
| [WalletResource.php](file:///d:/LaravelHerd/korp-assignment/app/Http/Resources/WalletResource.php) | L10-14 |
| [TransactionResource.php](file:///d:/LaravelHerd/korp-assignment/app/Http/Resources/TransactionResource.php) | L10-14 |
| [TeamMemberResource.php](file:///d:/LaravelHerd/korp-assignment/app/Http/Resources/TeamMemberResource.php) | L11-15 |
| [User.php](file:///d:/LaravelHerd/korp-assignment/app/Models/User.php) | L22-26, L37-41, L54-56, L62-64, L74-78, L97-99, L105-107, L115-118 |
| [Wallet.php](file:///d:/LaravelHerd/korp-assignment/app/Models/Wallet.php) | L43-45, L63-65, L85-87 |
| [AuthService.php](file:///d:/LaravelHerd/korp-assignment/app/Services/AuthService.php) | L16-20, L59-63, L87-89, L97-99, L114-116, L128-130 |
| [TransactionService.php](file:///d:/LaravelHerd/korp-assignment/app/Services/TransactionService.php) | L11-15 |
| [WalletService.php](file:///d:/LaravelHerd/korp-assignment/app/Services/WalletService.php) | L9-11, L20-22, L34-36 |
| [DashboardService.php](file:///d:/LaravelHerd/korp-assignment/app/Services/DashboardService.php) | L15-19 |
| [ProfileService.php](file:///d:/LaravelHerd/korp-assignment/app/Services/ProfileService.php) | L11-13, L47-49, L56-58 |
| [TeamMemberService.php](file:///d:/LaravelHerd/korp-assignment/app/Services/TeamMemberService.php) | L16-20, L51-53, L68-70 |

**Fix:** Remove DocBlocks that merely restate the method name or add no value beyond the type hints. Keep only DocBlocks that describe non-obvious behavior (e.g., the `@return` array shapes in [AuthService](file:///d:/LaravelHerd/korp-assignment/app/Services/AuthService.php#14-143) are actually useful).

> [!NOTE]
> The `@return array{...}` shape annotations in [AuthService](file:///d:/LaravelHerd/korp-assignment/app/Services/AuthService.php#14-143) are **valuable** and should be kept — they convey information beyond what PHP type hints can express.

---

## 3. Naming Conventions

### 🟡 `$team_member` parameter uses snake_case — should be `$teamMember`

In [TeamMemberController](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/TeamMemberController.php#L76), the route model binding parameter is `$team_member` (snake_case). The best practices state variables should use **camelCase**.

```php
// Current
public function update(UpdateTeamMemberRequest $request, User $team_member): JsonResponse

// Should be
public function update(UpdateTeamMemberRequest $request, User $teamMember): JsonResponse
```

> [!IMPORTANT]
> This also requires the route parameter in [api.php](file:///d:/LaravelHerd/korp-assignment/routes/api.php) to match: change `team-members/{team_member}` to `team-members/{teamMember}`.

---

### 🟢 [DataController](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/DataController.php#10-19) — Vague naming

[DataController.php](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/DataController.php) is generic. Since it only has a [dashboard](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/DataController.php#12-18) method, renaming to `DashboardController` would be more descriptive and follow the convention of singular, descriptive controller names.

---

## 4. Shorter / More Readable Syntax  

### 🟡 `auth()->user()` instead of `$request->user()`

In [ProfileController::cancelPendingEmail](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Settings/ProfileController.php#L36):

```php
// Current — uses auth() while other methods use $request->user()
$user = auth()->user();

// Consistent — inject Request or use existing parameter
public function cancelPendingEmail(Request $request): JsonResponse
{
    $this->profileService->cancelPendingEmail($request->user());
```

This inconsistency makes the code harder to test and breaks the pattern of the rest of the controller.

---

### 🟡 `response()->json(...)` vs `response()->noContent()` inconsistency

Across controllers, delete operations return different response styles:
- `WalletController::destroy` → `response()->noContent()` ✅
- `TeamMemberController::destroy` → `response()->json(['message' => '...'])` — less idiomatic

**Fix:** Standardize on `response()->noContent()` for delete operations.

---

## 5. Authorization Approach Inconsistency

### 🟡 Mixed authorization patterns

| Controller | Pattern |
|-----------|---------|
| [WalletController](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/WalletController.php#16-94) | `$this->authorize()` with Policies ✅ |
| [TeamMemberController](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/TeamMemberController.php#16-106) | Manual inline role checks ❌ |
| [TwoFactorController](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/TwoFactorController.php#13-66) | None (relies on `auth:sanctum`) |

**Fix:** Create a `TeamMemberPolicy` and use `$this->authorize()` consistently:
- [index](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/TransactionController.php#16-31) → `viewAny`
- [store](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/TwoFactorController.php#15-21) → [create](file:///d:/LaravelHerd/korp-assignment/client/src/api/team-members.ts#25-28) (includes company authorization)
- [update](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/WalletController.php#74-82)/[destroy](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/WalletController.php#83-93) → check role is [Member](file:///d:/LaravelHerd/korp-assignment/client/src/api/team-members.ts#3-13)

---

## 6. Mass Assignment / Eloquent Patterns

### 🟡 `Wallet::getBalanceAttribute` triggers N+1

The [balance accessor](file:///d:/LaravelHerd/korp-assignment/app/Models/Wallet.php#L38-L41) calls `->sum('amount')` on two relationships every time `$wallet->balance` is accessed. This executes **2 queries per wallet** and is an N+1 trap when used in collections.

```php
// Current — 2 queries per wallet
public function getBalanceAttribute(): float
{
    return (float) ($this->toTransactions()->sum('amount') + $this->fromTransactions()->sum('amount'));
}
```

The [scopeWithBalance](file:///d:/LaravelHerd/korp-assignment/app/Models/Wallet.php#43-52) scope exists on L46-51 but is **not used** in most places (e.g., [DashboardService](file:///d:/LaravelHerd/korp-assignment/app/Services/DashboardService.php#11-49) accesses `->balance` without the scope).

**Fix:** Modify [DashboardService](file:///d:/LaravelHerd/korp-assignment/app/Services/DashboardService.php#11-49) and [WalletResource](file:///d:/LaravelHerd/korp-assignment/app/Http/Resources/WalletResource.php#8-29) to always use `->withBalance()` and compute the balance from the pre-loaded sums rather than re-querying.

---

## 7. No Direct `env()` Usage ✅

No direct `env()` calls were found anywhere in the `app/` directory. Config values are accessed via `config()`. This is correct.

---

## 8. Routes File ✅

The [api.php](file:///d:/LaravelHerd/korp-assignment/routes/api.php) routes file contains **no logic** — only route declarations with middleware groups. This follows the best practice correctly.

---

## Summary

| Category | 🔴 High | 🟡 Medium | 🟢 Low |
|----------|---------|-----------|--------|
| Fat Controllers / SRP | 3 | 3 | — |
| DocBlocks | — | — | 1 (many files) |
| Naming | — | 1 | 1 |
| Readable Syntax | — | 2 | — |
| Authorization | — | 1 | — |
| N+1 / Eloquent | — | 1 | — |
| **Total** | **3** | **8** | **2** |

### Priority order for fixes
1. Move query logic from `TeamMemberController::index` → [TeamMemberService](file:///d:/LaravelHerd/korp-assignment/app/Services/TeamMemberService.php#14-78)
2. Move authorization from `TeamMemberController::store` → FormRequest or Policy
3. Extract `VerificationController::verify` logic → `VerificationService`
4. Create `TeamMemberPolicy` for consistent authorization
5. Fix `Wallet::balance` N+1 by using [scopeWithBalance](file:///d:/LaravelHerd/korp-assignment/app/Models/Wallet.php#43-52) everywhere
6. Move [register](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/AuthController.php#44-67) orchestration → [AuthService](file:///d:/LaravelHerd/korp-assignment/app/Services/AuthService.php#14-143)
7. Rename `$team_member` → `$teamMember` + update route parameter
8. Standardize delete responses to `noContent()`
9. Remove unnecessary DocBlocks
10. Rename [DataController](file:///d:/LaravelHerd/korp-assignment/app/Http/Controllers/Api/DataController.php#10-19) → `DashboardController`

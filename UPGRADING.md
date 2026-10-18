# Upgrading

## `*.0.x` → `*.1.0` (October 2026 security release)

`*.1.0` hardens the verification path. Behaviour changes are intentional —
read before upgrading.

### Required

```bash
composer update adeshsuryan/laravel-otp-login
php artisan migrate
```

The new migration (`2026_10_18_000000_otp_security_upgrade`) adds an
`attempts` column to `one_time_password_logs`, drops the plaintext `otp_code`
index, and creates `otp_trusted_devices`.

### Behaviour changes

| Area | Before (`*.0.x`) | Now (`*.1.0`) |
| --- | --- | --- |
| OTP storage | plaintext in `one_time_password_logs.otp_code` (`OTP_ENCODE_PASSWORD=false`) | **hashed** by default (`Hash::make` / `Hash::check`) |
| Brute force | unlimited wrong codes | `OTP_MAX_ATTEMPTS=5` — the Nth wrong code discards the OTP and logs the user out |
| Verify route | no rate limit | `throttle:` middleware, `OTP_VERIFY_THROTTLE=10,1` (empty value disables) |
| "Verified" cookie | predictable value `user_id_{id}` (safe only while `EncryptCookies` covers it) | random 64-char token; only its SHA-256 hash is stored in `otp_trusted_devices` |
| Middleware tail | request **admitted** when no OTP state exists | **challenged** (issues an OTP + redirects to `otp.view`); old behaviour via `OTP_FAIL_OPEN=true` |
| Expiry anchor | parent `one_time_passwords.created_at` — re-issued OTPs inherited the *first-ever* issue time | latest OTP log's `created_at` — the actual current issuance |
| OTP input | unvalidated | `required|string|digits_between:4,9` |

### Migration notes

- **Pending plaintext OTPs** issued before the upgrade stop matching under
  `Hash::check`. They expire within `OTP_TIMEOUT` anyway; users re-request a
  code. To keep the old behaviour temporarily set
  `OTP_ENCODE_PASSWORD=false` — not recommended.
- **Existing `user_id_*` cookies** are no longer accepted; users re-verify
  once on their next request. If your app deliberately excluded the cookie
  from `EncryptCookies`, the old value was forgeable — this release fixes
  that properly.
- Housekeeping: `php artisan otp:prune` deletes expired trusted devices —
  schedule it daily if you use the cookie path.

### Rollback

`composer require adeshsuryan/laravel-otp-login:^*.0` restores `*.0.3` code.
The new migration's `down()` drops `otp_trusted_devices`, removes `attempts`,
and restores the `otp_code` index.

## *.1.1 — Laravel 13 line + static-analysis pass

- New `13.x` line for Laravel 13 (PHP `^8.3` minimum on that line).
- No schema changes; `otp:prune` and `*.1.0` migrations are unchanged.
- Method signatures gained parameter/return types where they were already
  documented (e.g. `TrustedDevice::issue($userId, int $minutes, ?...)`,
  `OneTimePasswordMail::__construct(string $otp, string $ref)`). Callers
  passing documented types are unaffected; subclasses must match.
- `OTP_ENCODE_PASSWORD` uses `Hash::make`/`Hash::check` (bcrypt, ~100 ms
  per verify). Chosen over HMAC-SHA256 because Laravel's default hasher is
  configurable per app and bcrypt's cost factor is already tuned by the
  framework; brute-force is separately capped by `OTP_MAX_ATTEMPTS`.

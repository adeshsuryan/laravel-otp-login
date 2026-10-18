# Changelog

All notable changes to this package are documented in this file.

## [*.1.0] - 2026-10-18 — released as 8.1.0, 9.1.0, 10.1.0, 11.1.0, 12.1.0

Security hardening release on every supported line. See
[UPGRADING.md](UPGRADING.md) — defaults changed deliberately.

### Security

- **Brute-force lockout**: wrong verification codes now increment an
  `attempts` counter; at `OTP_MAX_ATTEMPTS` (default 5) the OTP is discarded
  and the pending login is ended.
- **Route throttle**: `otp.verify` carries `throttle:` middleware
  (`OTP_VERIFY_THROTTLE`, default `10,1`; empty disables).
- **Hashed OTP storage by default**: `OTP_ENCODE_PASSWORD` now defaults to
  `true`; plaintext remains an explicit opt-out for debugging.
- **Trusted-device cookie**: the `otp_login_verified` cookie is a random
  token whose SHA-256 hash lives in the new `otp_trusted_devices` table —
  the predictable `user_id_{id}` value is no longer accepted.
- **Fail-closed middleware**: a logged-in request with no valid OTP state is
  issued an OTP and redirected to `otp.view` instead of being admitted.
  `OTP_FAIL_OPEN=true` restores the old behaviour for migrations.
- **OTP expiry anchored to the latest issuance** (log row), not the
  first-ever parent row — re-issued OTPs no longer inherit stale clocks.
- **Input validation**: `code` must be `required|string|digits_between:4,9`.
- **SMS channels** (Twilio/Nexmo/Msg91/BioTekno) now set
  `CURLOPT_CONNECTTIMEOUT=5` / `CURLOPT_TIMEOUT=15` so a hung provider
  cannot pin a PHP worker.

### Added

- Migration `2026_10_18_000000_otp_security_upgrade`: `attempts` column,
  `otp_code` index drop, `otp_trusted_devices` table.
- `otp:prune` artisan command for expired trusted devices.
- `TrustedDevice` model; `OtpSecurityTest` (13 tests covering lockout,
  cookie forgery, fail-closed, expiry anchoring, hashing, validation).
- GitHub Actions CI: PHP 8.2/8.3/8.4 × Laravel 10/11/12 matrix,
  `composer audit`, Larastan/PHPStan level 5.
- `UPGRADING.md`, expanded `SECURITY.md` (threat model + support windows).

### Fixed

- `Twilio` channel passes `CURLOPT_POST` as `bool` (was `int`).
- `OneTimePassword::isExpired()` measured expiry from the parent row's
  `created_at` — every OTP re-issued after the first inherited the first
  one's clock.

## [*.0.3] - 2026-10-05 — released as 8.0.3, 9.0.3, 10.0.3, 11.0.3, 12.0.3

Per-Laravel-major release tags (`8.0.3` … `12.0.3`) of the same codebase,
each with `illuminate/*` pinned to its Laravel major. Note: `*.0.0`–`*.0.2`
tags were never cut; `*.0.3` is the first live modern line.

## [12.0.0] - 2026-10-03

Modern Laravel line for **Laravel 12** (PHP ^8.2). Same feature set as 8.0.0�11.0.0.

## [11.0.0] - 2026-10-03

Modern Laravel line for **Laravel 11** (PHP ^8.2).

## [10.0.0] - 2026-10-03

Modern Laravel line for **Laravel 10** (PHP ^8.1).

## [9.0.0] - 2026-10-03

Modern Laravel line for **Laravel 9** (PHP ^8.0).

## [8.0.0] - 2026-10-03

First modern release for **Laravel 8** (PHP ^8.0).

### Added

- Composer constraints for PHP and Illuminate components (per Laravel major tag).
- Free local/CI channels: `array`, `log`, and `mail` (Mailpit-friendly).
- Configurable user model, users table, phone/email fields (no hard-coded `App\User` or `learners` table).
- Restored OTP verification view and `web` + `auth` routes (`otp.view`, `otp.verify`).
- `otp.verified` middleware alias.
- Secure OTP generation via `random_int`, default timeout 10 minutes.
- Cookie flags via Laravel Cookie jar (HttpOnly, SameSite=Lax, Secure when HTTPS).
- PHPUnit / Orchestra Testbench coverage for the array-channel flow.
- Professional README, banner, SECURITY.md.

### Changed

- Default delivery channel is `log` (not Nexmo).
- Namespace remains `adeshsuryan\LaravelOTPLogin` for install continuity.

### Legacy

Tags `0.1`�`5.13` stay on Packagist unchanged. Pin those for Laravel 5-era apps:

```bash
composer require adeshsuryan/laravel-otp-login:5.13
```

Do not upgrade a Laravel 5 app to `^8.0` / `^12.0` without a framework upgrade.

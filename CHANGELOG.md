# Changelog

All notable changes to this package are documented in this file.

## [12.0.0] - 2026-10-03

Modern Laravel line for **Laravel 12** (PHP ^8.2). Same feature set as 8.0.0–11.0.0.

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

Tags `0.1`–`5.13` stay on Packagist unchanged. Pin those for Laravel 5-era apps:

```bash
composer require adeshsuryan/laravel-otp-login:5.13
```

Do not upgrade a Laravel 5 app to `^8.0` / `^12.0` without a framework upgrade.

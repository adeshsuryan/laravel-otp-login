# Security

## Supported versions

| Version | Laravel | Security fixes |
| --- | --- | --- |
| 12.x | 12 | yes |
| 11.x | 11 | yes |
| 10.x | 10 | yes |
| 9.x | 9 | yes |
| 8.x | 8 | yes |
| 5.x and older | Laravel 5-era | frozen snapshot only |

## Reporting a vulnerability

Email adeshsuryan2005@gmail.com with steps to reproduce. Do not open a public issue for an active exploit.

## OTP notes

- Prefer short `OTP_TIMEOUT` values (default 600 seconds).
- Use `array` or `log` only in local/CI; never in production if logs are broadly readable.
- Production SMS credentials must live in environment variables, not source control.
- Rate-limit `/login/check` at the application or reverse-proxy layer.

## Secret hygiene

This package never ships live SMS/API credentials. `config/otp.php` only reads `env(...)` placeholders.

Do not commit `.env`, `access.txt`, or provider keys. If a key was ever pushed to GitHub, revoke it at the provider and rotate it even after history rewrite.

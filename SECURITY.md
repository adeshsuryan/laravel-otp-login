# Security Policy

## Reporting a vulnerability

Email **adeshsuryan2005@gmail.com** with details and reproduction steps.
Do not open a public issue for unpatched vulnerabilities.

## Supported versions

Each package major tracks a Laravel major. Security fixes land on every line
where the framework itself receives security fixes.

| Package line | Laravel | Security status |
| --- | --- | --- |
| `12.x` | 12.x | **Supported** |
| `11.x` | 11.x | Package fixes ship; **Laravel 11 is EOL** — upgrade the framework |
| `10.x` | 10.x | Package fixes ship; **Laravel 10 is EOL** — upgrade the framework |
| `9.x` | 9.x | Package fixes ship; **Laravel 9 is EOL** — upgrade the framework |
| `8.x` | 8.x | Package fixes ship; **Laravel 8 is EOL** — upgrade the framework |
| `5.x` and earlier | legacy | Frozen — no fixes |

Composer's default `policy.advisories.block` refuses Laravel majors whose
entire line carries advisories; an EOL framework is the bigger risk than any
single dependency.

## Threat model

This package adds a second factor **after** a successful first-factor login.
It assumes the host application's session, cookie-encryption, and password
plumbing are sound.

- OTPs are stored **hashed** (`OTP_ENCODE_PASSWORD=true`, default since
  `*.1.0`) and expire after `OTP_TIMEOUT` (600 s).
- Verification is brute-force limited: `OTP_MAX_ATTEMPTS` (5) wrong codes
  discard the OTP and end the pending login; `otp.verify` also carries a
  route throttle (`OTP_VERIFY_THROTTLE`, default `10,1`).
- The "verified" cookie is a random token — only its SHA-256 hash is stored.
  It is issued `httpOnly`, `Secure` on HTTPS, `SameSite=Lax`. A predictable
  `user_id_{id}` value (pre-`*.1.0`) is no longer accepted.
- The middleware **fails closed** (`OTP_FAIL_OPEN=false`): a logged-in user
  with no valid OTP state is issued an OTP and redirected to verification.
- Never run the `log` channel in production — it writes OTPs to the Laravel
  log by design. Use `mail` or an SMS channel.
- SMS channels (Twilio/Nexmo/Msg91/BioTekno) carry your provider keys via
  `OTP_*` env vars; the package never logs them, and calls are bounded by
  5 s connect / 15 s total timeouts.

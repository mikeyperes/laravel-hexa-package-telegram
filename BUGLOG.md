# Telegram bug log

## TELEGRAM-BUG-001 — Shared webhook had no authenticated account identity

Severity: High. Multi-account inbound handlers could infer the active bot and cross contexts. Add explicit per-bot route with derived account-bound authentication; server overwrites inbound transport metadata. Keep the legacy route compatible, without granting it Chatbox account authority. Guard: bound-secret mismatch and metadata-forgery tests.

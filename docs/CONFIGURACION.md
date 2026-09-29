# Configuración

Las variables sensibles deben estar en `.env` y nunca en Git. Como mínimo revisar:

- `APP_URL`
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
- `APP_KEY`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`
- `GOOGLE_GMAIL_SCOPES`
- `OPENAI_API_KEY`
- `OPENAI_MODEL`
- `OPENAI_TIMEOUT`
- `OPENAI_MAX_EMAIL_CHARS`
- `GMAIL_SYNC_MAX_MESSAGES`
- `GMAIL_SYNC_QUERY`

No compartir `.env` ni claves en repositorios.

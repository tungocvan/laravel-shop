# Google Drive Readiness — Database Backup

The Database Backup workspace distinguishes OAuth token presence from actual Google Drive API readiness.

When Drive returns `ACCESS_TOKEN_SCOPE_INSUFFICIENT` / insufficient permission, the UI reports that OAuth permission must be granted again instead of presenting the connection as operational. Drive upload actions remain unavailable until readiness succeeds.

The recovery flow retains least-privilege authorization: correct the configured required scope, reauthorize once, resolve the configured `Laravel-Backup` root, then re-run catalog/list/upload operations. An existing refresh token cannot acquire a newly required OAuth scope without reauthorization.
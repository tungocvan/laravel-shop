# Security Review Checklist

- Every privileged route has authentication and capability-level authorization.
- Every mutating Livewire action authorizes in PHP.
- Super Admin bypass does not replace explicit permissions for other roles.
- User input is validated at the boundary.
- File uploads validate size, MIME, extension, and storage location.
- Browser-provided paths, table names, commands, class names, and IDs are not trusted.
- Destructive actions require confirmation and audit logging.
- Import/export files with sensitive data use private storage.
- Raw exception text is not returned to users.
- Secrets are not committed, logged, exported, or passed through command strings.
- Database backup and restore paths are server-controlled.
- Ownership checks protect records, downloads, documents, orders, admissions, accounts, and chat sessions.

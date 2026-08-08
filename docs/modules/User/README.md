# User Module

Legacy staff-account compatibility module for `/admin/user`.

Use `Identity` for new canonical user/profile/identity work. Keep this module focused on preserving the existing staff CRUD route surface until the Identity cutover is verified.

Import/export is available through the shared Excel panel on the staff index page. It maps by header name, uses `email` as the unique key, defaults empty roles to `user`, creates missing roles on the `admin` guard, and blocks non-Super Admin users from assigning `Super Admin`.

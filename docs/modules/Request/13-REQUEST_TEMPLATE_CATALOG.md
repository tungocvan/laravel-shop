# Request Template Catalog

## 1. Purpose

These starter templates help administrators build common internal requests without hard-coding business behavior. They are product examples, not guaranteed legal/HR/finance policies. Applying a template creates an editable draft that must be mapped, validated, reviewed, and published by authorized users.

No template calls another domain module or assumes a department/manager source. Approval placeholders use supported fixed users/roles or a user field.

## 2. Safe installation rules

- Choose group/type name/code and review localization.
- Map every placeholder role/user explicitly to existing active Shell identities.
- Review data classification, retention, offline eligibility, attachment types, and export fields.
- Validate candidate cardinality and self-approval behavior.
- Publish through normal diff/validation/audit; never auto-publish.

## 3. Recommended starter templates

| Template key | Suggested fields | Suggested approval | Notes |
|---|---|---|---|
| `leave_request_v1` | leave type, start/end, partial day, reason, handover | single HR/operations role, then optional role ALL | Policy calculation/payroll integration excluded |
| `remote_work_v1` | dates, location, reason, availability plan | selected responsible user or role | No manager auto-resolution |
| `business_trip_v1` | destination, dates, purpose, estimate, currency, attachment | role ANY then role ALL if configured | No travel booking/accounting posting |
| `payment_request_v1` | payee text, purpose, amount/currency, due date, evidence | finance role ANY then approver role ALL | No ledger/payment execution |
| `cash_advance_v1` | purpose, amount/currency, required date, settlement note | finance role ANY | No balance or settlement domain |
| `purchase_request_v1` | item list as bounded text/fields, estimate, justification | purchasing role ANY then approver role ALL | No supplier/PO/inventory integration; repeating table future |
| `equipment_request_v1` | equipment, specification, purpose, date | IT/admin role ANY | No asset assignment/inventory write |
| `recruitment_request_v1` | position text, count, reason, target date, description attachment | HR role ANY then executive role ALL | No candidate/recruiting domain integration |
| `contract_review_v1` | counterparty text, purpose, value, deadline, contract attachment | legal role ANY | Private file policy is critical |
| `general_admin_request_v1` | subject, description, requested date, attachments | administrative role ANY | Generic fallback with strict bounded fields |

## 4. Template field guidance

- Keep forms short and explain why each field is needed.
- Prefer stable options over free text when a small approved list exists.
- Amount always uses currency field, never float.
- Dates do not imply an SLA or automatic timer.
- User selector can be used for the `form_user_field` resolver only after publication validator confirms it.
- Attachment fields define classification and allowed purpose; they do not accept public links as a substitute.
- Avoid collecting national ID, bank/card credentials, medical information, secrets, or other high-risk data unless a separate privacy/security review approves it.

## 5. Approval placeholder guidance

Because no canonical organization hierarchy exists:

- Use a deliberately configured role with `parallel_any` when any current role member may decide.
- Use `parallel_all` only when every resolved current member truly must decide; broad roles can create excessive/stuck tasks.
- Use fixed user only for stable operational ownership and document replacement process.
- Use a required user field when requester explicitly selects a responsible person and policy permits it.
- Never name a role “Manager” and assume it identifies the requester's manager.

## 6. Template exclusions

Templates do not provide:

- legal compliance or company-policy approval;
- automatic balance, leave-day, budget, inventory, employee, supplier, or accounting validation;
- conditional amount-based stages;
- manager/department resolver;
- digital signature;
- external system posting;
- repeating item tables or calculated formulas;
- offline submission/approval.

If a real business process requires these, the administrator must not simulate them with hidden fields, comments, role names, or manual database access. It requires a separately analyzed feature/integration.

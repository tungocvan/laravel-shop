# UI Guidelines

## Consistency

Use the same component for the same job. A create button, status badge, dropdown menu, page title, and destructive confirmation should look and behave the same across Admin modules.

## Hierarchy

Every admin screen should have a clear order:

1. Page title and context.
2. Primary action.
3. Filters or toolbar.
4. Main content.
5. Secondary actions.

Avoid placing important actions deep inside cards when they apply to the whole page.

## Spacing

Use compact spacing for operational screens. Large hero spacing is not appropriate inside the authenticated admin shell.

Rules:

- Keep page padding consistent.
- Use smaller gaps inside tables, filters, and settings panels.
- Use larger gaps only between unrelated page sections.

## Visual Balance

The admin shell should feel stable:

- Header height should not change between pages.
- Sidebar width should not change except explicit collapse.
- Long labels should truncate or wrap intentionally.
- Badges should not push action buttons out of view.

## SaaS Admin Standards

Expected patterns:

- Left navigation.
- Sticky top header.
- Page header with actions.
- Filterable, paginated data lists.
- Clear empty states.
- Safe destructive confirmations.
- Persistent notifications and transient toasts.
- Mobile drawer navigation.

## Interaction

Rules:

- Interactive controls need hover, focus, active, disabled, and loading states.
- Keyboard users must be able to reach every action.
- Icons without text need labels and tooltips.
- Focus rings must be visible and not removed.

## Content

Use concise interface text:

- Button labels should be verbs.
- Empty states should explain the next action.
- Error messages should explain what failed and how to recover.
- Do not expose raw exception text.

## Page Density

Use the right density for the workflow:

| Workflow | Density |
|---|---|
| Dashboard | Comfortable |
| Data table | Compact |
| Settings | Comfortable |
| Long forms | Comfortable with clear groups |
| Bulk operations | Dense but readable |

## Icons And Labels

Use icons for repeated tools and text labels for commands that might be ambiguous. In collapsed sidebar mode, always preserve accessible labels.


---
paths:
  - 'app/**'
---

# App

## OrganizationScope does not filter when unauthenticated
`app/Models/Scopes/OrganizationScope.php` returns early and applies NO filter when `auth()->check()` is false. Any code path without an authenticated user (console commands, queued jobs, the stdio MCP server) therefore reads every organization's rows, not zero. Scope explicitly with `->forOrganization($id)` in those paths; treat the global scope as a second layer only.

Separately, the scope is opt-in per model and most models do not have it. Never assume a model is scoped — check with `grep -rl OrganizationScope app/Models` (the scoped set grows as models are added, so any list written here rots). Known to lack it: `Payment`, `InvoiceItem`, `Location`, `Organization` — and `Payment` has no `organization_id` column at all, only `invoice_id`. Reach them through an already-scoped parent, or join explicitly: `Payment::whereHas('invoice', fn ($q) => $q->withoutGlobalScopes()->where('organization_id', $id))`. A bare `Payment` aggregate silently totals every organization while returning a plausible-looking number.

Also note `Invoice::unpaid()` means `amount_paid = 0`, so it excludes partially paid invoices. For "what is still owed" use `whereColumn('amount_paid', '<', 'total')` instead.

## Customer locations live only on the detail page
Since `b1c3b564` the only place a location can be added is `Customers/Show.vue` (`LocationModal`); the listing has no such affordance. Any controller flashing "add a location" must `redirect()->route('customers.show', ...)`, never `back()` — `store()` did the latter and left new customers reading "0 locations" with nothing to click. Nothing enforces >=1 location at creation: `cannot_delete_last_location` is a don't-go-backwards guard that only fires once one exists, so zero-location customers are a legitimate state the UI has to handle.

## Assert the redirect target, not just that one happened
A bare `assertRedirect()` passes for `back()` and for the correct destination alike. That is exactly how the customer-create redirect bug shipped past a test that looked like it covered the path. Pass the expected URL: `assertRedirect(route('customers.show', $customer))`.

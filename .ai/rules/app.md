---
paths:
  - 'app/**'
---

# App

## Customer locations live only on the detail page
Since `b1c3b564` the only place a location can be added is `Customers/Show.vue` (`LocationModal`); the listing has no such affordance. Any controller flashing "add a location" must `redirect()->route('customers.show', ...)`, never `back()` — `store()` did the latter and left new customers reading "0 locations" with nothing to click. Nothing enforces >=1 location at creation: `cannot_delete_last_location` is a don't-go-backwards guard that only fires once one exists, so zero-location customers are a legitimate state the UI has to handle.

## Assert the redirect target, not just that one happened
A bare `assertRedirect()` passes for `back()` and for the correct destination alike. That is exactly how the customer-create redirect bug shipped past a test that looked like it covered the path. Pass the expected URL: `assertRedirect(route('customers.show', $customer))`.

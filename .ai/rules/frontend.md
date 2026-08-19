---
paths:
  - 'resources/js/**'
---

# Frontend

## A hidden field hides its validation error
Per-field error messages are only as reachable as the block they are rendered inside. `InvoiceForm.vue` wraps the billing and shipping selects — and their `customer_location_id` / `customer_shipping_location_id` errors — in `v-if="selectedCustomerId && customerLocations.length > 0"`, so picking a customer with no location made Save do nothing at all, with the 422 visible only in the network tab.

Two habits prevent it: keep a form-level summary bound to `form.hasErrors` so no message can be orphaned, and give every conditionally rendered section a `v-else` that explains the state (the organization side of the same form already had its "No locations found" notice; the customer side did not).

## `preserveScroll` accepts more than a boolean
Inertia resolves it through `resolvePreserveOption`, so a function `(page) => boolean` or the string `'errors'` both work; `'errors'` is the readable form. This matters when a successful submit navigates elsewhere — `preserveScroll: true` would carry the old page's scroll offset onto the new one.

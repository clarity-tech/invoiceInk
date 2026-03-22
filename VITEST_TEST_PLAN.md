# Vitest Frontend Test Plan — InvoiceInk

Comprehensive plan for unit, component, and integration tests using Vitest + @vue/test-utils.

**Current state:** 8 test files covering composables (formatMoney, invoiceCalculator, flash) and simple components (ConfirmationModal, ItemRow, MoneyDisplay, StatusBadge, utils).

**Goal:** Full coverage of all pages, forms, modals, composables, and user interactions.

---

## Test Infrastructure

- **Framework:** Vitest 4 + @vue/test-utils 2
- **DOM:** happy-dom
- **Globals:** enabled (no imports for describe/it/expect)
- **Alias:** `@/` → `resources/ts/`
- **Pattern:** `resources/ts/__tests__/**/*.test.ts`

### Mocking Conventions

```typescript
// Inertia mocking (before imports)
const mockUsePage = vi.fn();
const mockRouter = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn(), reload: vi.fn() };
const mockUseForm = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mockUsePage(),
    router: mockRouter,
    useForm: (data) => ({ ...data, post: vi.fn(), put: vi.fn(), delete: vi.fn(), processing: false, errors: {}, reset: vi.fn() }),
    Link: { template: '<a><slot /></a>' },
    Head: { template: '<div />' },
}));

// Wayfinder route mocking
vi.mock('@/routes', () => ({ dashboard: { url: () => '/dashboard' }, logout: { url: () => '/logout' } }));
```

### Factory Helpers (shared across test files)

```typescript
// tests/ts/__tests__/helpers.ts
function makeUser(overrides = {}) { ... }
function makeOrganization(overrides = {}) { ... }
function makeCustomer(overrides = {}) { ... }
function makeInvoice(overrides = {}) { ... }
function makeLineItem(overrides = {}) { ... }
function makeLocation(overrides = {}) { ... }
function makePageProps(overrides = {}) { ... }  // shared Inertia page props (auth, flash)
```

---

## 1. Composables (Unit Tests)

### 1.1 `useFormatDate.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Formats valid date string to "DD Mon YYYY" |
| 2 | Returns empty string for null/undefined |
| 3 | Handles ISO date strings |
| 4 | Handles date-only strings (no time) |
| 5 | Returns fallback for invalid date strings |

### 1.2 `useFormatMoney.test.ts` — EXISTS ✅
Already covers all 9 currencies, zero, negatives, large amounts.

### 1.3 `useInvoiceCalculator.test.ts` — EXISTS ✅
Already covers line totals, tax, reactive totals.

**Additions needed:**
| # | Test Case |
|---|-----------|
| 1 | Empty items array returns zero totals |
| 2 | Items with zero quantity |
| 3 | Items with zero unit_price |
| 4 | Items with zero tax_rate |
| 5 | Reactivity: adding item updates totals |
| 6 | Reactivity: removing item updates totals |
| 7 | Reactivity: updating item field updates totals |
| 8 | Very large quantities (overflow safety) |
| 9 | Decimal precision across many items (rounding) |

### 1.4 `useFlash.test.ts` — EXISTS ✅
Already covers basic flash retrieval.

**Additions needed:**
| # | Test Case |
|---|-----------|
| 1 | hasFlash returns true when success message exists |
| 2 | hasFlash returns true when error message exists |
| 3 | hasFlash returns false when all null |
| 4 | Flash values update reactively when page props change |

---

## 2. Components — Display & Utility

### 2.1 `StatusBadge.test.ts` — EXISTS ✅

**Additions needed:**
| # | Test Case |
|---|-----------|
| 1 | Renders correct color for each status (draft=gray, sent=blue, accepted=green, partially_paid=yellow, paid=green, void=red) |
| 2 | Displays human-readable label (e.g., "Partially Paid" not "partially_paid") |

### 2.2 `MoneyDisplay.test.ts` — EXISTS ✅
Sufficient coverage.

### 2.3 `ConfirmationModal.test.ts` — EXISTS ✅

**Additions needed:**
| # | Test Case |
|---|-----------|
| 1 | Does not render content when show=false |
| 2 | Renders custom title and message |
| 3 | Renders custom button labels |
| 4 | Applies destructive styling when destructive=true |
| 5 | Emits cancel on backdrop click |

### 2.4 `FlashMessages.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders success flash message with green styling |
| 2 | Renders error flash message with red styling |
| 3 | Renders generic message |
| 4 | Renders nothing when no flash messages |
| 5 | Renders multiple flash types simultaneously |

### 2.5 `TipTapEditor.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders editor with placeholder text |
| 2 | Emits update:modelValue on content change |
| 3 | Initializes with provided modelValue |
| 4 | insertText() method inserts text at cursor |

---

## 3. Components — Forms & Modals

### 3.1 `CustomerForm.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders all fields (name, phone, currency, contacts) |
| 2 | Shows "Create" button when isEditing=false |
| 3 | Shows "Update" button when isEditing=true |
| 4 | Emits submit on form submission |
| 5 | Emits cancel on cancel button click |
| 6 | Adds new contact row on "Add Contact" click |
| 7 | Removes contact row on remove button click |
| 8 | Cannot remove last contact row |
| 9 | Displays validation errors from form.errors |
| 10 | Email validation: shows error for invalid email format |
| 11 | Name required validation when email is provided |
| 12 | Customer name required validation |
| 13 | Currency select shows all available currencies |
| 14 | Pre-fills form fields when editing existing customer |
| 15 | Submit button disabled when form.processing=true |

### 3.2 `LocationModal.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders when show=true, hidden when show=false |
| 2 | Shows "Add Location" title for new location |
| 3 | Shows "Edit Location" title when location prop provided |
| 4 | Pre-fills fields with existing location data |
| 5 | Renders all address fields (name, gstin, address_line_1, address_line_2, city, state, country, postal_code) |
| 6 | GSTIN field validates format |
| 7 | Emits close on cancel/backdrop click |
| 8 | Calls form.post() on create submit |
| 9 | Calls form.put() on edit submit |
| 10 | Submit button disabled during processing |
| 11 | Displays server validation errors |
| 12 | Country dropdown shows all countries |

### 3.3 `Invoice/InvoiceForm.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders correct title for invoice vs estimate |
| 2 | Renders correct title for create vs edit mode |
| 3 | Renders customer dropdown with all customers |
| 4 | Customer selection auto-populates billing location |
| 5 | Customer selection updates currency |
| 6 | Organization location dropdown shows all org locations |
| 7 | Customer shipping location dropdown appears and is optional |
| 8 | Status dropdown shows all status options |
| 9 | Issued date defaults to today |
| 10 | Due date defaults to today + 30 days |
| 11 | Numbering series dropdown shown for invoices only |
| 12 | Notes textarea renders and is editable |
| 13 | Add Item button adds new line item row |
| 14 | Cannot remove last line item |
| 15 | Remove button removes line item |
| 16 | Totals section shows subtotal, tax breakdown, total |
| 17 | Totals update reactively when items change |
| 18 | Submit calls form.post() in create mode |
| 19 | Submit calls form.put() in edit mode |
| 20 | Cancel navigates to /invoices |
| 21 | Displays flash success/error messages |
| 22 | Pre-fills all fields when editing existing invoice |
| 23 | Submit button disabled during processing |
| 24 | Displays server validation errors on fields |

### 3.4 `Invoice/ItemRow.test.ts` — EXISTS ✅

**Additions needed:**
| # | Test Case |
|---|-----------|
| 1 | Tax template selector populates tax_rate |
| 2 | Currency symbol displays correctly for different currencies |
| 3 | Quantity input enforces min=1 |
| 4 | Emits remove when remove button clicked |
| 5 | Remove button hidden when canRemove=false |
| 6 | Displays per-field errors from errors prop |
| 7 | Line total computed correctly (quantity × unit_price) |
| 8 | Line total includes tax display |

### 3.5 `Invoice/EmailModal.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders when show=true, hidden when show=false |
| 2 | Template type selector shows all template types |
| 3 | Changing template type fetches new template content |
| 4 | Recipients: add email chip on enter/comma |
| 5 | Recipients: remove email chip on click |
| 6 | Recipients: validates email format before adding |
| 7 | CC: add and remove email chips |
| 8 | Subject field renders and is editable |
| 9 | Body field uses TipTap editor |
| 10 | Attach PDF checkbox toggles |
| 11 | Send button calls form.post() with correct data |
| 12 | Send button disabled during processing |
| 13 | Close button emits close |
| 14 | Form resets when modal reopens |
| 15 | Displays validation errors |

### 3.6 `Invoice/PaymentModal.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders when show=true |
| 2 | Amount defaults to balance due |
| 3 | Balance due calculated as total - amount_paid |
| 4 | Payment date defaults to today |
| 5 | Payment method dropdown shows all methods (bank_transfer, cash, cheque, UPI, credit_card, paypal, other) |
| 6 | Reference and notes fields render |
| 7 | Submit calls form.post() with amount in cents |
| 8 | Submit button disabled when amount <= 0 |
| 9 | Submit button disabled during processing |
| 10 | Emits close on cancel |
| 11 | Form resets when modal reopens with new invoice |
| 12 | Displays payment summary (total, paid, balance) |

---

## 4. Page Components — Authentication

### 4.1 `Pages/Auth/Login.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders email and password fields |
| 2 | Renders "Remember me" checkbox |
| 3 | Renders "Forgot password?" link |
| 4 | Renders submit button |
| 5 | Password visibility toggle works |
| 6 | Form submits with correct data via form.post() |
| 7 | Submit button disabled during processing |
| 8 | Displays validation errors (email, password) |
| 9 | Displays status message when status prop provided |

### 4.2 `Pages/Auth/Register.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders name, email, password, password_confirmation fields |
| 2 | Renders terms checkbox |
| 3 | Password visibility toggles (2 separate) |
| 4 | Password mismatch computed property shows warning |
| 5 | Form submits via form.post() |
| 6 | Displays validation errors |
| 7 | Terms and Privacy Policy links render |
| 8 | Submit disabled during processing |

### 4.3 `Pages/Auth/ForgotPassword.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders email field |
| 2 | Submit sends form.post() |
| 3 | Displays status message on success |
| 4 | Displays validation error for invalid email |

### 4.4 `Pages/Auth/ResetPassword.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders email (read-only), password, password_confirmation |
| 2 | Initializes with token and email from props |
| 3 | Submit sends form.post() |
| 4 | Displays validation errors |

### 4.5 `Pages/Auth/TwoFactorChallenge.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders code input by default |
| 2 | Toggle switches to recovery code input |
| 3 | Toggle switches back to code input |
| 4 | Submit sends form.post() with code |
| 5 | Submit sends recovery_code when in recovery mode |
| 6 | Displays validation errors |

### 4.6 `Pages/Auth/VerifyEmail.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders verification message |
| 2 | "Resend" button sends form.post() |
| 3 | Displays status message after resend |
| 4 | "Log Out" button calls router.post() |
| 5 | "Edit Profile" link renders |

### 4.7 `Pages/Auth/ConfirmPassword.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders password field |
| 2 | Submit sends form.post() |
| 3 | Displays validation error |

---

## 5. Page Components — Core Features

### 5.1 `Pages/Dashboard.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders period selector with all options |
| 2 | Period change triggers router.reload() with new period param |
| 3 | Renders revenue stats cards (total revenue, collected, outstanding) |
| 4 | Renders invoice count and collection rate |
| 5 | Renders status breakdown table |
| 6 | Renders recent invoices list with correct data |
| 7 | Renders overdue invoices with "X days ago" text |
| 8 | Renders recent payments list |
| 9 | Renders top customers sorted by revenue |
| 10 | Renders monthly trend bars |
| 11 | Trend bar heights calculated correctly relative to max |
| 12 | Renders estimate stats section |
| 13 | Renders customer count |
| 14 | Quick action links navigate correctly |
| 15 | Handles empty data gracefully (no invoices, no payments) |
| 16 | Money values formatted with correct currency |

### 5.2 `Pages/Customers/Index.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders paginated customer list |
| 2 | Search filters customers by name |
| 3 | Search filters customers by email |
| 4 | Search filters customers by phone |
| 5 | "Create Customer" button opens modal with empty form |
| 6 | Edit button opens modal with pre-filled form |
| 7 | Delete button opens confirmation modal |
| 8 | Confirm delete calls router.delete() with optimistic UI |
| 9 | Cancel delete closes modal without action |
| 10 | Expand row shows customer locations |
| 11 | "Add Location" opens LocationModal |
| 12 | "Edit Location" opens LocationModal with data |
| 13 | "Set Primary" calls router.post() |
| 14 | "Delete Location" opens confirmation |
| 15 | Cannot delete last location (button disabled or prevented) |
| 16 | Pagination renders and navigates correctly |
| 17 | Empty state message when no customers |
| 18 | Customer currency badge displays correctly |

### 5.3 `Pages/Invoices/Index.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders paginated invoice list |
| 2 | Tab switching: All / Invoices / Estimates |
| 3 | Status filter dropdown filters list |
| 4 | View link navigates to public view |
| 5 | Edit link navigates to edit page |
| 6 | PDF download with loading state |
| 7 | Duplicate action calls router.post() with loading state |
| 8 | Convert action (estimate only) calls router.post() |
| 9 | Convert button hidden for invoices |
| 10 | Delete opens confirmation modal |
| 11 | Confirm delete with optimistic UI |
| 12 | Status badges render correct colors |
| 13 | Money amounts formatted correctly |
| 14 | Pagination works |
| 15 | Empty state message |
| 16 | Type badges (Invoice vs Estimate) display correctly |

### 5.4 `Pages/Invoices/Create.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders InvoiceForm with mode="create" |
| 2 | Passes type="invoice" for invoice creation |
| 3 | Passes type="estimate" for estimate creation |
| 4 | Passes all required props to InvoiceForm |

### 5.5 `Pages/Invoices/Edit.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders InvoiceForm with mode="edit" and invoice data |
| 2 | Payment section visible for invoices |
| 3 | Payment section hidden for estimates |
| 4 | "Record Payment" button opens PaymentModal |
| 5 | Payment summary shows total, paid, balance |
| 6 | Payment history table renders all payments |
| 7 | Delete payment button opens confirmation |
| 8 | Confirm delete payment calls router.delete() |
| 9 | Send Email button opens EmailModal |
| 10 | View Public link renders with correct ULID URL |
| 11 | Download PDF link with loading state |

### 5.6 `Pages/Organizations/Index.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders organization list |
| 2 | Tab switching: basics, location, bank, logo |
| 3 | Edit button populates form with org data |
| 4 | Basics form: update name, emails, currency, country, tax info |
| 5 | Basics form: add/remove dynamic email fields |
| 6 | Basics form: country change auto-updates currency |
| 7 | Basics form: submit calls form.put() |
| 8 | Location form: renders all address fields |
| 9 | Location form: submit calls form.put() |
| 10 | Bank form: renders all bank fields (account name, number, IFSC, SWIFT, PAN) |
| 11 | Bank form: submit calls form.put() |
| 12 | Logo tab: upload button triggers file input |
| 13 | Logo tab: shows current logo when exists |
| 14 | Logo tab: remove logo calls form.delete() |
| 15 | Delete org opens confirmation |
| 16 | Displays validation errors per tab |
| 17 | Pagination works |

### 5.7 `Pages/Organizations/Setup.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders step 1 (Company Info) by default |
| 2 | Step progress indicator shows current step |
| 3 | Step 1: renders company name, tax, registration fields |
| 4 | Step 1: Next button submits and advances to step 2 |
| 5 | Step 2: renders location fields |
| 6 | Step 2: Previous button goes back to step 1 |
| 7 | Step 2: Next button submits and advances to step 3 |
| 8 | Step 3: renders country, currency, financial year selectors |
| 9 | Step 3: country change auto-populates currency |
| 10 | Step 3: Next button submits and advances to step 4 |
| 11 | Step 4: renders email and phone fields |
| 12 | Step 4: add/remove dynamic email fields |
| 13 | Step 4: Complete button submits final step |
| 14 | Validation errors prevent step advancement |
| 15 | Displays validation errors on fields |

### 5.8 `Pages/NumberingSeries/Index.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders series list |
| 2 | "Create Series" button opens form modal |
| 3 | Form renders all fields (org, location, name, prefix, format_pattern, etc.) |
| 4 | Live preview generates sample number on input change |
| 5 | Preview uses debounced fetch (300ms) |
| 6 | Format token reference shown |
| 7 | Create submit calls form.post() |
| 8 | Edit button opens form with pre-filled data |
| 9 | Edit submit calls form.put() |
| 10 | Toggle active calls router.post() |
| 11 | Set default calls router.post() |
| 12 | Delete opens confirmation |
| 13 | Cannot delete series with invoices (disabled or error) |
| 14 | Pagination works |

### 5.9 `Pages/EmailTemplates/Index.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders invoice template types table |
| 2 | Renders estimate template types table |
| 3 | Shows "Customized" badge for customized templates |
| 4 | Shows "Default" badge for default templates |
| 5 | Edit/Customize links navigate correctly |

### 5.10 `Pages/EmailTemplates/Edit.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders subject and body fields |
| 2 | Body uses TipTap editor |
| 3 | Variable list shows all available variables |
| 4 | Click variable inserts into editor |
| 5 | Save calls form.put() |
| 6 | Reset to default opens confirmation |
| 7 | Confirm reset calls router.delete() |
| 8 | Restore default loads default values into form |
| 9 | Preview button fetches rendered preview |
| 10 | Preview modal shows rendered HTML |
| 11 | isModified computed detects changes |
| 12 | isDifferentFromDefault computed detects customization |

---

## 6. Page Components — Profile & Teams

### 6.1 `Pages/Profile/Show.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders all profile sections |
| 2 | Passes correct props to child form components |

### 6.2 `Pages/Profile/Partials/UpdateProfileInformationForm.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders name and email fields with current values |
| 2 | Submit calls form.put() |
| 3 | Displays validation errors |
| 4 | Shows success message after update |

### 6.3 `Pages/Profile/Partials/UpdatePasswordForm.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders current_password, password, password_confirmation |
| 2 | Submit calls form.put() |
| 3 | Form resets after successful update |
| 4 | Displays validation errors |

### 6.4 `Pages/Profile/Partials/TwoFactorAuthenticationForm.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Shows "Enable" button when 2FA disabled |
| 2 | Shows QR code after enabling |
| 3 | Shows recovery codes |
| 4 | "Disable" button calls correct endpoint |
| 5 | "Regenerate Recovery Codes" button works |

### 6.5 `Pages/Profile/Partials/LogoutOtherSessionsForm.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Shows active sessions list |
| 2 | "Logout Other Sessions" button opens password confirmation |
| 3 | Submit calls form.delete() |
| 4 | Displays session info (device, IP, last active) |

### 6.6 `Pages/Profile/Partials/DeleteUserForm.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | "Delete Account" button opens confirmation modal |
| 2 | Requires password to confirm |
| 3 | Submit calls form.delete() |
| 4 | Cancel closes modal |

### 6.7 `Pages/Teams/Create.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders team name field |
| 2 | Shows current user info |
| 3 | Submit calls form.post() |
| 4 | Displays validation errors |

### 6.8 `Pages/Teams/Show.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders team name |
| 2 | Shows team members |
| 3 | Shows pending invitations |
| 4 | Update team name form works |
| 5 | Add member form works |
| 6 | Change member role works |
| 7 | Remove member opens confirmation |
| 8 | Delete team section renders for owner only |

---

## 7. Layout Components

### 7.1 `Layouts/GuestLayout.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders app name from VITE_APP_NAME |
| 2 | Renders Head with title prop |
| 3 | Renders slot content |
| 4 | App name links to "/" |

### 7.2 `Layouts/NavigationMenu.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Renders app name from VITE_APP_NAME |
| 2 | Renders navigation links (Dashboard, Customers, Invoices, etc.) |
| 3 | Active link has correct styling |
| 4 | User dropdown shows name and email |
| 5 | Logout triggers router.post() |
| 6 | Team switcher shows current team |
| 7 | Team switcher switches team via router.put() |
| 8 | Mobile menu toggle works |
| 9 | Mobile menu shows/hides navigation links |

---

## 8. Integration-Level Tests

These test interactions between multiple components working together.

### 8.1 `integration/InvoiceCreationFlow.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Full invoice creation: select customer → add items → verify totals → submit |
| 2 | Customer change resets location and currency |
| 3 | Adding multiple items with different tax rates → correct aggregated totals |
| 4 | Removing all items except one → totals recalculate |
| 5 | Form validation prevents submit with missing required fields |
| 6 | Successful submit redirects (via onSuccess callback) |

### 8.2 `integration/CustomerManagementFlow.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Create customer → add location → set primary → verify state |
| 2 | Edit customer contacts → add new → remove existing → save |
| 3 | Delete customer with confirmation → optimistic removal from list |

### 8.3 `integration/PaymentFlow.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Record full payment → verify status change to PAID in UI |
| 2 | Record partial payment → balance due updates |
| 3 | Delete payment → balance recalculates |

### 8.4 `integration/EmailSendFlow.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Open email modal → load template → add recipients → send |
| 2 | Change template type → content updates |
| 3 | Insert variable into body → appears in editor |

### 8.5 `integration/OrganizationSetupFlow.test.ts` — NEW
| # | Test Case |
|---|-----------|
| 1 | Complete all 4 steps → form.post() called for each step |
| 2 | Navigate back and forth between steps → data preserved |
| 3 | Validation error on step 3 prevents advancement |

---

## Summary

| Category | New Files | New Test Cases | Existing Files |
|----------|-----------|----------------|----------------|
| Composables | 1 | 14 | 3 (+ additions) |
| Display Components | 2 | 10 | 3 (+ additions) |
| Form Components | 5 | 66 | 1 (+ additions) |
| Auth Pages | 7 | 34 | 0 |
| Core Pages | 10 | 107 | 0 |
| Profile/Team Pages | 8 | 28 | 0 |
| Layout Components | 2 | 13 | 0 |
| Integration Tests | 5 | 17 | 0 |
| **TOTAL** | **40** | **~289** | **7** |

### Priority Order
1. **P0 — Forms & Modals** (InvoiceForm, CustomerForm, PaymentModal, EmailModal, LocationModal) — highest business logic density
2. **P1 — Core Pages** (Invoices/Index, Customers/Index, Dashboard) — main user flows
3. **P2 — Integration Tests** — end-to-end form flows
4. **P3 — Auth Pages** — standard forms, lower risk
5. **P4 — Profile/Teams, Layouts** — lower complexity

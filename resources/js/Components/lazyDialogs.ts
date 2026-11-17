import { defineLazyDialog } from '@/composables/useLazyDialog';

/**
 * Dialogs too heavy to ship with the pages that open them. Declared here, once,
 * so every page shares one loaded flag per dialog. Pages where the editor or
 * the phone input is the main content (email templates, organization setup and
 * settings) import them directly instead.
 */

/** The email composer: TipTap. Opened from invoices and estimates. */
export const emailModalDialog = defineLazyDialog(
    () => import('@/Components/Invoice/EmailModal.vue'),
);

/** One reminder for several unpaid invoices: TipTap. */
export const customerReminderDialog = defineLazyDialog(
    () => import('@/Components/Invoice/CustomerReminderModal.vue'),
);

/** Add or edit a customer: libphonenumber, through PhoneInput. */
export const customerFormDialog = defineLazyDialog(
    () => import('@/Components/CustomerForm.vue'),
);

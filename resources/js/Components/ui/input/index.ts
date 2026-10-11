export { default as Input } from './Input.vue';

/**
 * The shared look of every text-like control in the app: input, select and
 * textarea. Kept in one string so a select never ends up a pixel taller than
 * the input beside it, which is the usual way a form starts looking untidy.
 */
export const controlClasses =
    'block w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs transition-colors ' +
    'placeholder:text-muted-foreground ' +
    'focus:border-ring focus:ring-2 focus:ring-ring/30 focus:outline-none ' +
    'disabled:cursor-not-allowed disabled:bg-muted disabled:text-muted-foreground ' +
    'aria-invalid:border-destructive aria-invalid:focus:ring-destructive/30';

/**
 * The same control, without its box until you reach for it.
 *
 * A table of these is mostly read, not edited: twenty bordered inputs stacked
 * in a grid read as a form to fill in rather than as lines already written.
 * The border and background appear on hover and on focus, so the control is
 * still obviously a control the moment the pointer or the keyboard arrives.
 */
export const quietControlClasses =
    'border-transparent bg-transparent shadow-none hover:border-input hover:bg-background';

export { default as Input } from './Input.vue';

/**
 * The shared look of every text-like control in the app: input, select and
 * textarea. Kept in one string so a select never ends up a pixel taller than
 * the input beside it, which is the usual way a form starts looking untidy.
 *
 * 16px on a touch screen, 14px under a mouse. iOS Safari zooms the whole page
 * into any field set smaller than 16px the moment it is tapped, and leaves it
 * zoomed after the keyboard closes -- so every field on a phone cost a pinch
 * to get the page back. Keyed to the pointer rather than to the width, so a
 * tablet is covered in either orientation.
 */
export const controlClasses =
    'block w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs transition-colors pointer-coarse:text-base ' +
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
 *
 * Only where there is a hover to bring the box back. A touch screen never
 * delivers one, so on a phone or a tablet these read as plain text -- nothing
 * said the lines could be edited at all. There the box simply stays.
 */
export const quietControlClasses =
    '[@media(hover:hover)]:border-transparent [@media(hover:hover)]:bg-transparent [@media(hover:hover)]:shadow-none hover:border-input hover:bg-background';

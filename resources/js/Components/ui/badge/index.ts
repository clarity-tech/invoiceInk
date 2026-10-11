import { cva, type VariantProps } from 'class-variance-authority';

export { default as Badge } from './Badge.vue';

export const badgeVariants = cva(
    'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap [&_svg]:size-3 [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                default:
                    'border-transparent bg-primary text-primary-foreground',
                secondary:
                    'border-transparent bg-secondary text-secondary-foreground',
                outline: 'border-border text-foreground',
                muted: 'border-transparent bg-muted text-muted-foreground',
                /**
                 * Soft tints, the same weight as StatusBadge's pills. The
                 * filled `default`/`success` variants shout next to one, which
                 * is wrong for a label that only says what kind of document a
                 * row is.
                 *
                 * `infoSoft` is sky rather than green on purpose. StatusBadge
                 * already spends gray, brand, yellow, amber, green and red, so
                 * a green pill one column from a green "Paid" pill reads as a
                 * status. Sky is the nearest hue none of them uses.
                 */
                brandSoft: 'border-transparent bg-brand-100 text-brand-800',
                infoSoft: 'border-transparent bg-sky-100 text-sky-800',
                success:
                    'border-transparent bg-success text-success-foreground',
                destructive:
                    'border-transparent bg-destructive text-destructive-foreground',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;

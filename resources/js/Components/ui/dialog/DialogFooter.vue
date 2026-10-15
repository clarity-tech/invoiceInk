<template>
    <div
        data-slot="dialog-footer"
        class="flex flex-col-reverse gap-3 border-t border-border bg-muted px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="text-xs text-muted-foreground">
            <slot name="meta" />
        </div>
        <div class="flex items-center justify-end gap-2">
            <slot />
        </div>
    </div>
</template>

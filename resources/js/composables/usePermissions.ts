import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CurrentTeamAuth {
    role: string | null;
    permissions: string[];
}

/**
 * Role/permission helpers for the current organization.
 *
 * Reads the role + permission list shared by HandleInertiaRequests under
 * `auth.currentTeam`. Server-side policies are the source of truth; these
 * helpers only drive UI affordances (hiding/disabling actions).
 */
export function usePermissions() {
    const page = usePage();

    const currentTeam = computed(
        () =>
            (page.props.auth as { currentTeam?: CurrentTeamAuth | null })
                ?.currentTeam ?? null,
    );

    const role = computed<string | null>(() => currentTeam.value?.role ?? null);
    const permissions = computed<string[]>(
        () => currentTeam.value?.permissions ?? [],
    );

    function can(permission: string): boolean {
        return (
            permissions.value.includes('*') ||
            permissions.value.includes(permission)
        );
    }

    const isOwner = computed(() => role.value === 'owner');
    const canManage = computed(() => can('manage'));

    return { role, permissions, can, isOwner, canManage };
}

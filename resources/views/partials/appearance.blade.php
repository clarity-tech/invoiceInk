{{--
    Applies the colour theme before the first paint, for every choice: an
    explicit one from the cookie, and `system`, which only the browser can
    resolve. It then keeps following the operating system while the page is
    open. The server also renders `class="dark"` for an explicit dark choice
    (see HandleAppearance), so a page is right even where this script cannot
    run.

    The cookie is read live rather than taken from the server, so a choice made
    on this page (see useAppearance.ts) is what the listener honours.
--}}
<meta name="color-scheme" content="light dark">
<script>
    (function () {
        var root = document.documentElement;
        var media = window.matchMedia('(prefers-color-scheme: dark)');

        function apply() {
            var match = document.cookie.match(/(?:^|;\s*){{ \App\Enums\Appearance::COOKIE }}=(light|dark|system)(?:;|$)/);
            var choice = match ? match[1] : 'system';

            root.classList.toggle('dark', choice === 'dark' || (choice === 'system' && media.matches));
        }

        apply();
        media.addEventListener('change', apply);
    })();
</script>

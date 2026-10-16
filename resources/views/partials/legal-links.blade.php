<nav aria-label="Legal" class="mt-6 mb-10 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-muted-foreground">
    <a href="{{ url('/') }}" class="hover:text-foreground">{{ config('app.name', 'InvoiceInk') }}</a>
    <a href="{{ route('terms.show') }}" @if (request()->routeIs('terms.show')) aria-current="page" @endif class="hover:text-foreground aria-[current=page]:font-medium aria-[current=page]:text-foreground">Terms of Service</a>
    <a href="{{ route('policy.show') }}" @if (request()->routeIs('policy.show')) aria-current="page" @endif class="hover:text-foreground aria-[current=page]:font-medium aria-[current=page]:text-foreground">Privacy Policy</a>
</nav>

<div class="footer{{ $tight ? ' footer-tight' : '' }}">
    @if($organization)
    <p class="footer-org" style="margin: 0 0 10px 0; font-weight: 600;">
        {{ $organization->company_name ?? $organization->name }}
    </p>
    @if($location)
    <p style="color: #9ca3af; margin: 0;">
        {{ $location->address_line_1 }}@if($location->address_line_2), {{ $location->address_line_2 }}@endif<br>
        {{ $location->city }} {{ $location->state }} {{ $location->postal_code }}<br>
        {{ $location->countryName() }}
    </p>
    @endif
    @endif
</div>

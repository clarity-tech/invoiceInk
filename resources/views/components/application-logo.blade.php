{{-- InvoiceInk combined logo lockup (ink-drop mark + two-tone wordmark). --}}
{{-- "Invoice" uses currentColor so it adapts to the surrounding text color --}}
{{-- (dark on light backgrounds, white on dark); the mark and "Ink" stay brand --}}
{{-- violet. Keep visually in sync with resources/js/Components/AppLogo.vue. --}}
<svg viewBox="0 0 210 48" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="InvoiceInk" {{ $attributes }}>
  <g transform="translate(-8.1,3.75) scale(0.81)">
    <path d="M24 4C24 4 10 22 10 32a14 14 0 0028 0C38 22 24 4 24 4z" fill="#7c3aed"/>
    <path d="M24 14l-4 12h8L24 14z" fill="#ffffff" opacity="0.9"/>
  </g>
  <text x="34" y="36" font-family="Figtree, system-ui, sans-serif" font-size="36" font-weight="700">
    <tspan fill="currentColor">Invoice</tspan><tspan fill="#7c3aed">Ink</tspan>
  </text>
</svg>

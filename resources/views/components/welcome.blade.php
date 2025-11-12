@php
$currentTeam = auth()->user()->currentTeam;
@endphp

<div class="p-6 lg:p-8 bg-white border-b border-gray-200">
    <x-application-logo class="block h-12 w-auto" />

    <h1 class="mt-8 text-2xl font-medium text-gray-900">
        Welcome to your Invoicing Dashboard
        @if($currentTeam)
            <span class="text-lg text-gray-600 font-normal">- {{ $currentTeam->name }}</span>
        @endif
    </h1>

    <p class="mt-6 text-gray-500 leading-relaxed">
        Manage your invoices, customers, and organizations with this powerful Laravel-based invoicing system. 
        Create professional invoices and estimates, track payments, and grow your business efficiently.
    </p>
</div>

<div class="p-6 lg:p-8">
    <!-- Organization Setup Status -->
    <x-organization-setup-status />

    <!-- Quick Actions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Create Invoice -->
        <a href="{{ route('invoices.index') }}" 
           class="bg-blue-50 hover:bg-blue-100 transition-colors duration-200 rounded-lg p-6 group">
            <div class="flex items-center">
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-blue-900 group-hover:text-blue-700">
                        Create Invoice
                    </h3>
                    <p class="text-sm text-blue-600">
                        Generate new invoices
                    </p>
                </div>
            </div>
        </a>

        <!-- Manage Customers -->
        <a href="{{ route('customers.index') }}" 
           class="bg-green-50 hover:bg-green-100 transition-colors duration-200 rounded-lg p-6 group">
            <div class="flex items-center">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-green-900 group-hover:text-green-700">
                        Manage Customers
                    </h3>
                    <p class="text-sm text-green-600">
                        Add and edit customers
                    </p>
                </div>
            </div>
        </a>

        <!-- Organizations -->
        <a href="{{ route('organizations.index') }}" 
           class="bg-purple-50 hover:bg-purple-100 transition-colors duration-200 rounded-lg p-6 group">
            <div class="flex items-center">
                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-purple-900 group-hover:text-purple-700">
                        Organizations
                    </h3>
                    <p class="text-sm text-purple-600">
                        Manage your businesses
                    </p>
                </div>
            </div>
        </a>

        <!-- Settings -->
        <a href="{{ route('numbering-series.index') }}" 
           class="bg-gray-50 hover:bg-gray-100 transition-colors duration-200 rounded-lg p-6 group">
            <div class="flex items-center">
                <svg class="h-8 w-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-gray-700">
                        Settings
                    </h3>
                    <p class="text-sm text-gray-600">
                        Invoice numbering
                    </p>
                </div>
            </div>
        </a>
    </div>

    @if($currentTeam)
        <!-- Organization Info -->
        <div class="mt-8 bg-gray-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Organization</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg p-4">
                    <div class="text-sm text-gray-600">Organization</div>
                    <div class="text-lg font-semibold text-gray-900">{{ $currentTeam->name }}</div>
                </div>
                @if($currentTeam->currency)
                    <div class="bg-white rounded-lg p-4">
                        <div class="text-sm text-gray-600">Currency</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $currentTeam->currency->name() }}</div>
                    </div>
                @endif
                @if($currentTeam->country_code)
                    <div class="bg-white rounded-lg p-4">
                        <div class="text-sm text-gray-600">Country</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $currentTeam->country_code->flag() }} {{ $currentTeam->country_code->name() }}</div>
                    </div>
                @endif
                @if($currentTeam->isSetupComplete())
                    <div class="bg-white rounded-lg p-4">
                        <div class="text-sm text-gray-600">Setup Status</div>
                        <div class="text-lg font-semibold text-green-600">Complete</div>
                    </div>
                @else
                    <div class="bg-white rounded-lg p-4">
                        <div class="text-sm text-gray-600">Setup Status</div>
                        <div class="text-lg font-semibold text-yellow-600">{{ $currentTeam->getSetupCompletionPercentage() }}% Complete</div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
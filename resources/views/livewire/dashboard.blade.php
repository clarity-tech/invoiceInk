<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 sm:px-0">
        <!-- Organization Setup Status -->
        <x-organization-setup-status />

        <!-- Header with Period Selector -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ auth()->user()->currentTeam?->displayName ?? 'Dashboard' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">Business overview and analytics</p>
            </div>
            <select wire:model.live="period" class="rounded-md border-gray-300 shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="this_quarter">This Quarter</option>
                <option value="this_year">This Year</option>
                <option value="all_time">All Time</option>
            </select>
        </div>

        @php
            $stats = $this->stats;
            $currency = $stats['currency'];
            $fmt = fn($v) => $currency ? $currency->formatAmount($v) : number_format($v / 100, 2);
        @endphp

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Total Revenue -->
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-gray-500">Total Invoiced</div>
                    <svg class="size-5 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $fmt($stats['total_revenue']) }}</div>
                <div class="mt-1 text-sm text-gray-500">{{ $stats['invoice_count'] }} {{ Str::plural('invoice', $stats['invoice_count']) }}</div>
            </div>

            <!-- Collected -->
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-gray-500">Collected</div>
                    <svg class="size-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="mt-2 text-2xl font-bold text-green-600">{{ $fmt($stats['total_collected']) }}</div>
                <div class="mt-1 text-sm text-gray-500">{{ $stats['collection_rate'] }}% collection rate</div>
            </div>

            <!-- Outstanding -->
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-gray-500">Outstanding</div>
                    <svg class="size-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="mt-2 text-2xl font-bold text-amber-600">{{ $fmt($stats['total_outstanding']) }}</div>
                <div class="mt-1 text-sm {{ $stats['overdue_count'] > 0 ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                    {{ $stats['overdue_count'] }} overdue
                </div>
            </div>

            <!-- Customers & Estimates -->
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-gray-500">Customers</div>
                    <svg class="size-5 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-4.5 0 2.625 2.625 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <div class="mt-2 text-2xl font-bold text-gray-900">{{ $this->customerCount }}</div>
                @php $estStats = $this->estimateStats; @endphp
                <div class="mt-1 text-sm text-gray-500">{{ $estStats['count'] }} {{ Str::plural('estimate', $estStats['count']) }} ({{ $estStats['accepted'] }} accepted)</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Status Breakdown -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Invoice Status Breakdown</h3>
                <div class="space-y-3">
                    @foreach($this->statusBreakdown as $item)
                        @if($item['count'] > 0)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $item['status']->badge() }}">
                                        {{ $item['status']->label() }}
                                    </span>
                                    <span class="text-sm text-gray-500">{{ $item['count'] }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $fmt($item['total']) }}</span>
                            </div>
                        @endif
                    @endforeach
                    @if(collect($this->statusBreakdown)->sum('count') === 0)
                        <p class="text-sm text-gray-400 text-center py-4">No invoices in this period</p>
                    @endif
                </div>
            </div>

            <!-- 6-Month Trend -->
            <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">6-Month Trend</h3>
                @php
                    $trend = $this->monthlyTrend;
                    $maxVal = max(1, max(array_column($trend, 'invoiced')));
                @endphp
                <div class="flex items-end justify-between gap-2 h-40">
                    @foreach($trend as $month)
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-full flex flex-col items-center justify-end h-28 gap-0.5">
                                @php
                                    $invoicedH = $maxVal > 0 ? max(2, ($month['invoiced'] / $maxVal) * 100) : 2;
                                    $collectedH = $maxVal > 0 ? max(0, ($month['collected'] / $maxVal) * 100) : 0;
                                @endphp
                                <div class="w-full max-w-[2rem] rounded-t bg-brand-200 transition-all" style="height: {{ $invoicedH }}%"
                                     title="Invoiced: {{ $fmt($month['invoiced']) }}"></div>
                                @if($month['collected'] > 0)
                                    <div class="w-full max-w-[2rem] rounded-t bg-green-400 transition-all" style="height: {{ $collectedH }}%"
                                         title="Collected: {{ $fmt($month['collected']) }}"></div>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500">{{ $month['short'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-brand-200"></span> Invoiced</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-green-400"></span> Collected</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Top Customers -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Top Customers</h3>
                @if(count($this->topCustomers) > 0)
                    <div class="space-y-3">
                        @foreach($this->topCustomers as $cust)
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $cust['name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $cust['invoice_count'] }} {{ Str::plural('invoice', $cust['invoice_count']) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-gray-900">{{ $fmt($cust['total']) }}</div>
                                    @if($cust['outstanding'] > 0)
                                        <div class="text-xs text-amber-600">{{ $fmt($cust['outstanding']) }} due</div>
                                    @else
                                        <div class="text-xs text-green-600">Fully paid</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-4">No customer data in this period</p>
                @endif
            </div>

            <!-- Overdue Invoices -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">
                    Overdue Invoices
                    @if($this->overdueInvoices->isNotEmpty())
                        <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">{{ $this->overdueInvoices->count() }}</span>
                    @endif
                </h3>
                @if($this->overdueInvoices->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($this->overdueInvoices as $inv)
                            <a href="{{ route('invoices.edit', $inv) }}" class="flex items-center justify-between hover:bg-gray-50 -mx-2 px-2 py-1 rounded">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $inv->invoice_number }}</div>
                                    <div class="text-xs text-gray-500">{{ $inv->customer?->name ?? 'N/A' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-red-600">{{ $inv->formatted_remaining_balance }}</div>
                                    <div class="text-xs text-red-500">Due {{ $inv->due_at->diffForHumans() }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <svg class="mx-auto size-8 text-green-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="mt-1 text-sm text-gray-400">No overdue invoices</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Recent Invoices -->
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900">Recent Invoices</h3>
                    <a href="{{ route('invoices.index') }}" class="text-xs text-brand-600 hover:text-brand-800">View all</a>
                </div>
                @if($this->recentInvoices->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($this->recentInvoices as $inv)
                            <a href="{{ route('invoices.edit', $inv) }}" class="flex items-center justify-between hover:bg-gray-50 -mx-2 px-2 py-1 rounded">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $inv->invoice_number }}</div>
                                    <div class="text-xs text-gray-500">{{ $inv->customer?->name ?? 'N/A' }} &middot; {{ $inv->issued_at?->format('M j') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-gray-900">{{ $inv->formatted_total }}</div>
                                    <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-xs font-medium {{ $inv->status->badge() }}">{{ $inv->status->label() }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-4">No invoices yet</p>
                @endif
            </div>

            <!-- Recent Payments -->
            <div class="bg-white rounded-lg shadow p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Recent Payments</h3>
                @if($this->recentPayments->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($this->recentPayments as $payment)
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $payment->invoice?->invoice_number }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $payment->invoice?->customer?->name ?? 'N/A' }}
                                        @if($payment->payment_method)
                                            &middot; {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-green-600">+{{ $payment->formatted_amount }}</div>
                                    <div class="text-xs text-gray-500">{{ $payment->payment_date->format('M j, Y') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-4">No payments recorded yet</p>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="{{ route('invoices.create') }}" class="flex items-center gap-3 bg-white rounded-lg shadow p-4 hover:bg-brand-50 transition-colors">
                <svg class="size-5 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="text-sm font-medium text-gray-700">New Invoice</span>
            </a>
            <a href="{{ route('estimates.create') }}" class="flex items-center gap-3 bg-white rounded-lg shadow p-4 hover:bg-green-50 transition-colors">
                <svg class="size-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span class="text-sm font-medium text-gray-700">New Estimate</span>
            </a>
            <a href="{{ route('customers.index') }}" class="flex items-center gap-3 bg-white rounded-lg shadow p-4 hover:bg-purple-50 transition-colors">
                <svg class="size-5 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                <span class="text-sm font-medium text-gray-700">Customers</span>
            </a>
            <a href="{{ route('numbering-series.index') }}" class="flex items-center gap-3 bg-white rounded-lg shadow p-4 hover:bg-gray-50 transition-colors">
                <svg class="size-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                <span class="text-sm font-medium text-gray-700">Settings</span>
            </a>
        </div>
    </div>
</div>

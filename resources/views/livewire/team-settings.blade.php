<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Team Settings</h1>
            <p class="mt-2 text-gray-600">Manage your team configuration and URL settings.</p>
        </div>

        @if (session()->has('message'))
            <div class="mb-4 p-4 text-green-700 bg-green-100 border border-green-300 rounded">
                {{ session('message') }}
            </div>
        @endif

        <div class="space-y-6">
            <!-- Team Name -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Team Name</h2>
                    <p class="text-sm text-gray-600">Update your team's display name.</p>
                </div>
                
                <form wire:submit="updateTeamName" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Team Name *</label>
                            <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                Update Name
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- URL Handle -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">URL Handle</h2>
                    <p class="text-sm text-gray-600">Configure a custom URL slug for your team (e.g., your-company.clarity-invoicing.com)</p>
                </div>
                
                <form wire:submit="updateSlug" class="p-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">URL Handle</label>
                            <div class="flex items-center">
                                <input wire:model="slug" type="text" placeholder="your-company" 
                                       class="flex-1 border border-gray-300 rounded-l-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span class="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-gray-500 text-sm">
                                    .clarity-invoicing.com
                                </span>
                            </div>
                            @error('slug') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @if($slug)
                                <p class="mt-1 text-sm text-gray-500">
                                    Your team URL: <span class="font-mono">https://{{ $slug }}.clarity-invoicing.com</span>
                                </p>
                            @endif
                        </div>
                        <div>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                Update URL Handle
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Custom Domain -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Custom Domain</h2>
                    <p class="text-sm text-gray-600">Use your own domain for your team (e.g., invoices.yourcompany.com)</p>
                </div>
                
                <form wire:submit="updateCustomDomain" class="p-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Custom Domain</label>
                            <input wire:model="custom_domain" type="text" placeholder="invoices.yourcompany.com"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('custom_domain') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @if($custom_domain)
                                <p class="mt-1 text-sm text-gray-500">
                                    Your custom URL: <span class="font-mono">https://{{ $custom_domain }}</span>
                                </p>
                            @endif
                            <p class="mt-1 text-xs text-gray-400">
                                Note: You'll need to configure DNS records to point your domain to our servers.
                            </p>
                        </div>
                        <div>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                Update Custom Domain
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Current URLs -->
            @if($team)
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Current Team URLs</h2>
                    <p class="text-sm text-gray-600">Your team can be accessed via these URLs:</p>
                </div>
                
                <div class="p-6 space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                        <span class="text-sm font-medium text-gray-700">Primary URL:</span>
                        <code class="text-sm text-blue-600">{{ $team->url }}</code>
                    </div>
                    
                    @if($team->slug)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                        <span class="text-sm font-medium text-gray-700">Slug URL:</span>
                        <code class="text-sm text-blue-600">https://{{ $team->slug }}.clarity-invoicing.com</code>
                    </div>
                    @endif
                    
                    @if($team->custom_domain)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                        <span class="text-sm font-medium text-gray-700">Custom Domain:</span>
                        <code class="text-sm text-blue-600">https://{{ $team->custom_domain }}</code>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
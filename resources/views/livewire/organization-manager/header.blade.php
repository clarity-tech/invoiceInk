<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">
            @if($autoEdit)
                {{ __('documents.headers.manage_your_business') }}
            @else
                {{ __('actions.navigation.organizations') }}
            @endif
        </h1>
        @if($autoEdit)
            <div class="mt-2">
                <a href="{{ route('organizations.index') }}" class="text-sm text-brand-600 hover:text-brand-800">
                    {{ __('actions.buttons.view_all_organizations') }}
                </a>
            </div>
        @endif
    </div>
    @if (!$showForm && !$autoEdit)
        <button wire:click="create" class="bg-brand-500 hover:bg-brand-700 text-white font-bold py-2 px-4 rounded">
            {{ __('actions.buttons.add_organization') }}
        </button>
    @endif
</div>

@if (session()->has('message'))
    <div class="mb-4 p-4 text-green-700 bg-green-100 border border-green-300 rounded">
        {{ session('message') }}
    </div>
@endif

{{-- Display general errors --}}
@if ($errors->any())
    <div class="mb-4 p-4 text-red-700 bg-red-100 border border-red-300 rounded">
        <div class="font-medium">
            {{ __('Whoops! Something went wrong.') }}
        </div>

        <ul class="mt-3 list-disc list-inside text-sm text-red-600">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

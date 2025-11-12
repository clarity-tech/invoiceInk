@php
$currentTeam = auth()->user()->currentTeam;
$isSetupComplete = $currentTeam ? $currentTeam->isSetupComplete() : true;
$setupPercentage = $currentTeam ? $currentTeam->getSetupCompletionPercentage() : 100;
$missingFields = $currentTeam ? $currentTeam->getMissingSetupFields() : [];
@endphp

@if($currentTeam && !$currentTeam->personal_team && !$isSetupComplete)
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-yellow-800">
                    Organization Setup Incomplete
                </h3>
                <div class="mt-2">
                    <div class="flex items-center mb-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $setupPercentage }}%"></div>
                        </div>
                        <span class="ml-2 text-sm font-medium text-yellow-800">{{ $setupPercentage }}%</span>
                    </div>
                    <p class="text-sm text-yellow-700">
                        Complete your organization setup to unlock all features.
                        @if(count($missingFields) > 0)
                            Missing: {{ implode(', ', $missingFields) }}
                        @endif
                    </p>
                </div>
                <div class="mt-4">
                    <a href="{{ route('organization.setup') }}" 
                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200">
                        Complete Setup
                        <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L13.586 11H4a1 1 0 110-2h9.586l-3.293-3.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
@elseif($currentTeam && !$currentTeam->personal_team && $isSetupComplete)
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">
                    Organization Setup Complete
                </h3>
                <p class="text-sm text-green-700">
                    Your organization is fully configured and ready for invoicing.
                    @if($currentTeam->setup_completed_at)
                        Setup completed on {{ $currentTeam->setup_completed_at->format('M j, Y') }}.
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif
<div class="space-y-6">
    <div class="rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <div class="space-y-4">
            <!-- Progress Steps -->
            <div class="flex items-center justify-between pb-4">
                <div class="flex items-center">
                    @for($i = 1; $i <= 3; $i++)
                        <div class="flex items-center">
                            <div class="flex flex-col items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 {{ $i <= $step ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-300 text-gray-500 dark:border-gray-600 dark:text-gray-400' }}">
                                    {{ $i }}
                                </div>
                                <div class="mt-2 text-sm font-medium {{ $i <= $step ? 'text-brand-500' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ array_keys($getSteps())[$i - 1] }}
                                </div>
                            </div>
                            @if($i < 3)
                                <div class="mx-4 h-0.5 w-24 bg-gray-300 dark:bg-gray-600"></div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Step Content -->
            <div class="mt-8">
                <h2 class="text-xl font-semibold text-ink dark:text-dark-ink mb-2">{{ $getStepTitle() }}</h2>
                <p class="text-muted-ink dark:text-gray-400">{{ $getStepDescription() }}</p>
            </div>

            <!-- Form Content -->
            <div class="mt-6">
                {{ $this->form }}
            </div>

            <!-- Step Actions -->
            <div class="flex justify-between pt-6">
                <div>
                    @if($step > 1)
                        <x-filament::button
                            wire:click="previousStep"
                            color="gray"
                        >
                            Previous
                        </x-filament::button>
                    @endif
                </div>
                
                <div>
                    @if($step < 3)
                        <x-filament::button
                            wire:click="nextStep"
                        >
                            Next
                        </x-filament::button>
                    @else
                        <x-filament::button
                            wire:click="startImport"
                            color="success"
                        >
                            Start Import
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
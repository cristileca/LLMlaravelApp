<div x-data="{ openIndex: null }" class="space-y-4">
    @foreach ($summary as $index => $item)
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <button @click="openIndex === {{ $index }} ? openIndex = null : openIndex = {{ $index }}"
                class="w-full text-left bg-blue-600 text-gray-800 py-3 px-4 rounded-t-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                <span class="font-semibold">Anexa {{ $index + 1 }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 inline ml-2 transition-transform"
                    :class="{ 'rotate-180': openIndex === {{ $index }} }" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="openIndex === {{ $index }}" class="p-4 text-sm text-gray-300 dark:text-gray-300"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">

                <div class="mb-4">
                    <div class="mt-2 font-medium text-base">Schimbări:</div>
                    <div class="mt-2 text-gray-900 dark:text-gray-100 overflow-hidden max-h-48 overflow-y-auto"
                        style="font-size: 90%;">
                        @if (!$item->changes || is_string($item->changes))
                            <p class="mt-1">Nu sunt modificări.</p>
                        @else
                            @foreach ($item->changes as $change)
                                <p class="mt-1"><strong>Produs/Serviciu -
                                        {{ $change->nume_produs ?: 'nu s-a găsit' }}:</strong></p>
                                <p class="mt-1">- Preț: {{ $change->pret ?: 'nu s-a găsit' }}</p>
                                @if (!empty($change->detalii_aditionale))
                                    <p class="mt-1">- Costuri suplimentare: {{ $change->detalii_aditionale }}</p>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="mt-2 font-medium text-base">Numele Anexei:</div>
                <div class="text-gray-800 dark:text-gray-200">{{ $item->name }}</div>
                <div class="mt-2 font-medium text-base">Data Anexei:</div>
                <div class="text-gray-800 dark:text-gray-200">{{ $item->annex_date }}</div>
            </div>
        </div>
    @endforeach
</div>

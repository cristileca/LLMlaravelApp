<div class="rounded-lg shadow-md p-6 bg-white dark:bg-gray-900 transition-all duration-300">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-center" style="margin-bottom:1rem">
            {{ $supplier_name }}
        </h1>
        @if ($presigned)
            <div class="flex items-center">
                <a href="{{ $presigned }}"
                    style="
                        color: dodgerblue; 
                        margin-left: 0.5rem; 
                        text-decoration: none; 
                        border: 2px solid dodgerblue; 
                        border-radius: 4px; 
                        padding: 0.5rem 1rem; 
                        display: inline-block; 
                        font-weight: 500; 
                        transition: background-color 0.3s, color 0.3s;
                    "
                    class="text-blue-500 hover:bg-dodgerblue hover:text-white">
                    Accesați documentul
                </a>
            </div>
        @endif
    </div>

    <div class="mb-4 space-y-2">
        <div class="text-lg font-medium text-gray-800 dark:text-gray-300">
            <span class="font-semibold">Numar de contract:</span>
            <span class="text-gray-600 dark:text-gray-400">{{ $contract_number }}</span>
        </div>
        <div class="text-lg font-medium text-gray-800 dark:text-gray-300">
            <span class="font-semibold">Data contractului:</span>
            <span class="text-gray-600 dark:text-gray-400">{{ $issue_date }}</span>
        </div>
    </div>

    <div class="space-y-4">
        <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-lg space-y-4">
            <div class="p-4 border-b border-gray-300 dark:border-gray-600">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-1">Obiectiv</p>
                <p class="text-sm text-gray-700 dark:text-gray-400">{{ $objective }}</p>
            </div>
            <div class="p-4 border-b border-gray-300 dark:border-gray-600">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-1">Condiții de livrare</p>
                <p class="text-sm text-gray-700 dark:text-gray-400">{{ $delivery_conditions }}</p>
            </div>
            <div class="p-4 border-b border-gray-300 dark:border-gray-600">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-1">Preț</p>
                <p class="text-sm text-gray-700 dark:text-gray-400">{{ $price }}</p>
            </div>
            <div class="p-4 border-b border-gray-300 dark:border-gray-600">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-1">Penalități</p>
                <p class="text-sm text-gray-700 dark:text-gray-400">{{ $penalties }}</p>
            </div>
            <div class="p-4 border-b border-gray-300 dark:border-gray-600">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-1">Condiții de plată</p>
                <p class="text-sm text-gray-700 dark:text-gray-400">{{ $payment_conditions }}</p>
            </div>
            <div class="p-4">
                <p class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-1">Termen contract</p>
                <p class="text-sm text-gray-700 dark:text-gray-400">{{ $contract_term }}</p>
            </div>
        </div>
    </div>

    <div x-data="{ openIndex: null }" style="margin-top:1rem;margin-bottom:1rem">
        <!-- Dropdown Component -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <!-- Dropdown Trigger Button -->
            <button @click="openIndex === 0 ? openIndex = null : openIndex = 0"
                class="w-full text-left bg-blue-600 dark:bg-blue-500 text-white dark:text-gray-100 py-3 px-4 rounded-t-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition">
                <span class="font-semibold">Raw data</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 inline ml-2 transition-transform"
                    :class="{ 'rotate-180': openIndex === 0 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Dropdown Content -->
            <div x-show="openIndex === 0" class="p-4 text-sm text-gray-900 dark:text-gray-300"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">
                <div class="space-y-8">
                    <div>
                        <h1 class="text-red-600 dark:text-red-500">Summary</h1>
                        <pre id="json-summary"
                            class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-auto">
                            {{ $summary }}
                        </pre>
                    </div>
                    <hr class="border-2 border-gray-200 dark:border-gray-700 mx-4">
                    <div>
                        <h1 class="text-red-600 dark:text-red-500">Raw text</h1>
                        <pre id="json-raw-text"
                            class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-auto">
                            {{ $raw_text }}
                        </pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('jsonViewer', {
            init() {
                const summaryElement = document.getElementById('json-summary');
                const rawTextElement = document.getElementById('json-raw-text');

                try {
                    const summaryJson = JSON.parse(summaryElement.textContent.trim());
                    summaryElement.textContent = JSON.stringify(summaryJson, null, 4);
                } catch (e) {
                    console.error('Invalid JSON in Summary');
                }

                try {
                    const rawJson = JSON.parse(rawTextElement.textContent.trim());
                    rawTextElement.textContent = JSON.stringify(rawJson, null, 4);
                } catch (e) {
                    console.error('Invalid JSON in Raw Text');
                }
            }
        });
    });
</script>

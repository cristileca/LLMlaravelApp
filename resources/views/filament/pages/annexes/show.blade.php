<div class="w-full h-screen py-6">
    <div class="rounded-lg p-6 shadow-md bg-white dark:bg-gray-900 transition-all duration-300 space-y-6">
        <div class="space-y-6">
            <div class="flex items-start justify-between mb-4">
                <h1 class="text-5xl font-bold text-gray-900 dark:text-gray-100 text-left" style="font-size: 150%;">
                    {{ $annex->name }}
                </h1>
                @if ($presigned)
                    <div class="flex items-center">
                        <a href="{{ $presigned }}"
                            style="color: dodgerblue;
                                   margin-left: 0.5rem;
                                   text-decoration: none;
                                   border: 2px solid dodgerblue;
                                   border-radius: 4px;
                                   padding: 0.5rem 1rem;
                                   display: inline-block;
                                   font-weight: 500;
                                   transition: background-color 0.3s, color 0.3s; font-size: 100%;"
                            class="text-blue-500 hover:bg-dodgerblue hover:text-white">
                            Accesați documentul
                        </a>
                    </div>
                @endif
            </div>
            <div class="flex mb-4 space-x-4" style="margin-top: 2rem">
                <div class="w-1/2 flex" style="margin-right: 1rem">
                    <div x-data="{ hover: false }" @mouseover="hover = true" @mouseleave="hover = false"
                        :class="hover ? 'transform scale-105 shadow-xl bg-gray-200 dark:bg-gray-700' :
                            'bg-gray-100 dark:bg-gray-800'"
                        class="transition-all duration-300 p-4 rounded-lg flex-1">
                        <a href="/admin/suppliers/{{ $supplier_id }}" class="block h-full w-full">
                            <p class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2 transition-all duration-300"
                                :class="hover ? 'font-semibold' : ''">
                                {{ $supplier_name }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Adresă: {{ $supplier_address }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Cod fiscal: {{ $trade_register_number }}
                            </p>
                        </a>
                    </div>
                </div>

                <div class="w-1/2 flex">
                    <div x-data="{ hover: false }" @mouseover="hover = true" @mouseleave="hover = false"
                        :class="hover ? 'transform scale-105 shadow-xl bg-gray-200 dark:bg-gray-700' :
                            'bg-gray-100 dark:bg-gray-800'"
                        class="transition-all duration-300 p-4 rounded-lg flex-1">
                        <a href="/admin/contracts/{{ $contract_id }}" class="block h-full w-full">
                            <p class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2 transition-all duration-300"
                                :class="hover ? 'font-semibold' : ''">
                                Contract
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Nr contract: {{ $contract_number }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Data: {{ $contract_date }}
                            </p>
                        </a>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-full space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 text-left">Informatii Anexa</h2>
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 text-left">Data</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100"
                            style="font-size: 100%;margin-bottom:0.5rem">
                            {{ $annex->annex_date }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 text-left">Status</dt>
                        <dd class="text-sm">
                            <span
                                style="background-color:
                                {{ match ($annex->status) {
                                    'ocr_in_progress' => '#fbbf24',
                                    'llm_in_progress' => '#6366f1',
                                    'error' => '#ef4444',
                                    'in_progress' => '#f97316',
                                    'success' => '#10b981',
                                    default => '#6b7280',
                                } }};
                                color: white;
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-weight: bold;
                                text-transform: capitalize;">
                                {{ \App\Models\Annex::$statuses[$annex->status] ?? 'Unknown' }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 text-left">Last Status Message
                        </dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                            {{ $annex->last_status_message }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="w-full lg:w-full space-y-4 mt-4">
                <div x-data="{ open: false }">
                    <button @click="open = !open"
                        class="w-full flex items-center text-sm text-left focus:outline-none hover:bg-gray-300 dark:hover:bg-gray-800 rounded-lg py-2 duration-200"
                        style="font-size: 100%;">
                        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100 text-left"
                            style="font-size: 120%;">Detalii</span>
                        <span :class="{ 'rotate-180': open }" class="transition-transform duration-200"
                            style="margin-left: 0.5rem; display: flex; align-items: center;">
                            ▼
                        </span>
                    </button>

                    <dd x-show="open" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-full"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 max-h-full" x-transition:leave-end="opacity-0 max-h-0"
                        class="mt-2 text-sm text-gray-900 dark:text-gray-100 overflow-hidden max-h-48 overflow-y-auto"
                        style="font-size: 90%;">

                        @if (!$summary)
                            <p class="mt-1">Nu sunt modificări.</p>
                        @else
                            @foreach ($summary as $change)
                                <p class="mt-1"><strong>Produs/Serviciu -
                                        {{ $change->nume_produs ?: 'nu s-a găsit' }}:</strong></p>
                                <p class="mt-1">- Preț: {{ $change->pret ?: 'nu s-a găsit' }}</p>
                                @if (!empty($change->detalii_aditionale))
                                    <p class="mt-1">- Costuri suplimentare: {{ $change->detalii_aditionale }}</p>
                                @endif
                            @endforeach
                        @endif
                    </dd>
                </div>
            </div>
        </div>
    </div>
</div>

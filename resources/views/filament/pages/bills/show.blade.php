<div class="rounded-lg p-4 shadow-md bg-white dark:bg-gray-900 transition-all duration-300">
    @if (!$supplier_name || !$contract_number)
        <h1 class="text-3xl font-bold text-red-600 dark:text-red-400 mb-4 text-center">
            Informațiile despre furnizor și/sau contract lipsesc
        </h1>
    @else
        <div style="margin-right: 2rem;">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-center">
                    Bill #{{ $number }}</h1>
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
            <p class="text-gray-600 dark:text-gray-400">Data factura: {{ $date ?? 'N/A' }}</p>
            <p class="text-gray-600 dark:text-gray-400">Data scadenta: {{ $due_date ?? 'N/A' }}</p>
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
    @endif
</div>

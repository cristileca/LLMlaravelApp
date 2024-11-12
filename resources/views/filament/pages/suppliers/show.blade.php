<div x-data="{ activeTab: 'tab1' }" class="flex flex-col w-full">
    <div class="flex-shrink-0 border-b border-gray-300 dark:border-gray-700">
        <button
            :class="{
                'text-blue-500 border-blue-500 dark:text-blue-300 dark:border-blue-300': activeTab === 'tab1',
                'text-gray-500 dark:text-gray-400': activeTab !== 'tab1'
            }"
            @click="activeTab = 'tab1'"
            class="py-2 px-4 border-b-2 focus:outline-none transition-all duration-300 ease-in-out cursor-pointer">
            Informații generale
        </button>
        <button
            :class="{
                'text-blue-500 border-blue-500 dark:text-blue-300 dark:border-blue-300': activeTab === 'tab2',
                'text-gray-500 dark:text-gray-400': activeTab !== 'tab2'
            }"
            @click="activeTab = 'tab2'"
            class="py-2 px-4 border-b-2 focus:outline-none transition-all duration-300 ease-in-out cursor-pointer">
            Facturi asociate cu furnizorul
        </button>
    </div>

    <div class="flex-grow p-4 overflow-x-auto">
        <!-- Tab 1 content -->
        <div x-show="activeTab === 'tab1'" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4">

            <div
                class="w-full p-6 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg shadow-md">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-gray-300">
                        {{ $supplier->name }}
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-gray-600 dark:text-gray-400 font-semibold">CIF</h3>
                        <p class="font-semibold text-gray-900 dark:text-gray-300">{{ $supplier->TIN }}</p>
                    </div>
                    <div>
                        <h3 class="text-gray-600 dark:text-gray-400 font-semibold">Adresă</h3>
                        <p class="font-semibold text-gray-900 dark:text-gray-300">{{ $supplier->address }}</p>
                    </div>
                    <div>
                        <h3 class="text-gray-600 dark:text-gray-400 font-semibold">Nr. Registrul Comerțului</h3>
                        <p class="font-semibold text-gray-900 dark:text-gray-300">{{ $supplier->trade_register_number }}
                        </p>
                    </div>
                    <div>
                        <h3 class="text-gray-600 dark:text-gray-400 font-semibold">Telefon</h3>
                        <p class="font-semibold text-gray-900 dark:text-gray-300">{{ $supplier->phone }}</p>
                    </div>
                    <div>
                        <h3 class="text-gray-600 dark:text-gray-400 font-semibold">IBAN</h3>
                        <p class="font-semibold text-gray-900 dark:text-gray-300">{{ $supplier->IBAN }}</p>
                    </div>
                    <div>
                        <h3 class="text-gray-600 dark:text-gray-400 font-semibold">Email</h3>
                        <p class="font-semibold text-gray-900 dark:text-gray-300">{{ $supplier->email }}</p>
                    </div>
                </div>

                <hr class="border-gray-300 dark:border-gray-600 mt-6 mb-6">

                <div class="mt-6 border border-gray-300 dark:border-gray-600 p-4 rounded-lg">
                    <h3 class="text-gray-600 dark:text-gray-400 font-semibold">Notite</h3>
                    <div class="font-semibold mt-2 border-t border-gray-300 dark:border-gray-600 pt-2">
                        <p class="text-gray-900 dark:text-gray-300">{{ $supplier->notes }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2 content -->
        <div x-show="activeTab === 'tab2'" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4">
            <div class="w-full overflow-x-auto">
                <table
                    class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg"
                    style="width:100%">
                    <thead
                        class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-300 uppercase text-sm leading-normal">
                        <tr>
                            <th class="py-3 px-6 text-left">Număr factură</th>
                            <th class="py-3 px-6 text-left">Data</th>
                            <th class="py-3 px-6 text-left">Taxă</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 dark:text-gray-300 text-sm font-light">
                        @foreach ($bills as $bill)
                            <tr @click="window.location.href='/admin/bills/{{ $bill->id }}'"
                                class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-900 text-gray-800 dark:text-gray-400 transition-colors duration-150 cursor-pointer">
                                <td class="py-3 px-6">{{ $bill->number }}</td>
                                <td class="py-3 px-6">{{ formatDate($bill->date) }}</td>
                                <td class="py-3 px-6">{{ number_format($bill->fee, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

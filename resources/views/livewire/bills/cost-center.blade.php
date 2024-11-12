<div class="flex items-center p-4 bg-gray-100 dark:bg-gray-900 rounded-md">

    <p class="text-gray-900 dark:text-white mr-4">
        {{ $bill->cost_center ? "The bill is associated with the '{$cost_center_name}' cost center" : 'The bill is not associated with any cost center' }}
    </p>

    <div style="width: 200px;"></div>

    @if (count($cost_centres))
        <div class="flex flex-col w-64">
            <label id="listbox-label" class="text-sm font-medium leading-6 text-gray-900 dark:text-white mb-2">
                Assign to
            </label>
            <div class="relative">
                <button wire:click="closeOrOpen" type="button"
                    class="relative w-full cursor-pointer rounded-lg bg-white dark:bg-gray-800 py-2 px-4 text-left text-gray-900 dark:text-white shadow-md ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition ease-in-out duration-150"
                    aria-haspopup="listbox" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                    aria-labelledby="listbox-label">
                    <span class="flex items-center">
                        <span class="ml-3 block truncate">
                            {{ $bill->cost_center ? $cost_center_name : 'Select a cost center' }}
                        </span>
                    </span>
                </button>

                <ul class="{{ !$isOpen ? 'hidden' : '' }} absolute z-10 mt-2 w-72 max-h-56 overflow-auto rounded-lg bg-white dark:bg-gray-800 py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                    @foreach ($cost_centres as $id => $name)
                        <li wire:click="selectCostCenter('{{ $id }}', '{{$name}}' )" class="relative cursor-pointer select-none py-2 pl-4 pr-10 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition ease-in-out duration-150" role="option">
                            <div class="flex items-center">
                                <span class="ml-3 block truncate font-normal">{{ $name }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @else
    @endif
</div>

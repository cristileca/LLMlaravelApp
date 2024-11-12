<div x-data="{ open: false }" class="relative flex justify-end">
    <button @click="open = true"
        class="text-gray-800 dark:text-white bg-gray-200 dark:bg-gray-600 px-4 py-2 rounded-md mt-4 cursor-pointer">
        View Activity Logs
    </button>

    <div x-show="open" class="fixed inset-0 z-50 flex" x-cloak>
        <div @click="open = false" class="fixed inset-0 bg-black opacity-50"></div>

        <!-- Panel that opens on the right side -->
        <div class="ml-auto relative h-full"
            style="width: 400px; background-color: #ffffff; background-color: var(--tw-bg-opacity);">
            <div class="h-full bg-white dark:bg-gray-800 shadow-xl p-6 overflow-y-auto">
                <button @click="open = false"
                    class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    &times;
                </button>

                <div class="space-y-4">
                    @forelse ($activities as $activity)
                        <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <strong>Activity:</strong>
                                <div class="mt-1 px-2">
                                    {{ $activity['activity'] }}
                                </div>
                            </div>
                            @if (!empty($activity['description']))
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                    <strong>Description:</strong>
                                    <div class="mt-1 px-2">
                                        @foreach (explode("\n\n", trim($activity['description'])) as $paragraph)
                                            <div class="mb-2">
                                                @foreach (explode("\n", $paragraph) as $line)
                                                    <div>{{ $line }}</div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                <strong>Date:</strong>
                                <div class="mt-1 px-2">
                                    {{ $activity['created_at'] }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                No activity logs found.
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

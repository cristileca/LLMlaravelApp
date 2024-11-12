<div class="rounded-lg p-4 shadow-md bg-white dark:bg-gray-900 transition-all duration-300">
    @if (!empty($data))
        <div class="max-w-6xl shadow-md rounded-lg">
            <div class="mb-6 inline-block p-2 rounded-lg border bg-white dark:bg-gray-900 shadow-sm"
                style="border-color: {{ $statusColor }}; border-width: 2px; border-style: solid;">
                <h2 class="text-xl font-semibold" style="color: {{ $statusColor }};">
                    Status: {{ $statusName }}
                </h2>
            </div>
            <div class="pt-2">
                <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 px-2" style="color: #685bcd;">
                    Motiv:
                </h2>
                @if (!empty($data['motiv']))
                    <p class="px-6 pt-3">{{ $data['motiv']}}</p>
                @else
                    <p class="px-6 pt-3">Nu sunt observatii semnalate.</p>
                @endif
            </div>

            <div class="pt-2">
                <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 px-2" style="color: #685bcd;">
                    Observații:
                </h2>
                @if (!empty($data['observatii']))
                    <p class="px-6 pt-3">{{ $data['observatii']}}</p>
                @else
                    <p class="px-6 pt-3">Nu sunt observatii semnalate.</p>
                @endif
            </div>
        </div>
    @else
        <div class="mb-6">
            <h2 class="text-l font-semibold text-gray-700 dark:text-gray-200">
                Analiza nu a fost inca efectuata
            </h2>
        </div>
    @endif
</div>

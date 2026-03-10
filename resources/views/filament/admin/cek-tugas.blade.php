<div class="space-y-4">

    <div class="flex items-center justify-between mb-3">

    <div class="text-sm text-gray-300">
        Total Peserta: {{ $attendances->count() }} |
        Sudah Submit: {{ $attendances->whereNotNull('final_project_link')->count() }}
    </div>
{{--
    <div class="relative w-64">

        <!-- Input Search -->
        <input
            type="text"
            wire:model.live="search"
            placeholder="Cari"
            class="w-full pl-9 pr-3 py-2 text-sm rounded-lg !bg-gray-800 border border-gray-700 text-gray-200"
        >

    </div> --}}

</div>

    {{-- TABEL --}}
    <div class="overflow-x-auto max-h-96 overflow-y-auto">
        <table class="w-full text-sm text-left border border-gray-700 rounded-lg">

            <thead class="bg-gray-800 text-gray-200 sticky top-0">
                <tr>
                    <th class="px-4 py-3 border border-gray-700">No</th>
                    <th class="px-4 py-3 border border-gray-700">Nama User</th>
                    <th class="px-4 py-3 border border-gray-700">Status</th>
                    <th class="px-4 py-3 border border-gray-700">Link</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($attendances as $attendance)

                    <tr class="hover:bg-gray-800">

                        <td class="px-4 py-3 border border-gray-700">
                            {{ $loop->iteration }}
                        </td>

                        <td class="px-4 py-3 border border-gray-700">
                            {{ $attendance->user->name }}
                        </td>

                        {{-- STATUS --}}
                        <td class="px-4 py-3 border border-gray-700">
                            @if ($attendance->final_project_link)
                                <span class="text-success-500 font-semibold">
                                    ✅ Submit
                                </span>
                            @else
                                <span class="text-danger-500 font-semibold">
                                    ❌ Belum Submit
                                </span>
                            @endif
                        </td>

                        {{-- LINK --}}
                        <td class="px-4 py-3 border border-gray-700">
                            @if ($attendance->final_project_link)
                                <a href="{{ $attendance->final_project_link }}"
                                   target="_blank"
                                   class="text-primary-500 underline break-all">
                                    {{ $attendance->final_project_link }}
                                </a>
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="4" class="text-center py-4 text-gray-400">
                            Data tidak ditemukan
                        </td>
                    </tr>

                @endforelse
            </tbody>

        </table>
    </div>

</div>

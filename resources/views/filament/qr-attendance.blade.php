
<div class="flex flex-col items-center gap-4 p-6">

    {{-- QR Preview --}}
    {!! QrCode::size(250)->generate($token) !!}

    <p class="text-sm text-gray-500 text-center">
        Scan QR ini untuk melakukan absensi kehadiran event
    </p>


    {{-- Tombol Download PNG --}}
    <a
        href="{{ route('event.qr.download', $event->id) }}"
        class="inline-flex items-center gap-2 px-4 py-2
               text-sm font-semibold text-white
               bg-primary-600 rounded-lg
               hover:bg-primary-500 transition" >

        {{-- Icon download --}}
        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-4 h-4"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor">

            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2
                     M7 10l5 5m0 0l5-5m-5 5V4"/>
        </svg>

        Download QR

    </a>

</div>

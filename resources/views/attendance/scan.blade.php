<div
    wire:ignore

    x-data="{

        scanner: null,

        success: false,

        start() {

            setTimeout(() => {

                const audio = new Audio('https://actions.google.com/sounds/v1/cartoon/wood_plank_flicks.ogg')

                this.scanner = new Html5Qrcode('qr-reader')

                this.scanner.start(

                    { facingMode: 'environment' },

                    {

                        fps: 10,

                        qrbox: { width: 250, height: 250 },

                        aspectRatio: 1.0,

                        disableFlip: false

                    },

                    (decodedText) => {

                        console.log('QR RESULT:', decodedText)


                        fetch('{{ route('attendance.scan') }}', {

                            method: 'POST',

                            headers: {

                                'Content-Type': 'application/json',

                                'X-CSRF-TOKEN': '{{ csrf_token() }}'

                            },

                            body: JSON.stringify({

                                token: decodedText

                            })

                        })

                        .then(res => res.json())

                        .then(data => {

                            if(data.message.toLowerCase().includes('berhasil')) {

                                this.success = true

                                audio.play()

                                navigator.vibrate?.(200)

                            }


                            window.livewire.emit('notify', data.message)
                            {{-- alert(data.message) --}}

                            this.scanner.stop()


                            setTimeout(() => {

                                document.querySelector('.fi-modal-close-btn')?.click()

                                location.reload()

                            }, 1500)

                        })

                    },

                    (errorMessage) => {

                        // optional debug

                        // console.log(errorMessage)

                    }

                )

            }, 500)

        }

    }"

    x-init="start()"

    class="flex flex-col items-center gap-3"
>

    <div id="qr-reader" style="width:100%; max-width:400px;"></div>


    <div

        x-show="success"

        x-transition

        class="text-green-600 font-bold text-lg"

    >

        ✅ Presensi berhasil

    </div>

</div>

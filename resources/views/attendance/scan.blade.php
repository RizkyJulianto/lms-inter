<div wire:ignore x-data="{

    scanner: null,
    success: false,
    processing: false,

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

                async (decodedText) => {

                    if (this.processing) return

                    this.processing = true

                    console.log('QR RESULT:', decodedText)

                    try {

                        const res = await fetch('{{ route('attendance.scan') }}', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                token: decodedText
                            })
                        })

                        const data = await res.json()

                        const message = data.message.toLowerCase()

                        if (message.includes('berhasil')) {

                            this.success = true

                            audio.play()
                            navigator.vibrate?.(200)

                        } else {

                            this.success = false
                            navigator.vibrate?.(300)

                        }

                        alert(data.message)

                        await this.scanner.stop()

                        setTimeout(() => {
                            document.querySelector('.fi-modal-close-btn')?.click()
                            location.reload()
                        }, 1500)

                    } catch (e) {

                        console.error(e)
                        this.processing = false

                    }

                },

                (errorMessage) => {
                    // optional debug
                    // console.log(errorMessage)
                }

            )

        }, 500) 

    }

}" x-init="start()" class="flex flex-col items-center gap-3">

    <div id="qr-reader" style="width:100%; max-width:400px;"></div>

    <div x-show="success" x-transition class="text-green-600 font-bold text-lg">
        ✅ Presensi berhasil
    </div>

</div>
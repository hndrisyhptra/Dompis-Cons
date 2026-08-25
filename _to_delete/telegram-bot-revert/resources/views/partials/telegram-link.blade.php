{{-- Kartu "Hubungkan Telegram" -- di-include di halaman Profil (admin/pm/sdi, waspang, teknisi).
     Menampilkan status koneksi Telegram user yang sedang login, dan tombol untuk
     menghasilkan kode/deep-link penghubung (lihat TelegramLinkController). --}}
@php($tgUser = auth()->user())
<div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm" id="telegram-link-card">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center shrink-0">
            <i class="fa-brands fa-telegram text-lg"></i>
        </div>
        <div>
            <p class="font-bold text-sm text-gray-800">Notifikasi Telegram</p>
            <p class="text-xs text-gray-500">Terima pengingat project &amp; eviden langsung di Telegram</p>
        </div>
    </div>

    <input type="hidden" id="telegram-csrf-token" value="{{ csrf_token() }}">

    {{-- STATE: sudah terhubung --}}
    <div id="telegram-linked-state" class="{{ $tgUser->telegram_chat_id ? '' : 'hidden' }}">
        <p class="text-xs text-green-600 font-bold mb-3 flex items-center gap-1">
            <i class="fa-solid fa-circle-check"></i>
            <span>
                Telegram sudah terhubung
                @if($tgUser->telegram_linked_at)
                    sejak {{ $tgUser->telegram_linked_at->translatedFormat('d M Y, H:i') }}
                @endif
            </span>
        </p>
        <form method="POST" action="{{ route('telegram.unlink') }}" onsubmit="return confirm('Putuskan koneksi Telegram dari akun ini?')">
            @csrf
            <button type="submit" class="w-full h-10 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-xs font-bold transition">
                Putuskan Koneksi
            </button>
        </form>
    </div>

    {{-- STATE: belum terhubung --}}
    <div id="telegram-unlinked-state" class="{{ $tgUser->telegram_chat_id ? 'hidden' : '' }}">
        <button type="button" onclick="generateTelegramLink()" id="telegram-generate-btn"
                class="w-full h-10 rounded-xl bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold transition">
            Hubungkan Telegram
        </button>

        <div id="telegram-link-result" class="hidden mt-3 text-xs bg-sky-50 border border-sky-100 rounded-xl p-3 space-y-2">
            <p class="text-gray-600">Buka tautan berikut lewat HP untuk menghubungkan Telegram Anda:</p>
            <a id="telegram-deep-link" href="#" target="_blank" rel="noopener"
               class="hidden block w-full text-center h-9 leading-9 rounded-lg bg-sky-600 text-white font-bold">
                Buka di Telegram
            </a>
            <p id="telegram-code-fallback" class="hidden text-gray-500">
                Bot Telegram belum aktif. Simpan kode ini: <span class="font-mono font-bold text-gray-800" id="telegram-code-text"></span>
                — kode akan diminta saat integrasi bot sudah aktif.
            </p>
        </div>
    </div>
</div>

<script>
function generateTelegramLink() {
    const btn = document.getElementById('telegram-generate-btn');
    const token = document.getElementById('telegram-csrf-token').value;
    btn.disabled = true;
    btn.textContent = 'Memproses...';

    fetch('{{ route('telegram.link.generate') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = 'Hubungkan Telegram';

        const resultBox = document.getElementById('telegram-link-result');
        const deepLinkEl = document.getElementById('telegram-deep-link');
        const codeFallbackEl = document.getElementById('telegram-code-fallback');
        const codeTextEl = document.getElementById('telegram-code-text');

        resultBox.classList.remove('hidden');

        if (data.deep_link) {
            deepLinkEl.href = data.deep_link;
            deepLinkEl.classList.remove('hidden');
            codeFallbackEl.classList.add('hidden');
        } else {
            codeTextEl.textContent = data.code;
            codeFallbackEl.classList.remove('hidden');
            deepLinkEl.classList.add('hidden');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Hubungkan Telegram';
        alert('Gagal membuat kode. Coba lagi.');
    });
}
</script>

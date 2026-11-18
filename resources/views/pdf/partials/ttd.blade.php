{{-- Blok tanda tangan + QR verifikasi. QR selalu disertakan agar surat dapat
     diperiksa lewat /verify/{token} (BR-F12-2). --}}
<table class="ttd">
    <tr>
        <td class="ttd-qr">
            <img src="{{ $verifikasi['qr'] }}" alt="QR verifikasi surat">
            <div class="qr-caption">Pindai untuk verifikasi</div>
        </td>
        <td class="ttd-sign">
            <div>{{ $kop['kota'] }}, {{ $tanggal }}</div>
            <div>{{ $ttd['jabatan'] }}</div>
            <div class="ttd-space">
                @if ($ttd['image'] !== null)
                    <img src="{{ $ttd['image'] }}" alt="Tanda tangan Kepala Sekolah">
                @endif
            </div>
            <div class="ttd-nama">{{ $ttd['nama'] ?? '' }}</div>
            @if ($ttd['nip'] !== null)
                <div>NIP. {{ $ttd['nip'] }}</div>
            @endif
        </td>
    </tr>
</table>
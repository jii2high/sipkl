{{-- Kop surat resmi. Logo sekolah belum tersedia sebagai aset, jadi kop hanya
     memakai blok teks seperti contoh fisik (dicatat di TASKS.md). --}}
<table class="kop">
    <tr>
        <td>
            <div class="kop-baris">{{ $kop['baris1'] }}</div>
            <div class="kop-baris">{{ $kop['baris2'] }}</div>
            <div class="kop-baris">{{ $kop['baris3'] }}</div>
            <div class="kop-nama">{{ $kop['nama'] }}</div>
            <div class="kop-alamat">{{ $kop['alamat'] }}</div>
            <div class="kop-kontak">{{ $kop['kontak'] }}</div>
        </td>
    </tr>
</table>
<div class="kop-garis"></div>
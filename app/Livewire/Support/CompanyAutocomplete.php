<?php

namespace App\Livewire\Support;

use App\Models\Company;
use App\Support\MaskedContact;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Autocomplete perusahaan (TRD.md §14.3, T2.1). Mencari perusahaan berdasarkan nama
 * atau alamat dan mengembalikan daftar kandidat; pemilihan mengirim event
 * `company-selected` agar form pemanggil dapat memuat perusahaan itu. Kontak
 * narahubung ditampilkan ter-mask sampai pengajuan disetujui Kaprog (PRD F3).
 */
class CompanyAutocomplete extends Component
{
    public string $search = '';

    #[Locked]
    public ?int $selectedId = null;

    public string $selectedName = '';

    public function select(int $id): void
    {
        $company = Company::query()->whereNull('merged_into_id')->find($id);

        if ($company === null) {
            return;
        }

        $this->selectedId = (int) $company->getKey();
        $this->selectedName = (string) $company->nama;
        $this->search = (string) $company->nama;

        $this->dispatch('company-selected', id: $this->selectedId, nama: $this->selectedName);
    }

    /**
     * @return array<int, array{id: int, nama: string, alamat: string, telepon: ?string}>
     */
    public function results(): array
    {
        $term = trim($this->search);

        if (mb_strlen($term) < 2) {
            return [];
        }

        return Company::query()
            ->whereNull('merged_into_id')
            ->where(function ($query) use ($term): void {
                $query->where('nama', 'like', "%{$term}%")
                    ->orWhere('alamat', 'like', "%{$term}%");
            })
            ->orderBy('nama')
            ->limit(10)
            ->get(['id', 'nama', 'alamat', 'cp_phone'])
            ->map(fn (Company $company): array => [
                'id' => (int) $company->getKey(),
                'nama' => (string) $company->nama,
                'alamat' => (string) $company->alamat,
                'telepon' => MaskedContact::phone($company->cp_phone),
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.support.company-autocomplete', [
            'companies' => $this->results(),
        ]);
    }
}

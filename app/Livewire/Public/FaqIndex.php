<?php

namespace App\Livewire\Public;

use App\Models\Faq;
use App\Models\FaqCategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman publik `/faq` (BR-F16-1..4). Hanya FAQ `is_published`, dengan filter
 * kategori, pencarian LIKE pada pertanyaan/jawaban, dan paginasi 7-10 item.
 */
#[Layout('components.layouts.public')]
#[Title('Pertanyaan umum')]
class FaqIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->search = '';
        $this->category = '';
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $faqs = Faq::query()
            ->join('faq_categories', 'faq_categories.id', '=', 'faqs.faq_category_id')
            ->select('faqs.*')
            ->with('category')
            ->where('is_published', true)
            ->when($this->category !== '', fn ($query) => $query->where('faq_category_id', (int) $this->category))
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('pertanyaan', 'like', '%'.$search.'%')
                    ->orWhere('jawaban', 'like', '%'.$search.'%'),
            ))
            ->orderBy('faq_categories.sort_order')
            ->orderBy('faqs.sort_order')
            ->orderBy('faqs.id')
            ->paginate((int) config('pkl.faq.per_page'));

        return view('livewire.public.faq-index', [
            'faqs' => $faqs,
            'categories' => FaqCategory::query()->orderBy('sort_order')->orderBy('nama')->get(),
            'adaFilter' => $search !== '' || $this->category !== '',
        ]);
    }
}

<?php

use App\Filament\Hubin\Resources\FaqCategories\Pages\ManageFaqCategories;
use App\Filament\Hubin\Resources\Faqs\Pages\ManageFaqs;
use App\Livewire\Public\FaqIndex;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

function faqAdmin(): User
{
    return pklUser(['hubin']);
}

function faqItem(array $attributes = []): Faq
{
    return Faq::factory()->create([
        'created_by' => faqAdmin()->getKey(),
        ...$attributes,
    ]);
}

test('faq publik hanya menampilkan yang terbit', function () {
    $terbit = faqItem(['pertanyaan' => 'Bagaimana cara mengunggah surat balasan?']);
    $draf = faqItem(['pertanyaan' => 'Pertanyaan yang belum terbit', 'is_published' => false]);

    $this->get('/faq')
        ->assertOk()
        ->assertSee($terbit->pertanyaan)
        ->assertDontSee($draf->pertanyaan)
        ->assertSee(__('pkl.faq.hasil', ['total' => 1]));
});

test('filter kategori dan pencarian like menyaring daftar faq', function () {
    $pengajuan = FaqCategory::factory()->create(['nama' => 'Pengajuan']);
    $akun = FaqCategory::factory()->create(['nama' => 'Akun']);

    $cara = faqItem([
        'faq_category_id' => $pengajuan->id,
        'pertanyaan' => 'Bagaimana cara mengajukan tempat PKL?',
        'jawaban' => 'Kirim pengajuan lewat menu Pengajuan Saya.',
    ]);

    $sandi = faqItem([
        'faq_category_id' => $akun->id,
        'pertanyaan' => 'Bagaimana cara mengganti kata sandi?',
        'jawaban' => 'Gunakan menu ganti kata sandi saat login pertama.',
    ]);

    Livewire\Livewire::test(FaqIndex::class)
        ->assertSee($cara->pertanyaan)
        ->assertSee($sandi->pertanyaan)
        ->set('category', (string) $pengajuan->getKey())
        ->assertSee($cara->pertanyaan)
        ->assertDontSee($sandi->pertanyaan)
        ->set('search', 'kata sandi')
        ->assertSee(__('pkl.faq.kosong_filter'))
        ->set('search', '')
        ->assertSee($cara->pertanyaan);
});

test('pencarian mencocokkan isi jawaban dan menampilkan pesan kosong', function () {
    $faq = faqItem([
        'pertanyaan' => 'Apa syarat PKL?',
        'jawaban' => 'Balasan perusahaan harus diunggah sebelum PKL dimulai.',
    ]);

    Livewire\Livewire::test(FaqIndex::class)
        ->set('search', 'balasan perusahaan')
        ->assertSee($faq->pertanyaan)
        ->set('search', 'tidak ada yang cocok')
        ->assertSee(__('pkl.faq.kosong_filter'))
        ->assertDontSee($faq->pertanyaan)
        ->call('resetFilter')
        ->assertSee($faq->pertanyaan);
});

test('faq publik dipaginasi sesuai config', function () {
    foreach (range(1, 12) as $index) {
        faqItem(['pertanyaan' => 'Pertanyaan nomor '.$index]);
    }

    $perPage = (int) config('pkl.faq.per_page');

    Livewire\Livewire::test(FaqIndex::class)
        ->assertViewHas('faqs', fn ($faqs): bool => $faqs->count() === $perPage && $faqs->total() === 12)
        ->call('setPage', 2)
        ->assertViewHas('faqs', fn ($faqs): bool => $faqs->count() === 12 - $perPage);
});

test('landing menautkan faq dan halaman login', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(__('pkl.home.judul'))
        ->assertSee('href="'.route('faq').'"', false)
        ->assertSee('href="'.route('login').'"', false);
});

test('hubin dapat membuka halaman faq dan kategori', function () {
    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    $this->actingAs(faqAdmin())->withSession(['active_role' => 'hubin']);

    $this->get('/hubin/faqs')->assertOk();
    $this->get('/hubin/faq-categories')->assertOk();
});

test('hubin dapat membuat faq dan pencatatnya terisi', function () {
    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    $admin = faqAdmin();
    $this->actingAs($admin)->withSession(['active_role' => 'hubin']);

    $category = FaqCategory::factory()->create(['nama' => 'Pengajuan']);

    Livewire\Livewire::test(ManageFaqs::class)
        ->callAction('create', data: [
            'faq_category_id' => $category->getKey(),
            'pertanyaan' => 'Kapan pengajuan dibuka?',
            'jawaban' => 'Pengajuan dibuka saat periode aktif.',
            'sort_order' => 1,
            'is_published' => true,
        ])
        ->assertHasNoFormErrors();

    $faq = Faq::query()->where('pertanyaan', 'Kapan pengajuan dibuka?')->first();

    expect($faq)->not->toBeNull()
        ->and($faq->created_by)->toBe($admin->getKey());
});

test('kategori faq yang masih dipakai tidak dapat dihapus', function () {
    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    $this->actingAs(faqAdmin())->withSession(['active_role' => 'hubin']);

    $category = FaqCategory::factory()->create();
    faqItem(['faq_category_id' => $category->getKey()]);

    Livewire\Livewire::test(ManageFaqCategories::class)
        ->assertActionHidden(TestAction::make('delete')->table($category));
});

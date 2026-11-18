<?php

namespace App\Actions\Company;

use App\Mail\SignerVerificationMail;
use App\Models\Company;
use App\Models\User;
use App\Rules\SignerNameIndividual;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Kirim tautan verifikasi email signer industri (TASKS M2.8, AC-52).
 *
 * Tautan berupa signed URL berbatas waktu dan memuat hash email signer saat
 * dikirim. Mengubah email/nama signer otomatis mengosongkan status verifikasi
 * (lihat event `updating` di model Company), sehingga tautan lama tidak berlaku.
 */
final class SendSignerVerificationAction
{
    public const TIPE_PEMBIMBING = 'pembimbing';

    public const TIPE_KEPALA = 'kepala';

    public const TIPE = [self::TIPE_PEMBIMBING, self::TIPE_KEPALA];

    public function execute(Company $company, string $tipe, ?User $oleh = null): void
    {
        if (! in_array($tipe, self::TIPE, true)) {
            throw new InvalidArgumentException("Tipe signer [{$tipe}] tidak dikenal.");
        }

        /** @var Company $company */
        $company = Company::query()->findOrFail($company->getKey());

        [$nama, $email] = $this->dataSigner($company, $tipe);

        Validator::make(
            ['nama' => $nama, 'email' => $email],
            [
                'nama' => ['required', 'string', 'max:150', new SignerNameIndividual],
                'email' => ['required', 'email', 'max:150'],
            ],
            [
                'nama.required' => 'Nama signer belum diisi.',
                'email.required' => 'Email signer belum diisi.',
            ],
        )->validate();

        $url = URL::temporarySignedRoute(
            'signer.verifikasi',
            now()->addDays((int) config('company.signer_link_ttl_days', 3)),
            [
                'company' => $company->getKey(),
                'tipe' => $tipe,
                'email' => hash('sha256', self::kunciEmail($email)),
            ],
        );

        Mail::to($email)->send(new SignerVerificationMail($company, $tipe, $nama, $url));

        activity('company')
            ->performedOn($company)
            ->causedBy($oleh)
            ->withProperties([
                'aksi' => 'signer_verification_sent',
                'tipe' => $tipe,
            ])
            ->log('company.signer_verification_sent');
    }

    /**
     * @return array{0: string, 1: string}
     *
     * @throws ValidationException
     */
    private function dataSigner(Company $company, string $tipe): array
    {
        $nama = $tipe === self::TIPE_PEMBIMBING
            ? (string) $company->pembimbing_industri_nama
            : (string) $company->kepala_industri_nama;

        $email = $tipe === self::TIPE_PEMBIMBING
            ? (string) $company->pembimbing_industri_email
            : (string) $company->kepala_industri_email;

        if (trim($nama) === '' || trim($email) === '') {
            throw ValidationException::withMessages([
                'signer' => 'Lengkapi nama dan email signer sebelum mengirim verifikasi.',
            ]);
        }

        return [trim($nama), trim($email)];
    }

    public static function kunciEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}

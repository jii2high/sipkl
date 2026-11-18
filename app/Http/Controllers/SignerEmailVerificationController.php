<?php

namespace App\Http\Controllers;

use App\Actions\Company\SendSignerVerificationAction;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Terima klik tautan verifikasi signer industri (TASKS M2.8).
 *
 * Signature diverifikasi middleware `signed`; selain itu hash email di query
 * harus sama dengan email signer saat ini, sehingga tautan lama otomatis gugur
 * bila Hubin mengganti email signer.
 */
final class SignerEmailVerificationController
{
    public function __invoke(Request $request, Company $company, string $tipe): View
    {
        abort_unless(in_array($tipe, SendSignerVerificationAction::TIPE, true), 404);

        $email = $tipe === SendSignerVerificationAction::TIPE_PEMBIMBING
            ? $company->pembimbing_industri_email
            : $company->kepala_industri_email;

        $nama = $tipe === SendSignerVerificationAction::TIPE_PEMBIMBING
            ? $company->pembimbing_industri_nama
            : $company->kepala_industri_nama;

        $hashSekarang = hash('sha256', SendSignerVerificationAction::kunciEmail((string) $email));

        if ($email === null || ! hash_equals($hashSekarang, (string) $request->query('email'))) {
            abort(410, 'Tautan verifikasi sudah tidak berlaku. Minta Hubin mengirim ulang verifikasi.');
        }

        if (blank($nama)) {
            abort(422, 'Nama signer sudah dihapus. Minta Hubin melengkapi data signer.');
        }

        $kolom = $tipe === SendSignerVerificationAction::TIPE_PEMBIMBING
            ? 'pembimbing_industri_email_verified_at'
            : 'kepala_industri_email_verified_at';

        if ($company->{$kolom} === null) {
            $company->forceFill([$kolom => now()])->save();

            activity('company')
                ->performedOn($company)
                ->withProperties([
                    'aksi' => 'signer_email_verified',
                    'tipe' => $tipe,
                ])
                ->log('company.signer_email_verified');
        }

        return view('public.signer-verified', [
            'nama' => $nama,
            'tipe' => $tipe,
            'perusahaan' => $company->nama,
        ]);
    }
}

<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Penerbitan surat ditahan karena `signature_assets` tidak punya scan TTD Kepsek
 * aktif (BR-F12-2, TRD.md §11). Data dokumen tetap tersimpan, Hubin diberi tahu,
 * dan penerbitan dapat diulang setelah scan tersedia.
 */
class DocumentIssuanceHeld extends RuntimeException
{
    public static function noSignature(): self
    {
        return new self(__('pkl.document.held_no_signature'));
    }

    public static function noVerifiedSigner(): self
    {
        return new self(__('pkl.document.held_no_verified_signer'));
    }
}

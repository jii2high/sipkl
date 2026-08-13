<?php

namespace App\Models;

use Database\Factories\PasswordHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property string $password
 * @property Carbon|null $created_at
 */
class PasswordHistory extends Model
{
    /** @use HasFactory<PasswordHistoryFactory> */
    use HasFactory;

    public const HISTORY_LIMIT = 3;

    public $timestamps = false;

    protected $fillable = ['user_id', 'password', 'created_at'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(User $user, string $password): void
    {
        static::create([
            'user_id' => $user->id,
            'password' => $password,
            'created_at' => now(),
        ]);

        // ponytail: hapus riwayat terluas jika melebihi limit
        static::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->skip(static::HISTORY_LIMIT)
            ->take(PHP_INT_MAX)
            ->get()
            ->each->delete();
    }

    public static function isReused(User $user, string $password): bool
    {
        return static::where('user_id', $user->id)
            ->get()
            ->contains(fn (self $history) => password_verify($password, $history->password));
    }
}

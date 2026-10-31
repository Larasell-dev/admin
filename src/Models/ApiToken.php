<?php

namespace Larasell\Admin\Models;

use Carbon\CarbonInterface;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A credential an admin issued for the API: what $request->user() returns on routes behind
 * the larasell.api middleware.
 *
 * Extensions may rely on getAuthIdentifier(), e.g. to rate limit per token, and on the name
 * and description. Everything else is internal and may change, including how the secret is
 * stored: today as a SHA-256 hash on this row.
 *
 * @property int $id
 * @property string $name
 * @property ?string $description
 * @property ?int $created_by
 * @property string $token_hash
 * @property CarbonInterface $expires_at
 * @property ?CarbonInterface $last_used_at
 * @property-read ?Admin $createdBy
 */
class ApiToken extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'larasell_api_tokens';

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'token_hash',
        'expires_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @internal
     *
     * @return BelongsTo<Admin, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * @internal
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * API tokens have no password or remember token.
     */
    public function getRememberTokenName(): string
    {
        return '';
    }
}

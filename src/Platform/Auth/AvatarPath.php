<?php

namespace Larasell\Admin\Platform\Auth;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Larasell\Admin\Platform\Auth\Exceptions\InvalidAvatarPath;

/**
 * Where an admin's avatar is on the panel disk: a file directly in avatars/, the folder uploads
 * are stored in. The panel deletes avatars by this path, so it never points anywhere else; an
 * admin's avatar_path is cast to it, and a stored path outside avatars/ throws when read.
 */
final readonly class AvatarPath implements Castable
{
    public const DIRECTORY = 'avatars';

    private function __construct(public string $path) {}

    /**
     * @throws InvalidAvatarPath
     */
    public static function fromString(string $path): self
    {
        if (preg_match('#^'.self::DIRECTORY.'/[A-Za-z0-9][A-Za-z0-9._-]*$#', $path) !== 1) {
            throw InvalidAvatarPath::outsideAvatars($path);
        }

        return new self($path);
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     * @return CastsAttributes<self, self|string>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            public function get(Model $model, string $key, mixed $value, array $attributes): ?AvatarPath
            {
                return is_string($value) ? AvatarPath::fromString($value) : null;
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): ?string
            {
                return match (true) {
                    $value instanceof AvatarPath => $value->path,
                    is_string($value) => AvatarPath::fromString($value)->path,
                    default => null,
                };
            }
        };
    }
}

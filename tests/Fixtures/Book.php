<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A model that exists only in tests, so table tests don't depend on the package's own models.
 */
class Book extends Model
{
    protected $guarded = [];

    /**
     * @return HasMany<Book, $this>
     */
    public function booksBySameAuthor(): HasMany
    {
        return $this->hasMany(Book::class, 'author_name', 'author_name');
    }

    public static function createTable(): void
    {
        Schema::create('books', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('author_name');
            $table->timestamps();
        });
    }
}

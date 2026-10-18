<?php

namespace Larasell\Admin\Data\ViewModel;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

/**
 * Keeps Laravel's flat paginator shape (`data`, `current_page`, …). Subclasses
 * redeclare `$data` with a typed `@var` so the generated TypeScript knows the item type.
 */
abstract class PaginatedData extends Data
{
    /** @var array<int, mixed> */
    public array $data;

    public int $current_page;

    public int $per_page;

    public int $total;

    final public function __construct() {}

    /**
     * @template TItem of Data
     *
     * @param  LengthAwarePaginator<int, TItem>  $paginator
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): static
    {
        $page = new static;
        $page->data = $paginator->items();
        $page->current_page = $paginator->currentPage();
        $page->per_page = $paginator->perPage();
        $page->total = $paginator->total();

        return $page;
    }
}

<?php

namespace Workbench\App\ProductNotes;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Product\Events\ProductSaved;

/** Saves the note with the product, or removes it when it was emptied. */
class SaveProductNote
{
    public function handle(ProductSaved $event): void
    {
        $payload = $event->section(ProductNotesRequest::class)->payload();

        if ($payload === null) {
            return;
        }

        $notes = DB::table('workbench_product_notes')->where('product_id', $event->product->id);

        $payload->note === null
            ? $notes->delete()
            : DB::table('workbench_product_notes')->updateOrInsert(['product_id' => $event->product->id], ['note' => $payload->note, 'updated_at' => now()]);
    }
}

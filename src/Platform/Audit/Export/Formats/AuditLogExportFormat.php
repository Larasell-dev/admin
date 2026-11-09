<?php

namespace Larasell\Admin\Platform\Audit\Export\Formats;

/**
 * How an audit log export writes its records, e.g. as JSON. The export zips the file it writes.
 *
 * The panel binds JsonFormat. An app swaps the format by binding its own in a service
 * provider: $this->app->bind(AuditLogExportFormat::class, CsvFormat::class).
 *
 * @public Implement it for another file format.
 */
interface AuditLogExportFormat
{
    /**
     * The extension of the file in the zip archive, e.g. json.
     */
    public function extension(): string;

    /**
     * The media type of the file in the zip archive, e.g. application/json.
     */
    public function contentType(): string;

    /**
     * Writes what comes before the first record, e.g. a header. Called once per export.
     *
     * @param  resource  $stream
     */
    public function writeStart($stream): void;

    /**
     * Writes one record, called in the chain's order. Its columns are as the chain's spec reads
     * them, with previous_hash and hash last; properties is an object, so {} stays an object.
     *
     * @param  resource  $stream
     * @param  array<string, mixed>  $record
     */
    public function writeRecord($stream, array $record): void;

    /**
     * Writes the summary after the last record: exported_at, records, first_seq,
     * last_seq, head and verification.
     *
     * @param  resource  $stream
     * @param  array<string, mixed>  $summary
     */
    public function writeSummary($stream, array $summary): void;
}

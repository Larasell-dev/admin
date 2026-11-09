<?php

namespace Larasell\Admin\Platform\Audit\Export\Formats;

/**
 * One JSON document, {"records": [...], "summary": {...}}, written a record at a time so a large
 * log never sits in memory. Records keep their columns as the chain's spec reads them, so the
 * export can be verified without the app.
 *
 * @public The default AuditLogExportFormat.
 */
class JsonFormat implements AuditLogExportFormat
{
    private const int JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /** Whether the next record is the first, so it needs no comma before it. */
    private bool $first = true;

    public function extension(): string
    {
        return 'json';
    }

    public function contentType(): string
    {
        return 'application/json';
    }

    public function writeStart($stream): void
    {
        $this->first = true;
        fwrite($stream, '{"records":[');
    }

    public function writeRecord($stream, array $record): void
    {
        fwrite($stream, ($this->first ? "\n" : ",\n").json_encode($record, self::JSON_FLAGS));
        $this->first = false;
    }

    public function writeSummary($stream, array $summary): void
    {
        fwrite($stream, "\n],\n\"summary\":".json_encode($summary, self::JSON_FLAGS)."}\n");
    }
}

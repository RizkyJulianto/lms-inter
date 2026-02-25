<?php

namespace App\Filament\Exports;

use App\Models\Attendance;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EventAttendancesExporter extends Exporter
{
    protected static ?string $model = Attendance::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Nama'),
            ExportColumn::make('username')->label('Username'),
            ExportColumn::make('submission_score')->label('nilai tes'),
            ExportColumn::make('participation_score')->label('nilai keaktifan'),
            ExportColumn::make('user_id')->label('DEBUG USER ID'),
            ExportColumn::make('username')->label('DEBUG USERNAME'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your event attendances export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}

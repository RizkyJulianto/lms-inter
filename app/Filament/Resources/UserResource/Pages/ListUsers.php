<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Exports\UsersPointExport;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Konnco\FilamentImport\Actions\ImportAction;
use Konnco\FilamentImport\Actions\ImportField;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make('import user')
                ->handleBlankRows(true)
                ->fields([
                    ImportField::make('division_id')
                        ->required(),
                    ImportField::make('name')
                        ->required()
                        ->rules(['required', 'max:255']),
                    ImportField::make('email')
                        ->required()
                        ->rules(['email', 'max:255']),
                    ImportField::make('password')
                        ->required()
                        ->rules(['required', 'max:255']),
                    ImportField::make('username')
                        ->required()
                        ->rules(['required', 'max:255']),
                ])
                ->handleRecordCreation(function ($data) {
                    $user = User::create($data);
                    $user->assignRole('Member');
                    return $user;
                }),
            Actions\Action::make('export')
                ->label('Export')
                ->action(fn() => Excel::download(
                    new UsersPointExport,
                    'users-point.xlsx'
                )),
        ];
    }
}

<?php

namespace App\Filament\Resources\EventResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Exports\EventAttendancesExporter;
use App\Http\Controllers\CertificateController;
use App\Models\Event;
use App\Models\User;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = "Daftar Hadir";

    public static function shouldSkipAuthorization(): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('User')
                ->relationship('users', 'name')
                ->required()
                ->hiddenOn('edit'),
            Forms\Components\Checkbox::make('is_competence')
                ->visible(fn () => $this->getOwnerRecord()->has_final_project),
            Forms\Components\TextInput::make('final_project_link')
                ->label('Final Project Link')
                ->visible(fn () => $this->getOwnerRecord()->has_final_project),
            Forms\Components\TextInput::make('submission_score')
                ->visible(fn () => $this->getOwnerRecord()->has_final_project),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('User Name'),
                Tables\Columns\TextColumn::make('username'),
                Tables\Columns\IconColumn::make('pivot.is_competence')
                    ->label('Kompeten')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('pivot.final_project_link')
                ->label('Final Project')
                ->url(fn($record) => $record->pivot->final_project_link)
                ->openUrlInNewTab()
                ->icon('heroicon-m-arrow-top-right-on-square'),
                Tables\Columns\TextColumn::make('pivot.submission_score')
                    ->label('Nilai'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->after(function (array $data) {
                        $user = User::find($data['recordId']);
                        $record = $this->getOwnerRecord();

                        $event = Event::where('id', $record->id)
                            ->where('quota', '>', 0)
                            ->lockForUpdate() // Lock baris untuk mencegah race condition
                            ->first();

                        if (!$event) {
                            throw new \Exception('Kuota event sudah habis.');
                        }

                        $certificateNumber = (new CertificateController)->generateCertificateNumber($record, $user);

                        $event->event_users()->attach($user->id, [
                            'number_certificate' => $certificateNumber,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $record->quota -= 1;
                        $record->save();
                    }),
                ExportAction::make()
                    ->exporter(EventAttendancesExporter::class)
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('validateFinalProject')
                ->label('Validasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')

                ->visible(function (User $user) {

                    if (! $this->getOwnerRecord()->has_final_project) {
                        return false;
                    }

                    return $user->pivot->final_project_link
                        && ! $user->pivot->validated_at;
                })

                ->action(function (User $user) {

                    $this->getOwnerRecord()
                        ->attendances()
                        ->where('user_id', $user->id)
                        ->update([
                            'validated_at' => now(),
                            'is_competence' => true,
                        ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Final Project berhasil divalidasi')
                        ->success()
                        ->send();
                }),
                Tables\Actions\DetachAction::make(),
            Tables\Actions\Action::make('viewCertificateAction')
                ->label('Preview Sertifikat')
                ->icon('heroicon-o-document-text')

                ->visible(function (User $user) {

                    if (! $this->getOwnerRecord()->has_final_project) {
                        return false;
                    }

                    return $user->pivot->is_competence;
                })

                ->url(fn($record) => route(
                    'certificate.view',
                    $this->getOwnerRecord()->id
                ) . '?user=' . $record->id)
                ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(EventAttendancesExporter::class)
                ]),
            ]);
    }
}

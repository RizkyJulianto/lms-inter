<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Http\Controllers\CertificateController;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\FileUpload;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema([
                    TextInput::make('name')->required()->placeholder('Name')->autofocus(),
                    Textarea::make('description')->required()->placeholder('Description'),
                    FileUpload::make('module')
                        ->label('Modul Event')
                        ->disk('public')
                        ->directory('event-modules')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(51200)
                        ->preserveFilenames(),
                    TextInput::make('point_reward')
                        ->label('Point Reward')
                        ->numeric()
                        ->required()
                        ->maxValue(10)
                        ->helperText('Maksimal 10 poin'),
                    TextInput::make('occasion_date')
                        ->type('datetime-local')
                        ->required()
                        ->placeholder('Occasion Date'),
                    TextInput::make('start_register')
                        ->type('datetime-local')
                        ->required()
                        ->placeholder('Start Register'),
                    TextInput::make('end_register')
                        ->type('datetime-local')
                        ->required()
                        ->placeholder('Start Register'),
                    TextInput::make('quota')
                        ->type('number')
                        ->required()
                        ->placeholder('Quota'),
                ]),
                Section::make()->schema([
                    Select::make('division_id')
                        ->relationship('division', 'name')
                ])->visible(fn() => User::find(auth()->user()->id)->hasRole('Super Admin'))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->label('Nama Event'),
                TextColumn::make('division.name')->searchable()->sortable()->label('Divisi'),
                TextColumn::make('occasion_date')->sortable()->dateTime()->label('Tanggal Acara'),
                TextColumn::make('start_register')->sortable()->dateTime()->label('Mulai Pendaftaran'),
                TextColumn::make('end_register')->sortable()->dateTime()->label('Tutup Pendaftaran'),
                TextColumn::make('quota')->sortable()->label('Kuota'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('qrAttendance')
                    ->label('QR Kehadiran')
                    ->icon('heroicon-o-qr-code')
                    ->color('primary')

                    ->modalWidth('2xl')
                    ->extraModalWindowAttributes([
                        'style' => 'max-height: 90vh; overflow-y: auto;'
                    ])

                    ->visible(function () {

                        if (! auth()->check()) {
                            return false;
                        }

                        return auth()->user()
                            ->roles
                            ->pluck('name')
                            ->intersect(['Admin', 'Super Admin'])
                            ->isNotEmpty();
                    })

                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')

                    ->modalHeading('QR Kehadiran Event')
                    ->modalContent(
                        fn(Event $record) =>
                        view('filament.qr-attendance', [
                            'event' => $record,
                            'token' => $record->attendance_token
                        ])
                    ),
                Tables\Actions\Action::make('scanAttendance')
                    ->label('Scan Kehadiran')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')

                    ->visible(function (Event $record) {

                        if (! auth()->check()) {
                            return false;
                        }

                        $user = auth()->user();

                        $isAdmin = $user->roles
                            ->pluck('name')
                            ->intersect(['Admin', 'Super Admin'])
                            ->isNotEmpty();

                        $alreadyJoin = $record->users->contains($user);

                        return ! $isAdmin && $alreadyJoin;
                    })

                    ->modalHeading('Scan QR Kehadiran')


                    ->modalSubmitAction(false)


                    ->modalCancelActionLabel('Tutup')

                    ->modalContent(
                        fn($record) => view('attendance.scan', [
                            'event' => $record,
                        ])
                    ),

                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color(Color::Gray)
                    ->visible(fn(Event $record) => $record->users->contains(auth()->user()))
                    ->mountUsing(function (Forms\ComponentContainer $form, Event $record) {
                        $attendance = $record->attendances()
                            ->where('user_id', auth()->id())
                            ->first();

                        $form->fill([
                            'name' => $record->name,
                            'point' => $record->point_reward,
                        ]);
                    })
                    ->form([
                        TextInput::make('name')
                            ->label('Nama Event')
                            ->readOnly(),
                        Forms\Components\Placeholder::make('module')
                            ->label('Modul Event')
                            ->content(function (Event $record) {


                                if (! $record->module) {
                                    return new HtmlString('<span class="text-gray-500">-</span>');
                                }

                                // Jika belum hari event
                                if (! now()->isSameDay($record->occasion_date)) {
                                    return new HtmlString(
                                        '<span class="text-sm text-warning-600">
                    Modul akan tersedia saat hari event
                </span>'
                                    );
                                }

                                // Jika hari event → tombol download
                                return new HtmlString(
                                    '<a
                href="' . route('event.download.module', $record) . '"
                target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2
                       text-sm font-semibold text-white
                       bg-primary-600 rounded-lg
                       hover:bg-primary-500 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-4 h-4"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
                </svg>
                Download Modul
            </a>'
                                );
                            })
                            ->extraAttributes([
                                'class' => 'mt-0 space-y-0',
                            ]),


                        TextInput::make('point')
                            ->label('Point yang Didapat')
                            ->numeric()
                            ->readOnly(),
                    ])

                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),

                /* ================= JOIN EVENT ================= */
                Tables\Actions\Action::make('joinEventAction')
                    ->label('Join')
                    ->icon('heroicon-o-arrow-left-end-on-rectangle')
                    ->disabled(
                        fn(Event $record) =>
                        $record->quota <= 0 ||
                            now()->lt($record->start_register) ||
                            now()->gt($record->end_register)
                    )
                    ->visible(fn(Event $record) => !$record->users->contains(auth()->user()))
                    ->action(function (Event $record) {

                        $user = Auth::user();

                        $eventDateOnly = Carbon::parse($record->occasion_date)->toDateString();

                        $alreadyJoinSameDate = Attendance::where('user_id', $user->id)
                            ->whereHas('event', function ($query) use ($eventDateOnly) {
                                $query->whereDate('occasion_date', $eventDateOnly);
                            })
                            ->exists();

                        if ($alreadyJoinSameDate) {
                            Notification::make()
                                ->title('Gagal Join')
                                ->body('Kamu sudah join event lain di tanggal tersebut.')
                                ->danger()
                                ->send();

                            return;
                        }

                        DB::transaction(function () use ($record, $user) {

                            $certificateNumber = (new CertificateController)
                                ->generateCertificateNumber($record, $user);

                            $record->event_users()->attach($user->id, [
                                'number_certificate' => $certificateNumber,
                            ]);

                            Attendance::create([
                                'event_id' => $record->id,
                                'user_id' => $user->id,
                                'participation_score' => 0,
                            ]);

                            $record->decrement('quota');
                        });

                        Notification::make()
                            ->title('Berhasil Join Event')
                            ->success()
                            ->send();
                    })
                    ->modalAlignment(Alignment::Center),

                Tables\Actions\Action::make('cancelJoinEventAction')
                    ->label('Batal Join')
                    ->icon('heroicon-o-arrow-left-end-on-rectangle')
                    ->color(Color::Amber)
                    ->visible(fn(Event $record) => $record->users->contains(auth()->user()))
                    ->mountUsing(fn(Forms\ComponentContainer $form, Event $record) => $form->fill([
                        'name' => $record->name,
                        'description' => $record->description,
                        'occasion_date' => $record->occasion_date,
                        'quota' => $record->quota,
                        'user_id' => Auth::user()->id
                    ]))
                    ->form([
                        Select::make('user_id')
                            ->label('Sebagai')
                            ->options(User::query()->pluck('name', 'id')),
                        TextInput::make('name')->label('Nama Event'),
                        Textarea::make('description')->label('Deskripsi'),
                        TextInput::make('occasion_date')->label('Tanggal Acara')
                    ])
                    ->action(function (array $data, Event $record) {
                        DB::transaction(function () use ($data, $record) {
                            $record->users()->detach($data['user_id']);
                            $record->event_users()->detach($data['user_id']);

                            $record->quota += 1;
                            $record->save();
                        });
                    })
                    ->disabledForm()
                    ->modalAlignment(Alignment::Center)
                    ->modalSubmitAction(fn(StaticAction $action) => $action->label('Batal Join')),


                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])

            ->defaultSort('created_at');
    }

    public static function getRelations(): array
    {
        return [
            EventResource\RelationManagers\UsersRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}

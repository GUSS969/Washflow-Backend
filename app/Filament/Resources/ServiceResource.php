<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Layanan';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Layanan')
                    ->schema([
                        Forms\Components\TextInput::make('service_name')
                            ->label('Nama Layanan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Cuci Reguler, Cuci Express'),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0'),
                        Forms\Components\Select::make('unit')
                            ->label('Satuan')
                            ->required()
                            ->native(false)
                            ->options([
                                'kg'  => '⚖️ Kilogram (kg)',
                                'pcs' => '📦 Satuan (pcs)',
                            ])
                            ->helperText('Pilih kg untuk laundry kiloan, pcs untuk laundry satuan'),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('Deskripsi singkat layanan (opsional)'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service_name')
                    ->label('Nama Layanan')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                    ->icon('heroicon-m-sparkles'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                    ->color('success'),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'kg'  => 'per Kg',
                        'pcs' => 'per Pcs',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'kg'  => 'warning',
                        'pcs' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('service_name', 'asc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'view'   => Pages\ViewService::route('/{record}'),
            'edit'   => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
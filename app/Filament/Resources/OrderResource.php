<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Pesanan';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['diterima', 'dicuci', 'dikeringkan', 'disetrika'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pesanan')
                    ->schema([
                        Forms\Components\TextInput::make('invoice')
                            ->label('No. Invoice')
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('customer_id')
                            ->label('Pelanggan')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Kasir/Admin')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(3),

                Forms\Components\Section::make('Status & Pembayaran')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Laundry')
                            ->options([
                                'diterima'     => '📥 Diterima',
                                'dicuci'       => '🫧 Sedang Dicuci',
                                'dikeringkan'  => '💨 Dikeringkan',
                                'disetrika'    => '♨️ Disetrika',
                                'selesai'      => '✅ Selesai',
                                'sudah_diambil' => '🏠 Sudah Diambil',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('payment_status')
                            ->label('Status Bayar')
                            ->options([
                                'belum_lunas' => '❌ Belum Lunas',
                                'lunas'       => '✅ Lunas',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('delivery_type')
                            ->label('Jenis Pengiriman')
                            ->options([
                                'antar_sendiri'  => '🚶 Antar Sendiri',
                                'minta_dijemput' => '🛵 Jemput ke Lokasi',
                            ])
                            ->required()
                            ->native(false)
                            ->default('antar_sendiri'),
                    ])->columns(3),

                Forms\Components\Section::make('Harga & Catatan')
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Harga')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'diterima'      => 'Diterima',
                        'dicuci'        => 'Dicuci',
                        'dikeringkan'   => 'Dikeringkan',
                        'disetrika'     => 'Disetrika',
                        'selesai'       => 'Selesai',
                        'sudah_diambil' => 'Sudah Diambil',
                        default         => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'diterima'      => 'info',
                        'dicuci'        => 'primary',
                        'dikeringkan'   => 'primary',
                        'disetrika'     => 'warning',
                        'selesai'       => 'success',
                        'sudah_diambil' => 'gray',
                        default         => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Bayar')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lunas'       => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                        default       => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'lunas'       => 'success',
                        'belum_lunas' => 'danger',
                        default       => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),
                Tables\Columns\TextColumn::make('delivery_type')
                    ->label('Pengiriman')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'antar_sendiri'  => 'Antar Sendiri',
                        'minta_dijemput' => 'Dijemput',
                        default          => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'antar_sendiri'  => 'gray',
                        'minta_dijemput' => 'info',
                        default          => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('d M Y, H:i')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Laundry')
                    ->options([
                        'diterima'      => 'Diterima',
                        'dicuci'        => 'Dicuci',
                        'dikeringkan'   => 'Dikeringkan',
                        'disetrika'     => 'Disetrika',
                        'selesai'       => 'Selesai',
                        'sudah_diambil' => 'Sudah Diambil',
                    ])
                    ->native(false),
                SelectFilter::make('payment_status')
                    ->label('Status Bayar')
                    ->options([
                        'lunas'       => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                    ])
                    ->native(false),
                SelectFilter::make('delivery_type')
                    ->label('Jenis Pengiriman')
                    ->options([
                        'antar_sendiri'  => 'Antar Sendiri',
                        'minta_dijemput' => 'Dijemput',
                    ])
                    ->native(false),
                Filter::make('created_today')
                    ->label('Dibuat Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
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
            ->striped()
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view'   => Pages\ViewOrder::route('/{record}'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}

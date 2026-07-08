<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Pembayaran')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->label('No. Invoice')
                            ->relationship('order', 'invoice')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash'     => '💵 Tunai (Cash)',
                                'transfer' => '🏦 Transfer Bank',
                                'qris'     => '📱 QRIS',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Bayar')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\DateTimePicker::make('payment_date')
                            ->label('Tanggal Bayar')
                            ->required()
                            ->default(now()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.invoice')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->copyable()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('order.customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user'),
                Tables\Columns\TextColumn::make('method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash'     => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris'     => 'QRIS',
                        default    => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash'     => 'success',
                        'transfer' => 'info',
                        'qris'     => 'warning',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                    ->color('success'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal Bayar')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('payment_date', 'desc')
            ->filters([
                SelectFilter::make('method')
                    ->label('Metode Bayar')
                    ->options([
                        'cash'     => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris'     => 'QRIS',
                    ])
                    ->native(false),
                Filter::make('today')
                    ->label('Bayar Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('payment_date', today())),
            ])
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
            'index'  => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view'   => Pages\ViewPayment::route('/{record}'),
            'edit'   => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}

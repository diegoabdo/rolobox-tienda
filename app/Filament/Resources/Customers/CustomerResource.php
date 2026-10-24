<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Models\Customer;
use App\Support\Phone;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Clientes: se crean solos en el checkout (uno por número de WhatsApp) y se actualizan
 * con el nombre y correo del último pedido. Las compras cuentan solo pedidos pagados.
 */
class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $slug = 'clientes';

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string | UnitEnum | null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 2;

    /** Agrega a cada cliente sus compras pagadas: cantidad, total, primera y última. */
    public static function getEloquentQuery(): Builder
    {
        $paid = fn (Builder $q) => $q->whereNotNull('paid_at')->where('status', '!=', 'cancelled');

        return parent::getEloquentQuery()
            ->withCount(['orders as purchases_count' => $paid])
            ->withSum(['orders as spent_cents' => $paid], 'total_cents')
            ->withMax(['orders as last_purchase_at' => $paid], 'paid_at')
            ->withMin(['orders as first_purchase_at' => $paid], 'paid_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('phone')
                            ->label('WhatsApp')
                            ->helperText('Identifica al cliente; no se puede cambiar.')
                            ->formatStateUsing(fn (?string $state): ?string => $state ? Phone::display($state) : null)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->maxLength(160),
                        Textarea::make('notes')
                            ->label('Notas internas')
                            ->helperText('Solo las ve el equipo de RoloBox. Ej.: sabores favoritos, cómo prefiere la entrega.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $tz = config('rolobox.timezone');

        return $schema
            ->columns(3)
            ->components([
                Section::make('Cliente')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre')
                            ->weight('bold'),
                        TextEntry::make('phone')
                            ->label('WhatsApp')
                            ->formatStateUsing(fn (string $state): string => Phone::display($state))
                            ->url(fn (Customer $record): string => 'https://wa.me/'.$record->phone)
                            ->openUrlInNewTab()
                            ->color('primary'),
                        TextEntry::make('email')
                            ->label('Correo')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Primer pedido registrado')
                            ->dateTime('d/m/Y')
                            ->timezone($tz),
                        TextEntry::make('notes')
                            ->label('Notas internas')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ]),
                Section::make('Compras')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('purchases_count')
                            ->label('Compras pagadas')
                            ->weight('bold'),
                        TextEntry::make('spent_cents')
                            ->label('Total comprado')
                            ->money('GTQ', divideBy: 100)
                            ->placeholder('Q0.00'),
                        TextEntry::make('average')
                            ->label('Ticket promedio')
                            ->state(fn (Customer $record): ?int => $record->purchases_count > 0
                                ? intdiv((int) $record->spent_cents, (int) $record->purchases_count)
                                : null)
                            ->money('GTQ', divideBy: 100)
                            ->placeholder('—'),
                        TextEntry::make('last_purchase_at')
                            ->label('Última compra')
                            ->dateTime('d/m/Y')
                            ->timezone($tz)
                            ->placeholder('Todavía no compra'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $tz = config('rolobox.timezone');

        return $table
            ->defaultSort('last_purchase_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Customer $record): string => Phone::display($record->phone)),
                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchases_count')
                    ->label('Compras')
                    ->sortable(),
                TextColumn::make('spent_cents')
                    ->label('Total comprado')
                    ->money('GTQ', divideBy: 100)
                    ->placeholder('Q0.00')
                    ->sortable(),
                TextColumn::make('last_purchase_at')
                    ->label('Última compra')
                    ->dateTime('d/m/Y')
                    ->timezone($tz)
                    ->placeholder('Sin compras')
                    ->sortable(),
                TextColumn::make('first_purchase_at')
                    ->label('Cliente desde')
                    ->dateTime('d/m/Y')
                    ->timezone($tz)
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('buyers')
                    ->label('Compras')
                    ->placeholder('Todos')
                    ->trueLabel('Con al menos una compra')
                    ->falseLabel('Solo pedidos sin pagar')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('orders', fn ($q) => $q->whereNotNull('paid_at')),
                        false: fn (Builder $query) => $query->whereDoesntHave('orders', fn ($q) => $q->whereNotNull('paid_at')),
                    ),
                TernaryFilter::make('returning')
                    ->label('Recurrentes')
                    ->placeholder('Todos')
                    ->trueLabel('Dos compras o más')
                    ->falseLabel('Una compra')
                    ->queries(
                        true: fn (Builder $query) => $query->has('paidOrders', '>=', 2),
                        false: fn (Builder $query) => $query->has('paidOrders', '=', 1),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/editar'),
        ];
    }

    /** Los clientes se crean desde la tienda, no a mano. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'email'];
    }
}

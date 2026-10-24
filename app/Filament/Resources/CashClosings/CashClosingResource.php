<?php

namespace App\Filament\Resources\CashClosings;

use App\Filament\Resources\CashClosings\Pages\CreateCashClosing;
use App\Filament\Resources\CashClosings\Pages\ListCashClosings;
use App\Filament\Resources\CashClosings\Pages\ViewCashClosing;
use App\Filament\Support\Quetzales;
use App\Models\CashClosing;
use App\Services\SalesReport;
use BackedEnum;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Cierre de caja diario. El sistema calcula lo vendido (tarjeta, transferencia y efectivo), las
 * comisiones, los gastos y cuánto efectivo debería haber en caja; quien cierra anota lo que contó.
 * Al cerrar, los números del día quedan fijos y ya no se pueden agregar gastos ni ventas en persona.
 */
class CashClosingResource extends Resource
{
    protected static ?string $model = CashClosing::class;

    protected static ?string $slug = 'cierres';

    protected static ?string $modelLabel = 'cierre de caja';

    protected static ?string $pluralModelLabel = 'cierres de caja';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string | UnitEnum | null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 2;

    /** @var array<string, array<string, mixed>> Totales ya calculados por día (el formulario los pide varias veces). */
    private static array $previewCache = [];

    public static function form(Schema $schema): Schema
    {
        $tz = config('rolobox.timezone');

        return $schema
            ->columns(3)
            ->components([
                Section::make('Día')
                    ->columnSpan(1)
                    ->schema([
                        DatePicker::make('period_start')
                            ->label('Día que cierras')
                            ->default(fn (): string => CashClosing::nextPeriodStart()->toDateString())
                            ->maxDate(fn (): string => CarbonImmutable::now($tz)->toDateString())
                            ->required()
                            ->live()
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if ($value && CashClosing::covers(CarbonImmutable::parse($value)->toDateString())) {
                                    $fail('Ese día ya tiene cierre.');
                                }
                            }),
                        Quetzales::input('opening_cash_cents')
                            ->label('Fondo de caja al abrir')
                            ->helperText('Efectivo con que empezó el día. Por defecto, lo contado en el cierre anterior.')
                            // En centavos: el campo lo muestra en quetzales.
                            ->default(fn (): int => CashClosing::lastCountedCash())
                            ->required()
                            ->live(onBlur: true),
                        Quetzales::input('counted_cash_cents')
                            ->label('Efectivo contado al cerrar')
                            ->helperText('Cuenta los billetes y monedas de la caja.')
                            ->live(onBlur: true),
                        Quetzales::input('counted_cents')
                            ->label('Depósitos recibidos (opcional)')
                            ->helperText('Lo que Recurrente o el banco depositó por las ventas de este día, si ya llegó.')
                            ->live(onBlur: true),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->placeholder('Ej.: faltaron Q5 de cambio.')
                            ->rows(3),
                    ]),

                Section::make('Resumen del día')
                    ->columnSpan(2)
                    ->columns(3)
                    ->description('Pedidos pagados ese día (en línea y en persona, sin cancelados) y gastos registrados con esa fecha.')
                    ->schema([
                        self::previewEntry('orders', 'Pedidos pagados', fn ($t) => (string) $t['orders']),
                        self::previewEntry('gross', 'Total vendido', fn ($t) => Quetzales::format($t['gross'])),
                        self::previewEntry('in_person', 'En persona', fn ($t) => Quetzales::format($t['inPerson'])),
                        self::previewEntry('card', 'Con tarjeta', fn ($t) => Quetzales::format($t['card'])),
                        self::previewEntry('transfer', 'Con transferencia', fn ($t) => Quetzales::format($t['transfer'])),
                        self::previewEntry('cash', 'En efectivo', fn ($t) => Quetzales::format($t['cash'])),
                        self::previewEntry('fees', 'Comisiones estimadas', fn ($t) => Quetzales::format($t['fees'])),
                        self::previewEntry('expected_deposit', 'Debería depositarse', fn ($t) => Quetzales::format($t['expectedDeposit'])),
                        self::previewEntry('expenses', 'Gastos del día', fn ($t) => Quetzales::format($t['expenses']).' ('.Quetzales::format($t['cashExpenses']).' en efectivo)'),
                        TextEntry::make('preview_expected_cash')
                            ->label('Efectivo que debería haber')
                            ->state(function (Get $get): string {
                                $t = self::preview($get);

                                return $t === null ? '—' : Quetzales::format(self::expectedCash($get, $t));
                            })
                            ->helperText('Fondo + ventas en efectivo − gastos en efectivo'),
                        TextEntry::make('preview_cash_difference')
                            ->label('Diferencia de efectivo')
                            ->state(function (Get $get): string {
                                $t = self::preview($get);
                                $counted = Quetzales::toCents($get('counted_cash_cents'));
                                if ($t === null || $counted === null) {
                                    return 'Escribe el efectivo contado.';
                                }
                                $diff = $counted - self::expectedCash($get, $t);

                                return $diff === 0 ? 'Cuadra exacto' : ($diff > 0 ? 'Sobran ' : 'Faltan ').Quetzales::format(abs($diff));
                            })
                            ->weight('bold'),
                        self::previewEntry('net', 'Ganancia neta del día', fn ($t) => Quetzales::format($t['netProfit']))
                            ->helperText('Ventas − costos − comisiones − entregas − gastos'),
                    ]),
            ]);
    }

    private static function previewEntry(string $name, string $label, Closure $format): TextEntry
    {
        return TextEntry::make("preview_{$name}")
            ->label($label)
            ->state(function (Get $get) use ($format): string {
                $t = self::preview($get);

                return $t === null ? '—' : $format($t);
            });
    }

    /** @param  array<string, mixed>  $t */
    private static function expectedCash(Get $get, array $t): int
    {
        return (int) Quetzales::toCents($get('opening_cash_cents')) + $t['cash'] - $t['cashExpenses'];
    }

    /** @return array<string, mixed>|null */
    private static function preview(Get $get): ?array
    {
        $day = $get('period_start');
        if (! $day) {
            return null;
        }

        $date = CarbonImmutable::parse($day, config('rolobox.timezone'));

        return self::$previewCache[$date->toDateString()] ??= (new SalesReport($date, $date))->totals();
    }

    public static function infolist(Schema $schema): Schema
    {
        $tz = config('rolobox.timezone');
        $money = fn (string $name, string $label): TextEntry => TextEntry::make($name)->label($label)->money('GTQ', divideBy: 100);
        $difference = fn (string $name, string $label): TextEntry => TextEntry::make($name)
            ->label($label)
            ->money('GTQ', divideBy: 100)
            ->placeholder('No se anotó')
            ->color(fn (?int $state): string => $state === null || $state === 0 ? 'gray' : ($state > 0 ? 'success' : 'danger'))
            ->weight('bold');

        return $schema
            ->columns(3)
            ->components([
                Section::make('Cierre')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('period_start')
                            ->label('Día')
                            ->formatStateUsing(fn (CashClosing $record): string => ucfirst($record->period_start->locale('es')->isoFormat('dddd D [de] MMMM YYYY'))),
                        TextEntry::make('closedBy.name')
                            ->label('Cerró')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Hora del cierre')
                            ->dateTime('d/m/Y H:i')
                            ->timezone($tz),
                        TextEntry::make('notes')
                            ->label('Notas')
                            ->placeholder('Sin notas'),
                    ]),
                Section::make('Caja de efectivo')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        $money('opening_cash_cents', 'Fondo al abrir'),
                        $money('cash_sales_cents', '+ Ventas en efectivo'),
                        $money('cash_expenses_cents', '− Gastos en efectivo'),
                        $money('expected_cash_cents', '= Debería haber'),
                        $money('counted_cash_cents', 'Se contó')->placeholder('No se anotó'),
                        $difference('cash_difference_cents', 'Diferencia'),
                    ]),
                Section::make('Ventas y depósitos')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('orders_count')->label('Pedidos pagados'),
                        $money('gross_cents', 'Total vendido'),
                        $money('in_person_cents', 'En persona'),
                        $money('card_cents', 'Con tarjeta'),
                        $money('transfer_cents', 'Con transferencia'),
                        $money('fees_cents', 'Comisiones estimadas'),
                        $money('expected_deposit_cents', 'Debería depositarse'),
                        $money('counted_cents', 'Depósitos recibidos')->placeholder('No se anotó'),
                        $difference('difference_cents', 'Diferencia de depósitos'),
                    ]),
                Section::make('Resultado del día')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        $money('cost_cents', 'Costo de productos'),
                        $money('delivery_cost_cents', 'Costo de entregas'),
                        $money('profit_cents', 'Ganancia antes de gastos'),
                        $money('expenses_cents', 'Gastos del día'),
                        $money('net_profit_cents', 'Ganancia neta')->weight('bold'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('period_end', 'desc')
            ->columns([
                TextColumn::make('period_start')
                    ->label('Día')
                    ->formatStateUsing(fn (CashClosing $record): string => ucfirst($record->period_start->locale('es')->isoFormat('ddd D [de] MMM YYYY')))
                    ->weight('bold'),
                TextColumn::make('orders_count')
                    ->label('Pedidos'),
                TextColumn::make('gross_cents')
                    ->label('Vendido')
                    ->money('GTQ', divideBy: 100),
                TextColumn::make('expected_cash_cents')
                    ->label('Efectivo esperado')
                    ->money('GTQ', divideBy: 100),
                TextColumn::make('cash_difference_cents')
                    ->label('Diferencia de efectivo')
                    ->money('GTQ', divideBy: 100)
                    ->placeholder('—')
                    ->color(fn (?int $state): string => $state === null || $state === 0 ? 'gray' : ($state > 0 ? 'success' : 'danger')),
                TextColumn::make('expenses_cents')
                    ->label('Gastos')
                    ->money('GTQ', divideBy: 100)
                    ->toggleable(),
                TextColumn::make('net_profit_cents')
                    ->label('Ganancia neta')
                    ->money('GTQ', divideBy: 100),
                TextColumn::make('closedBy.name')
                    ->label('Cerró')
                    ->toggleable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashClosings::route('/'),
            'create' => CreateCashClosing::route('/crear'),
            'view' => ViewCashClosing::route('/{record}'),
        ];
    }

    /** Un cierre no se edita: si hubo un error se borra y se hace de nuevo. */
    public static function canEdit(Model $record): bool
    {
        return false;
    }
}

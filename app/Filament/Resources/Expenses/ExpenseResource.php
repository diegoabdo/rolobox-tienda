<?php

namespace App\Filament\Resources\Expenses;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Pages\EditExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Support\Labels;
use App\Filament\Support\Quetzales;
use App\Models\CashClosing;
use App\Models\Expense;
use BackedEnum;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Gastos del negocio. Se restan en la ganancia neta y los de efectivo cuentan en el cierre de caja. */
class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $slug = 'gastos';

    protected static ?string $modelLabel = 'gasto';

    protected static ?string $pluralModelLabel = 'gastos';

    protected static ?string $recordTitleAttribute = 'description';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string | UnitEnum | null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $tz = config('rolobox.timezone');

        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        DatePicker::make('spent_on')
                            ->label('Fecha')
                            ->default(fn (): string => CarbonImmutable::now($tz)->toDateString())
                            ->maxDate(fn (): string => CarbonImmutable::now($tz)->toDateString())
                            ->required()
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if ($value && CashClosing::covers(CarbonImmutable::parse($value)->toDateString())) {
                                    $fail('Ese día ya tiene cierre de caja. Borra el cierre para agregar o cambiar gastos.');
                                }
                            }),
                        Select::make('category')
                            ->label('Categoría')
                            ->options(ExpenseCategory::options())
                            ->required(),
                        TextInput::make('description')
                            ->label('Descripción')
                            ->placeholder('Harina, mantequilla y canela')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),
                        Quetzales::input('amount_cents')
                            ->label('Monto')
                            ->required()
                            ->minValue(0.01),
                        Select::make('payment_method')
                            ->label('Se pagó con')
                            ->options(Labels::paymentMethods())
                            ->default(PaymentMethod::Cash->value)
                            ->helperText('Lo pagado en efectivo se descuenta de la caja en el cierre.')
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('spent_on', 'desc')
            ->columns([
                TextColumn::make('spent_on')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => $state instanceof ExpenseCategory ? $state->label() : (string) $state),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('payment_method')
                    ->label('Pagado con')
                    ->formatStateUsing(fn ($state): string => Labels::paymentMethod($state)),
                TextColumn::make('amount_cents')
                    ->label('Monto')
                    ->money('GTQ', divideBy: 100)
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')->money('GTQ', divideBy: 100)),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(ExpenseCategory::options()),
                SelectFilter::make('payment_method')
                    ->label('Pagado con')
                    ->options(Labels::paymentMethods()),
                Filter::make('spent_on')
                    ->label('Fechas')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('spent_on', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('spent_on', '<=', $date))),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/crear'),
            'edit' => EditExpense::route('/{record}/editar'),
        ];
    }

    /** Los gastos de un día ya cerrado no se tocan. */
    public static function canEdit(Model $record): bool
    {
        return $record instanceof Expense && ! $record->isLocked();
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Expense && ! $record->isLocked();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}

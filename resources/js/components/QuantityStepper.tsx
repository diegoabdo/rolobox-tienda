type Props = {
    value: number;
    onChange: (value: number) => void;
    min?: number;
    max?: number;
    label: string;
    disabled?: boolean;
    disableIncrease?: boolean;
};

export function QuantityStepper({ value, onChange, min = 0, max = 20, label, disabled, disableIncrease }: Props) {
    return (
        <div className="flex items-center gap-1.5" role="group" aria-label={label}>
            <button
                type="button"
                className="flex size-11 cursor-pointer items-center justify-center rounded-full bg-well text-body transition-transform active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                onClick={() => onChange(Math.max(min, value - 1))}
                disabled={disabled || value <= min}
                aria-label={`Quitar uno · ${label}`}
            >
                −
            </button>
            <span className="min-w-6 text-center font-bold tabular-nums" aria-live="polite">
                {value}
            </span>
            <button
                type="button"
                className="flex size-11 cursor-pointer items-center justify-center rounded-full bg-accent text-body text-ground transition-transform active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                onClick={() => onChange(Math.min(max, value + 1))}
                disabled={disabled || disableIncrease || value >= max}
                aria-label={`Agregar uno · ${label}`}
            >
                +
            </button>
        </div>
    );
}

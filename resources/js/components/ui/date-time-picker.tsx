import { Clock } from 'lucide-react';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type DateTimePickerProps = {
    id?: string;
    /** Valeur au format `aaaa-mm-jjThh:mm` (comme un champ `datetime-local`), chaîne vide si aucune. */
    value: string;
    onChange: (value: string) => void;
    onBlur?: () => void;
    /** Bornes au format `aaaa-mm-jj` ou `aaaa-mm-jjThh:mm` (seule la date est prise en compte). */
    min?: string;
    max?: string;
    /** Heure proposée lorsqu'une date est choisie sans heure. */
    heureParDefaut?: string;
    invalid?: boolean;
    className?: string;
};

/** Sélecteur date + heure : calendrier shadcn/ui pour la date, champ heure à côté. */
export function DateTimePicker({ id, value, onChange, onBlur, min, max, heureParDefaut = '08:00', invalid, className }: DateTimePickerProps) {
    const [date = '', heure = ''] = value ? value.split('T') : [];

    return (
        <div className={cn('grid grid-cols-[minmax(0,1fr)_7.5rem] gap-2', className)}>
            <DatePicker
                id={id}
                value={date}
                onChange={(jour) => {
                    onChange(jour ? `${jour}T${heure.slice(0, 5) || heureParDefaut}` : '');
                    onBlur?.();
                }}
                min={min?.slice(0, 10)}
                max={max?.slice(0, 10)}
                invalid={invalid}
            />
            <Input
                type="time"
                aria-label="Heure"
                icon={<Clock />}
                value={heure.slice(0, 5)}
                disabled={!date}
                onChange={(e) => date && onChange(`${date}T${e.target.value || heureParDefaut}`)}
                onBlur={onBlur}
                invalid={invalid}
                className="pr-2 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400"
            />
        </div>
    );
}

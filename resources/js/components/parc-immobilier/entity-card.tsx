import { ArrowRight, Layers, MapPin, UserRound } from 'lucide-react';
import { cn } from '@/lib/utils';
import { ItemActions, type ParcActions } from './actions';
import { estActif, nombre, StatutBadge } from './labels';
import { OccupationBar, tauxOccupation } from './occupation-bar';
import type { BatimentItem, CiteItem } from './types';

/** Carte d'une cité ou d'un bâtiment : identité, chiffres clés, occupation et accès au niveau suivant. */
export function EntityCard({ item, actions }: { item: CiteItem | BatimentItem; actions: ParcActions }) {
    const actif = estActif(item);
    const { resume } = item;
    const chiffres =
        item.type === 'cite'
            ? [
                  { label: 'Bâtiments', value: item.batiments },
                  { label: 'Chambres', value: resume.chambres },
                  { label: 'Disponibles', value: resume.disponibles },
              ]
            : [
                  { label: 'Chambres', value: resume.chambres },
                  { label: 'Niveaux', value: item.niveaux },
                  { label: 'Disponibles', value: resume.disponibles },
              ];

    return (
        <article
            className={cn(
                'group flex flex-col rounded-brand border bg-white transition-shadow hover:shadow-[0_6px_20px_rgba(0,100,0,0.08)]',
                !actif && 'bg-gray-50/60',
            )}
        >
            <div className="flex items-start gap-3 p-4">
                <div className="min-w-0 flex-1">
                    <button
                        type="button"
                        onClick={() => actions.ouvrir(item)}
                        className="block max-w-full cursor-pointer truncate text-left text-base font-medium text-gray-900 hover:text-primary focus-visible:underline focus-visible:outline-none"
                    >
                        {item.name}
                    </button>
                    <p className="mt-0.5 truncate text-xs text-muted">
                        {[item.type === 'cite' ? item.code : item.cite, item.occupation].filter(Boolean).join(' · ') || '—'}
                    </p>
                </div>
                <StatutBadge statut={item.statut} />
            </div>

            <dl className="mx-4 grid grid-cols-3 divide-x rounded-brand border bg-surface/60">
                {chiffres.map((chiffre) => (
                    <div key={chiffre.label} className="px-3 py-2.5 text-center">
                        <dt className="text-[11px] tracking-wide text-muted uppercase">{chiffre.label}</dt>
                        <dd className="mt-0.5 text-lg font-medium text-gray-900">{nombre.format(chiffre.value)}</dd>
                    </div>
                ))}
            </dl>

            <div className="space-y-2 px-4 pt-4">
                <div className="flex items-baseline justify-between text-xs">
                    <span className="text-muted">Occupation</span>
                    <span className="font-medium text-gray-900">
                        {tauxOccupation(resume)} %
                        <span className="ml-1 font-normal text-muted">
                            · {nombre.format(resume.occupants)}/{nombre.format(resume.places)} places
                        </span>
                    </span>
                </div>
                <OccupationBar resume={resume} />
            </div>

            <ul className="space-y-1.5 px-4 pt-4 text-sm text-muted">
                <li className="flex items-center gap-2 truncate">
                    <UserRound size={16} strokeWidth={1.25} className="shrink-0" aria-hidden />
                    <span className="truncate">
                        <span className="text-gray-900">{item.type === 'cite' ? 'Responsable' : 'Concierge'} :</span>{' '}
                        {item.responsable_nom ?? 'non désigné'}
                    </span>
                </li>
                {item.type === 'cite' ? (
                    <li className="flex items-center gap-2 truncate">
                        <MapPin size={16} strokeWidth={1.25} className="shrink-0" aria-hidden />
                        <span className="truncate">{item.localisation ?? 'Localisation non renseignée'}</span>
                    </li>
                ) : (
                    <li className="flex items-center gap-2 truncate">
                        <Layers size={16} strokeWidth={1.25} className="shrink-0" aria-hidden />
                        <span className="truncate">{item.niveaux > 0 ? `${item.niveaux} niveau${item.niveaux > 1 ? 'x' : ''}` : 'Niveaux non renseignés'}</span>
                    </li>
                )}
            </ul>

            <div className="mt-4 flex items-center justify-between gap-2 border-t px-4 py-2.5">
                <button
                    type="button"
                    onClick={() => actions.ouvrir(item)}
                    className="group/voir inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-brand border-[1.5px] border-accent bg-accent px-3 text-sm font-semibold text-gray-900 transition-colors focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    {item.type === 'cite' ? 'Voir les bâtiments' : 'Voir les chambres'}
                    <ArrowRight size={16} strokeWidth={1.75} className="transition-transform group-hover/voir:translate-x-0.5" aria-hidden />
                </button>
                <ItemActions item={item} actions={actions} />
            </div>
        </article>
    );
}

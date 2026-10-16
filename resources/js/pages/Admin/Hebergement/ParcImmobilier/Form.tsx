import { BedDouble, Building, Building2, ChevronRight, type LucideIcon } from 'lucide-react';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { BatimentForm } from '@/components/parc-immobilier/form/batiment-form';
import { ChambreForm } from '@/components/parc-immobilier/form/chambre-form';
import { CiteForm } from '@/components/parc-immobilier/form/cite-form';
import type {
    BatimentItem,
    BatimentOption,
    CiteItem,
    CiteOption,
    FormUrls,
    Option,
    TypeChambreOption,
    UserOption,
} from '@/components/parc-immobilier/form/types';
import { cn } from '@/lib/utils';
import { Separator } from '@/components/ui/separator';

type TypeFormulaire = 'cite' | 'batiment' | 'chambre';

type FormProps = {
    type: TypeFormulaire | null;
    mode: 'create' | 'edit';
    item: CiteItem | BatimentItem | null;
    campusId: string;
    typesCite: Option[];
    cites: CiteOption[];
    selection: { cite: string | null; batiment: string | null };
    urls: FormUrls;
    users?: UserOption[];
    reglements?: Option[];
    batiments?: BatimentOption[];
    typesChambre?: TypeChambreOption[];
};

const CHOIX: { type: TypeFormulaire; titre: string; description: string; icon: LucideIcon }[] = [
    { type: 'cite', titre: 'Nouvelle cité ', description: 'Identité, contacts, localisation et services.', icon: Building2 },
    { type: 'batiment', titre: 'Nouveau bâtiment', description: 'Rattachement, structure (étages, ailes) et chambres prévues.', icon: Building },
    { type: 'chambre', titre: 'Nouvelles chambres', description: 'Numéros, types et lits des chambres d’un niveau.', icon: BedDouble },
];

const TITRES: Record<TypeFormulaire, { create: string; edit: string; sousTitre: string }> = {
    cite: { create: 'Nouvelle cité', edit: 'Modifier la cité', sousTitre: 'Renseignez la cité étape par étape ; chaque étape est vérifiée avant de passer à la suivante.' },
    batiment: {
        create: 'Nouveau bâtiment',
        edit: 'Modifier le bâtiment',
        sousTitre: 'Rattachez le bâtiment à une cité, décrivez sa structure : ses chambres seront prévues automatiquement.',
    },
    chambre: {
        create: 'Configurer des chambres',
        edit: 'Configurer des chambres',
        sousTitre: 'Choisissez un niveau d’un bâtiment, puis donnez un numéro, un type et des lits à ses chambres.',
    },
};

/** Ajout / modification d'une cité, d'un bâtiment, ou configuration des chambres (formulaires en plusieurs étapes). */
export default function ParcImmobilierForm(props: FormProps) {
    const { type, mode, urls } = props;
    const titre = type ? TITRES[type][mode] : 'Ajouter au parc immobilier';
    const breadcrumbs = [
        { label: 'Accueil' },
        { label: 'Gestion Hébergement', href: urls.hebergement },
        { label: 'Parc immobilier', href: urls.index },
        { label: titre },
    ];

    return (
        <AdminLayout title={titre} breadcrumbs={breadcrumbs}>
            <div className="mb-6 flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <h1 className="page-title text-xl font-medium text-gray-900">{titre}</h1>
                    <p className="text-sm text-muted">{type ? TITRES[type].sousTitre : 'Choisissez ce que vous souhaitez ajouter.'}</p>
                </div>
                {type && mode === 'create' && <Separator />}
                {type && mode === 'create' && (
                    // Passer d'un formulaire à l'autre sans revenir à la liste.
                    <nav aria-label="Type d’élément" className="inline-flex shrink-0 self-end rounded-brand border bg-white p-1">
                        {CHOIX.map((choix) => (
                            <a
                                key={choix.type}
                                href={urls.create[choix.type]}
                                aria-current={choix.type === type ? 'page' : undefined}
                                className={cn(
                                    'inline-flex h-9 items-center gap-2 rounded-[6px] px-3 text-sm transition-colors',
                                    choix.type === type ? 'bg-primary text-white' : 'text-muted hover:bg-soft hover:text-primary',
                                )}
                            >
                                <choix.icon {...ICON} className="size-4" aria-hidden />
                                {choix.type === 'cite' ? 'Cité' : choix.type === 'batiment' ? 'Bâtiment' : 'Chambres'}
                            </a>
                        ))}
                    </nav>
                )}
            </div>

            {type === null && (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    {CHOIX.map((choix) => (
                        <a
                            key={choix.type}
                            href={urls.create[choix.type]}
                            className="group flex items-start gap-4 rounded-brand border bg-white p-5 transition-colors hover:border-primary hover:bg-soft/40 focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                        >
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-brand bg-soft text-primary">
                                <choix.icon {...ICON} aria-hidden />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block text-sm font-medium text-gray-900">{choix.titre}</span>
                                <span className="block text-sm text-muted">{choix.description}</span>
                            </span>
                            <ChevronRight className="mt-1 size-4 text-muted transition-transform group-hover:translate-x-0.5 group-hover:text-primary" aria-hidden />
                        </a>
                    ))}
                </div>
            )}

            {type === 'cite' && (
                <CiteForm
                    item={mode === 'edit' ? (props.item as CiteItem) : null}
                    campusId={props.campusId}
                    typesCite={props.typesCite}
                    users={props.users ?? []}
                    reglements={props.reglements ?? []}
                    submitUrl={urls.submit}
                    cancelUrl={urls.index}
                />
            )}

            {type === 'batiment' && (
                <BatimentForm
                    item={mode === 'edit' ? (props.item as BatimentItem) : null}
                    campusId={props.campusId}
                    cites={props.cites}
                    typesCite={props.typesCite}
                    users={props.users ?? []}
                    preselectionCite={props.selection.cite}
                    submitUrl={urls.submit}
                    cancelUrl={urls.index}
                />
            )}

            {type === 'chambre' && (
                <ChambreForm
                    cites={props.cites}
                    batiments={props.batiments ?? []}
                    typesCite={props.typesCite}
                    typesChambre={props.typesChambre ?? []}
                    selection={props.selection}
                    urls={urls}
                />
            )}
        </AdminLayout>
    );
}

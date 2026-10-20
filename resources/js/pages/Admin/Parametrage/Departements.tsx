import { Landmark } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';
import { ImportReferentiel, RapportImportDialog, type ColonneImport, type RapportImport } from '@/components/parametrage-hebergement/import-referentiel';
import { ReferentielPage, type ChampReferentiel, type ElementReferentiel } from '@/components/parametrage-hebergement/referentiel-page';
import { nombre } from '@/lib/text';
import type { Paginated } from '@/types/global';

type Departement = ElementReferentiel & { abreviation: string | null; filieres: number; etudiants: number };

type Props = {
    elements: Paginated<Departement>;
    stats: { total: number; utilises: number; inutilises: number };
    filters: { q: string };
    import: RapportImport;
    urls: { index: string; store: string | null; import: string | null; modele: string | null };
};

const CHAMPS: ChampReferentiel[] = [
    { cle: 'name', label: 'Nom', max: 255, requis: 'Renseignez le nom du département.', placeholder: 'Ex. Sciences et technologies' },
    { cle: 'abreviation', label: 'Abréviation', max: 100, placeholder: 'Ex. UFR ST', hint: 'Facultative : sigle affiché dans les listes.' },
    { cle: 'description', label: 'Description', type: 'zone', max: 2000, placeholder: 'Description du département' },
];

const COLONNES_IMPORT: ColonneImport[] = [
    { nom: 'nom', requise: true, aide: 'Nom complet du département (ex. Sciences et technologies).' },
    { nom: 'abreviation', aide: 'Sigle affiché dans les listes (ex. UFR ST).' },
    { nom: 'description', aide: 'Description libre.' },
];

/** Départements (remplace `parametrage/departement/index.blade.php`) ; actions `store`, `update`, `delete` inchangées, + import en masse. */
export default function Departements({ elements, stats, filters, import: rapport, urls }: Props) {
    return (
        <ReferentielPage
            titre="Départements"
            description="Départements et UFR de rattachement des filières et des étudiants."
            singulier="département"
            un="un département"
            champs={CHAMPS}
            elements={elements}
            stats={stats}
            kpis={{ total: 'Départements enregistrés', utilises: 'Utilisés', inutilises: 'Sans filière ni étudiant' }}
            filters={filters}
            urls={urls}
            recherche="Rechercher un nom, un sigle ou une description"
            icone={<Landmark {...ICON} />}
            usage={(d) => ({ total: d.filieres + d.etudiants, libelle: 'filière(s) ou étudiant(s)' })}
            conseilSuppression="Rattachez ces éléments à un autre département avant de le supprimer."
            actions={
                urls.import && (
                    <>
                        <ImportReferentiel
                            url={urls.import}
                            modele={urls.modele}
                            titre="Importer des départements"
                            colonnes={COLONNES_IMPORT}
                            note="Un département portant déjà le même nom est mis à jour avec les cellules renseignées ; les autres lignes sont ajoutées."
                        />
                        {/* `key` : un nouvel import rouvre le rapport même si le précédent a été fermé. */}
                        <RapportImportDialog key={JSON.stringify(rapport)} rapport={rapport} />
                    </>
                )
            }
            colonnes={[
                {
                    key: 'abreviation',
                    header: 'Abréviation',
                    className: 'whitespace-nowrap',
                    cell: (d) => (d.abreviation ? <span className="text-gray-900">{d.abreviation}</span> : <span className="text-gray-400">—</span>),
                },
                {
                    key: 'usage',
                    header: 'Utilisation',
                    className: 'whitespace-nowrap text-sm',
                    cell: (d) =>
                        d.filieres + d.etudiants === 0 ? (
                            <span className="text-muted">Inutilisé</span>
                        ) : (
                            <span className="text-gray-900">
                                {nombre(d.filieres)} filière(s) · {nombre(d.etudiants)} étudiant(s)
                            </span>
                        ),
                },
            ]}
        />
    );
}

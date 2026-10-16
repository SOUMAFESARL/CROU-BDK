import { ExternalLink, FileText, Info, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Notice, Panneau, Vide } from '@/components/etudiant/panneau';
import { ICON } from '@/components/layout/app-sidebar';
import { EtudiantLayout } from '@/components/layout/etudiant-layout';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { sansAccents } from '@/lib/text';

type DocumentResidence = {
    id: string;
    type: string;
    titre: string;
    portee: string | null;
    annee: string | null;
    reference: string | null;
    version: string | null;
    dateApplication: string | null;
    url: string;
};

/** Page « Documents de la résidence » : règlements et documents officiels publiés par l'administration. */
export default function DocumentsResidence({ documents }: { documents: DocumentResidence[] }) {
    const [recherche, setRecherche] = useState('');
    const visibles = useMemo(() => {
        const terme = sansAccents(recherche.trim());
        if (!terme) return documents;
        return documents.filter((document) => sansAccents(`${document.titre} ${document.type} ${document.reference ?? ''}`).includes(terme));
    }, [documents, recherche]);

    return (
        <EtudiantLayout title="Documents de la résidence">
            <header className="mb-5">
                <h1 className="page-title text-xl font-medium text-gray-900">Documents de la résidence</h1>
                <p className="mt-1 text-sm text-muted">Règlements et documents officiels validés par l’administration.</p>
            </header>

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                <Panneau surtitre="Résidence" titre="Documents publiés" badge={documents.length}>
                    {documents.length > 3 && (
                        <div className="mb-3">
                            <Input
                                icon={<Search {...ICON} />}
                                type="search"
                                value={recherche}
                                onChange={(event) => setRecherche(event.target.value)}
                                placeholder="Rechercher un document"
                                aria-label="Rechercher un document"
                            />
                        </div>
                    )}

                    {visibles.length ? (
                        <ul className="flex flex-col gap-3">
                            {visibles.map((document) => (
                                <li key={document.id} className="rounded-brand border p-4">
                                    <div className="flex items-start gap-3">
                                        <span className="grid size-10 shrink-0 place-items-center rounded-brand bg-soft text-primary">
                                            <FileText {...ICON} />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <Badge variant="success">{document.type}</Badge>
                                            <h3 className="mt-1.5 text-sm font-medium break-words text-gray-900">{document.titre}</h3>
                                            {(document.portee || document.annee) && (
                                                <p className="text-xs text-muted">{[document.portee, document.annee].filter(Boolean).join(' · ')}</p>
                                            )}
                                            <dl className="mt-2 grid gap-x-4 gap-y-1 text-xs sm:grid-cols-3">
                                                <Detail libelle="Référence" valeur={document.reference} />
                                                <Detail libelle="Version" valeur={document.version} />
                                                <Detail libelle="Application" valeur={document.dateApplication} />
                                            </dl>
                                        </div>
                                    </div>
                                    <a
                                        href={document.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }), 'mt-3 w-full font-medium sm:ml-13 sm:w-fit')}
                                    >
                                        Consulter le PDF
                                        <ExternalLink {...ICON} />
                                    </a>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <Vide icone={FileText}>{documents.length ? 'Aucun document ne correspond à votre recherche.' : 'Aucun document validé n’est disponible pour le moment.'}</Vide>
                    )}

                    <Notice icone={Info} className="mt-4">
                        Les PDF s’ouvrent dans un nouvel onglet : vous pouvez les enregistrer ou les imprimer depuis votre navigateur.
                    </Notice>
                </Panneau>
            </div>
        </EtudiantLayout>
    );
}

function Detail({ libelle, valeur }: { libelle: string; valeur: string | null }) {
    return (
        <div className="min-w-0">
            <dt className="text-muted">{libelle}</dt>
            <dd className="truncate text-gray-900">{valeur || '—'}</dd>
        </div>
    );
}

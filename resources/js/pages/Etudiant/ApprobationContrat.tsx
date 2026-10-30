import { zodResolver } from '@hookform/resolvers/zod';
import { CalendarCheck, ChevronLeft, ChevronRight, ExternalLink, FileCheck2, FileText, FolderOpen, Info, Loader2, ShieldCheck } from 'lucide-react';
import { type ReactNode, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { Liste, Ligne, Notice, Panneau } from '@/components/etudiant/panneau';
import { ICON } from '@/components/layout/app-sidebar';
import { EtudiantLayout } from '@/components/layout/etudiant-layout';
import { Button, buttonVariants } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Stepper } from '@/components/ui/stepper';
import { envoyer } from '@/lib/inertia-form';
import { cn } from '@/lib/utils';
import { approbationSchema, type Approbation } from '@/schemas/approbation-contrat';

type ApprobationContratProps = {
    contrat: {
        id: string;
        empreinte: string;
        /** Version exacte du contrat soumise à l'approbation (son empreinte est vérifiée par le serveur). */
        document: string | null;
        approuve: boolean;
        dateApprobation: string | null;
        consentementDonne: string | null;
    } | null;
    consentement: string;
    liens: { pdf: string; approuver: string; entree: string; documents: string };
};

const ETAPES = ['Lire le contrat', 'Approuver'];

/** Page « Approbation de mon contrat » : lecture du contrat puis approbation en deux étapes. */
export default function ApprobationContrat({ contrat, consentement, liens }: ApprobationContratProps) {
    return (
        <EtudiantLayout title="Approbation de mon contrat">
            <header className="mb-5">
                <h1 className="page-title text-xl font-medium text-gray-900">Approbation de mon contrat</h1>
                <p className="mt-1 text-sm text-muted">Lisez votre contrat de logement, puis confirmez votre approbation pour obtenir votre rendez-vous d’entrée.</p>
            </header>

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                {!contrat ? (
                    <Panneau surtitre="Logement" titre="Mon contrat de logement">
                        <Notice icone={Info}>
                            Aucun contrat n’est disponible pour le moment. Il apparaît ici après l’attribution définitive de votre chambre.
                        </Notice>
                        <Liens liens={liens} />
                    </Panneau>
                ) : contrat.approuve ? (
                    <Panneau surtitre="Logement" titre="Mon contrat de logement" badge={<span className="text-primary">Approuvé</span>}>
                        <div className="flex items-start gap-3 rounded-brand border border-secondary/40 bg-soft p-4">
                            <ShieldCheck {...ICON} className="mt-0.5 shrink-0 text-primary" />
                            <div className="min-w-0">
                                <p className="text-sm font-medium text-gray-900">Contrat approuvé le {contrat.dateApprobation}</p>
                                {contrat.consentementDonne && <p className="mt-1 text-sm text-muted">« {contrat.consentementDonne} »</p>}
                            </div>
                        </div>
                        <Liens liens={liens} />
                    </Panneau>
                ) : (
                    <FormulaireApprobation contrat={contrat} consentement={consentement} liens={liens} />
                )}
            </div>
        </EtudiantLayout>
    );
}

function FormulaireApprobation({
    contrat,
    consentement,
    liens,
}: {
    contrat: NonNullable<ApprobationContratProps['contrat']>;
    consentement: string;
    liens: ApprobationContratProps['liens'];
}) {
    const [etape, setEtape] = useState(0);
    const [atteinte, setAtteinte] = useState(0);
    const {
        control,
        handleSubmit,
        trigger,
        formState: { errors, isSubmitting },
    } = useForm<Approbation>({ resolver: zodResolver(approbationSchema), defaultValues: { lu: false, consent: false } });

    const allerA = async (cible: number) => {
        if (cible > etape && !(await trigger('lu'))) return;
        setEtape(cible);
        setAtteinte((max) => Math.max(max, cible));
    };

    const approuver = handleSubmit(async () => {
        await envoyer(liens.approuver, { contract_id: contrat.id, document_hash: contrat.empreinte, consent: 1 });
    });

    return (
        <form onSubmit={approuver} noValidate className="rounded-brand border bg-white">
            <div className="px-4 pt-4 md:px-5 md:pt-5">
                <p className="text-xs text-muted">Logement</p>
                <h2 className="text-base font-medium text-gray-900">Mon contrat de logement</h2>
                <p className="mt-1 text-xs text-muted">
                    Étape {etape + 1} sur {ETAPES.length} · Les champs marqués <span className="text-red-600">*</span> sont obligatoires.
                </p>
            </div>
            <Stepper steps={ETAPES} current={etape} reached={atteinte} onStepClick={allerA} className="mt-3 px-4 md:px-5" />

            <div className="flex flex-col gap-4 p-4 md:p-5">
                {etape === 0 ? (
                    <>
                        {contrat.document ? (
                            // Contenu généré par le serveur, isolé sans script ni formulaire.
                            <iframe
                                title="Contrat de logement"
                                sandbox=""
                                srcDoc={contrat.document}
                                className="h-[60vh] min-h-80 w-full rounded-brand border bg-white"
                            />
                        ) : (
                            <Notice icone={Info}>Consultez la version PDF de votre contrat ci-dessous.</Notice>
                        )}
                        <a href={liens.pdf} target="_blank" rel="noopener noreferrer" className={cn(buttonVariants({ variant: 'secondary' }), 'w-full font-medium sm:w-fit')}>
                            <FileText {...ICON} />
                            Ouvrir le contrat en PDF
                            <ExternalLink {...ICON} />
                        </a>
                        <Case
                            id="contrat-lu"
                            libelle="J’ai lu l’intégralité de mon contrat de logement."
                            erreur={errors.lu?.message}
                            champ={<Controller control={control} name="lu" render={({ field }) => <Checkbox id="contrat-lu" checked={field.value} onCheckedChange={(v) => field.onChange(v === true)} aria-invalid={!!errors.lu} className="mt-0.5" />} />}
                        />
                    </>
                ) : (
                    <>
                        <Notice icone={FileCheck2}>
                            Votre approbation est enregistrée pour cette version du contrat. Votre rendez-vous d’entrée sera ensuite proposé selon les créneaux du service Logement.
                        </Notice>
                        <Case
                            id="contrat-consentement"
                            libelle={consentement}
                            erreur={errors.consent?.message}
                            champ={
                                <Controller
                                    control={control}
                                    name="consent"
                                    render={({ field }) => (
                                        <Checkbox id="contrat-consentement" checked={field.value} onCheckedChange={(v) => field.onChange(v === true)} aria-invalid={!!errors.consent} className="mt-0.5" />
                                    )}
                                />
                            }
                        />
                    </>
                )}
            </div>

            <div className="flex flex-col-reverse gap-2 border-t p-4 sm:flex-row sm:justify-between md:px-5">
                {etape === 0 ? (
                    <a href={liens.documents} className={cn(buttonVariants({ variant: 'outline' }), 'font-medium')}>
                        Retour à mes documents
                    </a>
                ) : (
                    <Button variant="outline" onClick={() => setEtape(0)} disabled={isSubmitting} className="font-medium">
                        <ChevronLeft {...ICON} />
                        Relire le contrat
                    </Button>
                )}
                {etape === 0 ? (
                    <Button key="suivant" onClick={() => allerA(1)} className="font-medium">
                        Continuer
                        <ChevronRight {...ICON} />
                    </Button>
                ) : (
                    <Button key="envoyer" type="submit" disabled={isSubmitting} className="font-medium">
                        {isSubmitting ? <Loader2 {...ICON} className="animate-spin" /> : <ShieldCheck {...ICON} />}
                        Approuver mon contrat
                    </Button>
                )}
            </div>
        </form>
    );
}

function Case({ id, libelle, erreur, champ }: { id: string; libelle: string; erreur?: string; champ: ReactNode }) {
    return (
        <div>
            <label
                htmlFor={id}
                className={cn('flex cursor-pointer items-start gap-3 rounded-brand border p-3 text-sm text-gray-900', erreur ? 'border-red-300 bg-red-50/40' : 'border-gray-300 hover:bg-surface')}
            >
                {champ}
                <span>
                    {libelle}
                    <span className="ml-0.5 text-red-600">*</span>
                </span>
            </label>
            {erreur && (
                <p role="alert" className="mt-1.5 text-xs font-medium text-red-600">
                    {erreur}
                </p>
            )}
        </div>
    );
}

function Liens({ liens }: { liens: ApprobationContratProps['liens'] }) {
    return (
        <Liste className="mt-3">
            <Ligne href={liens.pdf} nouvelOnglet icone={FileText} ton="vert" titre="Consulter mon contrat" detail="PDF · s’ouvre dans un nouvel onglet" fin={ExternalLink} />
            <Ligne href={liens.entree} icone={CalendarCheck} titre="Mon entrée et notifications" detail="Rendez-vous d’entrée et messages du service Logement" fin={ChevronRight} />
            <Ligne href={liens.documents} icone={FolderOpen} titre="Mes documents" detail="Contrat, états des lieux, reçus" fin={ChevronRight} />
        </Liste>
    );
}


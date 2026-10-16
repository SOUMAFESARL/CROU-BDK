import { zodResolver } from '@hookform/resolvers/zod';
import { router, usePage } from '@inertiajs/react';
import { ArrowLeft, BadgeCheck, Building2, ClipboardCheck, FileText, History, Trash2, Upload } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm, type FieldPath } from 'react-hook-form';
import { z } from 'zod';
import { ConfirmRetrait, erreurPdf, PDF_MAX_MO, StatutDocumentBadge } from '@/components/documents-legaux/shared';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { RecapSection } from '@/components/parc-immobilier/form/inputs';
import { FormAlert, SectionTitle, useWizard, Wizard, type WizardStep } from '@/components/parc-immobilier/form/wizard';
import { Button, buttonVariants } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Field } from '@/components/ui/field';
import { FileDrop } from '@/components/ui/file-drop';
import { Input } from '@/components/ui/input';
import { PageHeader } from '@/components/ui/page-header';
import { envoyer } from '@/lib/inertia-form';
import { dateFr } from '@/lib/text';
import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types/global';

type DocumentProps = {
    id: string | null;
    type: string;
    type_label: string;
    title: string;
    reference: string;
    version_label: string;
    institution: string;
    scope_label: string;
    academic_year: string;
    effective_date: string;
    lock_version: number;
    status: 'published' | 'draft';
    file_name: string | null;
    ancien_editeur: boolean;
    updated_at: string | null;
};

type Version = {
    id: string;
    revision: number;
    created_at: string | null;
    pdf: string | null;
};

type Props = {
    document: DocumentProps;
    versions: Version[];
    urls: {
        index: string;
        submit: string | null;
        pdf: string | null;
        publish: string | null;
        destroy: string | null;
    };
};

/** Mêmes limites que `LegalDocumentController::validated()`. */
const schema = z.object({
    title: z.string().trim().min(1, 'Saisissez le titre du document.').max(200, 'Le titre ne doit pas dépasser 200 caractères.'),
    reference: z.string().trim().max(100, 'La référence ne doit pas dépasser 100 caractères.'),
    version_label: z.string().trim().min(1, 'Indiquez la version du document.').max(30, 'La version ne doit pas dépasser 30 caractères.'),
    institution: z.string().trim().min(1, 'Indiquez l’établissement.').max(200, 'L’établissement ne doit pas dépasser 200 caractères.'),
    scope_label: z.string().trim().max(150, 'Le périmètre ne doit pas dépasser 150 caractères.'),
    academic_year: z.string().trim().max(30, 'L’année universitaire ne doit pas dépasser 30 caractères.'),
    effective_date: z.string().refine((valeur) => !valeur || /^\d{4}-\d{2}-\d{2}$/.test(valeur), 'Date invalide.'),
});

type Valeurs = z.infer<typeof schema>;
type Champ = FieldPath<Valeurs>;

const STEPS: (WizardStep & { fields: Champ[] })[] = [
    {
        label: 'Identification',
        description: 'Titre, référence et version',
        icon: FileText,
        fields: ['title', 'reference', 'version_label'],
    },
    {
        label: 'Application',
        description: 'Établissement, périmètre et date',
        icon: Building2,
        fields: ['institution', 'scope_label', 'academic_year', 'effective_date'],
    },
    {
        label: 'Fichier PDF',
        description: `Document signé, ${PDF_MAX_MO} Mo maximum`,
        icon: Upload,
        fields: [],
    },
    {
        label: 'Vérification',
        description: 'Relire avant d’enregistrer',
        icon: ClipboardCheck,
        fields: [],
    },
];
const ETAPE_FICHIER = 2;

const valeursInitiales = (document: DocumentProps): Valeurs => ({
    title: document.title,
    reference: document.reference,
    version_label: document.version_label,
    institution: document.institution,
    scope_label: document.scope_label,
    academic_year: document.academic_year,
    effective_date: document.effective_date,
});

/** Téléversement / remplacement d'un document de la résidence (remplace `legal_documents/edit.blade.php`). */
export default function DocumentsLegauxEdit(props: Props) {
    // Nouvelle clé après chaque enregistrement : le formulaire repart des valeurs du serveur (nouvelle `lock_version`).
    return <Editeur key={`${props.document.id ?? 'nouveau'}-${props.document.lock_version}`} {...props} />;
}

function Editeur({ document, versions, urls }: Props) {
    const { errors: serverErrors } = usePage<SharedProps>().props;
    const wizard = useWizard(STEPS.length, { toutesAccessibles: !!document.id });
    const [fichier, setFichier] = useState<File | null>(null);
    const [erreurFichier, setErreurFichier] = useState<string | null>(null);
    const [erreurGenerale, setErreurGenerale] = useState<string | null>(null);
    const [envoi, setEnvoi] = useState(false);
    const [validation, setValidation] = useState(false);
    const [validationEnCours, setValidationEnCours] = useState(false);
    const [retraitDemande, setRetraitDemande] = useState(false);
    const [retraitEnCours, setRetraitEnCours] = useState(false);
    const defaultValues = useMemo(() => valeursInitiales(document), [document]);
    const fichierObligatoire = !document.file_name;

    const {
        register,
        control,
        trigger,
        watch,
        setError,
        handleSubmit,
        formState: { errors, isDirty },
    } = useForm<Valeurs>({
        resolver: zodResolver(schema),
        mode: 'onTouched',
        defaultValues,
    });

    // Erreur renvoyée par une action hors formulaire (validation d'une version obsolète…).
    useEffect(() => {
        if (serverErrors.document) setErreurGenerale(serverErrors.document);
    }, [serverErrors]);

    const validerEtape = async (index: number) => {
        if (index === ETAPE_FICHIER) {
            const erreur = erreurPdf(fichier, fichierObligatoire);
            setErreurFichier(erreur);
            return !erreur;
        }
        return trigger(STEPS[index].fields, { shouldFocus: true });
    };

    const allerA = async (cible: number) => {
        for (let index = wizard.current; index < cible; index++) {
            if (!(await validerEtape(index))) {
                wizard.goTo(index);
                return;
            }
        }
        wizard.goTo(cible);
    };

    const enregistrer = async (valeurs: Valeurs) => {
        if (!urls.submit) return;
        setErreurGenerale(null);
        setEnvoi(true);
        const data: Record<string, string | File> = {
            ...valeurs,
            type: document.type,
        };
        if (document.id) data.lock_version = String(document.lock_version);
        if (fichier) data.file = fichier;
        await envoyer<Valeurs>(urls.submit, data, {
            method: document.id ? 'put' : 'post',
            forceFormData: true,
            onError: (erreurs) => {
                // Erreurs Laravel : sous les champs, puis retour sur la première étape concernée.
                let premiere = STEPS.length - 1;
                const autres: string[] = [];
                Object.entries(erreurs).forEach(([cle, message]) => {
                    const etape = STEPS.findIndex((step) => step.fields.includes(cle as Champ));
                    if (etape >= 0) {
                        setError(cle as Champ, { type: 'server', message });
                        premiere = Math.min(premiere, etape);
                    } else if (cle === 'file') {
                        setErreurFichier(message);
                        premiere = Math.min(premiere, ETAPE_FICHIER);
                    } else {
                        autres.push(message);
                    }
                });
                if (autres.length) setErreurGenerale(autres.join(' '));
                wizard.goTo(premiere);
            },
        });
        setEnvoi(false);
    };

    const suivant = async () => {
        if (wizard.isLast) {
            for (let index = 0; index < STEPS.length - 1; index++) {
                if (!(await validerEtape(index))) {
                    wizard.goTo(index);
                    return;
                }
            }
            void handleSubmit(enregistrer)();
            return;
        }
        if (await validerEtape(wizard.current)) wizard.next();
    };

    const publier = () => {
        if (!urls.publish) return;
        router.post(
            urls.publish,
            { lock_version: document.lock_version },
            {
                preserveScroll: true,
                onStart: () => setValidationEnCours(true),
                onFinish: () => {
                    setValidationEnCours(false);
                    setValidation(false);
                },
            },
        );
    };

    const retirer = () => {
        if (!urls.destroy) return;
        router.delete(urls.destroy, {
            onStart: () => setRetraitEnCours(true),
            onFinish: () => {
                setRetraitEnCours(false);
                setRetraitDemande(false);
            },
        });
    };

    const valeurs = watch();
    const modifie = isDirty || fichier !== null;
    const decrit = (champ: Champ) => (errors[champ] ? `${champ}-error` : undefined);

    return (
        <AdminLayout title={document.id ? document.title || document.type_label : 'Téléverser un document'}>
            <PageHeader
                title={document.type_label}
                description={
                    <span className="flex flex-wrap items-center gap-2">
                        {document.id ? (
                            <>
                                <StatutDocumentBadge status={document.status} />
                                <span>
                                    Dernière modification le {dateFr(document.updated_at)} à {document.updated_at?.slice(11, 16)}
                                </span>
                            </>
                        ) : (
                            'Importez le document préparé au format PDF. Les signatures éventuelles doivent déjà figurer dans le fichier.'
                        )}
                    </span>
                }
                actions={
                    <>
                        <a
                            href={urls.index}
                            className={cn(
                                buttonVariants({
                                    variant: 'outline',
                                    size: 'sm',
                                }),
                                'h-10 px-4',
                            )}
                        >
                            <ArrowLeft {...ICON} aria-hidden /> Retour aux documents
                        </a>
                        {urls.pdf && (
                            <a
                                href={urls.pdf}
                                target="_blank"
                                rel="noopener"
                                className={cn(
                                    buttonVariants({
                                        variant: 'secondary',
                                        size: 'sm',
                                    }),
                                    'h-10 px-4',
                                )}
                            >
                                <FileText {...ICON} aria-hidden /> Consulter le PDF
                            </a>
                        )}
                        {urls.destroy && (
                            <Button
                                variant="outline"
                                size="sm"
                                className="h-10 px-4 text-red-700 hover:bg-red-50 hover:text-red-800"
                                onClick={() => setRetraitDemande(true)}
                            >
                                <Trash2 {...ICON} aria-hidden /> Retirer le document
                            </Button>
                        )}
                    </>
                }
            />

            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    void suivant();
                }}
            >
                <Wizard
                    steps={STEPS}
                    current={wizard.current}
                    reached={wizard.reached}
                    onStepClick={(index) => void allerA(index)}
                    onPrevious={wizard.previous}
                    onNext={() => void suivant()}
                    cancelUrl={urls.index}
                    submitting={envoi}
                    submitLabel="Enregistrer le document"
                    submitDisabled={!urls.submit}
                >
                    {erreurGenerale && <FormAlert>{erreurGenerale}</FormAlert>}

                    {wizard.current === 0 && (
                        <>
                            <Field id="title" label="Titre du document" required error={errors.title?.message}>
                                <Input id="title" maxLength={200} invalid={!!errors.title} aria-describedby={decrit('title')} {...register('title')} />
                            </Field>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field id="reference" label="Référence" error={errors.reference?.message} hint="Facultatif.">
                                    <Input
                                        id="reference"
                                        maxLength={100}
                                        placeholder="Ex. REG-2026-001"
                                        invalid={!!errors.reference}
                                        aria-describedby={decrit('reference')}
                                        {...register('reference')}
                                    />
                                </Field>
                                <Field id="version_label" label="Version" required error={errors.version_label?.message}>
                                    <Input
                                        id="version_label"
                                        maxLength={30}
                                        placeholder="Ex. 1.0"
                                        invalid={!!errors.version_label}
                                        aria-describedby={decrit('version_label')}
                                        {...register('version_label')}
                                    />
                                </Field>
                            </div>
                        </>
                    )}

                    {wizard.current === 1 && (
                        <>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field id="institution" label="Établissement" required error={errors.institution?.message}>
                                    <Input
                                        id="institution"
                                        maxLength={200}
                                        invalid={!!errors.institution}
                                        aria-describedby={decrit('institution')}
                                        {...register('institution')}
                                    />
                                </Field>
                                <Field id="scope_label" label="Résidence ou périmètre concerné" error={errors.scope_label?.message} hint="Facultatif.">
                                    <Input
                                        id="scope_label"
                                        maxLength={150}
                                        placeholder="Ex. Ensemble des résidences"
                                        invalid={!!errors.scope_label}
                                        {...register('scope_label')}
                                    />
                                </Field>
                                <Field id="academic_year" label="Année universitaire" error={errors.academic_year?.message} hint="Facultatif.">
                                    <Input
                                        id="academic_year"
                                        maxLength={30}
                                        placeholder="Ex. 2026-2027"
                                        invalid={!!errors.academic_year}
                                        {...register('academic_year')}
                                    />
                                </Field>
                                <Field id="effective_date" label="Date d’application" error={errors.effective_date?.message} hint="Facultatif.">
                                    <Controller
                                        control={control}
                                        name="effective_date"
                                        render={({ field }) => (
                                            <DatePicker
                                                id="effective_date"
                                                value={field.value}
                                                onChange={field.onChange}
                                                invalid={!!errors.effective_date}
                                                className="h-11"
                                            />
                                        )}
                                    />
                                </Field>
                            </div>
                        </>
                    )}

                    {wizard.current === ETAPE_FICHIER && (
                        <>
                            {document.file_name && (
                                <div className="flex flex-col gap-2 rounded-brand border bg-surface px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                                    <span className="flex min-w-0 items-center gap-2">
                                        <FileText className="size-4 shrink-0 text-primary" aria-hidden />
                                        <span className="truncate">
                                            Fichier actuel : <strong className="font-medium text-gray-900">{document.file_name}</strong>
                                        </span>
                                    </span>
                                    {urls.pdf && (
                                        <a href={urls.pdf} target="_blank" rel="noopener" className="text-sm font-medium text-primary hover:underline">
                                            Consulter le PDF
                                        </a>
                                    )}
                                </div>
                            )}
                            {document.ancien_editeur && (
                                <FormAlert>Ce document a été rédigé avec l’ancien éditeur. Téléversez sa version PDF pour le mettre à jour.</FormAlert>
                            )}
                            <SectionTitle
                                hint={document.file_name ? 'Choisissez un fichier pour le remplacer, ou passez cette étape pour le conserver.' : 'Obligatoire.'}
                            >
                                Document PDF
                            </SectionTitle>
                            <div className="space-y-1.5">
                                <FileDrop
                                    id="file"
                                    file={fichier}
                                    accept=".pdf,application/pdf"
                                    title={document.file_name ? 'Choisir un nouveau PDF' : 'Choisir le PDF'}
                                    hint={`Format PDF, ${PDF_MAX_MO} Mo maximum.`}
                                    invalid={!!erreurFichier}
                                    onChange={(choisi) => {
                                        setFichier(choisi);
                                        setErreurFichier(erreurPdf(choisi, fichierObligatoire));
                                    }}
                                />
                                {erreurFichier && (
                                    <p role="alert" className="text-xs font-medium text-red-600">
                                        {erreurFichier}
                                    </p>
                                )}
                                {fichier && (
                                    <Button variant="ghost" size="sm" className="h-8" onClick={() => setFichier(null)}>
                                        Retirer ce fichier
                                    </Button>
                                )}
                            </div>
                        </>
                    )}

                    {wizard.current === 3 && (
                        <div className="space-y-4">
                            <RecapSection
                                title="Identification"
                                onEdit={() => wizard.goTo(0)}
                                rows={[
                                    ['Type', document.type_label],
                                    ['Titre', valeurs.title],
                                    ['Référence', valeurs.reference || 'Sans référence'],
                                    ['Version', valeurs.version_label],
                                ]}
                            />
                            <RecapSection
                                title="Application"
                                onEdit={() => wizard.goTo(1)}
                                rows={[
                                    ['Établissement', valeurs.institution],
                                    ['Périmètre', valeurs.scope_label],
                                    ['Année universitaire', valeurs.academic_year],
                                    ['Date d’application', valeurs.effective_date ? dateFr(valeurs.effective_date) : ''],
                                ]}
                            />
                            <RecapSection
                                title="Fichier"
                                onEdit={() => wizard.goTo(ETAPE_FICHIER)}
                                rows={[['Document PDF', fichier ? `${fichier.name} (nouveau)` : (document.file_name ?? '')]]}
                            />
                            {document.status === 'published' && (
                                <p className="text-sm text-muted">
                                    L’enregistrement repasse le document en brouillon : pensez à valider la nouvelle version ensuite.
                                </p>
                            )}
                        </div>
                    )}
                </Wizard>
            </form>

            {(urls.publish || versions.length > 0) && (
                <div className="mt-6 grid grid-cols-1 gap-6 lg:ml-[264px] xl:grid-cols-2">
                    {urls.publish && (
                        <section className="flex flex-col gap-3 rounded-brand border bg-white p-4 md:p-6">
                            <div className="flex items-start gap-3">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-brand bg-soft text-primary">
                                    <BadgeCheck {...ICON} aria-hidden />
                                </span>
                                <div className="min-w-0">
                                    <h2 className="text-base font-medium text-gray-900">Valider le document enregistré</h2>
                                    <p className="text-sm text-muted">
                                        La validation conserve le fichier dans l’historique et le met à disposition dans les documents de la résidence.
                                    </p>
                                </div>
                            </div>
                            {modifie && <p className="text-sm text-amber-800">Enregistrez d’abord vos modifications pour pouvoir valider cette version.</p>}
                            <div className="flex justify-end">
                                <Button className="h-10" disabled={modifie} onClick={() => setValidation(true)}>
                                    <BadgeCheck className="size-4" aria-hidden /> Valider cette version
                                </Button>
                            </div>
                        </section>
                    )}
                    {versions.length > 0 && (
                        <section className="rounded-brand border bg-white">
                            <div className="flex items-center gap-3 border-b px-4 py-4 md:px-6">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-brand bg-soft text-primary">
                                    <History {...ICON} aria-hidden />
                                </span>
                                <div>
                                    <h2 className="text-base font-medium text-gray-900">Historique des versions validées</h2>
                                    <p className="text-sm text-muted">{versions.length} révision(s)</p>
                                </div>
                            </div>
                            <ul className="divide-y">
                                {versions.map((version) => (
                                    <li key={version.id} className="flex items-center justify-between gap-3 px-4 py-3 text-sm md:px-6">
                                        <span>
                                            <span className="font-medium text-gray-900">Révision {version.revision}</span>
                                            <span className="text-muted">
                                                {' '}
                                                · {dateFr(version.created_at)} à {version.created_at?.slice(11, 16)}
                                            </span>
                                        </span>
                                        {version.pdf && (
                                            <a
                                                href={version.pdf}
                                                target="_blank"
                                                rel="noopener"
                                                className="flex items-center gap-1.5 font-medium text-primary hover:underline"
                                            >
                                                <FileText className="size-4" aria-hidden /> PDF
                                            </a>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                </div>
            )}

            <ConfirmDialog
                open={validation}
                onOpenChange={setValidation}
                title="Valider cette version ?"
                description={
                    <>
                        <strong className="font-medium text-gray-900">{document.title}</strong> (version {document.version_label}) sera mis à disposition des
                        résidents et conservé dans l’historique.
                    </>
                }
                confirmLabel="Valider la version"
                confirmIcon={<BadgeCheck className="size-4" aria-hidden />}
                processing={validationEnCours}
                onConfirm={publier}
            />

            <ConfirmRetrait document={retraitDemande ? document : null} onOpenChange={setRetraitDemande} processing={retraitEnCours} onConfirm={retirer} />
        </AdminLayout>
    );
}

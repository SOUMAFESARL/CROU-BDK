import { Trash2 } from 'lucide-react';
import { ConfirmDialog } from '@/components/feedback/confirm-dialog';
import { Badge } from '@/components/ui/badge';

/** État d'un document de la résidence : version validée (en ligne) ou brouillon. */
export function StatutDocumentBadge({ status }: { status: 'published' | 'draft' }) {
    return status === 'published' ? <Badge variant="success">Validé</Badge> : <Badge variant="warning">Brouillon</Badge>;
}

/** Taille maximale du PDF acceptée par `LegalDocumentController::validated()`. */
export const PDF_MAX_MO = 2;

/** Contrôle du fichier côté navigateur (le serveur revérifie l'en-tête `%PDF-`). */
export function erreurPdf(fichier: File | null, obligatoire: boolean): string | null {
    if (!fichier) return obligatoire ? 'Téléversez le document PDF.' : null;
    const estPdf = fichier.type === 'application/pdf' || fichier.name.toLowerCase().endsWith('.pdf');
    if (!estPdf) return 'Le document doit être un PDF.';
    if (fichier.size > PDF_MAX_MO * 1024 * 1024) return `Le document ne doit pas dépasser ${PDF_MAX_MO} Mo.`;
    return null;
}

/** Confirmation du retrait d'un document (liste et fiche du document). */
export function ConfirmRetrait({
    document,
    onOpenChange,
    processing,
    onConfirm,
}: {
    document: { title: string; status: 'published' | 'draft' } | null;
    onOpenChange: (ouvert: boolean) => void;
    processing: boolean;
    onConfirm: () => void;
}) {
    return (
        <ConfirmDialog
            open={document !== null}
            onOpenChange={onOpenChange}
            title="Retirer ce document ?"
            description={
                document && (
                    <>
                        <strong className="font-medium text-gray-900">{document.title}</strong>
                        {document.status === 'published' ? ' ne sera plus disponible pour les résidents. ' : ' sera supprimé. '}
                        Le fichier PDF et l’historique des versions validées seront définitivement supprimés.
                    </>
                )
            }
            confirmLabel="Retirer le document"
            confirmIcon={<Trash2 className="size-4" aria-hidden />}
            danger
            processing={processing}
            onConfirm={onConfirm}
        />
    );
}

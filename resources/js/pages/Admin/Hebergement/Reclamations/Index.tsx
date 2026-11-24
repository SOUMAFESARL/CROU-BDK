import { CalendarClock, ListChecks, type LucideIcon } from 'lucide-react';
import { AdminLayout } from '@/components/layout/admin-layout';
import { CartesModules, type ModuleRubrique } from '@/components/layout/rubriques-modules';
import { PageHeader } from '@/components/ui/page-header';

const ICONES: Record<string, LucideIcon> = { liste: ListChecks, calendrier: CalendarClock };

/** Accueil des réclamations : cartes de suivi et de paramétrage, comme l'accueil du paramétrage hébergement. */
export default function ReclamationsIndex({ modules }: { modules: ModuleRubrique[] }) {
    return (
        <AdminLayout title="Réclamations">
            <PageHeader title="Réclamations" description="Réclamations déposées par les étudiants sur leur demande d’hébergement et périodes d’ouverture." />
            <CartesModules label="Réclamations" modules={modules} icones={ICONES} />
        </AdminLayout>
    );
}

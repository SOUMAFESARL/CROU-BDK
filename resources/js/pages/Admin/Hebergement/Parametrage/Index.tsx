import { BedDouble, Building, CalendarClock, ChartPie, FileText, Gavel, Hotel, ListChecks, type LucideIcon, Sofa, Star, Wallet } from 'lucide-react';
import { AdminLayout } from '@/components/layout/admin-layout';
import { RubriquesModules, type Rubrique } from '@/components/layout/rubriques-modules';
import { PageHeader } from '@/components/ui/page-header';

const ICONES: Record<string, LucideIcon> = {
    city: Hotel,
    sofa: Sofa,
    bed: BedDouble,
    pie: ChartPie,
    building: Building,
    check: ListChecks,
    gavel: Gavel,
    star: Star,
    file: FileText,
    calendar: CalendarClock,
    wallet: Wallet,
};

/** Accueil du paramétrage hébergement (remplace `parametrage-herb/index.blade.php`) : modules accessibles par rubrique. */
export default function ParametrageHebergementIndex({ sections }: { sections: Rubrique[] }) {
    return (
        <AdminLayout title="Paramétrage">
            <PageHeader title="Paramétrage du module hébergement" description="Référentiels du parc, règles des campagnes, documents et parcours d’entrée des résidents." />

            <RubriquesModules sections={sections} icones={ICONES} vide="Aucun paramétrage n’est accessible avec vos droits." />
        </AdminLayout>
    );
}

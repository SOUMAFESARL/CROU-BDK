import { ArrowUpRight, BedDouble, Building, CalendarClock, ChartPie, FileText, Hotel, ListChecks, type LucideIcon, Sofa, Star } from 'lucide-react';
import { AdminLayout } from '@/components/layout/admin-layout';
import { ICON } from '@/components/layout/app-sidebar';
import { PageHeader } from '@/components/ui/page-header';

type Module = { label: string; icon: string; description: string; href: string };
type Section = { titre: string; description: string; modules: Module[] };

const ICONES: Record<string, LucideIcon> = {
    city: Hotel,
    sofa: Sofa,
    bed: BedDouble,
    pie: ChartPie,
    building: Building,
    check: ListChecks,
    star: Star,
    file: FileText,
    calendar: CalendarClock,
};

/** Accueil du paramétrage hébergement (remplace `parametrage-herb/index.blade.php`) : modules accessibles par rubrique. */
export default function ParametrageHebergementIndex({ sections }: { sections: Section[] }) {
    return (
        <AdminLayout title="Paramétrage">
            <PageHeader title="Paramétrage du module hébergement" description="Référentiels du parc, règles des campagnes, documents et parcours d’entrée des résidents." />

            {sections.length === 0 ? (
                <p className="rounded-brand border bg-white p-6 text-sm text-muted">Aucun paramétrage n’est accessible avec vos droits.</p>
            ) : (
                <div className="space-y-8">
                    {sections.map((section) => (
                        <section key={section.titre} aria-labelledby={`rubrique-${section.titre}`}>
                            <div className="mb-3">
                                <h2 id={`rubrique-${section.titre}`} className="text-base font-medium text-gray-900">
                                    {section.titre}
                                </h2>
                                <p className="text-sm text-muted">{section.description}</p>
                            </div>
                            <nav aria-label={section.titre} className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {section.modules.map((module) => {
                                    const Icone = ICONES[module.icon] ?? FileText;
                                    return (
                                        <a
                                            key={module.href}
                                            href={module.href}
                                            className="group flex items-center gap-3 rounded-brand border bg-white p-3.5 shadow-[0_7px_24px_rgba(30,45,75,0.045)] transition hover:-translate-y-0.5 hover:shadow-[0_10px_25px_rgba(30,45,75,0.09)] focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                                        >
                                            <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-soft text-primary" aria-hidden>
                                                <Icone {...ICON} />
                                            </span>
                                            <span className="flex min-w-0 flex-col">
                                                <small className="truncate text-sm text-muted">{module.description}</small>
                                                <strong className="truncate text-sm font-medium text-gray-900">{module.label}</strong>
                                            </span>
                                            <ArrowUpRight
                                                className="ml-auto size-4.5 shrink-0 text-gray-400 transition-colors group-hover:text-primary"
                                                strokeWidth={1.25}
                                                aria-hidden
                                            />
                                        </a>
                                    );
                                })}
                            </nav>
                        </section>
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}

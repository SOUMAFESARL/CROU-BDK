import { ArrowUpRight, FileText, type LucideIcon } from 'lucide-react';
import { ICON } from '@/components/layout/app-sidebar';

/** Carte d'un module et rubrique de cartes (accueils du paramétrage et des réclamations, déjà filtrés par droits). */
export type ModuleRubrique = { label: string; icon: string; description: string; href: string };
export type Rubrique = { titre: string; description: string; modules: ModuleRubrique[] };

/** Rubriques d'une page d'accueil : un titre, une description et une carte par module. */
export function RubriquesModules({ sections, icones, vide }: { sections: Rubrique[]; icones: Record<string, LucideIcon>; vide: string }) {
    if (sections.length === 0) {
        return <p className="rounded-brand border bg-white p-6 text-sm text-muted">{vide}</p>;
    }

    return (
        <div className="space-y-8">
            {sections.map((section) => (
                <section key={section.titre} aria-labelledby={`rubrique-${section.titre}`}>
                    <div className="mb-3">
                        <h2 id={`rubrique-${section.titre}`} className="text-base font-medium text-gray-900">
                            {section.titre}
                        </h2>
                        <p className="text-sm text-muted">{section.description}</p>
                    </div>
                    <CartesModules label={section.titre} modules={section.modules} icones={icones} />
                </section>
            ))}
        </div>
    );
}

/** Cartes de modules alignées sur une ligne (retour à la ligne selon la largeur). */
export function CartesModules({ label, modules, icones }: { label: string; modules: ModuleRubrique[]; icones: Record<string, LucideIcon> }) {
    return (
        <nav aria-label={label} className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {modules.map((module) => {
                const Icone = icones[module.icon] ?? FileText;
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
    );
}

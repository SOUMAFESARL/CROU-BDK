import { ModuleHome, type ModuleLink } from '@/components/layout/module-home';

/** Accueil de la Gestion Hébergement (remplace `admin/hebergement/index.blade.php`). */
export default function HebergementIndex({ modules }: { modules: ModuleLink[] }) {
    return <ModuleHome title="Gestion Hébergement" description="Modules du cycle de vie du logement : parc, admission, attribution, séjour, départ et pilotage." modules={modules} />;
}

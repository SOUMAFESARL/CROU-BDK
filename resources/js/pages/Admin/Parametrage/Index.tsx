import { ModuleHome, type ModuleLink } from '@/components/layout/module-home';

/** Accueil du Paramétrage (remplace `admin/parametrage/index.blade.php`). */
export default function ParametrageIndex({ modules }: { modules: ModuleLink[] }) {
    return <ModuleHome title="Gestion Paramétrage" description="Documents, paiements, modules, droits d’accès et référentiels de l’application." modules={modules} />;
}

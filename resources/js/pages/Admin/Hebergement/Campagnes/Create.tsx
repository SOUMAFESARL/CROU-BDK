import { CampagneForm } from '@/components/campagnes/campagne-form';
import type { CreateCampagneProps } from '@/components/campagnes/types';

/** Création d'une campagne d'admission (remplace la fenêtre « Nouvelle campagne » de l'ancienne vue Blade). */
export default function CampagneCreate(props: CreateCampagneProps) {
    return <CampagneForm {...props} />;
}

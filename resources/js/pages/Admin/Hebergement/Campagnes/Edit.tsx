import { CampagneForm } from '@/components/campagnes/campagne-form';
import type { EditCampagneProps } from '@/components/campagnes/types';

/** Modification d'une campagne : même assistant que la création, prérempli avec la campagne. */
export default function CampagneEdit(props: EditCampagneProps) {
    return <CampagneForm {...props} />;
}

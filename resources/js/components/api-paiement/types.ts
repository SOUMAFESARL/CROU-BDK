export type Identifiant = {
    field: CleIdentifiant;
    label: string;
    /** Une valeur est déjà enregistrée (chiffrée) : jamais renvoyée au navigateur. */
    saved: boolean;
    /** Obligatoire pour activer le réseau. */
    required: boolean;
};

export const CLES_IDENTIFIANTS = ['api_key', 'merchant_key', 'api_user', 'subscription_key', 'request_signing_secret', 'webhook_secret'] as const;
export type CleIdentifiant = (typeof CLES_IDENTIFIANTS)[number];

export type EnvironnementMtn = 'sandbox' | 'mtnivorycoast';

export type Reseau = {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
    environment: EnvironnementMtn | null;
    api_url: string;
    credentials: Identifiant[];
    /** Adresse imposée par le connecteur (`sandbox` / `mtnivorycoast` pour MTN, `default` sinon ; vide : libre). */
    expected_urls: Partial<Record<EnvironnementMtn | 'default', string>>;
    /** Connecteur raccordé (Wave, MTN, CinetPay) ; les autres sont provisoires. */
    connecte: boolean;
    /** Réellement proposé aux étudiants pour payer. */
    payable: boolean;
    webhook_url: string | null;
    update_url: string | null;
};

export const ENVIRONNEMENTS: { value: EnvironnementMtn; label: string }[] = [
    { value: 'mtnivorycoast', label: 'Côte d’Ivoire (XOF)' },
];

/** Adresse attendue par le serveur pour ce réseau (`PaymentProviderController::expectedUrl()`). */
export const urlAttendue = (reseau: Reseau, environnement: EnvironnementMtn | null) =>
    reseau.code === 'mtn' ? reseau.expected_urls[environnement ?? 'mtnivorycoast'] : reseau.expected_urls.default;

/** Note affichée pour chaque connecteur (reprise de l'ancienne page). */
export const noteReseau = (reseau: Reseau): string | null => {
    switch (reseau.code) {
        case 'wave':
            return 'Dans Wave Business, enregistrez la notification checkout.session.completed avec signature HMAC et l’adresse ci-dessous.';
        default:
            return null;
    }
};

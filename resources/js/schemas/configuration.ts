import { z } from 'zod';
import { telephoneFacultatif } from '@/lib/telephone';

/**
 * Schémas de la page Configuration (`ConfigurationRequest` reste la source de vérité).
 * Les champs indispensables au fonctionnement (nom du CROU…) sont exigés ici.
 */

const texte = (max: number, label: string) => z.string().trim().max(max, `${label} : ${max} caractères au maximum.`);
const requis = (max: number, label: string, message: string) => texte(max, label).min(1, message);
const email = (max: number, label: string) =>
    texte(max, label).refine((v) => v === '' || z.email().safeParse(v).success, 'Veuillez saisir une adresse e-mail valide.');
const url = (max: number, label: string, https = false) =>
    texte(max, label).refine(
        (v) => v === '' || (z.url().safeParse(v).success && (!https || v.toLowerCase().startsWith('https://'))),
        https ? 'Saisissez une adresse https:// valide.' : 'Veuillez saisir une URL valide (ex. https://crou-bondoukou.ci).',
    );

export const LOCALES = { fr: 'Français', en: 'English', es: 'Español' } as const;
export const FUSEAUX = { 'Africa/Abidjan': 'Africa/Abidjan (GMT+0)', 'Europe/Paris': 'Europe/Paris (GMT+2)', UTC: 'UTC' } as const;
export const FORMATS_DATE = { 'd/m/Y': 'JJ/MM/AAAA', 'm/d/Y': 'MM/JJ/AAAA', 'Y-m-d': 'AAAA-MM-JJ' } as const;
export const PAYS = ["Côte d'Ivoire", 'Sénégal', 'Mali', 'Burkina Faso', 'Guinée', 'Togo', 'Bénin', 'Niger', 'France'] as const;
export const PILOTES_MAIL = { smtp: 'SMTP', mailgun: 'Mailgun', sendgrid: 'SendGrid', ses: 'Amazon SES', log: 'Journal (tests)' } as const;
export const FOURNISSEURS_SMS = {
    helper_json: 'Passerelle EnvoiSMS (JSON / Bearer)',
    orange_sms_api: 'Orange SMS API',
    twilio: 'Twilio',
    africas_talking: 'Africa’s Talking',
    infobip: 'InfoBip',
    cinetpay_sms: 'CinetPay SMS',
} as const;

const cleDe = <T extends Record<string, string>>(liste: T) => z.enum(Object.keys(liste) as [keyof T & string, ...(keyof T & string)[]]);

export const generalSchema = z.object({
    app_name: requis(100, 'Le nom du CROU', 'Renseignez le nom du CROU.'),
    company_name: requis(255, 'Le nom complet', 'Renseignez le nom complet du CROU.'),
    locale: cleDe(LOCALES),
    timezone: requis(60, 'Le fuseau horaire', 'Choisissez le fuseau horaire.'),
    date_format: cleDe(FORMATS_DATE),
    maintenance_mode: z.boolean(),
    activity_log: z.boolean(),
});
export type GeneralValeurs = z.infer<typeof generalSchema>;

export const profilSchema = z.object({
    address: texte(255, 'L’adresse'),
    country: texte(80, 'Le pays').min(1, 'Choisissez le pays.'),
    phone: telephoneFacultatif(),
    email: email(150, 'L’e-mail'),
    website: url(200, 'Le site web'),
    brand_color: z.string().regex(/^#[0-9A-Fa-f]{6}$/, 'La couleur doit être au format hexadécimal (#RRGGBB).'),
    logo: z
        .instanceof(File)
        .nullable()
        .refine((f) => !f || /^image\/(png|jpe?g|svg\+xml|webp)$/.test(f.type), 'Le logo doit être une image PNG, JPG, SVG ou WEBP.')
        .refine((f) => !f || f.size <= 2 * 1024 * 1024, 'Le logo ne doit pas dépasser 2 Mo.'),
});
export type ProfilValeurs = z.infer<typeof profilSchema>;

export const EVENEMENTS = [
    { cle: 'notif_new_user', label: 'Nouvel utilisateur inscrit', hint: 'Alerte de l’administration à chaque inscription' },
    { cle: 'notif_new_order', label: 'Nouvelle demande', hint: 'Confirmation à l’étudiant et alerte de l’administration' },
    { cle: 'notif_payment_ok', label: 'Paiement réussi', hint: 'Reçu envoyé à l’étudiant' },
    { cle: 'notif_payment_fail', label: 'Échec de paiement', hint: 'Alerte de l’étudiant et de l’administration' },
    { cle: 'notif_low_stock', label: 'Stock faible', hint: 'Alerte sous le seuil d’équipements' },
    { cle: 'notif_report', label: 'Rapport quotidien', hint: 'Résumé envoyé chaque matin' },
] as const;
export type CleEvenement = (typeof EVENEMENTS)[number]['cle'];

/** Tous les champs sont facultatifs : chaque canal (e-mail, SMS) se configure indépendamment. Un champ secret vide conserve la valeur enregistrée. */
export const notificationsSchema = z.object({
    email_enabled: z.boolean(),
    mail_driver: cleDe(PILOTES_MAIL),
    mail_host: texte(150, 'L’hôte SMTP'),
    mail_port: z
        .string()
        .trim()
        .refine((v) => v === '' || (/^\d+$/.test(v) && Number(v) >= 1 && Number(v) <= 65535), 'Le port doit être un nombre entre 1 et 65535.'),
    mail_username: texte(150, 'L’identifiant SMTP'),
    mail_password: z.string().max(255, 'Le mot de passe : 255 caractères au maximum.'),
    mail_from_name: texte(100, 'Le nom de l’expéditeur'),
    mail_from_address: email(150, 'L’adresse de l’expéditeur'),
    sms_enabled: z.boolean(),
    sms_provider: texte(50, 'Le fournisseur SMS'),
    sms_api_url: url(2048, 'L’URL de la passerelle', true),
    sms_api_key: z.string().trim().max(255, 'La clé API : 255 caractères au maximum.'),
    sms_sender: texte(20, 'L’expéditeur SMS'),
    ...(Object.fromEntries(EVENEMENTS.map((e) => [e.cle, z.boolean()])) as Record<CleEvenement, z.ZodBoolean>),
});
export type NotificationsValeurs = z.infer<typeof notificationsSchema>;

/** Nombre saisi sous forme de texte : entier (ou décimal) borné, champ obligatoire. */
const nombreTexte = (label: string, min: number, max: number, decimal = false) =>
    z
        .string()
        .trim()
        .min(1, `Renseignez ${label}.`)
        .refine((v) => (decimal ? /^\d+([.,]\d{1,2})?$/ : /^\d+$/).test(v), decimal ? 'Saisissez un nombre (2 décimales au maximum).' : 'Saisissez un nombre entier.')
        .refine((v) => Number(v.replace(',', '.')) >= min && Number(v.replace(',', '.')) <= max, `Valeur entre ${min} et ${max}.`);

// ─── Paiements ────────────────────────────────────────────────────────────────

/** Modes acceptés : `cash` et `mobile` filtrent aussi les méthodes de saisie au guichet et le paiement en ligne. */
export const MODES_PAIEMENT = [
    { cle: 'cash', label: 'Espèces', hint: 'Paiement au guichet du CROU' },
    { cle: 'mobile', label: 'Mobile Money', hint: 'Orange, MTN, Moov, Wave (et paiement en ligne)' },
    { cle: 'transfer', label: 'Virement bancaire', hint: 'Saisi au guichet avec la méthode « Autre »' },
    { cle: 'card', label: 'Carte bancaire', hint: 'Saisi au guichet avec la méthode « Autre »' },
] as const;

export const paiementsSchema = z
    .object({
        methods: z.array(z.enum(['cash', 'mobile', 'transfer', 'card'])).min(1, 'Acceptez au moins un mode de paiement.'),
        online_payment_enabled: z.boolean(),
        auto_receipt: z.boolean(),
        min_amount: nombreTexte('le montant minimum', 0, 100_000_000),
        daily_limit: nombreTexte('le plafond journalier', 0, 1_000_000_000),
    })
    .superRefine((v, ctx) => {
        if (v.online_payment_enabled && !v.methods.includes('mobile')) {
            ctx.addIssue({ code: 'custom', path: ['online_payment_enabled'], message: 'Le paiement en ligne passe par Mobile Money : acceptez ce mode ou désactivez le paiement en ligne.' });
        }
        if (Number(v.daily_limit) > 0 && Number(v.daily_limit) < Number(v.min_amount)) {
            ctx.addIssue({ code: 'custom', path: ['daily_limit'], message: 'Le plafond journalier doit être supérieur au montant minimum (ou 0 pour illimité).' });
        }
    });
export type PaiementsValeurs = z.infer<typeof paiementsSchema>;

// ─── Taxes ────────────────────────────────────────────────────────────────────

export const AFFICHAGES_PRIX = { TTC: 'Prix TTC (TVA incluse)', HT: 'Prix HT (hors taxe)' } as const;

export const taxesSchema = z.object({
    tva_enabled: z.boolean(),
    tva_rate: nombreTexte('le taux de TVA', 0, 100, true),
    tva_number: texte(60, 'Le numéro de TVA'),
    price_display: cleDe(AFFICHAGES_PRIX),
    tva_on_services: z.boolean(),
    digital_tax_rate: nombreTexte('le taux', 0, 100, true),
    service_fee: nombreTexte('les frais de service', 0, 100_000_000),
});
export type TaxesValeurs = z.infer<typeof taxesSchema>;

// ─── Devises ──────────────────────────────────────────────────────────────────

export const DEVISES = {
    XOF: 'XOF — Franc CFA (UEMOA)',
    XAF: 'XAF — Franc CFA (CEMAC)',
    EUR: 'EUR — Euro',
    USD: 'USD — Dollar américain',
    GHS: 'GHS — Cedi ghanéen',
    NGN: 'NGN — Naira nigérian',
    KES: 'KES — Shilling kényan',
} as const;
export const DEVISES_SECONDAIRES = { ...DEVISES, GBP: 'GBP — Livre sterling' } as const;
export const POSITIONS_SYMBOLE = { after: 'Après (100 FCFA)', before: 'Avant (FCFA 100)' } as const;
export const SEPARATEURS = { espace: 'Espace (1 000)', point: 'Point (1.000)', virgule: 'Virgule (1,000)' } as const;
export const FREQUENCES = { hourly: 'Toutes les heures', daily: 'Quotidienne', weekly: 'Hebdomadaire', manual: 'Manuelle' } as const;

export const devisesSchema = z.object({
    base_currency: cleDe(DEVISES),
    currency_symbol: requis(10, 'Le symbole', 'Renseignez le symbole affiché.'),
    symbol_position: cleDe(POSITIONS_SYMBOLE),
    thousands_separator: cleDe(SEPARATEURS),
    secondary_currencies: z.array(cleDe(DEVISES_SECONDAIRES)),
    auto_rate: z.boolean(),
    rate_frequency: cleDe(FREQUENCES),
});
export type DevisesValeurs = z.infer<typeof devisesSchema>;

// ─── Sécurité ─────────────────────────────────────────────────────────────────

/** `contexte` : adresse IP et protocole de l'administrateur connecté, pour ne pas se couper l'accès. */
export const securiteSchema = (contexte: { ip: string; https: boolean }) =>
    z
        .object({
            two_factor_auth: z.boolean(),
            session_lifetime: nombreTexte('la durée de session', 5, 1440),
            allow_multiple_sessions: z.boolean(),
            password_min_length: nombreTexte('la longueur minimale', 6, 32),
            password_require_uppercase: z.boolean(),
            password_require_number: z.boolean(),
            password_require_special: z.boolean(),
            max_login_attempts: nombreTexte('le nombre de tentatives', 1, 100),
            lockout_duration: nombreTexte('la durée de blocage', 1, 1440),
            ip_restriction: z.boolean(),
            allowed_ips: texte(2000, 'La liste des adresses IP'),
            force_https: z.boolean(),
        })
        .superRefine((v, ctx) => {
            if (v.ip_restriction) {
                const ips = v.allowed_ips.split(/[\s,;]+/).filter(Boolean);
                const exiger = (message: string) => ctx.addIssue({ code: 'custom', path: ['allowed_ips'], message });
                if (ips.length === 0) exiger('Indiquez au moins une adresse IP autorisée.');
                else if (ips.some((ip) => !z.ipv4().safeParse(ip).success && !z.ipv6().safeParse(ip).success)) exiger('Saisissez des adresses IP valides.');
                else if (contexte.ip && !ips.includes(contexte.ip)) exiger(`Ajoutez votre adresse IP actuelle (${contexte.ip}), sinon vous perdrez l’accès.`);
            }
            if (v.force_https && !contexte.https) {
                ctx.addIssue({ code: 'custom', path: ['force_https'], message: 'Activez HTTPS uniquement depuis une connexion HTTPS : sinon l’application deviendrait inaccessible.' });
            }
        });
export type SecuriteValeurs = z.infer<ReturnType<typeof securiteSchema>>;

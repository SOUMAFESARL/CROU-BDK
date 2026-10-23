import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import {
    Building,
    Building2,
    ChevronRight,
    GraduationCap,
    Home,
    Layers,
    LocateFixed,
    Mail,
    MapPin,
    Maximize2,
    Navigation,
    Phone,
    Search,
    X,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import { ICON } from '@/components/layout/app-sidebar';
import { EtudiantLayout } from '@/components/layout/etudiant-layout';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type Position = { lat: number; lng: number };

type CiteCarte = {
    id: string;
    name: string;
    type: string | null;
    adresse: string | null;
    telephone: string | null;
    email: string | null;
    position: Position | null;
    services: string[];
};

type BatimentCarte = { id: string; name: string; cite_id: string; occupation: string | null; etages: number | null; position: Position | null };

type CampusCarte = { id: string; name: string; adresse: string | null; telephone: string | null; position: Position };

type Residence = { cite_id: string | null; batiment_id: string | null; chambre: string | null };

type CarteProps = {
    cites: CiteCarte[];
    batiments: BatimentCarte[];
    campus: CampusCarte[];
    residence: Residence | null;
};

type Calque = 'cites' | 'batiments' | 'campus';
type Fond = 'plan' | 'satellite';
type Selection = { type: 'cite' | 'batiment' | 'campus'; id: string } | null;

/** Bondoukou : centre par défaut quand aucun élément n'est géolocalisé. */
const CENTRE_DEFAUT: L.LatLngExpression = [8.0402, -2.8];

const FONDS: Record<Fond, { url: string; attribution: string; maxNativeZoom: number }> = {
    plan: { url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', attribution: '© OpenStreetMap', maxNativeZoom: 19 },
    satellite: {
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        attribution: 'Imagerie © Esri',
        maxNativeZoom: 18,
    },
};

// Pictogrammes Lucide en SVG brut pour les marqueurs Leaflet (HTML hors React).
const SVG = (contenu: string, taille: number) =>
    `<svg xmlns="http://www.w3.org/2000/svg" width="${taille}" height="${taille}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">${contenu}</svg>`;
const PICTOS: Record<'cite' | 'batiment' | 'campus', string> = {
    cite: '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>',
    batiment:
        '<rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/>',
    campus: '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
};

function icone(type: 'cite' | 'batiment' | 'campus', { choisi = false, chezMoi = false } = {}): L.DivIcon {
    const taille = type === 'cite' ? 38 : type === 'campus' ? 34 : 30;
    const couleurs = type === 'campus' ? 'bg-accent text-gray-900' : type === 'cite' ? 'bg-primary text-white' : 'bg-white text-primary border-primary';

    return L.divIcon({
        className: '',
        html: `<div class="carte-marqueur ${couleurs} ${choisi ? 'carte-marqueur--choisi' : ''} ${chezMoi ? 'carte-marqueur--residence' : ''} ${type === 'batiment' ? 'rounded-[8px]' : 'rounded-full'}" style="width:${taille}px;height:${taille}px">${SVG(PICTOS[type], Math.round(taille * 0.5))}</div>`,
        iconSize: [taille, taille],
        iconAnchor: [taille / 2, taille / 2],
        tooltipAnchor: [0, -taille / 2],
    });
}

/** Infobulle au survol : nom en texte brut (jamais interprété comme HTML). */
function infobulle(nom: string, detail: string | null): HTMLElement {
    const racine = document.createElement('div');
    const titre = document.createElement('strong');
    titre.textContent = nom;
    racine.appendChild(titre);
    if (detail) {
        const sous = document.createElement('div');
        sous.textContent = detail;
        racine.appendChild(sous);
    }
    return racine;
}

const normaliser = (texte: string) =>
    texte
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase();

/** Lien d'itinéraire (Google Maps) vers un point de la carte. */
const itineraire = ({ lat, lng }: Position) => `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;

export default function Carte({ cites, batiments, campus, residence }: CarteProps) {
    const conteneur = useRef<HTMLDivElement>(null);
    const carte = useRef<L.Map | null>(null);
    const fondCouche = useRef<L.TileLayer | null>(null);
    const groupes = useRef<Record<Calque, L.LayerGroup> | null>(null);
    const positionCouche = useRef<L.LayerGroup | null>(null);

    const [recherche, setRecherche] = useState('');
    const [calques, setCalques] = useState<Record<Calque, boolean>>({ cites: true, batiments: true, campus: true });
    const [fond, setFond] = useState<Fond>('plan');
    const [selection, setSelection] = useState<Selection>(null);
    const [localisation, setLocalisation] = useState(false);

    const batimentsParCite = useMemo(() => {
        const groupes = new Map<string, BatimentCarte[]>();
        batiments.forEach((batiment) => groupes.set(batiment.cite_id, [...(groupes.get(batiment.cite_id) ?? []), batiment]));
        return groupes;
    }, [batiments]);

    const maCite = residence?.cite_id ? cites.find((cite) => cite.id === residence.cite_id) : undefined;
    const monBatiment = residence?.batiment_id ? batiments.find((b) => b.id === residence.batiment_id) : undefined;

    const batimentChoisi = selection?.type === 'batiment' ? batiments.find((b) => b.id === selection.id) : undefined;
    const citeChoisie =
        selection?.type === 'cite' ? cites.find((cite) => cite.id === selection.id) : batimentChoisi ? cites.find((cite) => cite.id === batimentChoisi.cite_id) : undefined;
    const campusChoisi = selection?.type === 'campus' ? campus.find((c) => c.id === selection.id) : undefined;

    // Liste filtrée : une cité reste visible si son nom, son adresse ou l'un de ses bâtiments correspond.
    const terme = normaliser(recherche.trim());
    const citesListees = useMemo(
        () =>
            cites.filter(
                (cite) =>
                    terme === '' ||
                    normaliser(`${cite.name} ${cite.type ?? ''} ${cite.adresse ?? ''}`).includes(terme) ||
                    (batimentsParCite.get(cite.id) ?? []).some((b) => normaliser(b.name).includes(terme)),
            ),
        [cites, batimentsParCite, terme],
    );
    const campusListes = useMemo(() => campus.filter((c) => terme === '' || normaliser(`${c.name} ${c.adresse ?? ''}`).includes(terme)), [campus, terme]);

    // Création de la carte (une seule fois).
    useEffect(() => {
        if (!conteneur.current) return;
        const map = L.map(conteneur.current, { zoomControl: false, maxZoom: 21 }).setView(CENTRE_DEFAUT, 13);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
        map.attributionControl.setPrefix(false);
        groupes.current = { cites: L.layerGroup().addTo(map), batiments: L.layerGroup().addTo(map), campus: L.layerGroup().addTo(map) };
        positionCouche.current = L.layerGroup().addTo(map);
        carte.current = map;

        // La carte vit dans une grille : on recalcule sa taille quand la mise en page change (barre latérale repliée…).
        const observateur = new ResizeObserver(() => map.invalidateSize());
        observateur.observe(conteneur.current);

        return () => {
            observateur.disconnect();
            map.remove();
            carte.current = null;
        };
    }, []);

    // Fond de carte.
    useEffect(() => {
        const map = carte.current;
        if (!map) return;
        fondCouche.current?.remove();
        fondCouche.current = L.tileLayer(FONDS[fond].url, { attribution: FONDS[fond].attribution, maxNativeZoom: FONDS[fond].maxNativeZoom, maxZoom: 21 }).addTo(map);
        fondCouche.current.bringToBack();
    }, [fond]);

    // Marqueurs (recréés quand la sélection change, pour mettre l'élément choisi en évidence).
    useEffect(() => {
        const g = groupes.current;
        if (!g) return;
        Object.values(g).forEach((groupe) => groupe.clearLayers());

        cites.forEach((cite) => {
            if (!cite.position) return;
            const choisi = selection?.type === 'cite' && selection.id === cite.id;
            const chezMoi = residence?.cite_id === cite.id;
            g.cites.addLayer(
                L.marker(cite.position, { icon: icone('cite', { choisi, chezMoi }), zIndexOffset: choisi || chezMoi ? 1000 : 500, riseOnHover: true })
                    .bindTooltip(infobulle(cite.name, chezMoi ? 'Ma résidence' : cite.type), { direction: 'top', className: 'carte-infobulle' })
                    .on('click', () => setSelection({ type: 'cite', id: cite.id })),
            );
        });
        batiments.forEach((batiment) => {
            if (!batiment.position) return;
            const choisi = selection?.type === 'batiment' && selection.id === batiment.id;
            const chezMoi = residence?.batiment_id === batiment.id;
            g.batiments.addLayer(
                L.marker(batiment.position, { icon: icone('batiment', { choisi, chezMoi }), zIndexOffset: choisi || chezMoi ? 1000 : 0, riseOnHover: true })
                    .bindTooltip(infobulle(batiment.name, chezMoi ? 'Mon bâtiment' : (cites.find((c) => c.id === batiment.cite_id)?.name ?? null)), {
                        direction: 'top',
                        className: 'carte-infobulle',
                    })
                    .on('click', () => setSelection({ type: 'batiment', id: batiment.id })),
            );
        });
        campus.forEach((c) => {
            const choisi = selection?.type === 'campus' && selection.id === c.id;
            g.campus.addLayer(
                L.marker(c.position, { icon: icone('campus', { choisi }), riseOnHover: true })
                    .bindTooltip(infobulle(c.name, c.adresse ?? 'Campus'), { direction: 'top', className: 'carte-infobulle' })
                    .on('click', () => setSelection({ type: 'campus', id: c.id })),
            );
        });
    }, [cites, batiments, campus, selection, residence]);

    // Affichage des calques.
    useEffect(() => {
        const map = carte.current;
        const g = groupes.current;
        if (!map || !g) return;
        (Object.keys(g) as Calque[]).forEach((calque) => (calques[calque] ? g[calque].addTo(map) : g[calque].remove()));
    }, [calques]);

    // Cadrage initial : sur la résidence de l'étudiant si elle est géolocalisée, sinon sur l'ensemble.
    useEffect(() => {
        const point = monBatiment?.position ?? maCite?.position;
        if (point) carte.current?.setView(point, 17);
        else recadrer();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    function recadrer(points?: Position[]) {
        const map = carte.current;
        if (!map) return;
        const liste = points ?? [...cites, ...batiments, ...campus].flatMap((element) => (element.position ? [element.position] : []));
        if (liste.length === 0) map.setView(CENTRE_DEFAUT, 13);
        else if (liste.length === 1) map.flyTo(liste[0], 18, { duration: 0.6 });
        else map.flyToBounds(L.latLngBounds(liste.map((p) => [p.lat, p.lng])).pad(0.25), { maxZoom: 18, duration: 0.6 });
    }

    function choisirCite(cite: CiteCarte) {
        setSelection({ type: 'cite', id: cite.id });
        const points = [cite.position, ...(batimentsParCite.get(cite.id) ?? []).map((b) => b.position)].filter((p): p is Position => p !== null);
        if (points.length > 0) recadrer(points);
        else toast.info(`La cité « ${cite.name} » n'est pas encore positionnée sur la carte.`);
    }

    function choisirBatiment(batiment: BatimentCarte) {
        setSelection({ type: 'batiment', id: batiment.id });
        if (batiment.position) carte.current?.flyTo(batiment.position, 19, { duration: 0.6 });
        else toast.info(`Le bâtiment « ${batiment.name} » n'est pas encore positionné sur la carte.`);
    }

    function choisirCampus(c: CampusCarte) {
        setSelection({ type: 'campus', id: c.id });
        carte.current?.flyTo(c.position, 17, { duration: 0.6 });
    }

    function voirMaResidence() {
        if (monBatiment) choisirBatiment(monBatiment);
        else if (maCite) choisirCite(maCite);
    }

    function maPosition() {
        if (!navigator.geolocation) {
            toast.error("La géolocalisation n'est pas prise en charge par ce navigateur.");
            return;
        }
        setLocalisation(true);
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                setLocalisation(false);
                const couche = positionCouche.current;
                const map = carte.current;
                if (!couche || !map) return;
                couche.clearLayers();
                L.circle([coords.latitude, coords.longitude], { radius: coords.accuracy, color: '#0284c7', weight: 1, fillOpacity: 0.08 }).addTo(couche);
                L.circleMarker([coords.latitude, coords.longitude], { radius: 7, color: '#ffffff', weight: 3, fillColor: '#0284c7', fillOpacity: 1 })
                    .bindTooltip(`Votre position (± ${Math.round(coords.accuracy)} m)`, { direction: 'top', className: 'carte-infobulle' })
                    .addTo(couche);
                map.flyTo([coords.latitude, coords.longitude], 17, { duration: 0.6 });
            },
            (erreur) => {
                setLocalisation(false);
                toast.error(
                    erreur.code === erreur.PERMISSION_DENIED
                        ? 'Accès à la position refusé. Autorisez la géolocalisation dans le navigateur.'
                        : 'Votre position est introuvable pour le moment. Réessayez.',
                );
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
        );
    }

    const compteurs: Record<Calque, number> = {
        cites: cites.filter((c) => c.position).length,
        batiments: batiments.filter((b) => b.position).length,
        campus: campus.length,
    };
    const residencePosition = monBatiment?.position ?? maCite?.position ?? null;

    return (
        <EtudiantLayout title="Carte interactive">
            <div className="flex flex-col gap-5">
                <header className="min-w-0">
                    <h1 className="page-title text-xl font-medium text-gray-900">Carte interactive</h1>
                    <p className="mt-2 text-sm text-muted">Repérez les campus, les cités universitaires et leurs bâtiments, et trouvez votre chemin jusqu'à votre résidence.</p>
                </header>

                {residence && (maCite || monBatiment) && (
                    <section aria-label="Ma résidence" className="flex flex-col gap-3 rounded-brand border border-secondary/60 bg-soft p-4 sm:flex-row sm:items-center">
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-brand bg-primary text-white" aria-hidden>
                            <Home {...ICON} />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-medium tracking-wide text-primary uppercase">Ma résidence</p>
                            <p className="truncate text-sm text-gray-900">
                                {[maCite?.name, monBatiment && `Bâtiment ${monBatiment.name}`, residence.chambre && `Chambre ${residence.chambre}`].filter(Boolean).join(' · ')}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {residencePosition ? (
                                <>
                                    <button
                                        type="button"
                                        onClick={voirMaResidence}
                                        className="inline-flex h-10 cursor-pointer items-center gap-2 rounded-brand border border-primary bg-white px-4 text-sm font-medium text-primary transition-colors hover:bg-soft focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                                    >
                                        <MapPin {...ICON} aria-hidden />
                                        Voir sur la carte
                                    </button>
                                    <a
                                        href={itineraire(residencePosition)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex h-10 items-center gap-2 rounded-brand bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                                    >
                                        <Navigation {...ICON} aria-hidden />
                                        Itinéraire
                                    </a>
                                </>
                            ) : (
                                <span className="text-xs text-muted">Votre résidence n'est pas encore positionnée sur la carte.</span>
                            )}
                        </div>
                    </section>
                )}

                <div className="grid overflow-hidden rounded-brand border bg-white lg:h-[calc(100vh-14rem)] lg:min-h-[560px] lg:grid-cols-[320px_minmax(0,1fr)]">
                    {/* Carte (en premier sur mobile) */}
                    <div className="relative isolate h-[420px] lg:order-2 lg:h-full">
                        <div ref={conteneur} className="size-full bg-surface" aria-label="Carte des campus et cités universitaires" role="region" />

                        <div className="pointer-events-none absolute inset-x-3 top-3 z-[500] flex items-start justify-between gap-2">
                            <div role="radiogroup" aria-label="Fond de carte" className="pointer-events-auto inline-flex h-9 rounded-brand border bg-white p-0.5 shadow-sm">
                                {(['plan', 'satellite'] as const).map((valeur) => (
                                    <button
                                        key={valeur}
                                        type="button"
                                        role="radio"
                                        aria-checked={fond === valeur}
                                        onClick={() => setFond(valeur)}
                                        className={cn(
                                            'cursor-pointer rounded-[6px] px-3 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                            fond === valeur ? 'bg-primary text-white' : 'text-muted hover:bg-soft hover:text-primary',
                                        )}
                                    >
                                        {valeur === 'plan' ? 'Plan' : 'Satellite'}
                                    </button>
                                ))}
                            </div>
                            <div className="pointer-events-auto flex gap-2">
                                {residencePosition && <BoutonCarte label="Ma résidence" icon={Home} onClick={voirMaResidence} />}
                                <BoutonCarte label="Tout afficher" icon={Maximize2} onClick={() => recadrer()} />
                                <BoutonCarte label="Ma position" icon={LocateFixed} onClick={maPosition} occupe={localisation} />
                            </div>
                        </div>

                        {(citeChoisie || batimentChoisi || campusChoisi) && (
                            <Fiche
                                cite={citeChoisie}
                                batiment={batimentChoisi}
                                campus={campusChoisi}
                                residence={residence}
                                onClose={() => setSelection(null)}
                                onCentrer={() =>
                                    batimentChoisi ? choisirBatiment(batimentChoisi) : citeChoisie ? choisirCite(citeChoisie) : campusChoisi && choisirCampus(campusChoisi)
                                }
                            />
                        )}

                        <Legende avecResidence={residencePosition !== null} />
                    </div>

                    {/* Panneau latéral */}
                    <aside className="flex min-h-0 flex-col border-t lg:order-1 lg:border-t-0 lg:border-r">
                        <div className="space-y-3 border-b p-4">
                            <Input
                                type="search"
                                value={recherche}
                                onChange={(event) => setRecherche(event.target.value)}
                                placeholder="Rechercher une cité, un bâtiment…"
                                aria-label="Rechercher sur la carte"
                                icon={<Search />}
                                className="h-10"
                            />
                            <div className="flex flex-wrap gap-2" role="group" aria-label="Éléments affichés sur la carte">
                                {(
                                    [
                                        { cle: 'cites', label: 'Cités', icon: Building2 },
                                        { cle: 'batiments', label: 'Bâtiments', icon: Building },
                                        { cle: 'campus', label: 'Campus', icon: GraduationCap },
                                    ] as const
                                ).map(({ cle, label, icon: Icon }) => (
                                    <button
                                        key={cle}
                                        type="button"
                                        aria-pressed={calques[cle]}
                                        onClick={() => setCalques((actuels) => ({ ...actuels, [cle]: !actuels[cle] }))}
                                        className={cn(
                                            'inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-brand border px-3 text-xs font-medium transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                            calques[cle] ? 'border-primary bg-soft text-primary' : 'border-gray-300 bg-white text-muted hover:bg-surface',
                                        )}
                                    >
                                        <Icon size={14} strokeWidth={1.5} aria-hidden />
                                        {label}
                                        <span className={cn('tabular-nums', calques[cle] ? 'text-primary' : 'text-gray-400')}>{compteurs[cle]}</span>
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="min-h-0 flex-1 overflow-y-auto px-2 pb-3 max-lg:max-h-[480px]">
                            <p className="px-2 pt-3 pb-2 text-xs text-muted">
                                {citesListees.length} cité{citesListees.length > 1 ? 's' : ''} universitaire{citesListees.length > 1 ? 's' : ''}
                            </p>
                            <ul>
                                {citesListees.length === 0 && <li className="px-2 py-8 text-center text-sm text-muted">Aucune cité ne correspond à cette recherche.</li>}
                                {citesListees.map((cite) => {
                                    const ouverte = citeChoisie?.id === cite.id;
                                    const enfants = batimentsParCite.get(cite.id) ?? [];
                                    const chezMoi = residence?.cite_id === cite.id;
                                    return (
                                        <li key={cite.id}>
                                            <button
                                                type="button"
                                                onClick={() => choisirCite(cite)}
                                                aria-expanded={ouverte}
                                                className={cn(
                                                    'flex w-full cursor-pointer items-center gap-3 rounded-brand px-2 py-2.5 text-left transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                                    ouverte ? 'bg-soft' : 'hover:bg-surface',
                                                )}
                                            >
                                                <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-primary text-white" aria-hidden>
                                                    <Building2 size={16} strokeWidth={1.5} />
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <span className="flex items-center gap-1.5">
                                                        <span className={cn('truncate text-sm font-medium', ouverte ? 'text-primary' : 'text-gray-900')}>{cite.name}</span>
                                                        {chezMoi && <span className="shrink-0 rounded-sm bg-[#FAAB04]/15 px-1.5 py-0.5 text-[11px] font-medium text-[#a86f00]">Ma résidence</span>}
                                                    </span>
                                                    <span className="block truncate text-xs text-muted">
                                                        {[cite.type, `${enfants.length} bâtiment${enfants.length > 1 ? 's' : ''}`].filter(Boolean).join(' · ')}
                                                    </span>
                                                </span>
                                                <ChevronRight size={16} strokeWidth={1.25} className={cn('shrink-0 text-gray-400 transition-transform', ouverte && 'rotate-90 text-primary')} aria-hidden />
                                            </button>
                                            {ouverte && enfants.length > 0 && (
                                                <ul className="mt-0.5 mb-1 ml-[1.6rem] border-l pl-2">
                                                    {enfants.map((batiment) => (
                                                        <li key={batiment.id}>
                                                            <button
                                                                type="button"
                                                                onClick={() => choisirBatiment(batiment)}
                                                                className={cn(
                                                                    'flex w-full cursor-pointer items-center gap-2 rounded-brand px-2 py-1.5 text-left text-sm transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                                                    batimentChoisi?.id === batiment.id ? 'bg-soft font-medium text-primary' : 'text-gray-700 hover:bg-surface',
                                                                )}
                                                            >
                                                                <Building size={14} strokeWidth={1.5} className="shrink-0 text-primary" aria-hidden />
                                                                <span className="min-w-0 flex-1 truncate">{batiment.name}</span>
                                                                {residence?.batiment_id === batiment.id && <Home size={14} strokeWidth={1.5} className="shrink-0 text-[#a86f00]" aria-label="Mon bâtiment" />}
                                                            </button>
                                                        </li>
                                                    ))}
                                                </ul>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>

                            {campusListes.length > 0 && (
                                <>
                                    <p className="px-2 pt-4 pb-2 text-xs text-muted">Campus</p>
                                    <ul>
                                        {campusListes.map((c) => (
                                            <li key={c.id}>
                                                <button
                                                    type="button"
                                                    onClick={() => choisirCampus(c)}
                                                    className={cn(
                                                        'flex w-full cursor-pointer items-center gap-3 rounded-brand px-2 py-2.5 text-left transition-colors focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                                        campusChoisi?.id === c.id ? 'bg-soft' : 'hover:bg-surface',
                                                    )}
                                                >
                                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-brand bg-accent text-gray-900" aria-hidden>
                                                        <GraduationCap size={16} strokeWidth={1.5} />
                                                    </span>
                                                    <span className="min-w-0 flex-1">
                                                        <span className={cn('block truncate text-sm font-medium', campusChoisi?.id === c.id ? 'text-primary' : 'text-gray-900')}>{c.name}</span>
                                                        {c.adresse && <span className="block truncate text-xs text-muted">{c.adresse}</span>}
                                                    </span>
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                </>
                            )}
                        </div>
                    </aside>
                </div>
            </div>
        </EtudiantLayout>
    );
}

function BoutonCarte({ label, icon: Icon, onClick, occupe = false }: { label: string; icon: LucideIcon; onClick: () => void; occupe?: boolean }) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={occupe}
            title={label}
            aria-label={label}
            className="inline-flex size-9 cursor-pointer items-center justify-center rounded-brand border bg-white text-gray-700 shadow-sm transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none disabled:cursor-wait disabled:opacity-60"
        >
            <Icon {...ICON} className={cn(occupe && 'animate-pulse')} aria-hidden />
        </button>
    );
}

function Legende({ avecResidence }: { avecResidence: boolean }) {
    const elements = [
        { label: 'Cité', classe: 'rounded-full bg-primary text-white', icon: Building2 },
        { label: 'Bâtiment', classe: 'rounded-[5px] border border-primary bg-white text-primary', icon: Building },
        { label: 'Campus', classe: 'rounded-full bg-accent text-gray-900', icon: GraduationCap },
        ...(avecResidence ? [{ label: 'Ma résidence', classe: 'rounded-full bg-primary text-white ring-2 ring-[#FAAB04]', icon: Home }] : []),
    ];

    return (
        <div className="absolute bottom-3 left-3 z-[500] hidden rounded-brand border bg-white/95 px-3 py-2.5 shadow-sm backdrop-blur sm:block">
            <p className="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-gray-900">
                <Layers size={14} strokeWidth={1.5} aria-hidden />
                Légende
            </p>
            <ul className="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs text-muted">
                {elements.map(({ label, classe, icon: Icon }) => (
                    <li key={label} className="flex items-center gap-1.5">
                        <span className={cn('flex size-5 items-center justify-center', classe)} aria-hidden>
                            <Icon size={11} strokeWidth={2} />
                        </span>
                        {label}
                    </li>
                ))}
            </ul>
        </div>
    );
}

type FicheProps = {
    cite?: CiteCarte;
    batiment?: BatimentCarte;
    campus?: CampusCarte;
    residence: Residence | null;
    onClose: () => void;
    onCentrer: () => void;
};

/** Fiche flottante de l'élément choisi : type, services, contacts et itinéraire. */
function Fiche({ cite, batiment, campus, residence, onClose, onCentrer }: FicheProps) {
    const element = batiment ?? cite ?? campus;
    if (!element) return null;

    const Icon = campus ? GraduationCap : batiment ? Building : Building2;
    const chezMoi = batiment ? residence?.batiment_id === batiment.id : cite ? residence?.cite_id === cite.id : false;
    const sousTitre = campus
        ? 'Campus'
        : batiment
          ? [cite?.name, batiment.occupation, batiment.etages && `${batiment.etages} niveau${batiment.etages > 1 ? 'x' : ''}`].filter(Boolean).join(' · ')
          : [cite?.type, 'Cité universitaire'].filter(Boolean).join(' · ');
    const contact = campus ?? cite;

    return (
        <section
            aria-label={`Fiche ${element.name}`}
            className="absolute inset-x-3 bottom-3 z-[600] max-h-[calc(100%-4.5rem)] overflow-y-auto rounded-brand border bg-white shadow-[0_12px_32px_rgba(0,0,0,0.14)] sm:inset-x-auto sm:top-14 sm:right-3 sm:bottom-auto sm:w-80"
        >
            <header className="flex items-start gap-3 p-4 pb-3">
                <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-brand', campus ? 'bg-accent/30 text-gray-900' : 'bg-soft text-primary')} aria-hidden>
                    <Icon {...ICON} />
                </span>
                <div className="min-w-0 flex-1">
                    <h2 className="truncate text-base font-medium text-gray-900">{element.name}</h2>
                    <p className="truncate text-xs text-muted">{sousTitre || '—'}</p>
                </div>
                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Fermer la fiche"
                    className="-mt-1 -mr-1 inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-brand text-muted hover:bg-surface hover:text-gray-900 focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                >
                    <X {...ICON} />
                </button>
            </header>

            {chezMoi && (
                <p className="mx-4 mb-1 inline-flex items-center gap-1.5 rounded-sm bg-[#FAAB04]/15 px-2 py-1 text-xs font-medium text-[#a86f00]">
                    <Home size={14} strokeWidth={1.5} aria-hidden />
                    {batiment ? 'Votre bâtiment' : 'Votre résidence'}
                    {batiment && residence?.chambre && ` · chambre ${residence.chambre}`}
                </p>
            )}

            {contact && (
                <ul className="space-y-1.5 px-4 pt-2 text-sm text-muted">
                    <Ligne icon={MapPin}>{contact.adresse ?? 'Adresse non renseignée'}</Ligne>
                    {contact.telephone && (
                        <Ligne icon={Phone}>
                            <a href={`tel:${contact.telephone}`} className="hover:text-primary">
                                {contact.telephone}
                            </a>
                        </Ligne>
                    )}
                    {'email' in contact && contact.email && (
                        <Ligne icon={Mail}>
                            <a href={`mailto:${contact.email}`} className="break-all hover:text-primary">
                                {contact.email}
                            </a>
                        </Ligne>
                    )}
                </ul>
            )}

            {cite && !batiment && cite.services.length > 0 && (
                <div className="px-4 pt-3">
                    <p className="mb-1.5 text-xs text-muted">Services disponibles</p>
                    <div className="flex flex-wrap gap-1.5">
                        {cite.services.map((service) => (
                            <span key={service} className="rounded-sm bg-soft px-2 py-0.5 text-xs font-medium text-primary">
                                {service}
                            </span>
                        ))}
                    </div>
                </div>
            )}

            <footer className="mt-4 flex items-center gap-2 border-t px-3 py-2.5">
                {element.position ? (
                    <a
                        href={itineraire(element.position)}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex h-9 items-center gap-1.5 rounded-brand bg-primary px-3 text-sm font-medium text-white transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                    >
                        <Navigation size={16} strokeWidth={1.25} aria-hidden />
                        Itinéraire
                    </a>
                ) : (
                    <span className="text-xs text-muted">Pas encore positionné sur la carte.</span>
                )}
                {element.position && (
                    <button
                        type="button"
                        onClick={onCentrer}
                        title="Centrer sur la carte"
                        aria-label="Centrer sur la carte"
                        className="ml-auto inline-flex size-9 cursor-pointer items-center justify-center rounded-brand text-muted transition-colors hover:bg-soft hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none"
                    >
                        <Maximize2 size={16} strokeWidth={1.25} aria-hidden />
                    </button>
                )}
            </footer>
        </section>
    );
}

function Ligne({ icon: Icon, children }: { icon: LucideIcon; children: ReactNode }) {
    return (
        <li className="flex items-start gap-2">
            <Icon size={16} strokeWidth={1.25} className="mt-0.5 shrink-0" aria-hidden />
            <span className="min-w-0">{children}</span>
        </li>
    );
}

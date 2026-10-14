import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Check, Crosshair, Loader2, MapPin, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Dialog, DialogBody, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

/** Centre par défaut (Bondoukou) quand aucune coordonnée n'est encore saisie. */
export const POSITION_DEFAUT = { lat: 8.0402, lng: -2.8 };

export type Position = { lat: number; lng: number };

/** Lit une coordonnée saisie (« 8,04 » accepté) ; null si vide ou invalide. */
export function lireCoordonnee(valeur: string): number | null {
    const nombre = Number.parseFloat(valeur.replace(',', '.'));
    return Number.isFinite(nombre) ? nombre : null;
}

/** Recherche d'une adresse (OpenStreetMap Nominatim, comme l'ancien formulaire), limitée à la Côte d'Ivoire si demandé. */
export async function geocoder(adresse: string, ciSeulement = true): Promise<(Position & { label: string }) | null> {
    const params = new URLSearchParams({ format: 'json', q: adresse, limit: '1' });
    if (ciSeulement) params.set('countrycodes', 'ci');
    const reponse = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`, { headers: { Accept: 'application/json' } });
    if (!reponse.ok) throw new Error('geocodage');
    const resultats = (await reponse.json()) as { lat: string; lon: string; display_name: string }[];
    if (!resultats.length) return null;

    return { lat: Number.parseFloat(resultats[0].lat), lng: Number.parseFloat(resultats[0].lon), label: resultats[0].display_name };
}

const MARQUEUR = L.divIcon({
    className: '',
    html: '<div class="carte-marqueur bg-primary text-white rounded-full" style="width:34px;height:34px"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></div>',
    iconSize: [34, 34],
    iconAnchor: [17, 34],
});

type PositionPickerProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initial: Position | null;
    titre: string;
    onConfirm: (position: Position) => void;
};

/** Fenêtre de choix d'une position : clic sur la carte ou marqueur déplaçable, recherche d'adresse, « Ma position ». */
export function PositionPicker({ open, onOpenChange, initial, titre, onConfirm }: PositionPickerProps) {
    const conteneur = useRef<HTMLDivElement | null>(null);
    const carte = useRef<L.Map | null>(null);
    const marqueur = useRef<L.Marker | null>(null);
    const [position, setPosition] = useState<Position>(initial ?? POSITION_DEFAUT);
    const [recherche, setRecherche] = useState('');
    const [chargement, setChargement] = useState<'recherche' | 'gps' | null>(null);

    // La carte est créée à l'ouverture (le conteneur n'existe qu'à ce moment) et détruite à la fermeture.
    useEffect(() => {
        if (!open) return;
        const depart = initial ?? POSITION_DEFAUT;
        setPosition(depart);
        const timer = window.setTimeout(() => {
            if (!conteneur.current) return;
            const map = L.map(conteneur.current, { maxZoom: 21 }).setView([depart.lat, depart.lng], initial ? 17 : 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap',
                maxNativeZoom: 19,
                maxZoom: 21,
            }).addTo(map);
            const m = L.marker([depart.lat, depart.lng], { icon: MARQUEUR, draggable: true }).addTo(map);
            m.on('dragend', () => {
                const { lat, lng } = m.getLatLng();
                setPosition({ lat, lng });
            });
            map.on('click', (event: L.LeafletMouseEvent) => {
                m.setLatLng(event.latlng);
                setPosition({ lat: event.latlng.lat, lng: event.latlng.lng });
            });
            carte.current = map;
            marqueur.current = m;
            map.invalidateSize();
        }, 150);

        return () => {
            window.clearTimeout(timer);
            carte.current?.remove();
            carte.current = null;
            marqueur.current = null;
        };
        // `initial` n'est lu qu'à l'ouverture.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const deplacer = (cible: Position) => {
        setPosition(cible);
        marqueur.current?.setLatLng([cible.lat, cible.lng]);
        carte.current?.setView([cible.lat, cible.lng], 18);
    };

    const rechercher = async () => {
        if (!recherche.trim()) {
            toast.error('Saisissez une adresse à rechercher.');
            return;
        }
        setChargement('recherche');
        try {
            const resultat = await geocoder(recherche, false);
            if (resultat) {
                deplacer(resultat);
            } else {
                toast.error('Adresse introuvable. Précisez votre recherche.');
            }
        } catch {
            toast.error('La recherche d’adresse a échoué. Réessayez.');
        } finally {
            setChargement(null);
        }
    };

    const maPosition = () => {
        if (!navigator.geolocation) {
            toast.error('La géolocalisation n’est pas disponible sur cet appareil.');
            return;
        }
        setChargement('gps');
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                deplacer({ lat: coords.latitude, lng: coords.longitude });
                setChargement(null);
            },
            (erreur) => {
                setChargement(null);
                toast.error(erreur.code === erreur.PERMISSION_DENIED ? 'Autorisez la géolocalisation pour utiliser votre position.' : 'Position indisponible.');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-4xl">
                <DialogHeader>
                    <DialogTitle>{titre}</DialogTitle>
                    <DialogDescription>Cliquez sur la carte ou faites glisser le marqueur pour ajuster la position.</DialogDescription>
                </DialogHeader>
                <DialogBody className="space-y-3">
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <form
                            className="flex flex-1 gap-2"
                            onSubmit={(event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                void rechercher();
                            }}
                        >
                            <div className="flex-1">
                                <Input
                                    aria-label="Rechercher une adresse"
                                    placeholder="Rechercher une adresse…"
                                    icon={<Search />}
                                    value={recherche}
                                    onChange={(event) => setRecherche(event.target.value)}
                                />
                            </div>
                            <Button type="submit" variant="secondary" className="h-11" disabled={chargement !== null}>
                                {chargement === 'recherche' ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Search className="size-4" aria-hidden />}
                                <span className="hidden sm:inline">Rechercher</span>
                            </Button>
                        </form>
                        <Button variant="outline" className="h-11" onClick={maPosition} disabled={chargement !== null}>
                            {chargement === 'gps' ? <Loader2 className="size-4 animate-spin" aria-hidden /> : <Crosshair className="size-4" aria-hidden />}
                            Ma position
                        </Button>
                    </div>
                    <div ref={conteneur} className="h-[50vh] min-h-72 w-full overflow-hidden rounded-brand border" />
                    <p className="flex items-center gap-2 text-sm text-muted">
                        <MapPin className="size-4 text-primary" aria-hidden />
                        <span className="tabular-nums">
                            {position.lat.toFixed(6)}, {position.lng.toFixed(6)}
                        </span>
                    </p>
                </DialogBody>
                <DialogFooter>
                    <Button variant="outline" size="sm" className="h-10" onClick={() => onOpenChange(false)}>
                        Annuler
                    </Button>
                    <Button
                        size="sm"
                        className="h-10"
                        onClick={() => {
                            onConfirm(position);
                            onOpenChange(false);
                        }}
                    >
                        <Check className="size-4" aria-hidden />
                        Utiliser cette position
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

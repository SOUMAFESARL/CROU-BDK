<?php

use App\Http\Controllers\preferenceController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Nette\Utils\Random;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;





if (!function_exists('allConfig')) {
 function allConfig()
{
    static $config;
    static $loaded = false;

    if ($loaded) {
        return $config;
    }

    // Table absente (installation incomplète, tests) : valeurs par défaut de systemConfig().
    $config = rescue(fn () => app()->make(ConfigAppRepositoryInterface::class)->getConfig(), null, false);
    $loaded = true;

    return $config;

//    return  ([
//        // Alias pratiques utilisés dans la vue
//        'config'       => $config,
//        'profil'       => $config,
//        'notif'        => $config,
//        'paiement'     => $config,
//        'taxes'        => $config,
//        'devises'      => $config,
//        'integrations' => $config,
//        'securite'     => $config,
//    ]);
}
};

if (!function_exists('systemConfig')) {
    function systemConfig($key = null, $default = null)
    {
        $config = allConfig();

        if ($key === null) {
            return $config;
        }

        return data_get($config, $key, $default) ?? $default;
    }
}

if (!function_exists('systemLogo')) {
    function systemLogo($fallback = 'files/assets/logo/logo.jpg')
    {
        $logo = systemConfig('logo');

        if (!$logo || $logo === 'null') {
            return asset($fallback);
        }

        // Anciens enregistrements : URL absolue d'un autre domaine (ex. la prod vue depuis le local).
        // Si le fichier existe dans public/, on le sert depuis le domaine courant.
        if (filter_var($logo, FILTER_VALIDATE_URL)) {
            $chemin = ltrim(rawurldecode((string) parse_url($logo, PHP_URL_PATH)), '/');
            foreach ([$chemin, preg_replace('~^public/~', '', $chemin)] as $candidat) {
                if ($candidat !== '' && is_file(public_path($candidat))) {
                    return asset($candidat);
                }
            }

            return $logo;
        }

        return asset(ltrim($logo, '/'));
    }
}

if (!function_exists('systemLogoDocument')) {
    function systemLogoDocument()
    {
        $url = systemLogo();
        $path = rawurldecode(parse_url($url, PHP_URL_PATH) ?: '');
        $base = rtrim(parse_url(asset('/'), PHP_URL_PATH) ?: '', '/');
        if ($base && str_starts_with($path, $base.'/')) {
            $path = substr($path, strlen($base));
        }
        $file = realpath(public_path(ltrim($path, '/')));
        $root = realpath(public_path());
        if ($file && $root && str_starts_with(str_replace(chr(92), '/', $file), str_replace(chr(92), '/', $root).'/') && is_file($file)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
            if (str_starts_with($mime, 'image/')) {
                return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($file));
            }
        }
        return $url;
    }
}
if (!function_exists('systemCurrencySymbol')) {
    function systemCurrencySymbol()
    {
        return systemConfig('currency_symbol', systemConfig('currency', 'FCFA'));
    }
}

if (!function_exists('systemAppName')) {
    /** Nom court du CROU (Configuration › Général), affiché dans les en-têtes, titres et e-mails. */
    function systemAppName(): string
    {
        return (string) (systemConfig('app_name') ?: config('app.name') ?: 'CROU Bondoukou');
    }
}

if (!function_exists('systemFullName')) {
    /** Nom complet du CROU (Configuration › Général), affiché sur les pages de connexion et les documents. */
    function systemFullName(): string
    {
        return (string) (systemConfig('company_name') ?: 'Centre Régional des Œuvres Universitaires de Bondoukou');
    }
}

if (!function_exists('systemBrandColor')) {
    /**
     * Couleur principale de l'interface (Configuration › Profil), #006400 par défaut.
     * #000000 est la valeur d'un sélecteur jamais réglé dans l'ancienne page : traitée comme non renseignée.
     */
    function systemBrandColor(): string
    {
        $couleur = strtoupper((string) systemConfig('brand_color', ''));

        return preg_match('/^#[0-9A-F]{6}$/D', $couleur) && $couleur !== '#000000' ? $couleur : '#006400';
    }
}

if (!function_exists('systemBrandStyle')) {
    /** Balise <style> qui remplace la couleur principale des pages (React et Blade) ; vide pour la couleur par défaut. */
    function systemBrandStyle(): \Illuminate\Support\HtmlString
    {
        $couleur = systemBrandColor();
        if ($couleur === '#006400') {
            return new \Illuminate\Support\HtmlString('');
        }

        return new \Illuminate\Support\HtmlString("<style>:root{--color-primary:{$couleur};--color-primary-hover:color-mix(in srgb,{$couleur} 82%,#000);"
            ."--sidebar-primary:{$couleur};--sidebar-accent-foreground:{$couleur};--shell-primary:{$couleur}}</style>");
    }
}

if (!function_exists('systemPaymentMethods')) {
    /** Modes de paiement acceptés (Configuration › Paiements) : cash, mobile, transfer, card. */
    function systemPaymentMethods(): array
    {
        $modes = systemConfig('methods');

        return is_array($modes) ? array_values($modes) : ['cash', 'mobile'];
    }
}

if (!function_exists('systemThousandsSeparator')) {
    function systemThousandsSeparator(): string
    {
        $separateur = systemConfig('thousands_separator');

        return in_array($separateur, ['.', ','], true) ? $separateur : ' ';
    }
}

if (!function_exists('formatSystemAmount')) {
    function formatSystemAmount($amount, $decimals = 0)
    {
        $separateur = systemThousandsSeparator();
        $formatted = number_format((float) $amount, $decimals, $separateur === ',' ? '.' : ',', $separateur);
        $symbol = systemCurrencySymbol();

        return systemConfig('symbol_position', 'after') === 'before'
            ? $symbol . ' ' . $formatted
            : $formatted . ' ' . $symbol;
    }
}
if (!function_exists('upload')) {
function upload( $dir,  $format, $image, $names)
{

    // Photos EDL transmises en base64 puis decodées et validées par le service.
    if (is_string($dir) && str_starts_with($dir, 'public:')) {
        if (!preg_match('~^public:([a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*)$~D', $dir, $matches)) {
            throw new \InvalidArgumentException('Répertoire public invalide.');
        }
        $info = is_string($image) ? @getimagesizefromstring($image) : false;
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!$info || !isset($extensions[$info['mime']]) || strlen($image) > 2 * 1024 * 1024
            || $info[0] * $info[1] > 24000000) {
            throw new \InvalidArgumentException('Photo invalide ou trop volumineuse.');
        }
        $path = $matches[1].'/'.\Illuminate\Support\Str::uuid().'.'.$extensions[$info['mime']];
        if (!\Illuminate\Support\Facades\Storage::disk('public')->put($path, $image)) {
            throw new \RuntimeException('Impossible d’enregistrer les photos.');
        }
        return $path;
    }
    // Les signatures utilisent le même helper, sur le disque privé Laravel.
    // Les appels historiques sans préfixe conservent leur stockage public.
    if (is_string($dir) && str_starts_with($dir, 'private:')) {
        if (!preg_match('~^private:([a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*)$~D', $dir, $matches)) {
            throw new \InvalidArgumentException('Répertoire privé invalide.');
        }
        $file = $image instanceof \Illuminate\Http\UploadedFile
            ? $image
            : ($image instanceof \Illuminate\Http\Request ? $image->file($names) : null);
        if (!$file instanceof \Illuminate\Http\UploadedFile || !$file->isValid()) {
            return null;
        }
        // Le contrôleur valide les formats, dimensions et tailles autorisés.
        return $file->store($matches[1], 'local') ?: null;
    }

    if($_FILES[$names]['name']!="")
    {



        $tmpName = $_FILES[$names]['tmp_name'];
        $name = $_FILES[$names]['name'];

        $size = $_FILES[$names]['size'];
        //  dd('sdg');
        $error = $_FILES[$names]['error'];
        $tabExtension = explode('.', $name);
        $extension = strtolower(end($tabExtension));

        //Tableau des extensions que l'on accepte
        $extensions = ['jpg', 'png', 'jpeg', 'gif','pdf','docx'];
        //Taille max que l'on accepte
        $maxSize = 10000000;
//dd(in_array($extension, $extensions) && $size <= $maxSize);
        if(in_array($extension, $extensions) && $size <= $maxSize){

            $uniqueName = uniqid('', true);
            //uniqid génère quelque chose comme ca : 5f586bf96dcd38.73540086
            // $file = $uniqueName.".".$format;
            $file = $uniqueName.".".$extension;
            $file_path = 'admin/assets/images/'.$dir;
//dd(file_exists($file_path));
            // Checking whether file exists or not
            if (!file_exists($file_path)) {

                // Create a new file or direcotry
                mkdir($file_path, 0777, true);
                //   CHMOD($file_path, 0777);
//dd("j'ai creee");
            }
            move_uploaded_file($tmpName, "admin/assets/images/".$dir.'/'.$file);
            //  $image->move(public_path('assets/images/'.$dir), $file);
        }
        else{
            // echo "";
            return back()->withError('Mauvaise extension ou taille trop grande');
        }

        return config('app.app_url_image').'/assets/images/'.$dir.'/'.$file;
    }
}
};

if (!function_exists('update')) {

function update( $dir, $old_image,  $format, $image, $name)
{
    //  $file_path = 'assets/images/'.$dir; lorsque je n'enregistre pas le lien de l'image
    $file_path = 'admin/assets/images/';
    if($_FILES[$name]['name']!="")
    {
        if(($old_image)){
            // dd(file_exists( $file_path."/".$old_image));
            /* if(file_exists( $file_path."/".$old_image)){
                 unlink($file_path."/".$old_image);

                 }*/
            if(file_exists( $old_image)){
                unlink($old_image);

            }
            //   unlink($old_image);
        }

        $imageName = upload($dir, $format, $image, $name);
        return $imageName;
    }
}
};

if (!function_exists('delete')) {
function delete( $dir, $old_image)
{
    //  $file_path = 'assets/images/'.$dir; lorsque je n'enregistre pas le lien de l'image
    $file_path = 'admin/assets/images/';
    if(($old_image)){
        // dd(file_exists( $file_path."/".$old_image));
        /* if(file_exists( $file_path."/".$old_image)){
             unlink($file_path."/".$old_image);

             }*/
        if(file_exists( $old_image)){
            unlink($old_image);

        }
    }
    return [
        'success' => 1,
        'message' => 'Removed successfully !'
    ];

}
};
if (!function_exists('doc_upload')) {
function doc_upload( $dir,  $format, $image = null)
{ //dd($image->getClientOriginalName());

    $test = explode('/',$dir);

    // dd($test);

    if ($image != null) {
        $tabExtension = explode('.', $image->getClientOriginalName());
        $extension = strtolower(end($tabExtension));
        //  dd( $extension);
        $imageName = Carbon::now()->toDateString() . "-" . uniqid() . "." . $extension;
        if (!Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        // dd($dir);
        if(count($test)>1)
        {
            $autre=$test[1];
            Storage::disk('public')->put($dir.'/'.$autre . $imageName, file_get_contents($image));
        }
        else{
            Storage::disk('public')->put($dir .'/'. $imageName, file_get_contents($image));
        }


    } else {
        $imageName = 'def.png';
    }

    return config('app.app_url_image_storage').'/'.$dir.'/'.$imageName;
}
};

if (!function_exists('doc_update')) {
function doc_update( $dir, $old_image,  $format, $image = null)
{
    foreach (json_decode($old_image) as $key => $value) {
        if (Storage::disk('public')->exists($dir . $value)) {
            Storage::disk('public')->delete($dir . $value);
        }

    }
    $imageName = doc_upload($dir, $format, $image);
    /*    if (Storage::disk('public')->exists($dir . $old_image)) {
            Storage::disk('public')->delete($dir . $old_image);
        }
        $imageName = PreferenceController::doc_upload($dir, $format, $image);*/
    return $imageName;
}
};

if (!function_exists('doc_delete')) {
function doc_delete($full_path)
{
    if (Storage::disk('public')->exists($full_path)) {
        Storage::disk('public')->delete($full_path);
    }

    return [
        'success' => 1,
        'message' => 'Removed successfully !'
    ];

}
};
if (!function_exists('getFileSizeFromUrl')) {
function getFileSizeFromUrl($url)
{
    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE); // Ne récupère que les headers
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); // Suit les redirections
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);

        return $size > 0 ? $size : 0;
    } catch (\Exception $e) {
        return 0;
    }
}
};

if (!function_exists('seeting')) {
function seeting(){

    return $test = "test" ;
}
};
if (!function_exists('photo_url')) {
function photo_url($civilite,$string)
{
   // dd($civilite,$string);
    if ($string!=null) {
        return $string;
    }

    return ($civilite == 1 || $civilite ==null)
        ? config('app.app_url_image') . '/assets/images/men.jpg'
        : config('app.app_url_image') . '/assets/images/women.png';
}
};
if (!function_exists('generate')) {
function generate($table,$reference,$nbre,$string)
{

    $val=   Random::generate($nbre,$string);


    if (!empty(DB::table($table)->where($reference, $val)->first())) {
        // récursion sans $this
        return generate($table, $reference, $nbre, $string);
    }

    // dd($val);
    return $val;
}
};
if (!function_exists('getInfoAdmin')) {
function getInfoAdmin()
{
    $response ="";
    $data = [];
 //dd(Auth::guard('admin')->check());
    if(Auth::guard('admin')->check())
    {
        $data['users'] = Auth::guard('admin')->user();

        // $data['civilite'] = Civilite::where('id', $user->genre_id)->first();
      //  $data['systeme'] = SysConfiguration::first();


    }


    else{
        $data['users'] = null;


      //  $data['systeme'] = SysConfiguration::first();

    }

    return (object) $data;
}
};
if (!function_exists('getInitials')) {
function getInitials($name, $maxLength = 3)
{
    if (empty($name)) {
        return '';
    }

    $words = explode(' ', trim($name));
    $initials = '';

    foreach ($words as $word) {
        if (!empty($word) && ctype_alpha($word[0])) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        if (strlen($initials) >= $maxLength) {
            break;
        }
    }

    return $initials;
}
};
if (!function_exists('formatDateFr')) {
function formatDateFr($date, $withDay = false) {
    if (!$date instanceof DateTime) {
        $date = new DateTime($date);
    }

    $mois = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
    ];

    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

    $jour = $date->format('j');
    $moisNum = (int)$date->format('n');
    $annee = $date->format('Y');

    if ($withDay) {
        $nomJour = $jours[$date->format('w')];
        return ucfirst($nomJour) . " $jour {$mois[$moisNum]} $annee";
    }

    return "$jour {$mois[$moisNum]} $annee";
}
};
if (!function_exists('getAnneeUniversitaire')) {
function getAnneeUniversitaire($date = null) {
    $date = $date ? new DateTime($date) : new DateTime();
    $annee = (int)$date->format('Y');
    $mois = (int)$date->format('m');

    // L'année universitaire commence en septembre
    if ($mois >= 9) {
        return $annee . '-' . ($annee + 1);
    } else {
        return ($annee - 1) . '-' . $annee;
    }
}
}

if (!function_exists('formatPaiementMois')) {
    function formatPaiementMois($mois, $groupBy = 2)
    {
        if (empty($mois)) {
            return '-';
        }

        // Tableau des mois en français
        $moisNoms = [
            '01' => 'janvier', '02' => 'février', '03' => 'mars',
            '04' => 'avril', '05' => 'mai', '06' => 'juin',
            '07' => 'juillet', '08' => 'août', '09' => 'septembre',
            '10' => 'octobre', '11' => 'novembre', '12' => 'décembre'
        ];

        // Mois en anglais vers français
        $moisAnglais = [
            'January' => 'janvier', 'February' => 'février', 'March' => 'mars',
            'April' => 'avril', 'May' => 'mai', 'June' => 'juin',
            'July' => 'juillet', 'August' => 'août', 'September' => 'septembre',
            'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre'
        ];

        // Fonction pour extraire le mois et l'année
        $extraireMoisAnnee = function($chaine) use ($moisNoms, $moisAnglais) {
            if (empty($chaine)) {
                return null;
            }

            // Si c'est déjà un mois français avec année (janvier-2026)
            if (preg_match('/^([a-zéèêëàâôûîïç]+)-(\d{4})$/i', $chaine, $matches)) {
                $moisNom = strtolower($matches[1]);
                $annee = $matches[2];
                if (in_array($moisNom, $moisNoms)) {
                    return ['mois' => $moisNom, 'annee' => $annee];
                }
                if (isset($moisAnglais[ucfirst($moisNom)])) {
                    return ['mois' => $moisAnglais[ucfirst($moisNom)], 'annee' => $annee];
                }
                return ['mois' => $moisNom, 'annee' => $annee];
            }

            // Format YYYY-MM
            if (preg_match('/^(\d{4})-(\d{2})$/', $chaine, $matches)) {
                $annee = $matches[1];
                $moisNum = $matches[2];
                $moisNom = $moisNoms[$moisNum] ?? $moisNum;
                return ['mois' => $moisNom, 'annee' => $annee];
            }

            // Format MM-YYYY
            if (preg_match('/^(\d{2})-(\d{4})$/', $chaine, $matches)) {
                $moisNum = $matches[1];
                $annee = $matches[2];
                $moisNom = $moisNoms[$moisNum] ?? $moisNum;
                return ['mois' => $moisNom, 'annee' => $annee];
            }

            // Format YYYY-MM-DD
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $chaine, $matches)) {
                $annee = $matches[1];
                $moisNum = $matches[2];
                $moisNom = $moisNoms[$moisNum] ?? $moisNum;
                return ['mois' => $moisNom, 'annee' => $annee];
            }

            return null;
        };

        // Récupérer la liste des mois
        $listeMois = [];

        // Si c'est un tableau
        if (is_array($mois)) {
            $listeMois = $mois;
        }
        // Si c'est un objet
        elseif (is_object($mois)) {
            $listeMois = (array) $mois;
        }
        // Si c'est un string JSON
        elseif (is_string($mois) && (str_starts_with($mois, '{') || str_starts_with($mois, '['))) {
            $decoded = json_decode($mois, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $listeMois = is_array($decoded) ? $decoded : [$decoded];
            } else {
                $listeMois = [$mois];
            }
        }
        // Si c'est une chaîne simple
        else {
            $listeMois = [(string) $mois];
        }

        // Formater chaque mois
        $moisFormates = [];
        foreach ($listeMois as $item) {
            if (is_array($item)) {
                // Si c'est un tableau imbriqué, on le traite récursivement
                $moisFormates = array_merge($moisFormates, explode(', ', formatPaiementMois($item)));
                continue;
            }

            $result = $extraireMoisAnnee($item);
            if ($result) {
                $moisFormates[] = $result['mois'] . '-' . $result['annee'];
            } else {
                $moisFormates[] = (string) $item;
            }
        }

        // Supprimer les doublons tout en préservant l'ordre
        $moisFormates = array_unique($moisFormates);

        // Grouper par 2 mois
        if ($groupBy > 0 && count($moisFormates) > $groupBy) {
            $groupes = array_chunk($moisFormates, $groupBy);
            $resultat = [];
            foreach ($groupes as $groupe) {
                $resultat[] = implode(', ', $groupe);
            }
            return implode('<br>', $resultat);
        }

        return implode(', ', $moisFormates);
    }
}


function EnvoiSMS($dest_phone, $message)
{
    $numero = numeroCI($dest_phone);
    if (!$numero) return false;
    $settings = \App\Models\ConfigApp::query()->first();
    if (!$settings || !$settings->sms_enabled || $settings->sms_provider !== 'helper_json' || !$settings->sms_api_url || !$settings->sms_api_key || !$settings->sms_sender) {
        return false;
    }
    // Passerelle JSON correspondant au contrat du helper historique.
    $auth = $settings->sms_api_key;
    if (!str_starts_with(strtolower($auth), 'bearer ')) $auth = 'Bearer '.$auth;
    try {
        $response = Http::acceptJson()->asJson()->withHeaders(['Authorization' => $auth])
            ->connectTimeout(5)->timeout(20)->withoutRedirecting()
            ->post($settings->sms_api_url, [
                'recipient' => '225'.$numero,
                'sender_id' => $settings->sms_sender,
                'type' => 'plain',
                'message' => $message,
            ]);
        return $response->successful()
            && !in_array(strtolower((string) $response->json('status')), ['error', 'failed', 'failure'], true)
            && $response->json('success') !== false;
    } catch (\Throwable $exception) {
        return false;
    }
}

function numeroCI($numero)
{
    // Nettoyer et corriger le numéro
    $numero = preg_replace('/\D/', '', $numero);

    // Enlever +225 ou 225
    if (substr($numero, 0, 3) === '225') {
        $numero = substr($numero, 3);
    }

    // Vérifier si c'est un numéro valide
    if (strlen($numero) === 10 && in_array(substr($numero, 0, 2), ['07', '05', '01', '27'])) {
        return $numero;
    }

    return false;
}



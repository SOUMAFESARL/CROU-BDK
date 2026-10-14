<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigurationRequest;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class configurationController extends Controller
{
    public function __construct(
        private readonly ConfigAppRepositoryInterface $repo
    ) {}

    // ─── Affichage ────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        abort_if(in_array($request->query('menus'), ['paiements', 'securite', 'taxes', 'devises'], true), 404);
        $config = $this->repo->getConfig();
        if ($config && !$request->user('admin')->hasPermission('parametrage.configuration.configure')) {
            $config = clone $config;
            foreach (['mail_password', 'sms_api_key', 'api_key_secret'] as $secret) {
                $config->setAttribute($secret, null);
            }
        }

        return view('admin.parametrage.configurations.index', [
            // Alias pratiques utilisés dans la vue
            'config'       => $config,
            'profil'       => $config,
            'notif'        => $config,
            'integrations' => $config,
        ]);
    }

    // ─── Mise à jour ──────────────────────────────────────────────────────────

    public function update(ConfigurationRequest $request,$menus =null): RedirectResponse
    {
        $menu    = $menus ?? 'configuration';  //$request->query('menus', 'configuration');
        $data    = $request->validated();



        // Normaliser les checkboxes absents (non cochés = false/0)
        $data = $this->normalizeBooleans($data, $menu);

        // Gestion de l'upload du logo (section profil)
        if ($menu === 'profil' && $request->hasFile('logo')) {
            $data['logo'] = upload( 'logos',  'png', $request , 'logo') ;
        }
//dd($data);
        // Dispatch vers la bonne méthode du repository
        match ($menu) {
            'profil'        => $this->repo->updateProfil($data),
            'notifications' => $this->repo->updateNotifications($data),
            'integrations'  => $this->repo->updateIntegrations($data),
            default         => $this->repo->updateGeneral($data),
        };

        return redirect()
            ->route('admin.parametrage.configuration.index', ['menus' => $menu])
            ->with('success', 'Configuration mise à jour avec succès.');
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Les checkboxes non cochées ne sont pas envoyées par le navigateur.
     * On les force à false pour que la BDD soit correctement mise à jour.
     */
    private function normalizeBooleans(array $data, string $menu): array
    {
        $booleansByMenu = [
            'configuration' => [
                'maintenance_mode', 'activity_log',
            ],
            'notifications' => [
                'email_enabled', 'sms_enabled',
                'notif_new_user', 'notif_new_order', 'notif_payment_ok',
                'notif_payment_fail', 'notif_low_stock', 'notif_report',
            ],
            'paiements' => [
                'online_payment_enabled', 'sandbox_mode', 'auto_receipt',
                'three_d_secure', 'anti_fraud',
            ],
            'taxes' => [
                'tva_enabled', 'tva_on_services', 'tax_on_delivery',
            ],
            'devises' => [
                'auto_rate',
            ],
            'integrations' => [
                'google_maps_enabled', 'livechat_enabled',
                'social_google', 'social_facebook', 'social_github',
            ],
            'securite' => [
                'two_factor_auth', 'allow_multiple_sessions',
                'password_require_uppercase', 'password_require_number',
                'password_require_special', 'ip_restriction', 'force_https',
            ],
        ];

        foreach ($booleansByMenu[$menu] ?? [] as $field) {
            $data[$field] = isset($data[$field]) ? true : false;
        }

        return $data;
    }
}

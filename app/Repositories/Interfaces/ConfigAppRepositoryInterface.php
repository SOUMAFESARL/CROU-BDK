<?php

namespace App\Repositories\Interfaces;

use App\Models\ConfigApp;

interface ConfigAppRepositoryInterface
{
    /**
     * Récupérer la configuration active (ligne unique).
     */
    public function getConfig(): ?ConfigApp;

    /**
     * Créer ou mettre à jour la configuration (upsert sur config_id = 1).
     *
     * @param array $data
     * @return ConfigApp
     */
    public function updateOrCreate(array $data): ConfigApp;

    // ─── Sections individuelles ────────────────────────────────────────────────

    /** Mettre à jour uniquement les champs de la section Général */
    public function updateGeneral(array $data): ConfigApp;

    /** Mettre à jour uniquement les champs de la section Profil */
    public function updateProfil(array $data): ConfigApp;

    /** Mettre à jour uniquement les champs de la section Notifications */
    public function updateNotifications(array $data): ConfigApp;

    /** Mettre à jour uniquement les champs de la section Paiements */
    public function updatePaiements(array $data): ConfigApp;

    /** Mettre à jour uniquement les champs de la section Taxes & TVA */
    public function updateTaxes(array $data): ConfigApp;

    /** Mettre à jour uniquement les champs de la section Devises */
    public function updateDevises(array $data): ConfigApp;

    /** Mettre à jour uniquement les champs de la section Intégrations */
    public function updateIntegrations(array $data): ConfigApp;

    /** Mettre à jour uniquement les champs de la section Sécurité */
    public function updateSecurite(array $data): ConfigApp;
}
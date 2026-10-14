{{--@extends('admin.layouts.app', [--}}
{{--    'activeMenu' => 'parametres',--}}
{{--    'activeSubMenu' => 'paiements'--}}
{{--])--}}

{{--@section('title', 'Gestion Parametrage | Paiements')--}}

{{--@section('content')--}}

{{--    <div class="config-wrapper">--}}

{{--        --}}{{-- Sidebar navigation --}}
{{--        <aside class="config-sidebar">--}}
{{--            <div class="config-brand">--}}
{{--                <div class="config-brand-icon">--}}
{{--                    <i class="ti ti-grid-dots"></i>--}}
{{--                </div>--}}
{{--                <span class="config-brand-name">MonApp</span>--}}
{{--            </div>--}}

{{--            <nav class="config-nav">--}}
{{--                <div class="config-nav-section">Général</div>--}}

{{--                <a href="{{ route('admin.parametrage.configuration.index') }}" class="config-nav-item {{ request('menus') === 'configuration' || !request('menus') ? 'active' : '' }}">--}}
{{--                    <i class="ti ti-settings"></i> Configuration--}}
{{--                </a>--}}
{{--                <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'profil']) }}" class="config-nav-item {{ request('menus') === 'profil' ? 'active' : '' }}">--}}
{{--                    <i class="ti ti-user"></i> Profil--}}
{{--                </a>--}}
{{--                <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'notifications']) }}" class="config-nav-item {{ request('menus') === 'notifications' ? 'active' : '' }}">--}}
{{--                    <i class="ti ti-bell"></i> Notifications--}}
{{--                </a>--}}

{{--                <div class="config-nav-section">Finances</div>--}}

{{--                <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'paiements']) }}" class="config-nav-item {{ request('menus') === 'paiements' ? 'active' : '' }}">--}}
{{--                    <i class="ti ti-credit-card"></i> Paiements--}}
{{--                </a>--}}
{{--                <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'taxes']) }}" class="config-nav-item {{ request('menus') === 'taxes' ? 'active' : '' }}">--}}
{{--                    <i class="ti ti-receipt"></i> Taxes & TVA--}}
{{--                </a>--}}
{{--                <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'devises']) }}" class="config-nav-item {{ request('menus') === 'devises' ? 'active' : '' }}">--}}
{{--                    <i class="ti ti-currency-dollar"></i> Devises--}}
{{--                </a>--}}

{{--                <div class="config-nav-section">Avancé</div>--}}

{{--                <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'integrations']) }}" class="config-nav-item {{ request('menus') === 'integrations' ? 'active' : '' }}">--}}
{{--                    <i class="ti ti-plug"></i> Intégrations--}}
{{--                </a>--}}
{{--                <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'securite']) }}" class="config-nav-item {{ request('menus') === 'securite' ? 'active' : '' }}">--}}
{{--                    <i class="ti ti-lock"></i> Sécurité--}}
{{--                </a>--}}
{{--            </nav>--}}
{{--        </aside>--}}

{{--        --}}{{-- Contenu principal --}}
{{--        <main class="config-content">--}}

{{--            --}}{{-- ============================================================--}}
{{--                PAGE : Configuration générale--}}
{{--            ============================================================ --}}
{{--            @if(request('menus') === 'configuration' || !request('menus'))--}}

{{--                <div class="config-header">--}}
{{--                    <div>--}}
{{--                        <div class="config-breadcrumb">Configuration <span>/ Général</span></div>--}}
{{--                        <h1 class="config-page-title">Configuration de l'application</h1>--}}
{{--                        <p class="config-page-sub">Paramètres généraux de votre espace</p>--}}
{{--                    </div>--}}
{{--                    <span class="badge-status badge-on">Actif</span>--}}
{{--                </div>--}}

{{--                <form method="POST" action="{{ route('admin.parametrage.configuration.update') }}">--}}
{{--                    @csrf--}}
{{--                    @method('PUT')--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-building"></i> Informations générales</div>--}}
{{--                        <p class="config-card-desc">Identité et présentation de votre application</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Nom de l'application</div>--}}
{{--                                <div class="field-hint">Affiché dans l'en-tête</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="app_name" class="field-input"--}}
{{--                                   value="{{ old('app_name', $config->app_name ?? config('app.name')) }}" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Langue par défaut</div>--}}
{{--                                <div class="field-hint">Interface utilisateur</div>--}}
{{--                            </div>--}}
{{--                            <select name="locale" class="field-select">--}}
{{--                                <option value="fr" {{ ($config->locale ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>--}}
{{--                                <option value="en" {{ ($config->locale ?? '') === 'en' ? 'selected' : '' }}>English</option>--}}
{{--                                <option value="es" {{ ($config->locale ?? '') === 'es' ? 'selected' : '' }}>Español</option>--}}
{{--                            </select>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Fuseau horaire</div>--}}
{{--                                <div class="field-hint">Pour les horaires et rapports</div>--}}
{{--                            </div>--}}
{{--                            <select name="timezone" class="field-select">--}}
{{--                                <option value="Africa/Abidjan" {{ ($config->timezone ?? 'Africa/Abidjan') === 'Africa/Abidjan' ? 'selected' : '' }}>Africa/Abidjan (GMT+0)</option>--}}
{{--                                <option value="Europe/Paris"   {{ ($config->timezone ?? '') === 'Europe/Paris'   ? 'selected' : '' }}>Europe/Paris (GMT+2)</option>--}}
{{--                                <option value="UTC"            {{ ($config->timezone ?? '') === 'UTC'            ? 'selected' : '' }}>UTC</option>--}}
{{--                            </select>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div><div class="field-label">Format de date</div></div>--}}
{{--                            <select name="date_format" class="field-select">--}}
{{--                                <option value="d/m/Y" {{ ($config->date_format ?? 'd/m/Y') === 'd/m/Y' ? 'selected' : '' }}>JJ/MM/AAAA</option>--}}
{{--                                <option value="m/d/Y" {{ ($config->date_format ?? '') === 'm/d/Y'       ? 'selected' : '' }}>MM/DD/YYYY</option>--}}
{{--                                <option value="Y-m-d" {{ ($config->date_format ?? '') === 'Y-m-d'       ? 'selected' : '' }}>AAAA-MM-JJ</option>--}}
{{--                            </select>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-adjustments-horizontal"></i> Fonctionnalités</div>--}}
{{--                        <p class="config-card-desc">Activer ou désactiver les modules de l'application</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Mode maintenance</div>--}}
{{--                                <div class="field-hint">Affiche une page de maintenance aux utilisateurs</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="maintenance_mode" value="1" {{ ($config->maintenance_mode ?? false) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Inscription publique</div>--}}
{{--                                <div class="field-hint">Autoriser les nouveaux comptes</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="public_registration" value="1" {{ ($config->public_registration ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Journal d'activité</div>--}}
{{--                                <div class="field-hint">Enregistrer les actions des utilisateurs</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="activity_log" value="1" {{ ($config->activity_log ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Mode sombre forcé</div>--}}
{{--                                <div class="field-hint">Imposer le thème sombre à tous</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="force_dark_mode" value="1" {{ ($config->force_dark_mode ?? false) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-actions">--}}
{{--                        <a href="{{ route('admin.parametrage.configuration.index') }}" class="btn-outline">Annuler</a>--}}
{{--                        <button type="submit" class="btn-save">Enregistrer les modifications</button>--}}
{{--                    </div>--}}
{{--                </form>--}}

{{--            @endif--}}


{{--            --}}{{-- ============================================================--}}
{{--                PAGE : Profil--}}
{{--            ============================================================ --}}
{{--            @if(request('menus') === 'profil')--}}

{{--                <div class="config-header">--}}
{{--                    <div>--}}
{{--                        <div class="config-breadcrumb">Configuration <span>/ Profil</span></div>--}}
{{--                        <h1 class="config-page-title">Profil du CROU</h1>--}}
{{--                        <p class="config-page-sub">Informations légales et coordonnées de votre organisation</p>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                <form method="POST" action="{{ route('admin.parametrage.configuration.update', ['menus' => 'profil']) }}">--}}
{{--                    @csrf--}}
{{--                    @method('PUT')--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-building"></i> Identité de l'entreprise</div>--}}
{{--                        <p class="config-card-desc">Informations qui apparaissent sur vos documents officiels</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Raison sociale</div>--}}
{{--                                <div class="field-hint">Nom légal de l'entreprise</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="company_name" class="field-input"--}}
{{--                                   value="{{ old('company_name', $profil->company_name ?? '') }}" placeholder="Ex: MonApp SARL" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Numéro RCCM</div>--}}
{{--                                <div class="field-hint">Registre du commerce</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="rccm" class="field-input"--}}
{{--                                   value="{{ old('rccm', $profil->rccm ?? '') }}" placeholder="CI-ABJ-2024-B-00000" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Numéro contribuable (NCC)</div>--}}
{{--                                <div class="field-hint">Identifiant fiscal</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="ncc" class="field-input"--}}
{{--                                   value="{{ old('ncc', $profil->ncc ?? '') }}" placeholder="0000000A" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Secteur d'activité</div>--}}
{{--                            </div>--}}
{{--                            <select name="sector" class="field-select">--}}
{{--                                @foreach(['Commerce', 'Services', 'Technologie', 'Santé', 'Éducation', 'Transport', 'Autre'] as $sector)--}}
{{--                                    <option value="{{ $sector }}" {{ ($profil->sector ?? '') === $sector ? 'selected' : '' }}>{{ $sector }}</option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-map-pin"></i> Coordonnées</div>--}}
{{--                        <p class="config-card-desc">Adresse et contacts officiels du CROU</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Adresse</div>--}}
{{--                                <div class="field-hint">Siège social</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="address" class="field-input"--}}
{{--                                   value="{{ old('address', $profil->address ?? '') }}" placeholder="Rue, quartier, ville" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Pays</div>--}}
{{--                            </div>--}}
{{--                            <select name="country" class="field-select">--}}
{{--                                @foreach(["Côte d'Ivoire", 'Sénégal', 'Mali', 'Burkina Faso', 'Guinea', 'Togo', 'Bénin', 'Niger', 'France'] as $country)--}}
{{--                                    <option value="{{ $country }}" {{ ($profil->country ?? "Côte d'Ivoire") === $country ? 'selected' : '' }}>{{ $country }}</option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Téléphone</div>--}}
{{--                                <div class="field-hint">Numéro principal</div>--}}
{{--                            </div>--}}
{{--                            <input type="tel" name="phone" class="field-input"--}}
{{--                                   value="{{ old('phone', $profil->phone ?? '') }}" placeholder="+225 07 00 00 00 00" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">E-mail de contact</div>--}}
{{--                                <div class="field-hint">Adresse officielle</div>--}}
{{--                            </div>--}}
{{--                            <input type="email" name="email" class="field-input"--}}
{{--                                   value="{{ old('email', $profil->email ?? '') }}" placeholder="contact@monapp.ci" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Site web</div>--}}
{{--                            </div>--}}
{{--                            <input type="url" name="website" class="field-input"--}}
{{--                                   value="{{ old('website', $profil->website ?? '') }}" placeholder="https://monapp.ci" />--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-photo"></i> Logo & identité visuelle</div>--}}
{{--                        <p class="config-card-desc">Apparence sur les documents et l'interface</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Logo principal</div>--}}
{{--                                <div class="field-hint">PNG ou SVG, 512×512px recommandé</div>--}}
{{--                            </div>--}}
{{--                            <div class="file-upload-zone">--}}
{{--                                @if($profil->logo ?? false)--}}
{{--                                    <img src="{{ asset('storage/'.$profil->logo) }}" alt="Logo" class="logo-preview" />--}}
{{--                                @endif--}}
{{--                                <input type="file" name="logo" accept="image/*" class="file-input" id="logo-input" />--}}
{{--                                <label for="logo-input" class="file-label">--}}
{{--                                    <i class="ti ti-upload"></i> Choisir un fichier--}}
{{--                                </label>--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Couleur principale</div>--}}
{{--                                <div class="field-hint">Utilisée dans l'interface</div>--}}
{{--                            </div>--}}
{{--                            <div style="display:flex;align-items:center;gap:10px">--}}
{{--                                <input type="color" name="brand_color" class="color-picker"--}}
{{--                                       value="{{ old('brand_color', $profil->brand_color ?? '#534AB7') }}" />--}}
{{--                                <span class="field-hint" id="color-hex">{{ $profil->brand_color ?? '#534AB7' }}</span>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-actions">--}}
{{--                        <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'profil']) }}" class="btn-outline">Annuler</a>--}}
{{--                        <button type="submit" class="btn-save">Enregistrer les modifications</button>--}}
{{--                    </div>--}}
{{--                </form>--}}

{{--            @endif--}}


{{--            --}}{{-- ============================================================--}}
{{--                PAGE : Notifications--}}
{{--            ============================================================ --}}
{{--            @if(request('menus') === 'notifications')--}}

{{--                <div class="config-header">--}}
{{--                    <div>--}}
{{--                        <div class="config-breadcrumb">Configuration <span>/ Notifications</span></div>--}}
{{--                        <h1 class="config-page-title">Notifications</h1>--}}
{{--                        <p class="config-page-sub">Gérez les alertes et les canaux de communication</p>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                <form method="POST" action="{{ route('admin.parametrage.configuration.update', ['menus' => 'notifications']) }}">--}}
{{--                    @csrf--}}
{{--                    @method('PUT')--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-mail"></i> Notifications par e-mail</div>--}}
{{--                        <p class="config-card-desc">Paramètres du serveur d'envoi et des alertes e-mail</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Activer les e-mails</div>--}}
{{--                                <div class="field-hint">Envoi de notifications par e-mail</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="email_enabled" value="1" id="email_toggle"--}}
{{--                                        {{ ($notif->email_enabled ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div id="email-config" class="{{ ($notif->email_enabled ?? true) ? '' : 'd-none' }}">--}}
{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Pilote SMTP</div>--}}
{{--                                    <div class="field-hint">Fournisseur d'envoi</div>--}}
{{--                                </div>--}}
{{--                                <select name="mail_driver" class="field-select">--}}
{{--                                    @foreach(['smtp', 'mailgun', 'sendgrid', 'ses', 'log'] as $driver)--}}
{{--                                        <option value="{{ $driver }}" {{ ($notif->mail_driver ?? 'smtp') === $driver ? 'selected' : '' }}>{{ strtoupper($driver) }}</option>--}}
{{--                                    @endforeach--}}
{{--                                </select>--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Hôte SMTP</div>--}}
{{--                                </div>--}}
{{--                                <input type="text" name="mail_host" class="field-input"--}}
{{--                                       value="{{ old('mail_host', $notif->mail_host ?? '') }}" placeholder="smtp.monserveur.com" />--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Port</div>--}}
{{--                                </div>--}}
{{--                                <input type="number" name="mail_port" class="field-input"--}}
{{--                                       value="{{ old('mail_port', $notif->mail_port ?? 587) }}" placeholder="587" />--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Identifiant SMTP</div>--}}
{{--                                </div>--}}
{{--                                <input type="text" name="mail_username" class="field-input"--}}
{{--                                       value="{{ old('mail_username', $notif->mail_username ?? '') }}" placeholder="user@domaine.com" />--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Mot de passe SMTP</div>--}}
{{--                                </div>--}}
{{--                                <input type="password" name="mail_password" class="field-input"--}}
{{--                                       value="{{ old('mail_password', $notif->mail_password ?? '') }}" placeholder="••••••••" />--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Expéditeur (nom)</div>--}}
{{--                                    <div class="field-hint">Nom affiché dans la boîte de réception</div>--}}
{{--                                </div>--}}
{{--                                <input type="text" name="mail_from_name" class="field-input"--}}
{{--                                       value="{{ old('mail_from_name', $notif->mail_from_name ?? config('app.name')) }}" />--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Adresse expéditeur</div>--}}
{{--                                </div>--}}
{{--                                <input type="email" name="mail_from_address" class="field-input"--}}
{{--                                       value="{{ old('mail_from_address', $notif->mail_from_address ?? '') }}" placeholder="noreply@monapp.ci" />--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-device-mobile"></i> Notifications SMS</div>--}}
{{--                        <p class="config-card-desc">Alertes envoyées par SMS via une passerelle</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Activer les SMS</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="sms_enabled" value="1" id="sms_toggle"--}}
{{--                                        {{ ($notif->sms_enabled ?? false) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div id="sms-config" class="{{ ($notif->sms_enabled ?? false) ? '' : 'd-none' }}">--}}
{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Fournisseur SMS</div>--}}
{{--                                </div>--}}
{{--                                <select name="sms_provider" class="field-select">--}}
{{--                                    @foreach(['Orange SMS API', 'Twilio', 'Africa\'s Talking', 'InfoBip', 'CinetPay SMS'] as $sms)--}}
{{--                                        <option value="{{ strtolower(str_replace([' ', '\''], ['_', ''], $sms)) }}"--}}
{{--                                                {{ ($notif->sms_provider ?? '') === strtolower(str_replace([' ', '\''], ['_', ''], $sms)) ? 'selected' : '' }}>--}}
{{--                                            {{ $sms }}--}}
{{--                                        </option>--}}
{{--                                    @endforeach--}}
{{--                                </select>--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Clé API SMS</div>--}}
{{--                                </div>--}}
{{--                                <input type="text" name="sms_api_key" class="field-input"--}}
{{--                                       value="{{ old('sms_api_key', $notif->sms_api_key ?? '') }}" placeholder="sms_••••••••" />--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Expéditeur SMS</div>--}}
{{--                                    <div class="field-hint">Nom ou numéro affiché</div>--}}
{{--                                </div>--}}
{{--                                <input type="text" name="sms_sender" class="field-input"--}}
{{--                                       value="{{ old('sms_sender', $notif->sms_sender ?? 'MonApp') }}" placeholder="MonApp" />--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-bell"></i> Événements déclencheurs</div>--}}
{{--                        <p class="config-card-desc">Choisissez quand envoyer des notifications automatiques</p>--}}

{{--                        @php--}}
{{--                            $events = [--}}
{{--                                ['key' => 'notif_new_user',     'label' => 'Nouvel utilisateur inscrit',    'hint' => 'Alert admin à chaque inscription'],--}}
{{--                                ['key' => 'notif_new_order',    'label' => 'Nouvelle commande',             'hint' => 'Confirmation client + alert admin'],--}}
{{--                                ['key' => 'notif_payment_ok',   'label' => 'Paiement réussi',               'hint' => 'Reçu envoyé au client'],--}}
{{--                                ['key' => 'notif_payment_fail', 'label' => 'Échec de paiement',             'hint' => 'Alerte client et administrateur'],--}}
{{--                                ['key' => 'notif_low_stock',    'label' => 'Stock faible',                  'hint' => 'Alerte admin sous le seuil'],--}}
{{--                                ['key' => 'notif_report',       'label' => 'Rapport quotidien',             'hint' => 'Résumé envoyé chaque matin'],--}}
{{--                            ]--}}
{{--                        @endphp--}}

{{--                        @foreach($events as $event)--}}
{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">{{ $event['label'] }}</div>--}}
{{--                                    <div class="field-hint">{{ $event['hint'] }}</div>--}}
{{--                                </div>--}}
{{--                                <label class="toggle-switch">--}}
{{--                                    <input type="checkbox" name="{{ $event['key'] }}" value="1"--}}
{{--                                            {{ ($notif->{$event['key']} ?? true) ? 'checked' : '' }}>--}}
{{--                                    <span class="toggle-track"></span>--}}
{{--                                </label>--}}
{{--                            </div>--}}
{{--                        @endforeach--}}
{{--                    </div>--}}

{{--                    <div class="config-actions">--}}
{{--                        <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'notifications']) }}" class="btn-outline">Annuler</a>--}}
{{--                        <button type="submit" class="btn-save">Enregistrer les modifications</button>--}}
{{--                    </div>--}}
{{--                </form>--}}

{{--            @endif--}}


{{--            --}}{{-- ============================================================--}}
{{--                PAGE : Paiements (FINANCES)--}}
{{--            ============================================================ --}}
{{--            @if(request('menus') === 'paiements')--}}

{{--                <div class="config-header">--}}
{{--                    <div>--}}
{{--                        <div class="config-breadcrumb">Configuration <span>/ Paiements</span></div>--}}
{{--                        <h1 class="config-page-title">Paramètres financiers</h1>--}}
{{--                        <p class="config-page-sub">Modes de paiement et paiement électronique</p>--}}
{{--                    </div>--}}
{{--                    <span class="badge-status badge-info">En ligne</span>--}}
{{--                </div>--}}

{{--                <form method="POST" action="{{ route('admin.parametrage.configuration.update', ['menus' => 'paiements']) }}">--}}
{{--                    @csrf--}}
{{--                    @method('PUT')--}}

{{--                    --}}{{-- CARD : Modes de paiement acceptés --}}
{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-wallet"></i> Modes de paiement acceptés</div>--}}
{{--                        <p class="config-card-desc">Sélectionnez les modes de paiement disponibles pour vos clients</p>--}}

{{--                        <div class="payment-grid">--}}
{{--                            @php--}}
{{--                                $paymentMethods = [--}}
{{--                                    ['key' => 'card',     'icon' => 'ti-credit-card',   'label' => 'Carte bancaire',   'hint' => 'Visa, Mastercard'],--}}
{{--                                    ['key' => 'mobile',   'icon' => 'ti-device-mobile', 'label' => 'Mobile Money',     'hint' => 'Orange, MTN, Moov'],--}}
{{--                                    ['key' => 'transfer', 'icon' => 'ti-building-bank', 'label' => 'Virement bancaire','hint' => 'IBAN / RIB'],--}}
{{--                                    ['key' => 'cash',     'icon' => 'ti-cash',          'label' => 'Espèces',          'hint' => 'Paiement en agence'],--}}
{{--                                    ['key' => 'paypal',   'icon' => 'ti-brand-paypal',  'label' => 'PayPal',           'hint' => 'Compte PayPal'],--}}
{{--                                    ['key' => 'qrcode',   'icon' => 'ti-qrcode',        'label' => 'QR Code',          'hint' => 'Scan & Pay'],--}}
{{--                                ]--}}
{{--                            @endphp--}}

{{--                            @foreach($paymentMethods as $method)--}}
{{--                                @php $isActive = in_array($method['key'], $paiement->methods ?? ['card', 'mobile']); @endphp--}}
{{--                                <label class="pay-option {{ $isActive ? 'selected' : '' }}">--}}
{{--                                    <input type="checkbox" name="payment_methods[]"--}}
{{--                                           value="{{ $method['key'] }}"--}}
{{--                                           {{ $isActive ? 'checked' : '' }}--}}
{{--                                           class="pay-checkbox">--}}
{{--                                    <i class="ti {{ $method['icon'] }}"></i>--}}
{{--                                    <div>--}}
{{--                                        <div class="pay-label">{{ $method['label'] }}</div>--}}
{{--                                        <div class="pay-hint">{{ $method['hint'] }}</div>--}}
{{--                                    </div>--}}
{{--                                </label>--}}
{{--                            @endforeach--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    --}}{{-- CARD : Paiement électronique dans l'application --}}
{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-world"></i> Paiement électronique dans l'application</div>--}}
{{--                        <p class="config-card-desc">Activez et configurez le module de paiement en ligne intégré</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Activer le paiement en ligne</div>--}}
{{--                                <div class="field-hint">Permet aux clients de payer directement dans l'app</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="online_payment_enabled" value="1"--}}
{{--                                       id="online_payment_toggle"--}}
{{--                                        {{ ($paiement->online_payment_enabled ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div id="online-payment-config" class="{{ ($paiement->online_payment_enabled ?? true) ? '' : 'd-none' }}">--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Passerelle de paiement</div>--}}
{{--                                    <div class="field-hint">Fournisseur de services de paiement</div>--}}
{{--                                </div>--}}
{{--                                <select name="payment_gateway" class="field-select">--}}
{{--                                    @foreach(['CinetPay', 'Stripe', 'PayDunya', 'Kkiapay', 'Flutterwave'] as $gw)--}}
{{--                                        <option value="{{ strtolower($gw) }}"--}}
{{--                                                {{ ($paiement->payment_gateway ?? 'cinetpay') === strtolower($gw) ? 'selected' : '' }}>--}}
{{--                                            {{ $gw }}--}}
{{--                                        </option>--}}
{{--                                    @endforeach--}}
{{--                                </select>--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Clé API publique</div>--}}
{{--                                    <div class="field-hint">Fournie par votre passerelle</div>--}}
{{--                                </div>--}}
{{--                                <input type="text" name="api_key_public" class="field-input"--}}
{{--                                       value="{{ old('api_key_public', $paiement->api_key_public ?? '') }}"--}}
{{--                                       placeholder="pk_live_••••••••••" />--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Clé secrète</div>--}}
{{--                                    <div class="field-hint">Ne jamais partager cette clé</div>--}}
{{--                                </div>--}}
{{--                                <input type="password" name="api_key_secret" class="field-input"--}}
{{--                                       value="{{ old('api_key_secret', $paiement->api_key_secret ?? '') }}"--}}
{{--                                       placeholder="sk_live_••••••••••" />--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Mode test (sandbox)</div>--}}
{{--                                    <div class="field-hint">Transactions simulées sans débit réel</div>--}}
{{--                                </div>--}}
{{--                                <label class="toggle-switch">--}}
{{--                                    <input type="checkbox" name="sandbox_mode" value="1"--}}
{{--                                            {{ ($paiement->sandbox_mode ?? true) ? 'checked' : '' }}>--}}
{{--                                    <span class="toggle-track"></span>--}}
{{--                                </label>--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Devise principale</div>--}}
{{--                                    <div class="field-hint">Monnaie de facturation</div>--}}
{{--                                </div>--}}
{{--                                <select name="currency" class="field-select">--}}
{{--                                    <option value="XOF" {{ ($paiement->currency ?? 'XOF') === 'XOF' ? 'selected' : '' }}>XOF — Franc CFA</option>--}}
{{--                                    <option value="EUR" {{ ($paiement->currency ?? '') === 'EUR' ? 'selected' : '' }}>EUR — Euro</option>--}}
{{--                                    <option value="USD" {{ ($paiement->currency ?? '') === 'USD' ? 'selected' : '' }}>USD — Dollar</option>--}}
{{--                                    <option value="GHS" {{ ($paiement->currency ?? '') === 'GHS' ? 'selected' : '' }}>GHS — Cedi</option>--}}
{{--                                </select>--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Reçu par e-mail automatique</div>--}}
{{--                                    <div class="field-hint">Envoi après chaque transaction réussie</div>--}}
{{--                                </div>--}}
{{--                                <label class="toggle-switch">--}}
{{--                                    <input type="checkbox" name="auto_receipt" value="1"--}}
{{--                                            {{ ($paiement->auto_receipt ?? true) ? 'checked' : '' }}>--}}
{{--                                    <span class="toggle-track"></span>--}}
{{--                                </label>--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Montant minimum</div>--}}
{{--                                    <div class="field-hint">En XOF</div>--}}
{{--                                </div>--}}
{{--                                <input type="number" name="min_amount" class="field-input"--}}
{{--                                       value="{{ old('min_amount', $paiement->min_amount ?? 500) }}" />--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    --}}{{-- CARD : Sécurité & conformité --}}
{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-shield-check"></i> Sécurité & conformité</div>--}}
{{--                        <p class="config-card-desc">Options de protection des transactions</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Authentification 3D Secure</div>--}}
{{--                                <div class="field-hint">Vérification supplémentaire pour les cartes</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="three_d_secure" value="1"--}}
{{--                                        {{ ($paiement->three_d_secure ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Anti-fraude automatique</div>--}}
{{--                                <div class="field-hint">Bloquer les transactions suspectes</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="anti_fraud" value="1"--}}
{{--                                        {{ ($paiement->anti_fraud ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Plafond journalier par client</div>--}}
{{--                                <div class="field-hint">En XOF (0 = illimité)</div>--}}
{{--                            </div>--}}
{{--                            <input type="number" name="daily_limit" class="field-input"--}}
{{--                                   value="{{ old('daily_limit', $paiement->daily_limit ?? 500000) }}" />--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-actions">--}}
{{--                        <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'paiements']) }}" class="btn-outline">Annuler</a>--}}
{{--                        <button type="submit" class="btn-save">Enregistrer les modifications</button>--}}
{{--                    </div>--}}
{{--                </form>--}}

{{--            @endif--}}


{{--            --}}{{-- ============================================================--}}
{{--                PAGE : Taxes & TVA--}}
{{--            ============================================================ --}}
{{--            @if(request('menus') === 'taxes')--}}

{{--                <div class="config-header">--}}
{{--                    <div>--}}
{{--                        <div class="config-breadcrumb">Configuration <span>/ Taxes & TVA</span></div>--}}
{{--                        <h1 class="config-page-title">Taxes & TVA</h1>--}}
{{--                        <p class="config-page-sub">Règles de taxation applicables à vos transactions</p>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                <form method="POST" action="{{ route('admin.parametrage.configuration.update', ['menus' => 'taxes']) }}">--}}
{{--                    @csrf--}}
{{--                    @method('PUT')--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-receipt"></i> Paramètres de la TVA</div>--}}
{{--                        <p class="config-card-desc">Configuration du taux et du mode d'affichage</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Activer la TVA</div>--}}
{{--                                <div class="field-hint">Appliquer la TVA sur les transactions</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="tva_enabled" value="1" id="tva_toggle"--}}
{{--                                        {{ ($taxes->tva_enabled ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div id="tva-config" class="{{ ($taxes->tva_enabled ?? true) ? '' : 'd-none' }}">--}}
{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Taux de TVA (%)</div>--}}
{{--                                    <div class="field-hint">Taux standard applicable</div>--}}
{{--                                </div>--}}
{{--                                <input type="number" name="tva_rate" class="field-input" step="0.01"--}}
{{--                                       value="{{ old('tva_rate', $taxes->tva_rate ?? 18) }}" placeholder="18" />--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Numéro de TVA intracommunautaire</div>--}}
{{--                                    <div class="field-hint">Affiché sur les factures</div>--}}
{{--                                </div>--}}
{{--                                <input type="text" name="tva_number" class="field-input"--}}
{{--                                       value="{{ old('tva_number', $taxes->tva_number ?? '') }}" placeholder="CI 000 000 000" />--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">Affichage des prix</div>--}}
{{--                                    <div class="field-hint">TTC ou HT par défaut</div>--}}
{{--                                </div>--}}
{{--                                <select name="price_display" class="field-select">--}}
{{--                                    <option value="TTC" {{ ($taxes->price_display ?? 'TTC') === 'TTC' ? 'selected' : '' }}>Prix TTC (TVA incluse)</option>--}}
{{--                                    <option value="HT"  {{ ($taxes->price_display ?? '') === 'HT'  ? 'selected' : '' }}>Prix HT (hors taxe)</option>--}}
{{--                                </select>--}}
{{--                            </div>--}}

{{--                            <div class="config-field-row">--}}
{{--                                <div>--}}
{{--                                    <div class="field-label">TVA sur les services</div>--}}
{{--                                    <div class="field-hint">Appliquer aux prestations de service</div>--}}
{{--                                </div>--}}
{{--                                <label class="toggle-switch">--}}
{{--                                    <input type="checkbox" name="tva_on_services" value="1"--}}
{{--                                            {{ ($taxes->tva_on_services ?? true) ? 'checked' : '' }}>--}}
{{--                                    <span class="toggle-track"></span>--}}
{{--                                </label>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-percentage"></i> Taxes additionnelles</div>--}}
{{--                        <p class="config-card-desc">Autres taxes ou contributions applicables</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Taxe sur les paiements électroniques</div>--}}
{{--                                <div class="field-hint">% retenu sur chaque transaction en ligne</div>--}}
{{--                            </div>--}}
{{--                            <input type="number" name="digital_tax_rate" class="field-input" step="0.01"--}}
{{--                                   value="{{ old('digital_tax_rate', $taxes->digital_tax_rate ?? 0) }}" placeholder="0" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Frais de service</div>--}}
{{--                                <div class="field-hint">Montant fixe ajouté à chaque commande (XOF)</div>--}}
{{--                            </div>--}}
{{--                            <input type="number" name="service_fee" class="field-input"--}}
{{--                                   value="{{ old('service_fee', $taxes->service_fee ?? 0) }}" placeholder="0" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Appliquer les taxes aux livraisons</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="tax_on_delivery" value="1"--}}
{{--                                        {{ ($taxes->tax_on_delivery ?? false) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-actions">--}}
{{--                        <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'taxes']) }}" class="btn-outline">Annuler</a>--}}
{{--                        <button type="submit" class="btn-save">Enregistrer les modifications</button>--}}
{{--                    </div>--}}
{{--                </form>--}}

{{--            @endif--}}


{{--            --}}{{-- ============================================================--}}
{{--                PAGE : Devises--}}
{{--            ============================================================ --}}
{{--            @if(request('menus') === 'devises')--}}

{{--                <div class="config-header">--}}
{{--                    <div>--}}
{{--                        <div class="config-breadcrumb">Configuration <span>/ Devises</span></div>--}}
{{--                        <h1 class="config-page-title">Gestion des devises</h1>--}}
{{--                        <p class="config-page-sub">Monnaies acceptées et taux de conversion</p>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                <form method="POST" action="{{ route('admin.parametrage.configuration.update', ['menus' => 'devises']) }}">--}}
{{--                    @csrf--}}
{{--                    @method('PUT')--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-currency-dollar"></i> Devise principale</div>--}}
{{--                        <p class="config-card-desc">Monnaie de référence pour toutes les transactions</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Devise de base</div>--}}
{{--                                <div class="field-hint">Toutes les conversions partent de cette devise</div>--}}
{{--                            </div>--}}
{{--                            <select name="base_currency" class="field-select">--}}
{{--                                @php--}}
{{--                                    $currencies = [--}}
{{--                                        'XOF' => 'XOF — Franc CFA (UEMOA)',--}}
{{--                                        'XAF' => 'XAF — Franc CFA (CEMAC)',--}}
{{--                                        'EUR' => 'EUR — Euro',--}}
{{--                                        'USD' => 'USD — Dollar américain',--}}
{{--                                        'GHS' => 'GHS — Cedi ghanéen',--}}
{{--                                        'NGN' => 'NGN — Naira nigérian',--}}
{{--                                        'KES' => 'KES — Shilling kényan',--}}
{{--                                    ]--}}
{{--                                @endphp--}}
{{--                                @foreach($currencies as $code => $label)--}}
{{--                                    <option value="{{ $code }}" {{ ($devises->base_currency ?? 'XOF') === $code ? 'selected' : '' }}>{{ $label }}</option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Symbole affiché</div>--}}
{{--                                <div class="field-hint">Avant ou après le montant</div>--}}
{{--                            </div>--}}
{{--                            <div style="display:flex;gap:8px;align-items:center">--}}
{{--                                <input type="text" name="currency_symbol" class="field-input" style="width:80px"--}}
{{--                                       value="{{ old('currency_symbol', $devises->currency_symbol ?? 'FCFA') }}" />--}}
{{--                                <select name="symbol_position" class="field-select" style="min-width:120px">--}}
{{--                                    <option value="after"  {{ ($devises->symbol_position ?? 'after')  === 'after'  ? 'selected' : '' }}>Après (100 FCFA)</option>--}}
{{--                                    <option value="before" {{ ($devises->symbol_position ?? '') === 'before' ? 'selected' : '' }}>Avant (FCFA 100)</option>--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Séparateur de milliers</div>--}}
{{--                            </div>--}}
{{--                            <select name="thousands_separator" class="field-select">--}}
{{--                                <option value=" "  {{ ($devises->thousands_separator ?? ' ') === ' '  ? 'selected' : '' }}>Espace (1 000)</option>--}}
{{--                                <option value="."  {{ ($devises->thousands_separator ?? '') === '.'  ? 'selected' : '' }}>Point (1.000)</option>--}}
{{--                                <option value=","  {{ ($devises->thousands_separator ?? '') === ','  ? 'selected' : '' }}>Virgule (1,000)</option>--}}
{{--                            </select>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-arrows-exchange"></i> Devises secondaires actives</div>--}}
{{--                        <p class="config-card-desc">Devises acceptées en plus de la devise principale</p>--}}

{{--                        <div class="payment-grid">--}}
{{--                            @php--}}
{{--                                $secondaryCurrencies = [--}}
{{--                                    ['code' => 'EUR', 'label' => 'Euro',            'flag' => '🇪🇺'],--}}
{{--                                    ['code' => 'USD', 'label' => 'Dollar',          'flag' => '🇺🇸'],--}}
{{--                                    ['code' => 'GHS', 'label' => 'Cedi',            'flag' => '🇬🇭'],--}}
{{--                                    ['code' => 'NGN', 'label' => 'Naira',           'flag' => '🇳🇬'],--}}
{{--                                    ['code' => 'XAF', 'label' => 'Franc CFA CEMAC','flag' => '🌍'],--}}
{{--                                    ['code' => 'GBP', 'label' => 'Livre sterling', 'flag' => '🇬🇧'],--}}
{{--                                ]--}}
{{--                            @endphp--}}
{{--                            @foreach($secondaryCurrencies as $cur)--}}
{{--                                @php $isActive = in_array($cur['code'], $devises->secondary_currencies ?? []); @endphp--}}
{{--                                <label class="pay-option {{ $isActive ? 'selected' : '' }}">--}}
{{--                                    <input type="checkbox" name="secondary_currencies[]"--}}
{{--                                           value="{{ $cur['code'] }}" {{ $isActive ? 'checked' : '' }} class="pay-checkbox">--}}
{{--                                    <span style="font-size:22px">{{ $cur['flag'] }}</span>--}}
{{--                                    <div>--}}
{{--                                        <div class="pay-label">{{ $cur['code'] }}</div>--}}
{{--                                        <div class="pay-hint">{{ $cur['label'] }}</div>--}}
{{--                                    </div>--}}
{{--                                </label>--}}
{{--                            @endforeach--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-refresh"></i> Taux de change</div>--}}
{{--                        <p class="config-card-desc">Source et fréquence de mise à jour des taux</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Mise à jour automatique</div>--}}
{{--                                <div class="field-hint">Récupérer les taux via une API externe</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="auto_rate" value="1"--}}
{{--                                        {{ ($devises->auto_rate ?? false) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Fréquence de mise à jour</div>--}}
{{--                            </div>--}}
{{--                            <select name="rate_frequency" class="field-select">--}}
{{--                                <option value="hourly"  {{ ($devises->rate_frequency ?? 'daily') === 'hourly'  ? 'selected' : '' }}>Toutes les heures</option>--}}
{{--                                <option value="daily"   {{ ($devises->rate_frequency ?? 'daily') === 'daily'   ? 'selected' : '' }}>Quotidienne</option>--}}
{{--                                <option value="weekly"  {{ ($devises->rate_frequency ?? 'daily') === 'weekly'  ? 'selected' : '' }}>Hebdomadaire</option>--}}
{{--                                <option value="manual"  {{ ($devises->rate_frequency ?? 'daily') === 'manual'  ? 'selected' : '' }}>Manuelle</option>--}}
{{--                            </select>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-actions">--}}
{{--                        <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'devises']) }}" class="btn-outline">Annuler</a>--}}
{{--                        <button type="submit" class="btn-save">Enregistrer les modifications</button>--}}
{{--                    </div>--}}
{{--                </form>--}}

{{--            @endif--}}


{{--            --}}{{-- ============================================================--}}
{{--                PAGE : Intégrations--}}
{{--            ============================================================ --}}
{{--            @if(request('menus') === 'integrations')--}}

{{--                <div class="config-header">--}}
{{--                    <div>--}}
{{--                        <div class="config-breadcrumb">Configuration <span>/ Intégrations</span></div>--}}
{{--                        <h1 class="config-page-title">Intégrations</h1>--}}
{{--                        <p class="config-page-sub">Connectez des services et outils tiers à votre application</p>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                <form method="POST" action="{{ route('admin.parametrage.configuration.update', ['menus' => 'integrations']) }}">--}}
{{--                    @csrf--}}
{{--                    @method('PUT')--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-map-pin"></i> Géolocalisation & cartographie</div>--}}
{{--                        <p class="config-card-desc">Services de maps et localisation</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Activer Google Maps</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="google_maps_enabled" value="1"--}}
{{--                                        {{ ($integrations->google_maps_enabled ?? false) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Clé API Google Maps</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="google_maps_key" class="field-input"--}}
{{--                                   value="{{ old('google_maps_key', $integrations->google_maps_key ?? '') }}"--}}
{{--                                   placeholder="AIza••••••••••••••••" />--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-chart-bar"></i> Analytique & statistiques</div>--}}
{{--                        <p class="config-card-desc">Outils de suivi du comportement utilisateur</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Google Analytics</div>--}}
{{--                                <div class="field-hint">ID de suivi (GA4)</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="ga_tracking_id" class="field-input"--}}
{{--                                   value="{{ old('ga_tracking_id', $integrations->ga_tracking_id ?? '') }}"--}}
{{--                                   placeholder="G-XXXXXXXXXX" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Facebook Pixel</div>--}}
{{--                                <div class="field-hint">ID du pixel Meta</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="fb_pixel_id" class="field-input"--}}
{{--                                   value="{{ old('fb_pixel_id', $integrations->fb_pixel_id ?? '') }}"--}}
{{--                                   placeholder="000000000000000" />--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-message-circle"></i> Chat & support client</div>--}}
{{--                        <p class="config-card-desc">Widgets de support intégrés à l'interface</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Activer le chat en direct</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="livechat_enabled" value="1"--}}
{{--                                        {{ ($integrations->livechat_enabled ?? false) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Fournisseur de chat</div>--}}
{{--                            </div>--}}
{{--                            <select name="livechat_provider" class="field-select">--}}
{{--                                @foreach(['Intercom', 'Crisp', 'Tawk.to', 'Zendesk', 'WhatsApp Business'] as $chat)--}}
{{--                                    <option value="{{ strtolower(str_replace([' ', '.'], ['_', ''], $chat)) }}"--}}
{{--                                            {{ ($integrations->livechat_provider ?? '') === strtolower(str_replace([' ', '.'], ['_', ''], $chat)) ? 'selected' : '' }}>--}}
{{--                                        {{ $chat }}--}}
{{--                                    </option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Clé / Token du widget</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="livechat_token" class="field-input"--}}
{{--                                   value="{{ old('livechat_token', $integrations->livechat_token ?? '') }}"--}}
{{--                                   placeholder="token••••••••" />--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-key"></i> Authentification sociale</div>--}}
{{--                        <p class="config-card-desc">Permettre la connexion via des comptes tiers</p>--}}

{{--                        @php--}}
{{--                            $socialProviders = [--}}
{{--                                ['key' => 'google',   'label' => 'Google',   'icon' => 'ti-brand-google'],--}}
{{--                                ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'ti-brand-facebook'],--}}
{{--                                ['key' => 'github',   'label' => 'GitHub',   'icon' => 'ti-brand-github'],--}}
{{--                            ]--}}
{{--                        @endphp--}}

{{--                        @foreach($socialProviders as $provider)--}}
{{--                            <div class="config-field-row">--}}
{{--                                <div style="display:flex;align-items:center;gap:10px">--}}
{{--                                    <i class="ti {{ $provider['icon'] }}" style="font-size:20px;color:#6b7280"></i>--}}
{{--                                    <div>--}}
{{--                                        <div class="field-label">{{ $provider['label'] }}</div>--}}
{{--                                        <div class="field-hint">Connexion via {{ $provider['label'] }}</div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                                <label class="toggle-switch">--}}
{{--                                    <input type="checkbox" name="social_{{ $provider['key'] }}" value="1"--}}
{{--                                            {{ ($integrations->{'social_'.$provider['key']} ?? false) ? 'checked' : '' }}>--}}
{{--                                    <span class="toggle-track"></span>--}}
{{--                                </label>--}}
{{--                            </div>--}}
{{--                        @endforeach--}}
{{--                    </div>--}}

{{--                    <div class="config-actions">--}}
{{--                        <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'integrations']) }}" class="btn-outline">Annuler</a>--}}
{{--                        <button type="submit" class="btn-save">Enregistrer les modifications</button>--}}
{{--                    </div>--}}
{{--                </form>--}}

{{--            @endif--}}


{{--            --}}{{-- ============================================================--}}
{{--                PAGE : Sécurité--}}
{{--            ============================================================ --}}
{{--            @if(request('menus') === 'securite')--}}

{{--                <div class="config-header">--}}
{{--                    <div>--}}
{{--                        <div class="config-breadcrumb">Configuration <span>/ Sécurité</span></div>--}}
{{--                        <h1 class="config-page-title">Sécurité</h1>--}}
{{--                        <p class="config-page-sub">Accès, authentification et politique de mots de passe</p>--}}
{{--                    </div>--}}
{{--                    <span class="badge-status badge-on">Protégé</span>--}}
{{--                </div>--}}

{{--                <form method="POST" action="{{ route('admin.parametrage.configuration.update', ['menus' => 'securite']) }}">--}}
{{--                    @csrf--}}
{{--                    @method('PUT')--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-shield-lock"></i> Authentification</div>--}}
{{--                        <p class="config-card-desc">Méthodes et règles de connexion</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Double authentification (2FA)</div>--}}
{{--                                <div class="field-hint">Obligatoire pour les administrateurs</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="two_factor_auth" value="1"--}}
{{--                                        {{ ($securite->two_factor_auth ?? false) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Méthode 2FA</div>--}}
{{--                            </div>--}}
{{--                            <select name="two_factor_method" class="field-select">--}}
{{--                                <option value="totp"  {{ ($securite->two_factor_method ?? 'totp') === 'totp'  ? 'selected' : '' }}>Application TOTP (Google Authenticator)</option>--}}
{{--                                <option value="sms"   {{ ($securite->two_factor_method ?? '') === 'sms'   ? 'selected' : '' }}>SMS</option>--}}
{{--                                <option value="email" {{ ($securite->two_factor_method ?? '') === 'email' ? 'selected' : '' }}>E-mail</option>--}}
{{--                            </select>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Durée de session (minutes)</div>--}}
{{--                                <div class="field-hint">Déconnexion automatique après inactivité</div>--}}
{{--                            </div>--}}
{{--                            <input type="number" name="session_lifetime" class="field-input"--}}
{{--                                   value="{{ old('session_lifetime', $securite->session_lifetime ?? 120) }}" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Sessions simultanées</div>--}}
{{--                                <div class="field-hint">Autoriser plusieurs connexions par compte</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="allow_multiple_sessions" value="1"--}}
{{--                                        {{ ($securite->allow_multiple_sessions ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-lock-password"></i> Politique de mots de passe</div>--}}
{{--                        <p class="config-card-desc">Règles de complexité imposées aux utilisateurs</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Longueur minimale</div>--}}
{{--                                <div class="field-hint">Nombre de caractères minimum</div>--}}
{{--                            </div>--}}
{{--                            <input type="number" name="password_min_length" class="field-input"--}}
{{--                                   value="{{ old('password_min_length', $securite->password_min_length ?? 8) }}" min="6" max="32" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Majuscule obligatoire</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="password_require_uppercase" value="1"--}}
{{--                                        {{ ($securite->password_require_uppercase ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Chiffre obligatoire</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="password_require_number" value="1"--}}
{{--                                        {{ ($securite->password_require_number ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Caractère spécial obligatoire</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="password_require_special" value="1"--}}
{{--                                        {{ ($securite->password_require_special ?? false) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Expiration du mot de passe (jours)</div>--}}
{{--                                <div class="field-hint">0 = jamais</div>--}}
{{--                            </div>--}}
{{--                            <input type="number" name="password_expiry_days" class="field-input"--}}
{{--                                   value="{{ old('password_expiry_days', $securite->password_expiry_days ?? 0) }}" />--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-ban"></i> Contrôle d'accès</div>--}}
{{--                        <p class="config-card-desc">Restrictions et protections contre les attaques</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Tentatives de connexion max</div>--}}
{{--                                <div class="field-hint">Avant blocage temporaire du compte</div>--}}
{{--                            </div>--}}
{{--                            <input type="number" name="max_login_attempts" class="field-input"--}}
{{--                                   value="{{ old('max_login_attempts', $securite->max_login_attempts ?? 5) }}" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Durée de blocage (minutes)</div>--}}
{{--                                <div class="field-hint">Après dépassement du nombre de tentatives</div>--}}
{{--                            </div>--}}
{{--                            <input type="number" name="lockout_duration" class="field-input"--}}
{{--                                   value="{{ old('lockout_duration', $securite->lockout_duration ?? 15) }}" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Restriction par IP</div>--}}
{{--                                <div class="field-hint">Autoriser uniquement certaines adresses IP</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="ip_restriction" value="1"--}}
{{--                                        {{ ($securite->ip_restriction ?? false) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Forcer HTTPS</div>--}}
{{--                                <div class="field-hint">Rediriger tout le trafic en HTTPS</div>--}}
{{--                            </div>--}}
{{--                            <label class="toggle-switch">--}}
{{--                                <input type="checkbox" name="force_https" value="1"--}}
{{--                                        {{ ($securite->force_https ?? true) ? 'checked' : '' }}>--}}
{{--                                <span class="toggle-track"></span>--}}
{{--                            </label>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="config-actions">--}}
{{--                        <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'securite']) }}" class="btn-outline">Annuler</a>--}}
{{--                        <button type="submit" class="btn-save">Enregistrer les modifications</button>--}}
{{--                    </div>--}}
{{--                </form>--}}

{{--            @endif--}}

{{--        </main>--}}
{{--    </div>--}}

{{--@endsection--}}


{{--@push('styles')--}}
{{--    <style>--}}
{{--        .config-wrapper {--}}
{{--            display: flex;--}}
{{--            border: 1px solid #e5e7eb;--}}
{{--            border-radius: 12px;--}}
{{--            background: #fff;--}}
{{--            min-height: 780px;--}}
{{--            overflow: hidden;--}}
{{--        }--}}

{{--        /* Sidebar */--}}
{{--        .config-sidebar {--}}
{{--            width: 220px;--}}
{{--            border-right: 1px solid #e5e7eb;--}}
{{--            padding: 1rem 0;--}}
{{--            flex-shrink: 0;--}}
{{--            background: #fff;--}}
{{--        }--}}
{{--        .config-brand {--}}
{{--            display: flex;--}}
{{--            align-items: center;--}}
{{--            gap: 8px;--}}
{{--            padding: 12px 16px;--}}
{{--            border-bottom: 1px solid #e5e7eb;--}}
{{--            margin-bottom: 8px;--}}
{{--        }--}}
{{--        .config-brand-icon {--}}
{{--            width: 28px; height: 28px;--}}
{{--            border-radius: 6px;--}}
{{--            background: #534AB7;--}}
{{--            display: flex; align-items: center; justify-content: center;--}}
{{--            color: #fff; font-size: 15px;--}}
{{--        }--}}
{{--        .config-brand-name { font-size: var(--app-font-min, 15px); font-weight: 500; color: #111; }--}}
{{--        .config-nav-section {--}}
{{--            font-size: var(--app-font-min, 15px); color: #9ca3af;--}}
{{--            padding: 12px 16px 4px;--}}
{{--            text-transform: uppercase; letter-spacing: .06em;--}}
{{--        }--}}
{{--        .config-nav-item {--}}
{{--            display: flex; align-items: center; gap: 10px;--}}
{{--            padding: 10px 16px;--}}
{{--            font-size: var(--app-font-min, 15px); color: #6b7280;--}}
{{--            text-decoration: none;--}}
{{--            transition: background .15s;--}}
{{--            border-left: 2px solid transparent;--}}
{{--        }--}}
{{--        .config-nav-item:hover { background: #f9fafb; color: #111; text-decoration: none; }--}}
{{--        .config-nav-item.active { background: #f3f4f6; color: #111; font-weight: 500; border-left-color: #534AB7; }--}}
{{--        .config-nav-item i { font-size: 17px; }--}}

{{--        /* Content */--}}
{{--        .config-content { flex: 1; padding: 2rem; overflow-y: auto; }--}}
{{--        .config-header {--}}
{{--            display: flex; align-items: flex-start;--}}
{{--            justify-content: space-between; margin-bottom: 1.5rem;--}}
{{--        }--}}
{{--        .config-breadcrumb { font-size: var(--app-font-min, 15px); color: #9ca3af; margin-bottom: 4px; }--}}
{{--        .config-breadcrumb span { color: #111; }--}}
{{--        .config-page-title { font-size: 18px; font-weight: 500; color: #111; margin: 0 0 4px; }--}}
{{--        .config-page-sub { font-size: var(--app-font-min, 15px); color: #6b7280; margin: 0; }--}}

{{--        /* Cards */--}}
{{--        .config-card {--}}
{{--            background: #fff;--}}
{{--            border: 1px solid #e5e7eb;--}}
{{--            border-radius: 12px;--}}
{{--            padding: 1.25rem;--}}
{{--            margin-bottom: 1rem;--}}
{{--        }--}}
{{--        .config-card-title {--}}
{{--            font-size: var(--app-font-min, 15px); font-weight: 500; color: #111;--}}
{{--            display: flex; align-items: center; gap: 8px; margin-bottom: 4px;--}}
{{--        }--}}
{{--        .config-card-title i { color: #534AB7; font-size: 16px; }--}}
{{--        .config-card-desc { font-size: var(--app-font-min, 15px); color: #6b7280; margin-bottom: 1rem; }--}}

{{--        /* Field rows */--}}
{{--        .config-field-row {--}}
{{--            display: flex; justify-content: space-between; align-items: center;--}}
{{--            padding: 10px 0;--}}
{{--            border-bottom: 1px solid #f3f4f6;--}}
{{--        }--}}
{{--        .config-field-row:last-child { border-bottom: none; padding-bottom: 0; }--}}
{{--        .field-label { font-size: var(--app-font-min, 15px); font-weight: 500; color: #111; }--}}
{{--        .field-hint  { font-size: var(--app-font-min, 15px); color: #9ca3af; margin-top: 2px; }--}}
{{--        .field-input, .field-select {--}}
{{--            padding: 6px 10px;--}}
{{--            border: 1px solid #d1d5db;--}}
{{--            border-radius: 8px;--}}
{{--            font-size: var(--app-font-min, 15px); color: #111; background: #fff;--}}
{{--            min-width: 160px;--}}
{{--        }--}}
{{--        .field-input { width: 200px; }--}}
{{--        .field-input:focus, .field-select:focus {--}}
{{--            outline: none; border-color: #534AB7;--}}
{{--            box-shadow: 0 0 0 3px rgba(83,74,183,.15);--}}
{{--        }--}}

{{--        /* Toggle */--}}
{{--        .toggle-switch { display: inline-flex; align-items: center; cursor: pointer; }--}}
{{--        .toggle-switch input { display: none; }--}}
{{--        .toggle-track {--}}
{{--            width: 38px; height: 22px;--}}
{{--            background: #d1d5db; border-radius: 11px;--}}
{{--            position: relative; transition: background .2s;--}}
{{--        }--}}
{{--        .toggle-track::after {--}}
{{--            content: ''; width: 16px; height: 16px;--}}
{{--            background: #fff; border-radius: 50%;--}}
{{--            position: absolute; top: 3px; left: 3px; transition: left .2s;--}}
{{--        }--}}
{{--        .toggle-switch input:checked + .toggle-track { background: #534AB7; }--}}
{{--        .toggle-switch input:checked + .toggle-track::after { left: 19px; }--}}

{{--        /* Payment / currency grid */--}}
{{--        .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }--}}
{{--        .pay-option {--}}
{{--            border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px;--}}
{{--            cursor: pointer; display: flex; align-items: center; gap: 10px;--}}
{{--            transition: border-color .15s, background .15s;--}}
{{--        }--}}
{{--        .pay-option.selected { border-color: #534AB7; background: #EEEDFE; }--}}
{{--        .pay-checkbox { display: none; }--}}
{{--        .pay-option i { font-size: 22px; color: #9ca3af; }--}}
{{--        .pay-option.selected i { color: #534AB7; }--}}
{{--        .pay-label { font-size: var(--app-font-min, 15px); font-weight: 500; color: #111; }--}}
{{--        .pay-hint  { font-size: var(--app-font-min, 15px); color: #6b7280; }--}}

{{--        /* Badges */--}}
{{--        .badge-status { font-size: var(--app-font-min, 15px); padding: 3px 10px; border-radius: 20px; font-weight: 500; }--}}
{{--        .badge-on   { background: #EAF3DE; color: #3B6D11; }--}}
{{--        .badge-info { background: #E6F1FB; color: #185FA5; }--}}

{{--        /* File upload */--}}
{{--        .file-upload-zone { display: flex; align-items: center; gap: 10px; }--}}
{{--        .file-input { display: none; }--}}
{{--        .file-label {--}}
{{--            display: inline-flex; align-items: center; gap: 6px;--}}
{{--            padding: 6px 14px;--}}
{{--            border: 1px solid #d1d5db; border-radius: 8px;--}}
{{--            font-size: var(--app-font-min, 15px); color: #6b7280; cursor: pointer;--}}
{{--            transition: background .15s;--}}
{{--        }--}}
{{--        .file-label:hover { background: #f9fafb; }--}}
{{--        .logo-preview { width: 40px; height: 40px; object-fit: contain; border-radius: 6px; border: 1px solid #e5e7eb; }--}}

{{--        /* Color picker */--}}
{{--        .color-picker { width: 40px; height: 34px; padding: 2px; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer; }--}}

{{--        /* Actions */--}}
{{--        .config-actions { display: flex; justify-content: flex-end; margin-top: 1rem; gap: 8px; }--}}
{{--        .btn-save {--}}
{{--            background: #534AB7; color: #fff; border: none;--}}
{{--            border-radius: 8px; padding: 9px 20px;--}}
{{--            font-size: var(--app-font-min, 15px); font-weight: 500; cursor: pointer;--}}
{{--        }--}}
{{--        .btn-save:hover { background: #3C3489; }--}}
{{--        .btn-outline {--}}
{{--            background: transparent; color: #6b7280;--}}
{{--            border: 1px solid #d1d5db; border-radius: 8px;--}}
{{--            padding: 9px 20px; font-size: var(--app-font-min, 15px); cursor: pointer; text-decoration: none;--}}
{{--        }--}}
{{--        .btn-outline:hover { background: #f9fafb; }--}}

{{--        .d-none { display: none; }--}}
{{--    </style>--}}
{{--@endpush--}}


{{--@push('scripts')--}}
{{--    <script>--}}
{{--        document.addEventListener('DOMContentLoaded', function () {--}}

{{--            /* ---- Paiement en ligne ---- */--}}
{{--            const payToggle = document.getElementById('online_payment_toggle');--}}
{{--            const payConfig = document.getElementById('online-payment-config');--}}
{{--            if (payToggle && payConfig) {--}}
{{--                payToggle.addEventListener('change', function () {--}}
{{--                    payConfig.classList.toggle('d-none', !this.checked);--}}
{{--                });--}}
{{--            }--}}

{{--            /* ---- E-mail ---- */--}}
{{--            const emailToggle = document.getElementById('email_toggle');--}}
{{--            const emailConfig = document.getElementById('email-config');--}}
{{--            if (emailToggle && emailConfig) {--}}
{{--                emailToggle.addEventListener('change', function () {--}}
{{--                    emailConfig.classList.toggle('d-none', !this.checked);--}}
{{--                });--}}
{{--            }--}}

{{--            /* ---- SMS ---- */--}}
{{--            const smsToggle = document.getElementById('sms_toggle');--}}
{{--            const smsConfig = document.getElementById('sms-config');--}}
{{--            if (smsToggle && smsConfig) {--}}
{{--                smsToggle.addEventListener('change', function () {--}}
{{--                    smsConfig.classList.toggle('d-none', !this.checked);--}}
{{--                });--}}
{{--            }--}}

{{--            /* ---- TVA ---- */--}}
{{--            const tvaToggle = document.getElementById('tva_toggle');--}}
{{--            const tvaConfig = document.getElementById('tva-config');--}}
{{--            if (tvaToggle && tvaConfig) {--}}
{{--                tvaToggle.addEventListener('change', function () {--}}
{{--                    tvaConfig.classList.toggle('d-none', !this.checked);--}}
{{--                });--}}
{{--            }--}}

{{--            /* ---- Sélection cartes de paiement / devises ---- */--}}
{{--            document.querySelectorAll('.pay-option').forEach(function (label) {--}}
{{--                label.addEventListener('click', function (e) {--}}
{{--                    // éviter double toggle si on clique sur la checkbox--}}
{{--                    if (e.target.tagName === 'INPUT') return;--}}
{{--                    this.classList.toggle('selected');--}}
{{--                    const cb = this.querySelector('.pay-checkbox');--}}
{{--                    if (cb) cb.checked = !cb.checked;--}}
{{--                });--}}
{{--            });--}}

{{--            /* ---- Aperçu couleur de marque ---- */--}}
{{--            const colorPicker = document.querySelector('.color-picker');--}}
{{--            const colorHex    = document.getElementById('color-hex');--}}
{{--            if (colorPicker && colorHex) {--}}
{{--                colorPicker.addEventListener('input', function () {--}}
{{--                    colorHex.textContent = this.value;--}}
{{--                });--}}
{{--            }--}}

{{--        });--}}
{{--    </script>--}}
{{--@endpush--}}




@extends('admin.layouts.app', [
    'activeMenu' => 'parametres',
    'activeSubMenu' => 'paiements'
])

@section('title', 'Gestion Parametrage | Configuration')

@section('content')

    <div class="config-wrapper">

        {{-- ── Sidebar ────────────────────────────────────────────────────────── --}}
        <aside class="config-sidebar">
            <div class="config-brand">

                <span class="config-brand-name">Parametres généraux</span>
            </div>

            <nav class="config-nav">
                <div class="config-nav-section">Général</div>

                @adminroute('admin.parametrage.configuration.index')
                <a href="{{ route('admin.parametrage.configuration.index') }}"
                   class="config-nav-item {{ !request('menus') || request('menus') === 'configuration' ? 'active' : '' }}">
                    <i class="ti ti-settings"></i> Configuration
                </a>
                @endadminroute
                @adminroute('admin.parametrage.configuration.index')
                <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'profil']) }}"
                   class="config-nav-item {{ request('menus') === 'profil' ? 'active' : '' }}">
                    <i class="ti ti-user"></i> Profil
                </a>
                @endadminroute
                @adminroute('admin.parametrage.configuration.index')
                <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'notifications']) }}"
                   class="config-nav-item {{ request('menus') === 'notifications' ? 'active' : '' }}">
                    <i class="ti ti-bell"></i> Notifications
                </a>
                @endadminroute




{{--                <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'integrations']) }}"--}}
{{--                   class="config-nav-item {{ request('menus') === 'integrations' ? 'active' : '' }}">--}}
{{--                    <i class="ti ti-plug"></i> Intégrations--}}
{{--                </a>--}}
            </nav>
        </aside>

        {{-- ── Contenu principal ──────────────────────────────────────────────── --}}
        <main class="config-content">

            {{-- Alertes succès / erreurs --}}
            @if(session('success'))
                <div class="alert-success">
                    <i class="ti ti-circle-check"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert-error">
                    <i class="ti ti-alert-circle"></i>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ================================================================
                 PAGE : Configuration générale
            ================================================================ --}}
            @if(!request('menus') || request('menus') === 'configuration')

                <div class="config-header">
                    <div>
                        <div class="config-breadcrumb">Configuration <span>/ Général</span></div>
                        <h1 class="config-page-title">Configuration de l'application</h1>
                        <p class="config-page-sub">Paramètres généraux de votre espace</p>
                    </div>
                    <span class="badge-status badge-on">Actif</span>
                </div>

                @adminroute('admin.parametrage.configuration.update')
                <form method="POST" action="{{ route('admin.parametrage.configuration.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-building"></i> Informations générales</div>
                        <p class="config-card-desc">Identité et présentation de votre application</p>

                        <div class="config-field-row">
                            <div>
                                <div class="field-label">Nom du Crou</div>
                                <div class="field-hint">Affiché dans l'en-tête</div>
                            </div>
                            <input type="text" name="app_name" class="field-input"
                                   value="{{ old('app_name', $config->app_name ?? config('app.name')) }}" />
                        </div>

                        <div class="config-field-row">
                            <div>
                                <label for="crou-full-name" class="field-label">Nom complet du CROU</label>
                                <div class="field-hint">Affiché en bas des pages de connexion administrateur</div>
                            </div>
                            <input type="text" id="crou-full-name" name="company_name" class="field-input" maxlength="255"
                                   value="{{ old('company_name', $config?->company_name ?: 'Centre Régional des Œuvres Universitaires de Bondoukou') }}" />
                            @error('company_name')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="config-field-row">
                            <div>
                                <div class="field-label">Langue par défaut</div>
                                <div class="field-hint">Interface utilisateur</div>
                            </div>
                            <select name="locale" class="field-select">
                                @foreach(['fr' => 'Français', 'en' => 'English', 'es' => 'Español'] as $code => $label)
                                    <option value="{{ $code }}" {{ old('locale', $config->locale ?? 'fr') === $code ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="config-field-row">
                            <div>
                                <div class="field-label">Fuseau horaire</div>
                                <div class="field-hint">Pour les horaires et rapports</div>
                            </div>
                            <select name="timezone" class="field-select">
                                @foreach(['Africa/Abidjan' => 'Africa/Abidjan (GMT+0)', 'Europe/Paris' => 'Europe/Paris (GMT+2)', 'UTC' => 'UTC'] as $tz => $label)
                                    <option value="{{ $tz }}" {{ old('timezone', $config->timezone ?? 'Africa/Abidjan') === $tz ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="config-field-row">
                            <div><div class="field-label">Format de date</div></div>
                            <select name="date_format" class="field-select">
                                @foreach(['d/m/Y' => 'JJ/MM/AAAA', 'm/d/Y' => 'MM/DD/YYYY', 'Y-m-d' => 'AAAA-MM-JJ'] as $fmt => $label)
                                    <option value="{{ $fmt }}" {{ old('date_format', $config->date_format ?? 'd/m/Y') === $fmt ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-adjustments-horizontal"></i> Fonctionnalités</div>
                        <p class="config-card-desc">Activer ou désactiver les modules de l'application</p>

                        @php
                            $togglesGeneral = [
                                ['name' => 'maintenance_mode',    'label' => 'Mode maintenance',      'hint' => 'Affiche une page de maintenance aux utilisateurs', 'default' => false],
                                ['name' => 'activity_log',        'label' => "Journal d'activité",     'hint' => 'Enregistrer les actions des utilisateurs',        'default' => true],
                            ]
                        @endphp

                        @foreach($togglesGeneral as $t)
                            <div class="config-field-row">
                                <div>
                                    <div class="field-label">{{ $t['label'] }}</div>
                                    <div class="field-hint">{{ $t['hint'] }}</div>
                                </div>
                                <label class="toggle-switch">
                                    {{-- old() gère le retour après erreur de validation --}}
                                    <input type="checkbox" name="{{ $t['name'] }}" value="1"
                                            {{ old($t['name'], $config->{$t['name']} ?? $t['default']) ? 'checked' : '' }}>
                                    <span class="toggle-track"></span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="config-actions">
                        @adminroute('admin.parametrage.configuration.index')
                        <a href="{{ route('admin.parametrage.configuration.index') }}" class="btn-outline">Annuler</a>
@endadminroute
                        <button type="submit" class="btn-save">Enregistrer les modifications</button>
                    </div>
                </form>
                @endadminroute


            @endif


            {{-- ================================================================
                 PAGE : Profil
            ================================================================ --}}
            @if(request('menus') === 'profil')

                <div class="config-header">
                    <div>
                        <div class="config-breadcrumb">Configuration <span>/ Profil</span></div>
                        <h1 class="config-page-title">Profil du CROU</h1>
                        <p class="config-page-sub">Coordonnées du CROU</p>
                    </div>
                </div>

                {{-- enctype obligatoire pour l'upload du logo --}}
                @adminroute('admin.parametrage.configuration.update')
                <form method="POST"
                      action="{{ route('admin.parametrage.configuration.update', ['menus' => 'profil']) }}"
                      enctype="multipart/form-data">
                    @csrf
                    @method('PUT')


{{--                    <div class="config-card">--}}
{{--                        <div class="config-card-title"><i class="ti ti-building"></i> Identité de l'entreprise</div>--}}
{{--                        <p class="config-card-desc">Informations qui apparaissent sur vos documents officiels</p>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Raison sociale</div>--}}
{{--                                <div class="field-hint">Nom légal de l'entreprise</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="company_name" class="field-input"--}}
{{--                                   value="{{ old('company_name', $profil->company_name ?? '') }}" placeholder="Ex: MonApp SARL" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Numéro RCCM</div>--}}
{{--                                <div class="field-hint">Registre du commerce</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="rccm" class="field-input"--}}
{{--                                   value="{{ old('rccm', $profil->rccm ?? '') }}" placeholder="CI-ABJ-2024-B-00000" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div>--}}
{{--                                <div class="field-label">Numéro contribuable (NCC)</div>--}}
{{--                                <div class="field-hint">Identifiant fiscal</div>--}}
{{--                            </div>--}}
{{--                            <input type="text" name="ncc" class="field-input"--}}
{{--                                   value="{{ old('ncc', $profil->ncc ?? '') }}" placeholder="0000000A" />--}}
{{--                        </div>--}}

{{--                        <div class="config-field-row">--}}
{{--                            <div><div class="field-label">Secteur d'activité</div></div>--}}
{{--                            <select name="sector" class="field-select">--}}
{{--                                @foreach(['Commerce', 'Services', 'Technologie', 'Santé', 'Éducation', 'Transport', 'Autre'] as $sector)--}}
{{--                                    <option value="{{ $sector }}"--}}
{{--                                            {{ old('sector', $profil->sector ?? '') === $sector ? 'selected' : '' }}>--}}
{{--                                        {{ $sector }}--}}
{{--                                    </option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                        </div>--}}
{{--                    </div>--}}

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-map-pin"></i> Coordonnées</div>
                        <p class="config-card-desc">Adresse et contacts officiels du CROU</p>

                        <div class="config-field-row">
                            <div>
                                <div class="field-label">Adresse</div>
                                <div class="field-hint">Siège social</div>
                            </div>
                            <input type="text" name="address" class="field-input"
                                   value="{{ old('address', $profil->address ?? '') }}" placeholder="Rue, quartier, ville" />
                        </div>

                        <div class="config-field-row">
                            <div><div class="field-label">Pays</div></div>
                            <select name="country" class="field-select">
                                @foreach(["Côte d'Ivoire", 'Sénégal', 'Mali', 'Burkina Faso', 'Guinea', 'Togo', 'Bénin', 'Niger', 'France'] as $country)
                                    <option value="{{ $country }}"
                                            {{ old('country', $profil->country ?? "Côte d'Ivoire") === $country ? 'selected' : '' }}>
                                        {{ $country }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="config-field-row">
                            <div>
                                <div class="field-label">Téléphone</div>
                                <div class="field-hint">Numéro principal</div>
                            </div>
                            <input type="tel" name="phone" class="field-input"
                                   value="{{ old('phone', $profil->phone ?? '') }}" placeholder="+225 07 00 00 00 00" />
                        </div>

                        <div class="config-field-row">
                            <div>
                                <div class="field-label">E-mail de contact</div>
                                <div class="field-hint">Adresse officielle</div>
                            </div>
                            <input type="email" name="email" class="field-input"
                                   value="{{ old('email', $profil->email ?? '') }}" placeholder="contact@monapp.ci" />
                        </div>

                        <div class="config-field-row">
                            <div><div class="field-label">Site web</div></div>
                            <input type="url" name="website" class="field-input"
                                   value="{{ old('website', $profil->website ?? '') }}" placeholder="https://monapp.ci" />
                        </div>
                    </div>

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-photo"></i> Logo & identité visuelle</div>
                        <p class="config-card-desc">Apparence sur les documents et l'interface</p>

                        <div class="config-field-row">
                            <div>
                                <div class="field-label">Logo principal</div>
                                <div class="field-hint">PNG ou SVG, 512×512px recommandé</div>
                            </div>
                            <div class="file-upload-zone">
                                @if($profil->logo ?? false)
                                    <img src="{{ systemLogo() }}" alt="Logo actuel" class="logo-preview" />
                                @endif
                                <input type="file" name="logo" accept="image/*" class="file-input" id="logo-input" />
                                <label for="logo-input" class="file-label">
                                    <i class="ti ti-upload"></i> Choisir un fichier
                                </label>
                                <span class="field-hint" id="logo-filename">Aucun fichier sélectionné</span>
                            </div>
                        </div>

                        <div class="config-field-row">
                            <div>
                                <div class="field-label">Couleur principale</div>
                                <div class="field-hint">Utilisée dans l'interface</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px">
                                <input type="color" name="brand_color" class="color-picker"
                                       value="{{ old('brand_color', $profil->brand_color ?? '#534AB7') }}" />
                                <span class="field-hint" id="color-hex">{{ old('brand_color', $profil->brand_color ?? '#534AB7') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="config-actions">
                        @adminroute('admin.parametrage.configuration.index')
                        <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'profil']) }}" class="btn-outline">Annuler</a>
@endadminroute
                        <button type="submit" class="btn-save">Enregistrer les modifications</button>
                    </div>
                </form>
                @endadminroute


            @endif


            {{-- ================================================================
                 PAGE : Notifications
            ================================================================ --}}
            @if(request('menus') === 'notifications')

                <div class="config-header">
                    <div>
                        <div class="config-breadcrumb">Configuration <span>/ Notifications</span></div>
                        <h1 class="config-page-title">Notifications</h1>
                        <p class="config-page-sub">Gérez les alertes et les canaux de communication</p>
                    </div>
                </div>

                @adminroute('admin.parametrage.configuration.update')
                <form method="POST"
                      action="{{ route('admin.parametrage.configuration.update', ['menus' => 'notifications']) }}">
                    @csrf
                    @method('PUT')

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-mail"></i> Notifications par e-mail</div>
                        <p class="config-card-desc">Paramètres du serveur d'envoi et des alertes e-mail</p>

                        <div class="config-field-row">
                            <div>
                                <div class="field-label">Activer les e-mails</div>
                                <div class="field-hint">Envoi de notifications par e-mail</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="email_enabled" value="1" id="email_toggle"
                                        {{ old('email_enabled', $notif->email_enabled ?? true) ? 'checked' : '' }}>
                                <span class="toggle-track"></span>
                            </label>
                        </div>

                        <div id="email-config" class="{{ old('email_enabled', $notif->email_enabled ?? true) ? '' : 'd-none' }}">

                            <div class="config-field-row">
                                <div>
                                    <div class="field-label">Pilote SMTP</div>
                                    <div class="field-hint">Fournisseur d'envoi</div>
                                </div>
                                <select name="mail_driver" class="field-select">
                                    @foreach(['smtp', 'mailgun', 'sendgrid', 'ses', 'log'] as $driver)
                                        <option value="{{ $driver }}"
                                                {{ old('mail_driver', $notif->mail_driver ?? 'smtp') === $driver ? 'selected' : '' }}>
                                            {{ strtoupper($driver) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="config-field-row">
                                <div><div class="field-label">Hôte SMTP</div></div>
                                <input type="text" name="mail_host" class="field-input"
                                       value="{{ old('mail_host', $notif->mail_host ?? '') }}" placeholder="smtp.monserveur.com" />
                            </div>

                            <div class="config-field-row">
                                <div><div class="field-label">Port</div></div>
                                <input type="number" name="mail_port" class="field-input"
                                       value="{{ old('mail_port', $notif->mail_port ?? 587) }}" placeholder="587" />
                            </div>

                            <div class="config-field-row">
                                <div><div class="field-label">Identifiant SMTP</div></div>
                                <input type="text" name="mail_username" class="field-input"
                                       value="{{ old('mail_username', $notif->mail_username ?? '') }}" placeholder="user@domaine.com" />
                            </div>

                            <div class="config-field-row">
                                <div><div class="field-label">Mot de passe SMTP</div></div>
                                {{-- Ne pas pré-remplir le mot de passe (sécurité) --}}
                                <input type="password" name="mail_password" class="field-input"
                                       value="" placeholder="••••••••" autocomplete="new-password" />
                            </div>

                            <div class="config-field-row">
                                <div>
                                    <div class="field-label">Expéditeur (nom)</div>
                                    <div class="field-hint">Nom affiché dans la boîte de réception</div>
                                </div>
                                <input type="text" name="mail_from_name" class="field-input"
                                       value="{{ old('mail_from_name', $notif->mail_from_name ?? config('app.name')) }}" />
                            </div>

                            <div class="config-field-row">
                                <div><div class="field-label">Adresse expéditeur</div></div>
                                <input type="email" name="mail_from_address" class="field-input"
                                       value="{{ old('mail_from_address', $notif->mail_from_address ?? '') }}" placeholder="noreply@monapp.ci" />
                            </div>
                        </div>
                    </div>

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-device-mobile"></i> Notifications SMS</div>
                        <p class="config-card-desc">Alertes envoyées par SMS via une passerelle</p>

                        <div class="config-field-row">
                            <div><div class="field-label">Activer les SMS</div></div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="sms_enabled" value="1" id="sms_toggle"
                                        {{ old('sms_enabled', $notif->sms_enabled ?? false) ? 'checked' : '' }}>
                                <span class="toggle-track"></span>
                            </label>
                        </div>

                        <div id="sms-config" class="{{ old('sms_enabled', $notif->sms_enabled ?? false) ? '' : 'd-none' }}">
                            <div class="config-field-row">
                                <div><div class="field-label">Fournisseur SMS</div></div>
                                <select name="sms_provider" class="field-select">
                                    @php
                                        $smsProviders = [
                                            'helper_json' => "Passerelle EnvoiSMS (JSON / Bearer)",
                                            'orange_sms_api'  => "Orange SMS API",
                                            'twilio'          => "Twilio",
                                            'africas_talking' => "Africa's Talking",
                                            'infobip'         => "InfoBip",
                                            'cinetpay_sms'    => "CinetPay SMS",
                                        ]
                                    @endphp
                                    @foreach($smsProviders as $val => $label)
                                        <option value="{{ $val }}"
                                                {{ old('sms_provider', $notif->sms_provider ?? '') === $val ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="config-field-row">
                                <div>
                                    <div class="field-label">URL de la passerelle SMS</div>
                                    <div class="field-hint">Passerelle compatible avec EnvoiSMS : JSON et authentification Bearer.</div>
                                </div>
                                <input type="url" name="sms_api_url" class="field-input"
                                       value="{{ old('sms_api_url', $notif->sms_api_url ?? '') }}" placeholder="https://passerelle.example/api/sms/send" />
                            </div>
                            <div class="config-field-row">
                                <div><div class="field-label">Clé API SMS</div></div>
                                <input type="text" name="sms_api_key" class="field-input"
                                       value="{{ old('sms_api_key', $notif->sms_api_key ?? '') }}" placeholder="sms_••••••••" />
                            </div>

                            <div class="config-field-row">
                                <div>
                                    <div class="field-label">Expéditeur SMS</div>
                                    <div class="field-hint">Nom ou numéro affiché</div>
                                </div>
                                <input type="text" name="sms_sender" class="field-input"
                                       value="{{ old('sms_sender', $notif->sms_sender ?? 'MonApp') }}" placeholder="MonApp" />
                            </div>
                        </div>
                    </div>

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-bell"></i> Événements déclencheurs</div>
                        <p class="config-card-desc">Choisissez quand envoyer des notifications automatiques</p>

                        @php
                            $events = [
                                ['key' => 'notif_new_user',     'label' => 'Nouvel utilisateur inscrit',    'hint' => 'Alerte admin à chaque inscription'],
                                ['key' => 'notif_new_order',    'label' => 'Nouvelle commande',             'hint' => 'Confirmation client + alerte admin'],
                                ['key' => 'notif_payment_ok',   'label' => 'Paiement réussi',               'hint' => 'Reçu envoyé au client'],
                                ['key' => 'notif_payment_fail', 'label' => 'Échec de paiement',             'hint' => 'Alerte client et administrateur'],
                                ['key' => 'notif_low_stock',    'label' => 'Stock faible',                  'hint' => 'Alerte admin sous le seuil'],
                                ['key' => 'notif_report',       'label' => 'Rapport quotidien',             'hint' => 'Résumé envoyé chaque matin'],
                            ]
                        @endphp

                        @foreach($events as $event)
                            <div class="config-field-row">
                                <div>
                                    <div class="field-label">{{ $event['label'] }}</div>
                                    <div class="field-hint">{{ $event['hint'] }}</div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="{{ $event['key'] }}" value="1"
                                            {{ old($event['key'], $notif->{$event['key']} ?? true) ? 'checked' : '' }}>
                                    <span class="toggle-track"></span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="config-actions">
                        @adminroute('admin.parametrage.configuration.index')
                        <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'notifications']) }}" class="btn-outline">Annuler</a>
@endadminroute
                        <button type="submit" class="btn-save">Enregistrer les modifications</button>
                    </div>
                </form>
                @endadminroute


            @endif


            {{-- ================================================================
                 PAGE : Paiements
            ================================================================ --}}



            {{-- ================================================================
                 PAGE : Taxes & TVA
            ================================================================ --}}



            {{-- ================================================================
                 PAGE : Devises
            ================================================================ --}}



            {{-- ================================================================
                 PAGE : Intégrations
            ================================================================ --}}
            @if(request('menus') === 'integrations')

                <div class="config-header">
                    <div>
                        <div class="config-breadcrumb">Configuration <span>/ Intégrations</span></div>
                        <h1 class="config-page-title">Intégrations</h1>
                        <p class="config-page-sub">Connectez des services et outils tiers à votre application</p>
                    </div>
                </div>

                @adminroute('admin.parametrage.configuration.update')
                <form method="POST"
                      action="{{ route('admin.parametrage.configuration.update', ['menus' => 'integrations']) }}">
                    @csrf
                    @method('PUT')

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-map-pin"></i> Géolocalisation & cartographie</div>
                        <p class="config-card-desc">Services de maps et localisation</p>

                        <div class="config-field-row">
                            <div><div class="field-label">Activer Google Maps</div></div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="google_maps_enabled" value="1"
                                        {{ old('google_maps_enabled', $integrations->google_maps_enabled ?? false) ? 'checked' : '' }}>
                                <span class="toggle-track"></span>
                            </label>
                        </div>

                        <div class="config-field-row">
                            <div><div class="field-label">Clé API Google Maps</div></div>
                            <input type="text" name="google_maps_key" class="field-input"
                                   value="{{ old('google_maps_key', $integrations->google_maps_key ?? '') }}"
                                   placeholder="AIza••••••••••••••••" />
                        </div>
                    </div>

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-chart-bar"></i> Analytique & statistiques</div>
                        <p class="config-card-desc">Outils de suivi du comportement utilisateur</p>

                        <div class="config-field-row">
                            <div>
                                <div class="field-label">Google Analytics</div>
                                <div class="field-hint">ID de suivi (GA4)</div>
                            </div>
                            <input type="text" name="ga_tracking_id" class="field-input"
                                   value="{{ old('ga_tracking_id', $integrations->ga_tracking_id ?? '') }}"
                                   placeholder="G-XXXXXXXXXX" />
                        </div>

                        <div class="config-field-row">
                            <div>
                                <div class="field-label">Facebook Pixel</div>
                                <div class="field-hint">ID du pixel Meta</div>
                            </div>
                            <input type="text" name="fb_pixel_id" class="field-input"
                                   value="{{ old('fb_pixel_id', $integrations->fb_pixel_id ?? '') }}"
                                   placeholder="000000000000000" />
                        </div>
                    </div>

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-message-circle"></i> Chat & support client</div>
                        <p class="config-card-desc">Widgets de support intégrés à l'interface</p>

                        <div class="config-field-row">
                            <div><div class="field-label">Activer le chat en direct</div></div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="livechat_enabled" value="1"
                                        {{ old('livechat_enabled', $integrations->livechat_enabled ?? false) ? 'checked' : '' }}>
                                <span class="toggle-track"></span>
                            </label>
                        </div>

                        <div class="config-field-row">
                            <div><div class="field-label">Fournisseur de chat</div></div>
                            <select name="livechat_provider" class="field-select">
                                @php
                                    $chatProviders = [
                                        'intercom'           => 'Intercom',
                                        'crisp'              => 'Crisp',
                                        'tawk_to'            => 'Tawk.to',
                                        'zendesk'            => 'Zendesk',
                                        'whatsapp_business'  => 'WhatsApp Business',
                                    ]
                                @endphp
                                @foreach($chatProviders as $val => $label)
                                    <option value="{{ $val }}"
                                            {{ old('livechat_provider', $integrations->livechat_provider ?? '') === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="config-field-row">
                            <div><div class="field-label">Clé / Token du widget</div></div>
                            <input type="text" name="livechat_token" class="field-input"
                                   value="{{ old('livechat_token', $integrations->livechat_token ?? '') }}"
                                   placeholder="token••••••••" />
                        </div>
                    </div>

                    <div class="config-card">
                        <div class="config-card-title"><i class="ti ti-key"></i> Authentification sociale</div>
                        <p class="config-card-desc">Permettre la connexion via des comptes tiers</p>

                        @php
                            $socialProviders = [
                                ['key' => 'social_google',   'label' => 'Google',   'icon' => 'ti-brand-google'],
                                ['key' => 'social_facebook', 'label' => 'Facebook', 'icon' => 'ti-brand-facebook'],
                                ['key' => 'social_github',   'label' => 'GitHub',   'icon' => 'ti-brand-github'],
                            ]
                        @endphp

                        @foreach($socialProviders as $provider)
                            <div class="config-field-row">
                                <div style="display:flex;align-items:center;gap:10px">
                                    <i class="ti {{ $provider['icon'] }}" style="font-size:20px;color:#6b7280"></i>
                                    <div>
                                        <div class="field-label">{{ $provider['label'] }}</div>
                                        <div class="field-hint">Connexion via {{ $provider['label'] }}</div>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="{{ $provider['key'] }}" value="1"
                                            {{ old($provider['key'], $integrations->{$provider['key']} ?? false) ? 'checked' : '' }}>
                                    <span class="toggle-track"></span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="config-actions">
                        @adminroute('admin.parametrage.configuration.index')
                        <a href="{{ route('admin.parametrage.configuration.index', ['menus' => 'integrations']) }}" class="btn-outline">Annuler</a>
                        @endadminroute

                        <button type="submit" class="btn-save">Enregistrer les modifications</button>
                    </div>
                </form>
                @endadminroute

            @endif


            {{-- ================================================================
                 PAGE : Sécurité
            ================================================================ --}}


        </main>
    </div>

@endsection


@push('styles')
    <style>
        /* ── Layout ─────────────────────────────────────────────────────────── */
        .config-wrapper {
            display: flex;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            min-height: 780px;
            overflow: hidden;
        }

        /* ── Sidebar ─────────────────────────────────────────────────────────── */
        .config-sidebar {
            width: 220px;
            border-right: 1px solid #e5e7eb;
            padding: 1rem 0;
            flex-shrink: 0;
            background: #fff;
        }
        .config-brand {
            display: flex; align-items: center; gap: 8px;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 8px;
        }
        .config-brand-icon {
            width: 28px; height: 28px;
            border-radius: 6px; background: #534AB7;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 15px;
        }
        .config-brand-name { font-size: var(--app-font-min, 15px); font-weight: 500; color: #111; }

        .config-nav-section {
            font-size: var(--app-font-min, 15px); color: #9ca3af;
            padding: 12px 16px 4px;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .config-nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px;
            font-size: var(--app-font-min, 15px); color: #6b7280;
            text-decoration: none;
            border-left: 2px solid transparent;
            transition: background .15s;
        }
        .config-nav-item:hover { background: #f9fafb; color: #111; text-decoration: none; }
        .config-nav-item.active { background: #f3f4f6; color: #111; font-weight: 500; border-left-color: #534AB7; }
        .config-nav-item i { font-size: 17px; }

        /* ── Content ─────────────────────────────────────────────────────────── */
        .config-content { flex: 1; padding: 2rem; overflow-y: auto; }

        .config-header {
            display: flex; align-items: flex-start;
            justify-content: space-between; margin-bottom: 1.5rem;
        }
        .config-breadcrumb { font-size: var(--app-font-min, 15px); color: #9ca3af; margin-bottom: 4px; }
        .config-breadcrumb span { color: #111; }
        .config-page-title { font-size: 18px; font-weight: 500; color: #111; margin: 0 0 4px; }
        .config-page-sub { font-size: var(--app-font-min, 15px); color: #6b7280; margin: 0; }

        /* ── Cards ───────────────────────────────────────────────────────────── */
        .config-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }
        .config-card-title {
            font-size: var(--app-font-min, 15px); font-weight: 500; color: #111;
            display: flex; align-items: center; gap: 8px; margin-bottom: 4px;
        }
        .config-card-title i { color: #534AB7; font-size: 16px; }
        .config-card-desc { font-size: var(--app-font-min, 15px); color: #6b7280; margin-bottom: 1rem; }

        /* ── Field rows ──────────────────────────────────────────────────────── */
        .config-field-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .config-field-row:last-child { border-bottom: none; padding-bottom: 0; }

        .field-label { font-size: var(--app-font-min, 15px); font-weight: 500; color: #111; }
        .field-hint  { font-size: var(--app-font-min, 15px); color: #9ca3af; margin-top: 2px; }

        .field-input,
        .field-select {
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: var(--app-font-min, 15px); color: #111; background: #fff;
            min-width: 160px;
        }
        .field-input { width: 200px; }
        .field-input:focus,
        .field-select:focus {
            outline: none; border-color: #534AB7;
            box-shadow: 0 0 0 3px rgba(83,74,183,.15);
        }

        /* ── Toggle switch ───────────────────────────────────────────────────── */
        .toggle-switch { display: inline-flex; align-items: center; cursor: pointer; }
        .toggle-switch input { display: none; }
        .toggle-track {
            width: 38px; height: 22px;
            background: #d1d5db; border-radius: 11px;
            position: relative; transition: background .2s;
        }
        .toggle-track::after {
            content: ''; width: 16px; height: 16px;
            background: #fff; border-radius: 50%;
            position: absolute; top: 3px; left: 3px; transition: left .2s;
        }
        .toggle-switch input:checked + .toggle-track { background: #534AB7; }
        .toggle-switch input:checked + .toggle-track::after { left: 19px; }

        /* ── Payment / currency grid ─────────────────────────────────────────── */
        .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
        .pay-option {
            border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px;
            cursor: pointer; display: flex; align-items: center; gap: 10px;
            transition: border-color .15s, background .15s;
        }
        .pay-option.selected { border-color: #534AB7; background: #EEEDFE; }
        .pay-checkbox { display: none; }
        .pay-option i { font-size: 22px; color: #9ca3af; }
        .pay-option.selected i { color: #534AB7; }
        .pay-label { font-size: var(--app-font-min, 15px); font-weight: 500; color: #111; }
        .pay-hint  { font-size: var(--app-font-min, 15px); color: #6b7280; }

        /* ── Badges ──────────────────────────────────────────────────────────── */
        .badge-status { font-size: var(--app-font-min, 15px); padding: 3px 10px; border-radius: 20px; font-weight: 500; }
        .badge-on   { background: #EAF3DE; color: #3B6D11; }
        .badge-info { background: #E6F1FB; color: #185FA5; }

        /* ── File upload ─────────────────────────────────────────────────────── */
        .file-upload-zone { display: flex; align-items: center; gap: 10px; }
        .file-input { display: none; }
        .file-label {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px;
            border: 1px solid #d1d5db; border-radius: 8px;
            font-size: var(--app-font-min, 15px); color: #6b7280; cursor: pointer;
            transition: background .15s;
        }
        .file-label:hover { background: #f9fafb; }
        .logo-preview { width: 40px; height: 40px; object-fit: contain; border-radius: 6px; border: 1px solid #e5e7eb; }

        /* ── Color picker ────────────────────────────────────────────────────── */
        .color-picker { width: 40px; height: 34px; padding: 2px; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer; }

        /* ── Alerts ──────────────────────────────────────────────────────────── */
        .alert-success,
        .alert-error {
            display: flex; align-items: flex-start; gap: 8px;
            padding: 12px 16px; border-radius: 8px; margin-bottom: 1.25rem;
            font-size: var(--app-font-min, 15px);
        }
        .alert-success { background: #EAF3DE; color: #3B6D11; border: 1px solid #c3e6a5; }
        .alert-error   { background: #FEE2E2; color: #991B1B; border: 1px solid #fca5a5; }
        .alert-error ul { margin: 0; padding-left: 16px; }

        /* ── Actions ─────────────────────────────────────────────────────────── */
        .config-actions { display: flex; justify-content: flex-end; margin-top: 1rem; gap: 8px; }
        .btn-save {
            background: #534AB7; color: #fff; border: none;
            border-radius: 8px; padding: 9px 20px;
            font-size: var(--app-font-min, 15px); font-weight: 500; cursor: pointer;
        }
        .btn-save:hover { background: #3C3489; }
        .btn-outline {
            background: transparent; color: #6b7280;
            border: 1px solid #d1d5db; border-radius: 8px;
            padding: 9px 20px; font-size: var(--app-font-min, 15px); cursor: pointer; text-decoration: none;
        }
        .btn-outline:hover { background: #f9fafb; }

        .d-none { display: none !important; }
    </style>
@endpush


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /* ── Paiement en ligne ─────────────────────────────────────────── */
            const payToggle = document.getElementById('online_payment_toggle');
            const payConfig = document.getElementById('online-payment-config');
            if (payToggle && payConfig) {
                payToggle.addEventListener('change', () => payConfig.classList.toggle('d-none', !payToggle.checked));
            }

            /* ── E-mail ────────────────────────────────────────────────────── */
            const emailToggle = document.getElementById('email_toggle');
            const emailConfig = document.getElementById('email-config');
            if (emailToggle && emailConfig) {
                emailToggle.addEventListener('change', () => emailConfig.classList.toggle('d-none', !emailToggle.checked));
            }

            /* ── SMS ───────────────────────────────────────────────────────── */
            const smsToggle = document.getElementById('sms_toggle');
            const smsConfig = document.getElementById('sms-config');
            if (smsToggle && smsConfig) {
                smsToggle.addEventListener('change', () => smsConfig.classList.toggle('d-none', !smsToggle.checked));
            }

            /* ── TVA ───────────────────────────────────────────────────────── */
            const tvaToggle = document.getElementById('tva_toggle');
            const tvaConfig = document.getElementById('tva-config');
            if (tvaToggle && tvaConfig) {
                tvaToggle.addEventListener('change', () => tvaConfig.classList.toggle('d-none', !tvaToggle.checked));
            }

            /* ── Sélection cartes de paiement / devises ────────────────────── */
            document.querySelectorAll('.pay-option').forEach(function (label) {
                label.addEventListener('click', function (e) {
                    if (e.target.tagName === 'INPUT') return; // géré nativement
                    const cb = this.querySelector('.pay-checkbox');
                    if (!cb) return;
                    cb.checked = !cb.checked;
                    this.classList.toggle('selected', cb.checked);
                });
            });

            /* ── Aperçu couleur de marque ──────────────────────────────────── */
            const colorPicker = document.querySelector('.color-picker');
            const colorHex    = document.getElementById('color-hex');
            if (colorPicker && colorHex) {
                colorPicker.addEventListener('input', function () {
                    colorHex.textContent = this.value;
                });
            }

            /* ── Nom du fichier logo sélectionné ───────────────────────────── */
            const logoInput    = document.getElementById('logo-input');
            const logoFilename = document.getElementById('logo-filename');
            if (logoInput && logoFilename) {
                logoInput.addEventListener('change', function () {
                    logoFilename.textContent = this.files[0]?.name ?? 'Aucun fichier sélectionné';
                });
            }

        });
    </script>
@endpush

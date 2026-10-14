@extends('web.layouts.app')

@section('title', 'Demande d’hébergement')

@section('content')

    <div class="signup-area pd-top-120 pd-bottom-120 mt-3">
        <div class="container mt-3">
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-10">



                    @php

                          $count=   $result['data']['stats']['active'] ==1;
                          $id_campagne ='';
                        if($count){

                         $document=   $result['data']['campagnes'][0]->documentCampagnes;

                         $id_campagne = $result['data']['campagnes'][0]->id_campagne;
                         $annee_campagne = $result['data']['campagnes'][0]->annee_campagne;
                       //  dd($id_campagne);
                        }
                     @endphp

                    <div class="signup-wrap">

                        <div class="section-title text-center">
                            <h2 class="title">Demande d’hébergement étudiant — {{ systemConfig('app_name', config('app.name')) }}</h2>
                            <p class="text-muted mt-2">Tous les champs marqués d'un astérisque (<span class="text-danger">*</span>) sont obligatoires</p>
                        </div>

                        @include('admin.layouts.flash')


                        <!-- Avertissement (visible par défaut) -->
                        <div id="warningSection" class="alert alert-warning mb-4">
                            <h5><i class="fa-solid fa-triangle-exclamation"></i> Avant de commencer</h5>
                            <p>Veuillez préparer les documents suivants :</p>
                            <ul>
                                @if($count)
                                @foreach($document as $item)

                                @foreach($item->documents as $items)
                                    <li>{{$items->name}}  <strong class="text-danger">{{$items->champs_requis ==true ? '*': ""}}</strong> </li>
                                    @endforeach
                                @endforeach
                                @endif

                            </ul>
                            </ul>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="ready">
                                <label class="form-check-label">
                                    Je dispose de tous les documents requis
                                </label>
                            </div>
                            <button type="button" class="btn btn-base" id="startBtn" disabled>Commencer</button>
                        </div>


                        <!-- Formulaire (caché par défaut) -->
                        <div id="formSection" style="display: none;">
                            <!-- Barre de progression -->
                            <div class="progress mb-4">
                                <div class="progress-bar" id="progressBar" style="width: 16%"></div>
                            </div>

                            <form action="{{route('register.post')}}" method="POST" enctype="multipart/form-data" id="formDossier">
                                @csrf

                                <!-- ================= STEP 1 ================= -->
                                <div class="form-step">
                                    <h4>1. Informations personnelles</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Civilité <span class="text-danger">*</span></label>
                                            <select name="genre" class="form-control mb-3" required>
                                                <option value="">Sélectionnez la civilité</option>
                                                @foreach($genre as $item)
                                                    <option value="{{$item->id}}">{{$item->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Nom et prenom <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control mb-3" placeholder="Ex: John Doe" required>
                                        </div>


                                        <div class="col-md-6">
                                            <label>Date de naissance <span class="text-danger">*</span></label>
                                            <input type="date" name="date_naissance" max="{{date('Y-m-d')}}" class="form-control mb-3" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Lieu de naissance <span class="text-danger">*</span></label>
                                            <input type="text" name="lieu_naissance" class="form-control mb-3" placeholder="Lieu de naissance" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Téléphone <span class="text-danger">*</span></label>
                                            <input type="tel" name="telephone" id="telephone1" class="form-control mb-3" placeholder="Votre numéro de téléphone" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" class="form-control mb-3" placeholder="votre.email@exemple.com" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Type de pièce <span class="text-danger">*</span></label>
                                            <select name="type_piece" class="form-control mb-3" required>
                                                <option value="">Sélectionnez le type de pièce</option>
                                                @foreach($typePiece as $item)
                                                    <option value="{{$item->id}}">{{$item->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Numero de la pièce <span class="text-danger">*</span></label>
                                            <input type="text" name="num_piece" class="form-control mb-3" placeholder="Numero de la piece" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Date d'expiration de la pièce <span class="text-danger">*</span></label>
                                            <input type="date" name="date_exp_piece" class="form-control mb-3" placeholder="Numero de la piece" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Groupe sanguin </label>
                                            <select name="groupe_sanguin" class="form-control mb-3" >
                                                <option value="">Sélectionnez votre groupe sanguin</option>
                                                @foreach($groupeSanguin as $item)
                                                    <option value="{{$item->id}}">{{$item->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                    </div>
                                </div>

                                <!-- ================= STEP 2 ================= -->
                                <div class="form-step d-none">
                                    <h4>2. Informations académiques</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Matricule<span class="text-danger">*</span></label>
                                            <input type="text" name="matricule" class="form-control mb-3" placeholder="Ex: xxxxxxxxxxxxx" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Annee Academique Précédent <span class="text-danger">*</span></label>
                                            <input type="text" name="annee_academique_precedent" class="form-control mb-3" placeholder="Ex: 2025-2026" required>
                                        </div>

                                        <input type="hidden" name="annee_academique_actuel" required value="{{$annee_campagne}}" id="">
                                        <div class="col-md-6">
                                            <label>Niveau d'étude precedent<span class="text-danger">*</span></label>
                                            <select name="niveau_precedent" class="form-control mb-3" required>
                                                <option value="">Sélectionnez votre niveau</option>
                                                @foreach($niveau_etude as $item)
                                                    <option value="{{$item->id_niveau_etude}}">{{$item->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Niveau d'étude Actuelle <span class="text-danger">*</span></label>
                                            <select name="niveau" class="form-control mb-3" required>
                                                <option value="">Sélectionnez votre niveau</option>
                                                @foreach($niveau_etude as $item)
                                                    <option value="{{$item->id_niveau_etude}}">{{$item->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label>Filière <span class="text-danger">*</span></label>
                                            <select name="filiere" class="form-control mb-3" required>
                                                <option value="">Sélectionnez votre niveau</option>
                                                @foreach($filiere as $item)
                                                    <option value="{{$item->id_filiere}}">{{$item->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label>Moyenne générale ou note du bac<span class="text-danger">*</span></label>
                                            <input type="number" required
                                                   step="0.01"
                                                   name="moyenne"
                                                   class="form-control mb-3"
                                                   placeholder="Votre moyenne"
                                                   min="1"
                                            oninput="this.value = this.value < 0 ? '' : this.value"> <!-- Empêche la saisie de valeurs négatives -->
                                        </div>
                                        <div class="col-md-6">
                                            <label>Pourcentage de Credit Valider </label>
                                            <input type="number"
                                                   step="0.01"
                                                   name="credit_valider"
                                                   class="form-control mb-3"
                                                   placeholder="Pourcentage de Credit Valider "
                                                   min="1"
                                                   oninput="this.value = this.value < 0 ? '' : this.value"> <!-- Empêche la saisie de valeurs négatives -->
                                        </div>
                                        <div class="col-md-6">
                                            <label>Situation académique</label>
                                            <select name="redoublement" class="form-control mb-3">
                                                <option value="0">Première inscription à ce niveau</option>
                                                <option value="1">Redoublant</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= STEP 3 ================= -->
                                <div class="form-step d-none">
                                    <h4>3. Situation sociale</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Situation de bourse</label>
                                            <select name="boursier" class="form-control mb-3">
                                                <option value="0">Non boursier</option>
                                                <option value="1">Boursier</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label>Personne à mobilité réduite (PMR)</label>
                                            <select name="pmr" class="form-control mb-3">
                                                <option value="0">Non</option>
                                                <option value="1">Oui, je suis une personne à mobilité réduite</option>
                                            </select>
                                            <small class="text-muted">PMR : Personne à Mobilité Réduite (besoin d'un logement adapté)</small>
                                        </div>
                                        <div class="col-md-12">
                                            <label>Situation particulière (optionnel)</label>
                                            <textarea name="cas_social" class="form-control mb-3" placeholder="Décrivez toute situation particulière que nous devrions connaître..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================= STEP 4 ================= -->
                                <div class="form-step d-none">
                                    <h4>4. Situation géographique</h4>
                                    <div class="row">

                                        <!-- Région -->
                                        <div class="col-md-6">
                                            <label>Région <span class="text-danger">*</span></label>
                                            <select name="region" class="form-control mb-3" required
                                                    onchange="getRequest('{{ url('/') }}/admin/list/city?parent_id='+this.value,'ville_id','select',this.value)"
                                            >
                                                <option value="">Sélectionnez votre région</option>

                                                @foreach($region as $item)
                                                    <option value="{{$item->id}}">{{$item->name}}</option>
                                                @endforeach


                                            </select>
                                        </div>

                                        <!-- Ville -->
                                        <div class="col-md-6">
                                            <label>Ville <span class="text-danger">*</span></label>
                                            <select name="ville" class="form-control mb-3" id="ville_id" required>
                                                <option value="">Sélectionnez votre ville</option>

                                            </select>
                                        </div>

                                        <!-- Adresse -->
                                        <div class="col-md-6">
                                            <label>Adresse complète <span class="text-danger">*</span></label>
                                            <input type="text" name="adresse" class="form-control mb-3" placeholder="Votre adresse géographique" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Nationalite<span class="text-danger">*</span></label>
                                            <input type="text" name="nationalite" class="form-control mb-3" placeholder="Votre nationalite" value="IVOIRIENNE" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Distance domicile - Campus (km) </label>
                                            <input type="number" min="0" name="distance"  class="form-control mb-3" placeholder="Ex:10 km"  >
                                        </div>

                                    </div>
                                </div>

                                <!-- ================= STEP 5 ================= -->

                                <div class="form-step d-none">
                                    <h4>5. Documents</h4>
                                    @if($count)
                                        @foreach($document as $items)

                                            @foreach($items->documents as $key => $item)
                                                <div class="mb-3">
                                                    <label>{{$item->name}} <span class="text-danger">{{$item->champs_requis==true ? '*':''}}</span></label>
                                                    <input type="file" name="{{$item->slug}}" accept="{{'.'.$item->format_accepte}}" class="form-control" {{$item->champs_requis==true ? 'required':''}}>
                                                    <small class="text-muted">Formats acceptés : {{$item->format_accepte}} (max 2 Mo)</small>
                                                </div>
                                            @endforeach
                                        @endforeach

                                    @endif




                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" required>
                                        <label class="form-check-label">
                                            Je certifie l'exactitude des informations fournies <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                </div>
                                <input type="text" name="id_campagne" value="{{$id_campagne}}" hidden="hidden">
                                <!-- Navigation -->
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-secondary" id="prevBtn">Précédent</button>
                                    <button type="button" class="btn btn-base" id="nextBtn">Suivant</button>
                                    <button type="submit" class="btn btn-success d-none" id="submitBtn">Soumettre ma demande</button>
                                     <label class="d-block mt-2"><input type="checkbox" name="save_as_draft" value="1"> Enregistrer le dossier en brouillon pour le soumettre plus tard depuis mon espace.</label>
                                </div>

                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // document.addEventListener('DOMContentLoaded', function () {
        //     const warningSection = document.getElementById('warningSection');
        //     const formSection = document.getElementById('formSection');
        //     const readyCheckbox = document.getElementById('ready');
        //     const startBtn = document.getElementById('startBtn');
        //
        //     const steps = document.querySelectorAll('.form-step');
        //     const nextBtn = document.getElementById('nextBtn');
        //     const prevBtn = document.getElementById('prevBtn');
        //     const submitBtn = document.getElementById('submitBtn');
        //     const progressBar = document.getElementById('progressBar');
        //
        //     let currentStep = 0;
        //
        //     // Activer/désactiver le bouton "Commencer" selon la checkbox
        //     readyCheckbox.addEventListener('change', function () {
        //         startBtn.disabled = !this.checked;
        //     });
        //
        //     // Clic sur "Commencer le formulaire"
        //     startBtn.addEventListener('click', function () {
        //         warningSection.style.display = 'none';
        //         formSection.style.display = 'block';
        //         showStep(currentStep);
        //     });
        //
        //     function showStep(index) {
        //         steps.forEach((step, i) => {
        //             step.classList.toggle('d-none', i !== index);
        //         });
        //
        //         prevBtn.style.display = index === 0 ? 'none' : 'inline-block';
        //         if (index === steps.length - 1) {
        //             nextBtn.classList.add('d-none');
        //             submitBtn.classList.remove('d-none');
        //         } else {
        //             nextBtn.classList.remove('d-none');
        //             submitBtn.classList.add('d-none');
        //         }
        //
        //         progressBar.style.width = ((index + 1) / steps.length) * 100 + '%';
        //     }
        //
        //     nextBtn.addEventListener('click', function () {
        //         if (currentStep < steps.length - 1) {
        //             currentStep++;
        //             showStep(currentStep);
        //         }
        //     });
        //
        //     prevBtn.addEventListener('click', function () {
        //         if (currentStep > 0) {
        //             currentStep--;
        //             showStep(currentStep);
        //         }
        //     });
        //
        //     // Initialisation
        //     showStep(currentStep);
        // });
    </script>

@push('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const warningSection = document.getElementById('warningSection');
            const formSection = document.getElementById('formSection');
            const readyCheckbox = document.getElementById('ready');
            const startBtn = document.getElementById('startBtn');

            const steps = document.querySelectorAll('.form-step');
            const nextBtn = document.getElementById('nextBtn');
            const prevBtn = document.getElementById('prevBtn');
            const submitBtn = document.getElementById('submitBtn');
            const progressBar = document.getElementById('progressBar');
            const form = document.getElementById('formDossier');

            let currentStep = 0;

            // Activer/désactiver le bouton "Commencer" selon la checkbox
            readyCheckbox.addEventListener('change', function () {
                startBtn.disabled = !this.checked;
            });

            // Clic sur "Commencer le formulaire"
            startBtn.addEventListener('click', function () {
                warningSection.style.display = 'none';
                formSection.style.display = 'block';
                showStep(currentStep);
            });

            function showStep(index) {
                steps.forEach((step, i) => {
                    step.classList.toggle('d-none', i !== index);
                });

                prevBtn.style.display = index === 0 ? 'none' : 'inline-block';
                if (index === steps.length - 1) {
                    nextBtn.classList.add('d-none');
                    submitBtn.classList.remove('d-none');
                } else {
                    nextBtn.classList.remove('d-none');
                    submitBtn.classList.add('d-none');
                }

                progressBar.style.width = ((index + 1) / steps.length) * 100 + '%';
            }

            // FONCTION DE VALIDATION D'UNE ÉTAPE
            function validateStep(stepIndex) {
                console.log("Validation de l'étape", stepIndex);

                // Récupérer l'étape à valider
                const step = steps[stepIndex];
                const requiredFields = step.querySelectorAll('[required]');

                console.log("Champs requis trouvés:", requiredFields.length);

                // Supprimer les anciens messages d'erreur
                step.querySelectorAll('.is-invalid').forEach(field => {
                    field.classList.remove('is-invalid');
                });
                step.querySelectorAll('.invalid-feedback').forEach(el => {
                    el.remove();
                });

                let isValid = true;

                // Vérifier chaque champ requis
                requiredFields.forEach(field => {
                    // Ignorer les champs désactivés
                    if (field.disabled) return;

                    let fieldValid = true;

                    // Traitement selon le type de champ
                    if (field.type === 'file') {
                        // Pour les fichiers
                        if (!field.files || field.files.length === 0) {
                            fieldValid = false;
                            console.log("Fichier manquant:", field.name);
                        } else {
                            // Vérifier la taille (max 2 Mo)
                            const file = field.files[0];
                            if (file.size > 2 * 1024 * 1024) {
                                fieldValid = false;
                                console.log("Fichier trop gros:", field.name);
                            }

                            // Vérifier le format si accept est spécifié
                            if (fieldValid && field.accept) {
                                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
                                const allowedTypes = field.accept.split(',').map(type => type.trim());

                                if (!allowedTypes.some(type => type === fileExtension || type === file.type)) {
                                    fieldValid = false;
                                    console.log("Format de fichier non accepté:", field.name);
                                }
                            }
                        }
                    } else {
                        // Pour les autres champs (input, select, textarea)
                        const fieldValue = field.value ? field.value.trim() : '';

                        if (fieldValue === '') {
                            fieldValid = false;
                            console.log("Champ vide:", field.name);
                        }

                        // Validations spécifiques
                        if (fieldValid && field.type === 'email' && fieldValue !== '') {
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailRegex.test(fieldValue)) {
                                fieldValid = false;
                                console.log("Email invalide:", fieldValue);
                            }
                        }

                        if (fieldValid && field.type === 'tel' && fieldValue !== '') {
                            // Validation téléphone Côte d'Ivoire
                            const cleanPhone = fieldValue.replace(/[\s\-\(\)]/g, '');
                            const phoneRegex = /^(?:\+225)?[0-9]{8,10}$/;
                            if (!phoneRegex.test(cleanPhone)) {
                                fieldValid = false;
                                console.log("Téléphone invalide:", fieldValue);
                            }
                        }

                        if (fieldValid && field.name === 'moyenne' && fieldValue !== '') {
                            const moyenne = parseFloat(fieldValue);
                            if (isNaN(moyenne) || moyenne < 0 || moyenne > 20) {
                                fieldValid = false;
                                console.log("Moyenne invalide:", moyenne);
                            }
                        }
                    }

                    // Si le champ n'est pas valide, afficher l'erreur
                    if (!fieldValid) {
                        isValid = false;
                        field.classList.add('is-invalid');

                        // Créer le message d'erreur
                        let errorDiv = document.createElement('div');
                        errorDiv.classList.add('invalid-feedback');

                        if (field.type === 'file') {
                            if (!field.files || field.files.length === 0) {
                                errorDiv.textContent = 'Ce document est obligatoire';
                            } else {
                                errorDiv.textContent = 'Le fichier ne doit pas dépasser 2 Mo ou le format est incorrect';
                            }
                        } else if (field.type === 'email' && field.value.trim() !== '') {
                            errorDiv.textContent = 'Veuillez entrer un email valide';
                        } else if (field.type === 'tel' && field.value.trim() !== '') {
                            errorDiv.textContent = 'Veuillez entrer un numéro de téléphone valide';
                        } else if (field.name === 'moyenne' && field.value.trim() !== '') {
                            errorDiv.textContent = 'La moyenne doit être comprise entre 0 et 20';
                        } else {
                            errorDiv.textContent = 'Ce champ est obligatoire';
                        }

                        // Ajouter le message après le champ
                        field.parentNode.insertBefore(errorDiv, field.nextSibling);
                    }
                });

                console.log("Résultat validation étape", stepIndex, ":", isValid ? "OK" : "ÉCHEC");
                return isValid;
            }

            // MODIFICATION DU BOUTON SUIVANT - Avec validation
            nextBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                console.log("Clic sur Suivant - Étape actuelle:", currentStep);

                // Valider l'étape actuelle avant de passer à la suivante
                if (validateStep(currentStep)) {
                    if (currentStep < steps.length - 1) {
                        currentStep++;
                        showStep(currentStep);

                        // Scroll en haut du formulaire
                        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } else {
                    // Afficher un message d'alerte
                    alert("Veuillez remplir correctement tous les champs obligatoires de cette étape.");
                }
            });

            // BOUTON PRÉCÉDENT (inchangé, pas de validation nécessaire)
            prevBtn.addEventListener('click', function (e) {
                e.preventDefault();

                if (currentStep > 0) {
                    currentStep--;
                    showStep(currentStep);

                    // Scroll en haut du formulaire
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });

            // VALIDATION AVANT SOUMISSION
            form.addEventListener('submit', function (e) {
                console.log("Tentative de soumission du formulaire");

                // Valider l'étape actuelle
                if (!validateStep(currentStep)) {
                    console.log("Soumission bloquée - étape actuelle invalide");
                    e.preventDefault();
                   // alert("Veuillez remplir correctement tous les champs obligatoires de cette étape.");
                    return false;
                }

                // Valider TOUTES les étapes
                let allStepsValid = true;
                let invalidStepIndex = -1;

                // Sauvegarder l'étape courante
                const savedStep = currentStep;

                // Vérifier chaque étape
                for (let i = 0; i < steps.length; i++) {
                    console.log("Vérification de l'étape", i);

                    if (!validateStep(i)) {
                        allStepsValid = false;
                        invalidStepIndex = i;
                        break;
                    }
                }

                // Revenir à l'étape courante
                currentStep = savedStep;
                showStep(currentStep);

                if (!allStepsValid) {
                    console.log("Soumission bloquée - l'étape", invalidStepIndex, "est invalide");
                    e.preventDefault();

                    // Aller à l'étape invalide
                    currentStep = invalidStepIndex;
                    showStep(currentStep);

                    alert("Veuillez remplir correctement tous les champs obligatoires de l'étape " + (invalidStepIndex + 1) + ".");
                    return false;
                }

                console.log("Formulaire valide, soumission autorisée");
                return true;
            });

            // VALIDATION EN TEMPS RÉEL (optionnel mais recommandé)
            document.querySelectorAll('[required]').forEach(field => {
                // Pour les champs de saisie
                field.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        if (this.value && this.value.trim() !== '') {
                            this.classList.remove('is-invalid');
                            const errorDiv = this.nextElementSibling;
                            if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                                errorDiv.remove();
                            }
                        }
                    }
                });

                // Pour les selects
                field.addEventListener('change', function() {
                    if (this.classList.contains('is-invalid')) {
                        if (this.value && this.value !== '') {
                            this.classList.remove('is-invalid');
                            const errorDiv = this.nextElementSibling;
                            if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                                errorDiv.remove();
                            }
                        }
                    }
                });

                // Pour les fichiers
                if (field.type === 'file') {
                    field.addEventListener('change', function() {
                        if (this.classList.contains('is-invalid')) {
                            if (this.files && this.files.length > 0) {
                                // Vérifier la taille
                                if (this.files[0].size <= 2 * 1024 * 1024) {
                                    this.classList.remove('is-invalid');
                                    const errorDiv = this.nextElementSibling;
                                    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                                        errorDiv.remove();
                                    }
                                }
                            }
                        }
                    });
                }
            });

            // Initialisation
            showStep(currentStep);
        });
    </script>

    <script>
        const input = document.querySelector("#telephone1");

        window.intlTelInput(input, {
            initialCountry: "ci",
            geoIpLookup: (success, failure) => {
                fetch("https://ipapi.co/json")
                    .then((res) => res.json())
                    .then((data) => success(data.country_code))
                    .catch(() => failure());
            }
        });

    </script>
@endpush

    @push('styles')
        <style>
            .iti {

                display: flex;
            }
        </style>
    @endpush
@endsection

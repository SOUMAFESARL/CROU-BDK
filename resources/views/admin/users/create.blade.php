{{--@extends('admin.layouts.app', [--}}
{{--    'activeMenu' => 'users',--}}
{{--    'activeSubMenu' => null--}}
{{--])--}}

{{--@section('title', 'Parc immobilier - Ajouter')--}}

{{--@section('content')--}}
{{--    <div class="page-body">--}}
{{--        <x-breadcrumbs--}}
{{--                title="Ajouter un utilisateur"--}}
{{--                :items="[--}}
{{--                'Utilisateur' => route('admin.users.index'),--}}

{{--                'Ajouter' => null,--}}
{{--            ]"--}}
{{--        />--}}



{{--        <div class="d-flex flex-column flex-md-row mt-4">--}}
{{--            <div class="card-classic latest-update-card" style="flex: 5;">--}}

{{--                <!-- Formulaire Campus -->--}}
{{--                <div id="campus-form" class="form-container"--}}
{{--                     style="">--}}
{{--                    <h4 class="card-title mb-4">Nouvel utilisateur</h4>--}}
{{--                    <form id="form-campus" action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data">--}}
{{--                        @csrf--}}



{{--                        <div class="form-row">--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Civilité <span class="required">*</span></label>--}}
{{--                                <select name="genre_id" class="form-select" id="genre_id" required>--}}
{{--                                    <option value="">Sélectionnez une civilité</option>--}}
{{--                                    @foreach($genre as $item)--}}
{{--                                        <option value="{{$item->id}}">{{$item->name}}</option>--}}
{{--                                    @endforeach--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Nom <span class="required">*</span></label>--}}
{{--                                <input type="text" name="name" placeholder="Ex: John Doe" required>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Matricule </label>--}}
{{--                                <input type="text" name="matricule" placeholder="Ex: xxxxxxxxxxx">--}}
{{--                            </div>--}}

{{--                        </div>--}}

{{--                        <div class="form-row">--}}

{{--                            <div class="form-group">--}}
{{--                                <label>Telephone <span class="required">*</span></label>--}}
{{--                                <input type="tel" name="telephone_principal" autocomplete="tel" id="telephone1" placeholder="Ex: 07xxxxxxxxxx" required>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Telephone </label>--}}
{{--                                <input type="tel" name="telephone_secondaire" id="telephone2" placeholder="Ex: 07xxxxxxxxxx" >--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Email <span class="required">*</span></label>--}}
{{--                                <input type="email" name="email" placeholder="Ex: johndoe@yahoo.fr" required>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Email </label>--}}
{{--                                <input type="email" name="email_secondaire" placeholder="Ex: johndoe@yahoo.fr" >--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div class="form-row">--}}

{{--                            <div class="form-group">--}}
{{--                                <label>Region </label>--}}
{{--                                <select name="region_id" class="form-select" id="region_id"--}}
{{--                                        onchange="getRequest('{{ url('/') }}/admin/list/city?parent_id='+this.value,'ville_id','select',this.value)"--}}
{{--                                >--}}
{{--                                    <option value="">selectionnez une region</option>--}}
{{--                                    @foreach($region as $item)--}}
{{--                                        <option value="{{$item->id}}">{{$item->name}}</option>--}}
{{--                                    @endforeach--}}
{{--                                </select>--}}
{{--                            </div>--}}

{{--                            <div class="form-group">--}}
{{--                                <label>Ville </label>--}}
{{--                                <select name="ville_id" class="form-select" id="ville_id" >--}}
{{--                                    <option value="">selectionnez une ville</option>--}}

{{--                                </select>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Adresse Géographique <span class="required">*</span></label>--}}
{{--                                <input type="text" name="adresse" placeholder="Ex: Rue 123, Quartier Centre" required>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Nationalite <span class="required">*</span></label>--}}
{{--                                <input type="text" name="nationalite" placeholder="Ex: IVOIRIENNE" value="IVOIRIENNE" required>--}}
{{--                            </div>--}}

{{--                        </div>--}}




{{--                        <div class="form-row">--}}

{{--                            <div class="form-group">--}}
{{--                                <label>Date naissance </label>--}}
{{--                                <input type="date" max="{{date('Y-m-d')}}" name="dateNaiss" placeholder="" >--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Lieu naissance </label>--}}
{{--                                <input type="text"  name="lieuNaiss" placeholder="Ex: Abidjan" >--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Numero CNI <span class="required">*</span></label>--}}
{{--                                <input type="text" name="numcni" placeholder="Ex: CIxxxxxxxx" required>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Date de delivrance CNI  </label>--}}
{{--                                <input type="date" max="{{date('Y-m-d')}}" name="datecni" placeholder="" >--}}
{{--                            </div>--}}


{{--                        </div>--}}

{{--                        <div class="form-row">--}}

{{--                            <div class="form-group">--}}
{{--                                <label>Type utilisateur <span class="required">*</span></label>--}}
{{--                                <select name="type_utilisateur_id" class="form-select" id="type_utilisateur_id" required>--}}
{{--                                    <option value="">Selectionnez un type utilisateur</option>--}}
{{--                                    @foreach($typeUtilisateur as $item)--}}
{{--                                        <option value="{{$item->id}}">{{$item->name}}</option>--}}
{{--                                    @endforeach--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Role <span class="required">*</span></label>--}}
{{--                                <select name="role_id" class="form-select" id="role_id" required>--}}
{{--                                    <option value="">Selectionnez un role</option>--}}
{{--                                    @foreach($role as $item)--}}
{{--                                        <option value="{{$item->id}}">{{$item->name}}</option>--}}
{{--                                    @endforeach--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Mot de paase <span class="required">*</span></label>--}}
{{--                                <input type="password" name="password" placeholder="Ex: xxxxxxxx" required>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label>Confirmation mot de passe <span class="required">*</span> </label>--}}
{{--                                <input type="password" name="password_confirmation" placeholder="Ex: xxxxxxxx">--}}

{{--                            </div>--}}


{{--                        </div>--}}

{{--                        <div class="form-group">--}}
{{--                            <label>Photo</label>--}}
{{--                            <div class="upload-area" onclick="document.getElementById('campus-fileInput').click()">--}}
{{--                                <div class="upload-icon">☁️</div>--}}
{{--                                <div class="upload-text">Cliquez pour ajouter une photo ( PNG, JPEG,JPG)</div>--}}
{{--                            </div>--}}
{{--                            <input type="file" id="campus-fileInput" name="photo" accept=".png,.jpg,.jpeg" style="display: none;">--}}
{{--                        </div>--}}

{{--                      --}}

{{--                        <div class="form-actions form-group float-end">--}}

{{--                            <button type="button" class="btn btn-secondary" onclick="resetForm('form-campus')">--}}
{{--                                <i class="fas fa-redo"></i> Réinitialiser--}}
{{--                            </button>--}}
{{--                            <button type="submit" class="btn btn-primary">--}}
{{--                                <i class="fas fa-save"></i> Enregistrer--}}
{{--                            </button>--}}
{{--                        </div>--}}
{{--                    </form>--}}
{{--                </div>--}}






{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--@endsection--}}

{{--@push('styles')--}}

{{--    <style>--}}
{{--        .cards-container {--}}
{{--            display: grid;--}}
{{--            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));--}}
{{--            gap: 20px;--}}
{{--            margin-bottom: 30px;--}}
{{--        }--}}

{{--        .card-select {--}}
{{--            border-radius: 15px;--}}
{{--            padding: 20px;--}}
{{--            cursor: pointer;--}}
{{--            transition: all 0.3s ease;--}}
{{--            border: 2px solid transparent;--}}
{{--            text-align: center;--}}
{{--            background: white;--}}
{{--            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);--}}
{{--        }--}}

{{--        .card-select:hover {--}}
{{--            transform: translateY(-5px);--}}
{{--            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);--}}
{{--        }--}}

{{--        .card-select.selected {--}}
{{--            border-color: #fbbf24;--}}
{{--            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.3);--}}
{{--        }--}}

{{--        .card-select.primary .icone-stats-card {--}}
{{--            background-color: #dbeafe;--}}
{{--            color: #1d4ed8;--}}
{{--        }--}}

{{--        .card-select.success .icone-stats-card {--}}
{{--            background-color: #d1fae5;--}}
{{--            color: #059669;--}}
{{--        }--}}

{{--        .card-select.warning .icone-stats-card {--}}
{{--            background-color: #fef3c7;--}}
{{--            color: #d97706;--}}
{{--        }--}}

{{--        .card-select.info .icone-stats-card {--}}
{{--            background-color: #e0f2fe;--}}
{{--            color: #0369a1;--}}
{{--        }--}}

{{--        .icone-stats-card {--}}
{{--            width: 60px;--}}
{{--            height: 60px;--}}
{{--            border-radius: 50%;--}}
{{--            display: flex;--}}
{{--            align-items: center;--}}
{{--            justify-content: center;--}}
{{--            margin: 0 auto 15px;--}}
{{--            font-size: 24px;--}}
{{--        }--}}

{{--        .titre-card-select {--}}
{{--            font-size: 16px;--}}
{{--            font-weight: 600;--}}
{{--            color: #374151;--}}
{{--        }--}}

{{--        .form-container {--}}
{{--            background: white;--}}
{{--            padding: 25px;--}}
{{--            border-radius: 12px;--}}
{{--            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);--}}
{{--        }--}}

{{--        .form-row {--}}
{{--            display: grid;--}}
{{--            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));--}}
{{--            gap: 20px;--}}
{{--            margin-bottom: 20px;--}}
{{--        }--}}

{{--        .form-group {--}}
{{--            margin-bottom: 15px;--}}
{{--        }--}}

{{--        .form-group label {--}}
{{--            display: block;--}}
{{--            margin-bottom: 8px;--}}
{{--            font-weight: 500;--}}
{{--            color: #374151;--}}
{{--        }--}}

{{--        .form-group .required {--}}
{{--            color: #ef4444;--}}
{{--        }--}}

{{--        .form-group input[type="text"],--}}
{{--        .form-group input[type="number"],--}}
{{--        .form-group input[type="email"],--}}
{{--        .form-group input[type="tel"],--}}
{{--        .form-group input[type="date"],--}}
{{--        .form-group input[type="password"],--}}
{{--        .form-group select,--}}
{{--        .form-group textarea {--}}
{{--            width: 100%;--}}
{{--            padding: 10px 12px;--}}
{{--            border: 1px solid #d1d5db;--}}
{{--            border-radius: 8px;--}}
{{--            font-size: var(--app-font-min, 15px);--}}
{{--            transition: border-color 0.3s;--}}
{{--        }--}}

{{--        .form-group input:focus,--}}
{{--        .form-group select:focus,--}}
{{--        .form-group textarea:focus {--}}
{{--            outline: none;--}}
{{--            border-color: #3b82f6;--}}
{{--            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);--}}
{{--        }--}}

{{--        .form-group input.is-invalid,--}}
{{--        .form-group select.is-invalid,--}}
{{--        .form-group textarea.is-invalid {--}}
{{--            border-color: #ef4444;--}}
{{--        }--}}

{{--        .upload-area {--}}
{{--            border: 2px dashed #d1d5db;--}}
{{--            border-radius: 8px;--}}
{{--            padding: 30px 20px;--}}
{{--            text-align: center;--}}
{{--            cursor: pointer;--}}
{{--            transition: all 0.3s;--}}
{{--            background-color: #f9fafb;--}}
{{--        }--}}

{{--        .upload-area:hover {--}}
{{--            border-color: #3b82f6;--}}
{{--            background-color: #f0f9ff;--}}
{{--        }--}}

{{--        .upload-icon {--}}
{{--            font-size: 32px;--}}
{{--            margin-bottom: 10px;--}}
{{--            opacity: 0.7;--}}
{{--        }--}}

{{--        .upload-text {--}}
{{--            color: #6b7280;--}}
{{--            font-size: var(--app-font-min, 15px);--}}
{{--        }--}}

{{--        .checkbox-group {--}}
{{--            display: flex;--}}
{{--            flex-wrap: wrap;--}}
{{--            gap: 15px;--}}
{{--            padding: 10px;--}}
{{--            background-color: #f9fafb;--}}
{{--            border-radius: 8px;--}}
{{--        }--}}

{{--        .checkbox-group label {--}}
{{--            display: flex;--}}
{{--            align-items: center;--}}
{{--            gap: 8px;--}}
{{--            cursor: pointer;--}}
{{--            font-weight: normal;--}}
{{--        }--}}

{{--        .checkbox-group input[type="checkbox"] {--}}
{{--            width: auto;--}}
{{--        }--}}

{{--        .form-actions {--}}
{{--            display: flex;--}}
{{--            gap: 15px;--}}
{{--            margin-top: 30px;--}}
{{--            padding-top: 20px;--}}
{{--            border-top: 1px solid #e5e7eb;--}}
{{--        }--}}

{{--        .btn {--}}
{{--            padding: 10px 24px;--}}
{{--            border-radius: 8px;--}}
{{--            font-weight: 500;--}}
{{--            cursor: pointer;--}}
{{--            transition: all 0.3s;--}}
{{--            border: none;--}}
{{--            display: inline-flex;--}}
{{--            align-items: center;--}}
{{--            gap: 8px;--}}
{{--        }--}}

{{--        .btn-primary {--}}
{{--            background-color: #3b82f6;--}}
{{--            color: white;--}}
{{--        }--}}

{{--        .btn-primary:hover {--}}
{{--            background-color: #2563eb;--}}
{{--        }--}}

{{--        .btn-success {--}}
{{--            background-color: #10b981;--}}
{{--            color: white;--}}
{{--        }--}}

{{--        .btn-success:hover {--}}
{{--            background-color: #059669;--}}
{{--        }--}}

{{--        .btn-warning {--}}
{{--            background-color: #f59e0b;--}}
{{--            color: white;--}}
{{--        }--}}

{{--        .btn-warning:hover {--}}
{{--            background-color: #d97706;--}}
{{--        }--}}

{{--        .btn-info {--}}
{{--            background-color: #06b6d4;--}}
{{--            color: white;--}}
{{--        }--}}

{{--        .btn-info:hover {--}}
{{--            background-color: #0891b2;--}}
{{--        }--}}

{{--        .btn-secondary {--}}
{{--            background-color: #6b7280;--}}
{{--            color: white;--}}
{{--        }--}}

{{--        .btn-secondary:hover {--}}
{{--            background-color: #4b5563;--}}
{{--        }--}}

{{--        @media (max-width: 768px) {--}}
{{--            .cards-container {--}}
{{--                grid-template-columns: 1fr;--}}
{{--            }--}}

{{--            .form-row {--}}
{{--                grid-template-columns: 1fr;--}}
{{--            }--}}

{{--            .form-actions {--}}
{{--                flex-direction: column;--}}
{{--            }--}}

{{--            .btn {--}}
{{--                width: 100%;--}}
{{--                justify-content: center;--}}
{{--            }--}}
{{--        }--}}
{{--    </style>--}}
{{--@endpush--}}

{{--@push('scripts')--}}
{{--    <script>--}}
{{--        document.addEventListener('DOMContentLoaded', function() {--}}
{{--            const cards = document.querySelectorAll('.card-option');--}}
{{--            const forms = document.querySelectorAll('.form-container');--}}
{{--            const defaultMessage = document.getElementById('default-message');--}}

{{--            // Gestion des fichiers pour l'input chambre--}}
{{--            const chambreFileInput = document.getElementById('chambre-fileInput');--}}
{{--            const chambreFileList = document.getElementById('chambre-fileList');--}}

{{--            if (chambreFileInput) {--}}
{{--                chambreFileInput.addEventListener('change', function() {--}}
{{--                    chambreFileList.innerHTML = '';--}}
{{--                    const files = Array.from(this.files);--}}

{{--                    if (files.length > 5) {--}}
{{--                        alert('Maximum 5 fichiers autorisés');--}}
{{--                        this.value = '';--}}
{{--                        return;--}}
{{--                    }--}}

{{--                    files.forEach((file, index) => {--}}
{{--                        const fileItem = document.createElement('div');--}}
{{--                        fileItem.className = 'file-item';--}}
{{--                        fileItem.innerHTML = `--}}
{{--                            <span>${file.name}</span>--}}
{{--                            <button type="button" onclick="removeFile(${index})">×</button>--}}
{{--                        `;--}}
{{--                        chambreFileList.appendChild(fileItem);--}}
{{--                    });--}}
{{--                });--}}
{{--            }--}}

{{--            // Gestion du clic sur les cartes--}}
{{--            cards.forEach(card => {--}}
{{--                card.addEventListener('click', function() {--}}
{{--                    const targetId = this.getAttribute('data-target');--}}

{{--                    // Retirer la classe selected de toutes les cartes--}}
{{--                    cards.forEach(c => c.classList.remove('selected'));--}}

{{--                    // Ajouter la classe selected à la carte cliquée--}}
{{--                    this.classList.add('selected');--}}

{{--                    // Cacher tous les formulaires et le message par défaut--}}
{{--                    forms.forEach(form => {--}}
{{--                        form.style.display = 'none';--}}
{{--                    });--}}
{{--                    defaultMessage.style.display = 'none';--}}

{{--                    // Afficher le formulaire correspondant--}}
{{--                    const targetForm = document.getElementById(targetId);--}}
{{--                    if (targetForm) {--}}
{{--                        targetForm.style.display = 'block';--}}
{{--                    }--}}
{{--                });--}}
{{--            });--}}

{{--            // Gestion de la soumission des formulaires--}}
{{--            const allForms = document.querySelectorAll('form[id^="form-"]');--}}
{{--            allForms.forEach(form => {--}}
{{--                form.addEventListener('submit', function(e) {--}}
{{--                    const requiredInputs = this.querySelectorAll('[required]');--}}
{{--                    let isValid = true;--}}

{{--                    requiredInputs.forEach(input => {--}}
{{--                        if (!input.value.trim()) {--}}
{{--                            isValid = false;--}}
{{--                            input.classList.add('is-invalid');--}}

{{--                            // Afficher le message d'erreur--}}
{{--                            let errorMsg = input.nextElementSibling;--}}
{{--                            if (!errorMsg || !errorMsg.classList.contains('error-message')) {--}}
{{--                                errorMsg = document.createElement('div');--}}
{{--                                errorMsg.className = 'error-message text-danger mt-1';--}}
{{--                                errorMsg.style.fontSize = '12px';--}}
{{--                                errorMsg.textContent = 'Ce champ est requis';--}}
{{--                                input.parentNode.appendChild(errorMsg);--}}
{{--                            }--}}
{{--                        } else {--}}
{{--                            input.classList.remove('is-invalid');--}}

{{--                            // Supprimer le message d'erreur--}}
{{--                            const errorMsg = input.nextElementSibling;--}}
{{--                            if (errorMsg && errorMsg.classList.contains('error-message')) {--}}
{{--                                errorMsg.remove();--}}
{{--                            }--}}
{{--                        }--}}
{{--                    });--}}

{{--                    if (!isValid) {--}}
{{--                        e.preventDefault();--}}
{{--                        return false;--}}
{{--                    }--}}

{{--                    // Afficher un loader pendant l'envoi--}}
{{--                    const submitBtn = this.querySelector('button[type="submit"]');--}}
{{--                    const originalText = submitBtn.innerHTML;--}}
{{--                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';--}}
{{--                    submitBtn.disabled = true;--}}

{{--                    // Le formulaire sera soumis normalement--}}
{{--                });--}}
{{--            });--}}

{{--            // Gestion des zones d'upload--}}
{{--            document.querySelectorAll('.upload-area').forEach(area => {--}}
{{--                area.addEventListener('dragover', function(e) {--}}
{{--                    e.preventDefault();--}}
{{--                    this.style.borderColor = '#3b82f6';--}}
{{--                    this.style.backgroundColor = '#f0f9ff';--}}
{{--                });--}}

{{--                area.addEventListener('dragleave', function(e) {--}}
{{--                    e.preventDefault();--}}
{{--                    this.style.borderColor = '#d1d5db';--}}
{{--                    this.style.backgroundColor = '#f9fafb';--}}
{{--                });--}}

{{--                area.addEventListener('drop', function(e) {--}}
{{--                    e.preventDefault();--}}
{{--                    this.style.borderColor = '#d1d5db';--}}
{{--                    this.style.backgroundColor = '#f9fafb';--}}

{{--                    const files = e.dataTransfer.files;--}}
{{--                    const inputId = this.getAttribute('onclick').match(/id='([^']+)'/)[1];--}}
{{--                    const input = document.getElementById(inputId);--}}

{{--                    if (input) {--}}
{{--                        input.files = files;--}}

{{--                        // Déclencher l'événement change pour les inputs multiples--}}
{{--                        if (input.multiple && inputId === 'chambre-fileInput') {--}}
{{--                            const event = new Event('change');--}}
{{--                            input.dispatchEvent(event);--}}
{{--                        } else {--}}
{{--                            // Mettre à jour le texte pour les fichiers simples--}}
{{--                            const uploadText = this.querySelector('.upload-text');--}}
{{--                            if (uploadText && files.length > 0) {--}}
{{--                                uploadText.textContent = `${files.length} fichier(s) sélectionné(s)`;--}}
{{--                            }--}}
{{--                        }--}}
{{--                    }--}}
{{--                });--}}
{{--            });--}}
{{--        });--}}

{{--        // Fonction pour supprimer un fichier--}}
{{--        function removeFile(index) {--}}
{{--            const input = document.getElementById('chambre-fileInput');--}}
{{--            const files = Array.from(input.files);--}}
{{--            files.splice(index, 1);--}}

{{--            // Créer un nouveau DataTransfer pour mettre à jour les fichiers--}}
{{--            const dataTransfer = new DataTransfer();--}}
{{--            files.forEach(file => dataTransfer.items.add(file));--}}
{{--            input.files = dataTransfer.files;--}}

{{--            // Déclencher l'événement change--}}
{{--            const event = new Event('change');--}}
{{--            input.dispatchEvent(event);--}}
{{--        }--}}

{{--        // Fonction pour réinitialiser un formulaire--}}
{{--        function resetForm(formId) {--}}
{{--            const form = document.getElementById(formId);--}}
{{--            if (form) {--}}
{{--                form.reset();--}}

{{--                // Réinitialiser les états d'erreur--}}
{{--                form.querySelectorAll('.is-invalid').forEach(input => {--}}
{{--                    input.classList.remove('is-invalid');--}}
{{--                    const errorMsg = input.nextElementSibling;--}}
{{--                    if (errorMsg && errorMsg.classList.contains('error-message')) {--}}
{{--                        errorMsg.remove();--}}
{{--                    }--}}
{{--                });--}}

{{--                // Réinitialiser les zones de fichier--}}
{{--                const fileLists = form.querySelectorAll('#chambre-fileList');--}}
{{--                fileLists.forEach(list => list.innerHTML = '');--}}

{{--                // Réinitialiser le texte des zones d'upload--}}
{{--                form.querySelectorAll('.upload-text').forEach(text => {--}}
{{--                    text.textContent = 'Cliquez pour ajouter un fichier (PDF, PNG, JPEG...)';--}}
{{--                });--}}


{{--            }--}}
{{--        }--}}
{{--    </script>--}}



{{--@endpush--}}



@extends('admin.layouts.app', [
    'activeMenu'    => 'users',
    'activeSubMenu' => null
])

@section('title', 'Utilisateurs - Ajouter')

@section('content')
    <div class="page-body">

        <x-breadcrumbs
                title="Ajouter un utilisateur"
                :items="[
                'Utilisateur' => route('admin.users.index'),
                'Ajouter'     => null,
            ]"
        />

        {{-- Erreurs de validation globales --}}
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <strong><i class="fas fa-exclamation-triangle me-2"></i>Veuillez corriger les erreurs suivantes :</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="d-flex flex-column flex-md-row mt-4">
            <div class="card-classic latest-update-card" style="flex: 5;">

                <div id="campus-form" class="form-container">
                    <h4 class="card-title mb-4">
                        <i class="fas fa-user-plus me-2 text-primary"></i>Nouvel utilisateur
                    </h4>

                    @adminroute('admin.users.store')
                    <form id="form-campus"
                          action="{{ route('admin.users.store') }}"
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf

                        @include('admin.users.form')

                        <div class="form-actions form-group float-end">
                            <button type="button" class="btn btn-secondary" onclick="resetForm('form-campus')">
                                <i class="fas fa-redo"></i> Réinitialiser
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                    @endadminroute
                </div>

            </div>
        </div>
    </div>
@endsection

@push('styles')
    @include('admin.users.styles')
@endpush

@push('scripts')
    @include('admin.users._scripts')
@endpush
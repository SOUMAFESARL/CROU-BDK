
// ========================================
// FONCTIONS JAVASCRIPT PRINCIPALES
// ========================================

// Compteurs basiques
function incrementCounter(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.value = parseInt(input.value || 0) + 1;

        // Déclencher les mises à jour appropriées
        if (inputId === 'nombre_etages_simple') {
            generateEtages();
        } else if (inputId === 'nombre_ailes') {
            generateAiles();
        } else if (inputId === 'nombre_niveaux_global') {
            // Pas besoin de régénérer les ailes
        }
    }
}

function decrementCounter(inputId) {
    const input = document.getElementById(inputId);
    if (input && parseInt(input.value || 0) > (inputId === 'nombre_etages_simple' || inputId === 'nombre_niveaux_global' || inputId === 'nombre_ailes' ? 1 : 0)) {
        input.value = parseInt(input.value) - 1;

        // Déclencher les mises à jour appropriées
        if (inputId === 'nombre_etages_simple') {
            generateEtages();
        } else if (inputId === 'nombre_ailes') {
            generateAiles();
        }
    }
}

// Générer les étages pour structure simple
function generateEtages() {
    const nombreEtages = parseInt(document.getElementById('nombre_etages_simple')?.value || 1);
    const etagesContainer = document.getElementById('etages-container');

    if (!etagesContainer) return;

    etagesContainer.innerHTML = '';

    // RDC
    const rdcRow = document.createElement('div');
    rdcRow.className = 'form-row';
    rdcRow.innerHTML = `
                <div class="form-group">
                    <label>Rez de chaussée</label>
                    <div class="counter-input">
                        <button type="button" class="counter-btn" onclick="decrementCounter('rdc_chambres')">−</button>
                        <input type="number" id="rdc_chambres" name="rdc_chambres" value="10" min="0" readonly>
                        <button type="button" class="counter-btn" onclick="incrementCounter('rdc_chambres')">+</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <select name="rdc_type" class="form-select">
                        <option value="hommes">Uniquement Hommes</option>
                        <option value="femmes">Uniquement Femmes</option>
                        <option value="mixte">Mixte</option>
                    </select>
                </div>
            `;
    etagesContainer.appendChild(rdcRow);

    // Étages
    for (let i = 1; i <= nombreEtages; i++) {
        const etageRow = document.createElement('div');
        etageRow.className = 'form-row';
        etageRow.innerHTML = `
                    <div class="form-group">
                        <label>Étage ${i}</label>
                        <div class="counter-input">
                            <button type="button" class="counter-btn" onclick="decrementCounter('etage${i}_chambres')">−</button>
                            <input type="number" id="etage${i}_chambres" name="etage${i}_chambres" value="10" min="0" readonly>
                            <button type="button" class="counter-btn" onclick="incrementCounter('etage${i}_chambres')">+</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <select name="etage${i}_type" class="form-select">
                            <option value="hommes">Uniquement Hommes</option>
                            <option value="femmes">Uniquement Femmes</option>
                            <option value="mixte">Mixte</option>
                        </select>
                    </div>
                `;
        etagesContainer.appendChild(etageRow);
    }

    showTotalChambres();
}

// Calculer le total des chambres (structure simple)
function showTotalChambres() {
    const nombreEtages = parseInt(document.getElementById('nombre_etages_simple')?.value || 1);
    let total = 0;

    // RDC
    total += parseInt(document.getElementById('rdc_chambres')?.value || 0);

    // Étages
    for (let i = 1; i <= nombreEtages; i++) {
        total += parseInt(document.getElementById(`etage${i}_chambres`)?.value || 0);
    }

    const totalDisplay = document.getElementById('total-chambres');
    if (totalDisplay) {
        totalDisplay.textContent = total;
    }

    // Mettre à jour également le résumé dans le JSON si nécessaire
    console.log(`Total chambres (simple) : ${total} (RDC + ${nombreEtages} étages)`);

    return total;
}

// Toggle configuration niveaux (structure complexe)
// Toggle configuration niveaux (structure complexe)
function toggleNiveauConfig(btn) {
    const buttons = document.querySelectorAll('.niveau-config-btn');
    buttons.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const config = btn.getAttribute('data-config');
    document.getElementById('config_niveaux').value = config;

    // Afficher/masquer la section "même hauteur"
    const memeHauteurSection = document.getElementById('meme-hauteur-section');
    if (memeHauteurSection) {
        memeHauteurSection.style.display = config === 'meme-hauteur' ? 'block' : 'none';
    }

    // Masquer l'aperçu en temps réel si on choisit hauteur variable
    const previewSection = document.getElementById('preview-section');
    if (previewSection) {
        previewSection.style.display = 'block';
    }

    // Mettre à jour l'aperçu
    updatePreview();
}

// Générer les ailes (structure complexe)
function generateAiles() {
    const nombreAiles = parseInt(document.getElementById('nombre_ailes')?.value || 4);
    const ailesContainer = document.getElementById('ailes-container');
    const ailesNiveauxContainer = document.getElementById('ailes-niveaux-container');

    if (!ailesContainer) return;

    ailesContainer.innerHTML = '';
    if (ailesNiveauxContainer) {
        ailesNiveauxContainer.innerHTML = '';
    }

    for (let i = 1; i <= nombreAiles; i++) {
        const aileItem = document.createElement('div');
        aileItem.className = 'aile-item';
        aileItem.innerHTML = `
                    <div class="aile-label">Aile ${i}</div>
                    <div class="aile-select-group">
                        <label>Nom de l'aile</label>
                        <input type="text"
                               name="aile${i}_nom"
                               placeholder="Ex: Aile Ouest"
                               value="">
                    </div>
                    <div class="aile-select-group">
                        <label>Type d'occupation</label>
                        <select name="aile${i}_type" class="form-select">
                            <option value="mixte">Mixte</option>
                            <option value="hommes">Uniquement Hommes</option>
                            <option value="femmes">Uniquement Femmes</option>
                        </select>
                    </div>
                    <button type="button" class="btn-ajouter-chambres" onclick="ajouterChambresAile(${i})">
                        <i class="fas fa-plus"></i> Ajouter chambres
                    </button>
                    <input type="hidden" id="aile${i}_chambres" name="aile${i}_chambres" value="0">
                `;
        ailesContainer.appendChild(aileItem);

        // Générer la configuration de niveaux pour chaque aile en mode hauteur variable
        if (ailesNiveauxContainer) {
            const configItem = document.createElement('div');
            configItem.className = 'aile-niveaux-config';
            configItem.innerHTML = `
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre de niveaux pour l'Aile ${i}</label>
                                <div class="counter-input">
                                    <button type="button" class="counter-btn" onclick="decrementCounter('aile${i}_niveaux')">−</button>
                                    <input type="number"
                                           id="aile${i}_niveaux"
                                           name="aile${i}_niveaux"
                                           value="2"
                                           min="1"
                                           readonly>
                                    <button type="button" class="counter-btn" onclick="incrementCounter('aile${i}_niveaux')">+</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button" class="btn-ajouter-chambres" onclick="ajouterChambresAile(${i})">
                                    <i class="fas fa-plus"></i> Configurer les chambres
                                </button>
                            </div>
                        </div>
                    `;
            ailesNiveauxContainer.appendChild(configItem);
        }
    }

    calculateTotalAiles();
}

// Gérer les changements dans le nombre de niveaux global
function handleGlobalNiveauxChange() {
    const nombreNiveauxGlobal = document.getElementById('nombre_niveaux_global');
    if (nombreNiveauxGlobal) {
        nombreNiveauxGlobal.addEventListener('change', function() {
            // Mettre à jour l'aperçu si la configuration est "même hauteur"
            const configNiveaux = document.getElementById('config_niveaux').value;
            if (configNiveaux === 'meme-hauteur') {
                updatePreview();
            }
        });
    }
}

// Ajouter des chambres à une aile
function ajouterChambresAile(aileNum) {
    const configNiveaux = document.getElementById('config_niveaux').value;
    const aileNom = document.querySelector(`input[name="aile${aileNum}_nom"]`).value || `Aile ${aileNum}`;

    openModal(aileNom, aileNum, configNiveaux);
}

// Ouvrir le modal
function openModal(aileNom, aileNum, mode) {
    const modal = document.getElementById('modal-etages-aile');
    const modalTitle = document.getElementById('modal-aile-name');
    const modalEtagesContainer = document.getElementById('modal-etages-container');

    modalTitle.textContent = aileNom;
    modal.setAttribute('data-aile-num', aileNum);
    modal.setAttribute('data-mode', mode);

    const aileType = document.querySelector(`select[name="aile${aileNum}_type"]`)?.value || 'mixte';
    const canModifyType = aileType === 'mixte';

    // HTML du modal
    modalEtagesContainer.innerHTML = `
        <!-- Champ nombre de niveaux (seulement pour hauteur variable) -->
        ${mode === 'hauteur-variable' ? `
        <div class="form-group">
            <label>Nombre de niveaux pour cette aile</label>
            <div class="counter-input">
                <button type="button" class="counter-btn" onclick="decrementCounterModal('modal_niveaux')">−</button>
                <input type="number" id="modal_niveaux" value="2" min="1" readonly>
                <button type="button" class="counter-btn" onclick="incrementCounterModal('modal_niveaux')">+</button>
            </div>
        </div>
        <div class="form-group" style="margin-top: 15px;">
            <button type="button" class="btn btn-outline-primary" onclick="applyNiveauxToModal()" style="width: 100%;">
                <i class="fas fa-sync-alt"></i> Appliquer le nombre de niveaux
            </button>
        </div>
        <hr style="margin: 20px 0;">
        ` : ''}

        <div id="modal-etages-content">
            <!-- Les étages seront générés dynamiquement ici -->
        </div>
    `;

    // Déterminer le nombre de niveaux selon le mode
    let nombreNiveaux;
    if (mode === 'meme-hauteur') {
        nombreNiveaux = parseInt(document.getElementById('nombre_niveaux_global')?.value || 2);
    } else {
        // Mode hauteur variable : utiliser la valeur par défaut ou charger une valeur existante
        nombreNiveaux = 2;
        // Vous pourriez charger une valeur sauvegardée ici si vous avez une variable
        // const savedNiveaux = document.getElementById(`aile${aileNum}_saved_niveaux`)?.value;
        // if (savedNiveaux) nombreNiveaux = parseInt(savedNiveaux);
    }

    // Générer les étages initiaux
    generateModalEtages(nombreNiveaux, aileType, canModifyType);

    // Afficher le modal
    modal.classList.add('show');
    modal.style.display = 'block';
    modal.offsetHeight; // Forcer le rafraîchissement
}

function generateModalEtages(nombreNiveaux, aileType, canModifyType) {
    const modalEtagesContent = document.getElementById('modal-etages-content');
    if (!modalEtagesContent) return;

    modalEtagesContent.innerHTML = '';

    // RDC
    const rdcRow = document.createElement('div');
    rdcRow.className = 'form-row';
    rdcRow.innerHTML = `
        <div class="form-group">
            <label>Rez de chaussée</label>
            <div class="counter-input">
                <button type="button" class="counter-btn" onclick="decrementCounterModal('modal_rdc_chambres')">−</button>
                <input type="number" id="modal_rdc_chambres" value="0" min="0" readonly>
                <button type="button" class="counter-btn" onclick="incrementCounterModal('modal_rdc_chambres')">+</button>
            </div>
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <select id="modal_rdc_type" class="form-select" ${canModifyType ? '' : 'disabled'}>
                <option value="hommes" ${aileType === 'hommes' ? 'selected' : ''}>Uniquement Hommes</option>
                <option value="femmes" ${aileType === 'femmes' ? 'selected' : ''}>Uniquement Femmes</option>
                <option value="mixte" ${aileType === 'mixte' ? 'selected' : ''}>Mixte</option>
            </select>
        </div>
    `;
    modalEtagesContent.appendChild(rdcRow);

    // Étages
    for (let i = 1; i <= nombreNiveaux; i++) {
        const etageRow = document.createElement('div');
        etageRow.className = 'form-row';
        etageRow.innerHTML = `
            <div class="form-group">
                <label>Étage ${i}</label>
                <div class="counter-input">
                    <button type="button" class="counter-btn" onclick="decrementCounterModal('modal_etage${i}_chambres')">−</button>
                    <input type="number" id="modal_etage${i}_chambres" value="0" min="0" readonly>
                    <button type="button" class="counter-btn" onclick="incrementCounterModal('modal_etage${i}_chambres')">+</button>
                </div>
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <select id="modal_etage${i}_type" class="form-select" ${canModifyType ? '' : 'disabled'}>
                    <option value="hommes" ${aileType === 'hommes' ? 'selected' : ''}>Uniquement Hommes</option>
                    <option value="femmes" ${aileType === 'femmes' ? 'selected' : ''}>Uniquement Femmes</option>
                    <option value="mixte" ${aileType === 'mixte' ? 'selected' : ''}>Mixte</option>
                </select>
            </div>
        `;
        modalEtagesContent.appendChild(etageRow);
    }
}

function applyNiveauxToModal() {
    const modal = document.getElementById('modal-etages-aile');
    const mode = modal.getAttribute('data-mode');
    const aileNum = modal.getAttribute('data-aile-num');

    if (mode === 'hauteur-variable') {
        const aileType = document.querySelector(`select[name="aile${aileNum}_type"]`)?.value || 'mixte';
        const canModifyType = aileType === 'mixte';
        const nombreNiveaux = parseInt(document.getElementById('modal_niveaux')?.value || 2);

        generateModalEtages(nombreNiveaux, aileType, canModifyType);
    }
}

// Fermer le modal
function closeModal() {
    const modal = document.getElementById('modal-etages-aile');
    modal.classList.remove('show');
    modal.style.display = 'none';
}

// Compteurs dans le modal
function incrementCounterModal(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.value = parseInt(input.value || 0) + 1;

        // Si c'est le champ "modal_niveaux", mettre à jour les étages immédiatement
        if (inputId === 'modal_niveaux') {
            // Vous pouvez choisir de mettre à jour automatiquement ou non
            // Pour l'instant, on laisse l'utilisateur cliquer sur "Appliquer"
        }
    }
}

function decrementCounterModal(inputId) {
    const input = document.getElementById(inputId);
    if (input && parseInt(input.value || 0) > (inputId === 'modal_niveaux' ? 1 : 0)) {
        input.value = parseInt(input.value) - 1;

        // Si c'est le champ "modal_niveaux", mettre à jour les étages immédiatement
        if (inputId === 'modal_niveaux') {
            // Vous pouvez choisir de mettre à jour automatiquement ou non
        }
    }
}

// Mettre à jour le type d'aile en fonction des types des niveaux
function updateAileTypeFromNiveaux() {
    const modal = document.getElementById('modal-etages-aile');
    if (!modal) return;

    const aileNum = modal.getAttribute('data-aile-num');
    if (!aileNum) return;

    // Récupérer tous les sélecteurs de type dans le modal
    const typeSelectors = [];

    // RDC
    const rdcType = document.getElementById('modal_rdc_type');
    if (rdcType) typeSelectors.push(rdcType);

    // Étages
    let i = 1;
    while (true) {
        const etageType = document.getElementById(`modal_etage${i}_type`);
        if (!etageType) break;
        typeSelectors.push(etageType);
        i++;
    }

    if (typeSelectors.length === 0) return;

    // Vérifier si tous les niveaux ont le même type
    const firstType = typeSelectors[0].value;
    let allSameType = true;

    for (const selector of typeSelectors) {
        if (selector.value !== firstType) {
            allSameType = false;
            break;
        }
    }

    // Déterminer le nouveau type d'aile
    let newAileType = 'mixte';
    if (allSameType) {
        if (firstType === 'hommes' || firstType === 'femmes') {
            newAileType = firstType;
        }
    }

    // Mettre à jour le sélecteur de type d'aile dans le formulaire principal
    const aileTypeSelector = document.querySelector(`select[name="aile${aileNum}_type"]`);
    if (aileTypeSelector) {
        aileTypeSelector.value = newAileType;

        // Mettre à jour l'état des sélecteurs dans le modal
        const canModifyType = newAileType === 'mixte';
        typeSelectors.forEach(selector => {
            selector.disabled = !canModifyType;
        });

        // Mettre à jour l'aperçu
        updatePreview();

        console.log(`Type d'aile ${aileNum} mis à jour à: ${newAileType} (tous les niveaux sont ${allSameType ? 'identiques' : 'différents'})`);
    }
}

// Enregistrer les étages du modal
function saveEtagesAile() {
    const modal = document.getElementById('modal-etages-aile');
    const aileNum = modal.getAttribute('data-aile-num');
    const mode = modal.getAttribute('data-mode');

    let nombreNiveaux;
    let totalChambres = 0;

    if (mode === 'meme-hauteur') {
        nombreNiveaux = parseInt(document.getElementById('nombre_niveaux_global')?.value || 2);
    } else {
        // Pour hauteur variable, utiliser le nombre de niveaux du modal
        nombreNiveaux = parseInt(document.getElementById('modal_niveaux')?.value || 2);

        // Sauvegarder le nombre de niveaux pour cette aile
        const niveauxInput = document.createElement('input');
        niveauxInput.type = 'hidden';
        niveauxInput.id = `aile${aileNum}_saved_niveaux`;
        niveauxInput.name = `aile${aileNum}_saved_niveaux`;
        niveauxInput.value = nombreNiveaux;

        // Ajouter au formulaire s'il n'existe pas déjà
        if (!document.getElementById(`aile${aileNum}_saved_niveaux`)) {
            document.getElementById('form-batiment').appendChild(niveauxInput);
        } else {
            document.getElementById(`aile${aileNum}_saved_niveaux`).value = nombreNiveaux;
        }
    }

    // Sauvegarder les données détaillées dans window.etagesData
    if (!window.etagesData) {
        window.etagesData = {};
    }

    // Initialiser les données pour cette aile
    window.etagesData[aileNum] = {
        nombre_niveaux: nombreNiveaux,
        etages: {}
    };

    // RDC
    const rdcChambres = parseInt(document.getElementById('modal_rdc_chambres')?.value || 0);
    const rdcType = document.getElementById('modal_rdc_type')?.value || 'mixte';

    window.etagesData[aileNum].rdc = {
        chambres: rdcChambres,
        type: rdcType
    };
    totalChambres += rdcChambres;

    // Étages
    for (let i = 1; i <= nombreNiveaux; i++) {
        const etageChambres = parseInt(document.getElementById(`modal_etage${i}_chambres`)?.value || 0);
        const etageType = document.getElementById(`modal_etage${i}_type`)?.value || 'mixte';

        window.etagesData[aileNum].etages[i] = {
            nord: 0,  // Par défaut, pas de répartition par orientation
            sud: 0,
            est: 0,
            ouest: 0,
            total: etageChambres,
            type: etageType
        };
        totalChambres += etageChambres;
    }

    // Mettre à jour le type d'aile en fonction des types des niveaux
    updateAileTypeFromNiveaux();

    // Mettre à jour l'input caché de l'aile
    const input = document.getElementById(`aile${aileNum}_chambres`);
    if (input) {
        input.value = totalChambres;
        calculateTotalAiles();
        updatePreview();
    }

    // Fermer le modal
    closeModal();

    // Debug: afficher les données sauvegardées
    console.log(`Données sauvegardées pour l'aile ${aileNum}:`, window.etagesData[aileNum]);
}

// Calculer le total des chambres (structure complexe)
function calculateTotalAiles() {
    const nombreAiles = parseInt(document.getElementById('nombre_ailes')?.value || 0);
    let total = 0;

    for (let i = 1; i <= nombreAiles; i++) {
        const chambres = parseInt(document.getElementById(`aile${i}_chambres`)?.value || 0);
        total += chambres;
    }

    const totalDisplay = document.getElementById('total-chambres-ailes');
    if (totalDisplay) {
        totalDisplay.textContent = total;
    }

    // Mettre à jour l'aperçu en temps réel
    updatePreview();
}

// Mettre à jour l'aperçu en temps réel
function updatePreview() {
    const previewSection = document.getElementById('preview-section');
    const previewContent = document.getElementById('preview-content');
    const nombreAiles = parseInt(document.getElementById('nombre_ailes')?.value || 0);
    const configNiveaux = document.getElementById('config_niveaux').value;

    // Afficher la section d'aperçu pour les deux modes
    if (nombreAiles > 0) {
        previewSection.style.display = 'block';
    } else {
        previewSection.style.display = 'none';
        return;
    }

    // Générer le contenu selon le mode
    let previewHTML = '';

    if (configNiveaux === 'meme-hauteur') {
        // Mode "Même hauteur" (existant)
        for (let i = 1; i <= nombreAiles; i++) {
            const aileNom = document.querySelector(`input[name="aile${i}_nom"]`)?.value || `Aile ${i}`;
            const aileType = document.querySelector(`select[name="aile${i}_type"]`)?.value || 'mixte';
            const chambresTotal = parseInt(document.getElementById(`aile${i}_chambres`)?.value || 0);
            const nombreNiveaux = parseInt(document.getElementById('nombre_niveaux_global')?.value || 2);

            const typeBadge = `<span class="preview-badge ${aileType}">${aileType}</span>`;

            previewHTML += `
                <div class="preview-card">
                    <div class="preview-card-header">
                        <div class="preview-card-title">
                            <i class="fas fa-wind"></i>
                            ${aileNom}
                        </div>
                        ${typeBadge}
                    </div>
                    <div class="preview-card-subtitle">
                        ${nombreNiveaux} niveau${nombreNiveaux > 1 ? 'x' : ''} + RDC
                    </div>
                    <div class="preview-total">
                        <span>Total chambres</span>
                        <span>${chambresTotal} chambre${chambresTotal > 1 ? 's' : ''}</span>
                    </div>
                </div>
            `;
        }
    } else {
        // Mode "Hauteur variable" (à implémenter)
        for (let i = 1; i <= nombreAiles; i++) {
            const aileNom = document.querySelector(`input[name="aile${i}_nom"]`)?.value || `Aile ${i}`;
            const aileType = document.querySelector(`select[name="aile${i}_type"]`)?.value || 'mixte';
            const chambresTotal = parseInt(document.getElementById(`aile${i}_chambres`)?.value || 0);

            // Récupérer le nombre de niveaux spécifique à cette aile
            const niveauxInput = document.getElementById(`aile${i}_saved_niveaux`);
            const nombreNiveaux = niveauxInput ? parseInt(niveauxInput.value) : 2;

            const typeBadge = `<span class="preview-badge ${aileType}">${aileType}</span>`;

            previewHTML += `
                <div class="preview-card">
                    <div class="preview-card-header">
                        <div class="preview-card-title">
                            <i class="fas fa-layer-group"></i>
                            ${aileNom}
                        </div>
                        ${typeBadge}
                    </div>
                    <div class="preview-card-subtitle">
                        ${nombreNiveaux} niveau${nombreNiveaux > 1 ? 'x' : ''} + RDC
                        <br><small style="color: #6b7280; font-size: 11px;">(Configuration personnalisée)</small>
                    </div>
                    <div class="preview-total">
                        <span>Total chambres</span>
                        <span>${chambresTotal} chambre${chambresTotal > 1 ? 's' : ''}</span>
                    </div>
                </div>
            `;
        }
    }

    previewContent.innerHTML = previewHTML;
}

// Gestion du changement de type d'architecture
function handleArchitectureChange() {
    const radioButtons = document.querySelectorAll('input[name="type_architecture"]');
    const simpleSection = document.getElementById('structure-simple');
    const complexeSection = document.getElementById('structure-complexe');

    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.architecture-option').forEach(option => {
                option.classList.remove('selected');
            });

            if (this.checked) {
                const parentLabel = this.closest('.architecture-option');
                if (parentLabel) {
                    parentLabel.classList.add('selected');
                }
            }

            if (this.value === 'simple') {
                if (simpleSection) simpleSection.style.display = 'block';
                if (complexeSection) complexeSection.style.display = 'none';
            } else if (this.value === 'complexe') {
                if (simpleSection) simpleSection.style.display = 'none';
                if (complexeSection) complexeSection.style.display = 'block';
            }
        });
    });

    const checkedRadio = document.querySelector('input[name="type_architecture"]:checked');
    if (checkedRadio) {
        checkedRadio.dispatchEvent(new Event('change'));
    }
}

// Ajouter un service
function addService() {
    const serviceName = prompt('Entrez le nom du nouveau service:');
    if (serviceName && serviceName.trim()) {
        const servicesGrid = document.querySelector('#cite-form .services-grid');
        const addBtn = servicesGrid?.querySelector('.add-service-btn');
        if (!servicesGrid || !addBtn) return;

        const serviceId = 'service_' + Date.now();
        const newService = document.createElement('div');
        newService.className = 'service-checkbox';
        newService.innerHTML = `
                    <input type="checkbox" id="${serviceId}" name="service_${serviceName.toLowerCase().replace(/\s+/g, '_')}" checked>
                    <label for="${serviceId}">${serviceName}</label>
                `;
        servicesGrid.insertBefore(newService, addBtn);
    }
}

function addServiceBatiment() {
    const serviceName = prompt('Entrez le nom du nouveau service:');
    if (serviceName && serviceName.trim()) {
        const servicesGrid = document.querySelector('#batiment-form .services-grid');
        const addBtn = servicesGrid?.querySelector('.add-service-btn');
        if (!servicesGrid || !addBtn) return;

        const serviceId = 'service_bat_' + Date.now();
        const newService = document.createElement('div');
        newService.className = 'service-checkbox';
        newService.innerHTML = `
                    <input type="checkbox" id="${serviceId}" name="service_${serviceName.toLowerCase().replace(/\s+/g, '_')}" checked>
                    <label for="${serviceId}">${serviceName}</label>
                `;
        servicesGrid.insertBefore(newService, addBtn);
    }
}

// Réinitialiser un formulaire
function resetForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
    }
}

// ========================================
// FONCTION POUR GÉNÉRER LES DONNÉES EN JSON
// ========================================

// Collecter toutes les données du formulaire bâtiment en JSON structuré
function collectBatimentDataJSON() {
    const typeArchitecture = document.querySelector('input[name="type_architecture"]:checked')?.value;

    const data = {
        // Informations de base du bâtiment
        informations_generales: {
            type: 'batiment',
            nom: document.getElementById('batiment-name')?.value || document.querySelector('#form-batiment input[name="name"]')?.value || document.querySelector('#form-batiment input[name="nom_batiment"]')?.value || '',
            type_occupation: document.querySelector('#form-batiment select[name="type_occupation"]')?.value || 'mixte',
            type_architecture: typeArchitecture,
            code_campus: document.querySelector('#form-batiment select[name="code_campus"]')?.value || '',
            code_cite: document.querySelector('#form-batiment select[name="code_cite"]')?.value || '',
            etat_initial: document.querySelector('#form-batiment select[name="etat_initial"]')?.value || 'operationnel',
            description: document.getElementById('batiment-description')?.value || document.querySelector('#form-batiment textarea[name="description"]')?.value || ''
        },

        // Données de structure (selon le type d'architecture)
        structure: null,

        // Résumé et totaux
        resume: {
            total_chambres: 0,
            total_etages: 0,
            total_ailes: 0,
            configuration_valide: false
        }
    };

    // Collecter les données selon le type d'architecture
    if (typeArchitecture === 'simple') {
        data.structure = collectStructureSimpleJSON();
        data.resume.total_chambres = data.structure.resume.total_chambres;
        data.resume.total_etages = data.structure.resume.total_etages;
    } else if (typeArchitecture === 'complexe') {
        data.structure = collectStructureComplexeJSON();
        data.resume.total_chambres = data.structure.resume.total_chambres;
        data.resume.total_ailes = data.structure.resume.total_ailes;
        data.resume.total_etages = data.structure.resume.total_etages;
    }

    // Validation de la configuration
    data.resume.configuration_valide = validateBatimentData(data);

    return data;
}

// Collecter les données de structure simple
function collectStructureSimpleJSON() {
    const nombreEtages = parseInt(document.getElementById('nombre_etages_simple')?.value || 1);
    const etages = [];
    let totalChambres = 0;
    let totalEtages = 1; // Commence à 1 pour le RDC

    // RDC - CORRECTION : récupérer correctement les valeurs
    const rdcChambresInput = document.getElementById('rdc_chambres');
    const rdcTypeSelect = document.querySelector('select[name="rdc_type"]');

    const rdcChambres = rdcChambresInput ? parseInt(rdcChambresInput.value) || 0 : 0;
    const rdcType = rdcTypeSelect ? rdcTypeSelect.value : 'mixte';

    etages.push({
        niveau: 'rdc',
        numero: 0,
        chambres: rdcChambres,
        type_occupation: rdcType
    });
    totalChambres += rdcChambres;

    // Étages - CORRECTION : récupérer correctement les valeurs
    for (let i = 1; i <= nombreEtages; i++) {
        const chambresInput = document.getElementById(`etage${i}_chambres`);
        const typeSelect = document.querySelector(`select[name="etage${i}_type"]`);

        const chambres = chambresInput ? parseInt(chambresInput.value) || 0 : 0;
        const type = typeSelect ? typeSelect.value : 'mixte';

        etages.push({
            niveau: 'etage',
            numero: i,
            chambres: chambres,
            type_occupation: type
        });
        totalChambres += chambres;
    }

    // Total des étages = RDC + nombre d'étages
    totalEtages = 1 + nombreEtages;

    return {
        type: 'simple',
        configuration: {
            nombre_etages: nombreEtages, // Nombre d'étages (sans RDC)
            total_niveaux: totalEtages,  // RDC + étages
            etages: etages
        },
        resume: {
            total_chambres: totalChambres,
            total_etages: totalEtages // Correction : inclut le RDC
        }
    };
}

// Collecter les données de structure complexe
function collectStructureComplexeJSON() {
    const nombreAiles = parseInt(document.getElementById('nombre_ailes')?.value || 4);
    const configNiveaux = document.getElementById('config_niveaux')?.value || 'meme-hauteur';
    const ailes = [];
    let totalChambres = 0;
    let totalNiveaux = 0;

    // Pour "Même hauteur partout"
    let niveauxGlobal = 0;
    if (configNiveaux === 'meme-hauteur') {
        niveauxGlobal = parseInt(document.getElementById('nombre_niveaux_global')?.value || 2);
    }

    for (let i = 1; i <= nombreAiles; i++) {
        const aileNom = document.querySelector(`input[name="aile${i}_nom"]`)?.value || `Aile ${i}`;
        const aileType = document.querySelector(`select[name="aile${i}_type"]`)?.value || 'mixte';
        const chambresAile = parseInt(document.getElementById(`aile${i}_chambres`)?.value || 0);

        // Déterminer le nombre de niveaux pour cette aile
        let nombreNiveauxAile = niveauxGlobal;
        if (configNiveaux === 'hauteur-variable') {
            const savedNiveaux = document.getElementById(`aile${i}_saved_niveaux`);
            nombreNiveauxAile = savedNiveaux ? parseInt(savedNiveaux.value) : 2;
        }

        // Collecter les données détaillées des étages si disponibles
        let etagesDetail = [];
        if (window.etagesData && window.etagesData[i]) {
            const aileConfig = window.etagesData[i];

            // RDC
            if (aileConfig.rdc) {
                etagesDetail.push({
                    niveau: 'rdc',
                    numero: 0,
                    chambres: aileConfig.rdc.chambres || 0,
                    type_occupation: aileConfig.rdc.type || 'mixte'
                });
            }

            // Étages
            for (let n = 1; n <= aileConfig.nombre_niveaux; n++) {
                if (aileConfig.etages && aileConfig.etages[n]) {
                    const etage = aileConfig.etages[n];
                    etagesDetail.push({
                        niveau: 'etage',
                        numero: n,
                        chambres: etage.total || 0,
                        type_occupation: etage.type || 'mixte',
                        repartition_par_orientation: {
                            nord: etage.nord || 0,
                            sud: etage.sud || 0,
                            est: etage.est || 0,
                            ouest: etage.ouest || 0
                        }
                    });
                }
            }
        }

        const aileData = {
            aile_numero: i,
            aile_nom: aileNom,
            type_occupation: aileType,
            configuration_niveaux: {
                mode: configNiveaux,
                nombre_niveaux: nombreNiveauxAile
            },
            chambres_total: chambresAile,
            etages: etagesDetail
        };

        ailes.push(aileData);
        totalChambres += chambresAile;
        totalNiveaux += nombreNiveauxAile;
    }

    return {
        type: 'complexe',
        configuration: {
            mode_configuration: configNiveaux,
            nombre_ailes: nombreAiles,
            ailes: ailes
        },
        resume: {
            total_chambres: totalChambres,
            total_ailes: nombreAiles,
            total_niveaux: totalNiveaux
        }
    };
}

// Collecter les données détaillées des niveaux d'une aile
function collectNiveauxAileJSON(aileNum, etagesData) {
    const niveaux = [];

    if (!etagesData || !etagesData.etages) return niveaux;

    // RDC
    if (etagesData.rdc) {
        niveaux.push({
            niveau: 'rdc',
            numero: 0,
            chambres: etagesData.rdc.chambres || 0,
            type_occupation: etagesData.rdc.type || 'mixte'
        });
    }

    // Étages
    Object.keys(etagesData.etages).forEach(etageNum => {
        const etage = etagesData.etages[etageNum];
        niveaux.push({
            niveau: 'etage',
            numero: parseInt(etageNum),
            chambres: etage.total || 0,
            type_occupation: etage.type || 'mixte',
            repartition_par_orientation: {
                nord: etage.nord || 0,
                sud: etage.sud || 0,
                est: etage.est || 0,
                ouest: etage.ouest || 0
            }
        });
    });

    return niveaux;
}

// Valider les données du bâtiment
function validateBatimentData(data) {
    let isValid = true;
    const errors = [];

    // Validation des informations générales
    if (!data.informations_generales.nom) {
        errors.push("Le nom du bâtiment est obligatoire");
        isValid = false;
    }

    if (!data.informations_generales.code_cite) {
        errors.push("La cité de rattachement est obligatoire");
        isValid = false;
    }

    if (!data.informations_generales.type_occupation) {
        errors.push("Le type d'occupation est obligatoire");
        isValid = false;
    }

    if (!data.informations_generales.type_architecture) {
        errors.push("Le type d'architecture est obligatoire");
        isValid = false;
    }

    // Validation selon le type d'architecture
    if (data.informations_generales.type_architecture === 'simple') {
        if (!data.structure || data.resume.total_chambres === 0) {
            errors.push("Veuillez configurer au moins une chambre dans la structure simple");
            isValid = false;
        }
    } else if (data.informations_generales.type_architecture === 'complexe') {
        if (!data.structure || data.resume.total_chambres === 0) {
            errors.push("Veuillez configurer au moins une chambre dans la structure complexe");
            isValid = false;
        }
    }

    // Stocker les erreurs si besoin
    if (errors.length > 0) {
        data.validation = {
            valide: false,
            erreurs: errors
        };
    } else {
        data.validation = {
            valide: true,
            erreurs: []
        };
    }

    return isValid;
}

// Afficher les données JSON dans une modal
function showBatimentDataJSON() {
    const data = collectBatimentDataJSON();
    const jsonString = JSON.stringify(data, null, 2);

    // Créer une modal pour afficher le JSON
    const modalId = 'json-data-modal';
    let modal = document.getElementById(modalId);

    if (!modal) {
        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'custom-modal';
        modal.innerHTML = `
            <div class="custom-modal-overlay" onclick="closeJSONModal()"></div>
            <div class="custom-modal-container">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <div class="custom-modal-header-top">
                            <h5 class="custom-modal-title">Données JSON du Bâtiment</h5>
                            <button type="button" class="custom-modal-close" onclick="closeJSONModal()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="custom-modal-subtitle">Structure organisée des données du formulaire</p>
                    </div>
                    <div class="custom-modal-body">
                        <pre id="json-display" style="max-height: 500px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 12px; white-space: pre-wrap;"></pre>
                        <div class="mt-3 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" onclick="copyJSONToClipboard()">
                                <i class="fas fa-copy"></i> Copier JSON
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="downloadJSONData()">
                                <i class="fas fa-download"></i> Télécharger JSON
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="showJSONSummary()">
                                <i class="fas fa-chart-bar"></i> Résumé
                            </button>
                        </div>
                    </div>
                    <div class="custom-modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeJSONModal()">Fermer</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Afficher le JSON
    document.getElementById('json-display').textContent = jsonString;

    // Afficher la modal
    modal.classList.add('show');
    modal.style.display = 'block';

    // Stocker les données pour les fonctions utilitaires
    window.currentBatimentJSON = data;
}

// Fermer la modal JSON
function closeJSONModal() {
    const modal = document.getElementById('json-data-modal');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
    }
}

// Copier le JSON dans le presse-papier
function copyJSONToClipboard() {
    const data = window.currentBatimentJSON;
    if (!data) return;

    const jsonString = JSON.stringify(data, null, 2);
    navigator.clipboard.writeText(jsonString).then(() => {
        alert('JSON copié dans le presse-papier !');
    }).catch(err => {
        console.error('Erreur lors de la copie : ', err);
        alert('Erreur lors de la copie du JSON');
    });
}

// Télécharger les données JSON
function downloadJSONData() {
    const data = window.currentBatimentJSON;
    if (!data) return;

    const jsonString = JSON.stringify(data, null, 2);
    const blob = new Blob([jsonString], { type: 'application/json' });
    const url = URL.createObjectURL(blob);

    const a = document.createElement('a');
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const nomBatiment = data.informations_generales.nom || 'batiment';
    a.href = url;
    a.download = `batiment-${nomBatiment}-${timestamp}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Afficher un résumé des données
function showJSONSummary() {
    const data = window.currentBatimentJSON;
    if (!data) return;

    let summary = `RÉSUMÉ DU BÂTIMENT\n`;
    summary += `==================\n\n`;
    summary += `Nom: ${data.informations_generales.nom || 'Non défini'}\n`;
    summary += `Type d'architecture: ${data.informations_generales.type_architecture || 'Non défini'}\n`;
    summary += `Type d'occupation: ${data.informations_generales.type_occupation || 'Non défini'}\n`;
    summary += `Cité: ${data.informations_generales.code_cite || 'Non définie'}\n\n`;

    if (data.informations_generales.type_architecture === 'simple') {
        summary += `STRUCTURE SIMPLE\n`;
        summary += `----------------\n`;
        summary += `Nombre d'étages: ${data.structure.configuration.nombre_etages}\n`;
        summary += `Total chambres: ${data.resume.total_chambres}\n`;
        summary += `Total étages (RDC inclus): ${data.resume.total_etages}\n`;
    } else if (data.informations_generales.type_architecture === 'complexe') {
        summary += `STRUCTURE COMPLEXE\n`;
        summary += `------------------\n`;
        summary += `Nombre d'ailes: ${data.structure.configuration.nombre_ailes}\n`;
        summary += `Mode configuration: ${data.structure.configuration.mode_configuration}\n`;
        summary += `Total chambres: ${data.resume.total_chambres}\n`;
        summary += `Total ailes: ${data.resume.total_ailes}\n`;
    }

    summary += `\nVALIDATION: ${data.validation.valide ? '✓ VALIDE' : '✗ INVALIDE'}\n`;
    if (!data.validation.valide && data.validation.erreurs.length > 0) {
        summary += `Erreurs:\n`;
        data.validation.erreurs.forEach(erreur => {
            summary += `  - ${erreur}\n`;
        });
    }

    alert(summary);
}

// Initialiser le bouton pour afficher les données JSON
function initJSONDataButton() {
    const formActions = document.querySelector('#batiment-form .form-actions');
    if (!formActions) return;

    // Vérifier si le bouton existe déjà
    if (document.getElementById('btn-show-json')) return;

    // Créer le bouton
    const jsonButton = document.createElement('button');
    jsonButton.type = 'button';
    jsonButton.id = 'btn-show-json';
    jsonButton.className = 'btn btn-info';
    jsonButton.innerHTML = '<i class="fas fa-code"></i> Voir données JSON';
    jsonButton.onclick = showBatimentDataJSON;

    // Ajouter le bouton après le bouton de réinitialisation
    const resetBtn = formActions.querySelector('button[onclick*="resetForm"]');
    if (resetBtn) {
        resetBtn.parentNode.insertBefore(jsonButton, resetBtn.nextSibling);
    } else {
        formActions.appendChild(jsonButton);
    }
}

// Mettre à jour le console.log en temps réel à chaque modification de champ
// Fonction simplifiée pour le logging en temps réel
function initRealTimeConsoleLogging() {
    const form = document.getElementById('form-batiment');
    if (!form) return;

    // Fonction pour logger les données
    function logBatimentData() {
        try {
            const batimentData = collectBatimentDataJSON();

            console.log('=== DONNÉES DU FORMULAIRE BÂTIMENT (TEMPS RÉEL) ===');
            console.log('Dernière mise à jour: ' + new Date().toLocaleTimeString());

            // Afficher un résumé
            console.log('\n=== RÉSUMÉ DES DONNÉES ===');
            console.log(`Nom du bâtiment: ${batimentData.informations_generales.nom || 'Non défini'}`);
            console.log(`Type d'architecture: ${batimentData.informations_generales.type_architecture || 'Non défini'}`);
            console.log(`Total chambres: ${batimentData.resume.total_chambres}`);

            // Afficher les données JSON complètes
            console.log('\n=== DONNÉES JSON COMPLÈTES ===');
            console.log(JSON.stringify(batimentData, null, 2));

        } catch (error) {
            console.error('Erreur lors de la collecte des données:', error);
        }
    }

    // Attacher des écouteurs d'événements à tous les champs
    function attachEventListeners() {
        // Inputs texte, nombres, textarea
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Supprimer les anciens écouteurs
            const newInput = input.cloneNode(true);
            input.parentNode.replaceChild(newInput, input);

            // Ajouter les nouveaux écouteurs
            newInput.addEventListener('input', logBatimentData);
            newInput.addEventListener('change', logBatimentData);
        });

        // Boutons radio
        const radios = form.querySelectorAll('input[type="radio"]');
        radios.forEach(radio => {
            radio.addEventListener('change', logBatimentData);
        });

        // Boutons de compteur
        const counterButtons = form.querySelectorAll('.counter-btn');
        counterButtons.forEach(button => {
            button.addEventListener('click', function() {
                setTimeout(logBatimentData, 100);
            });
        });

        // Boutons de configuration
        const configButtons = form.querySelectorAll('.niveau-config-btn');
        configButtons.forEach(button => {
            button.addEventListener('click', function() {
                setTimeout(logBatimentData, 100);
            });
        });
    }

    // Initialiser
    console.clear();
    console.log('=== LOGGING TEMPS RÉEL ACTIVÉ ===');
    console.log('Les modifications du formulaire seront affichées ici.');

    // Attacher les écouteurs
    attachEventListeners();

    // Premier log
    setTimeout(logBatimentData, 500);

    // Surveiller les changements dynamiques (pour les éléments ajoutés après)
    const observer = new MutationObserver(function() {
        setTimeout(attachEventListeners, 100);
    });

    observer.observe(form, {
        childList: true,
        subtree: true
    });
}

// ========================================
// INITIALISATION AU CHARGEMENT
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const selectedType = "{{ $selectedType ?? '' }}";

    // Gestion des cartes
    const cards = document.querySelectorAll('.card-option');
    const forms = document.querySelectorAll('.form-container');
    const defaultMessage = document.getElementById('default-message');

    cards.forEach(card => {
        card.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            cards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            forms.forEach(form => form.style.display = 'none');
            if (defaultMessage) defaultMessage.style.display = 'none';

            const targetForm = document.getElementById(targetId);
            if (targetForm) {
                targetForm.style.display = 'block';

                // Si c'est le formulaire bâtiment, initialiser
                if (targetId === 'batiment-form') {
                    setTimeout(() => {
                        handleArchitectureChange();
                        generateEtages();
                        generateAiles();
                        setTimeout(() => {
                            showTotalChambres();
                        }, 200);


                        // Initialiser la configuration des niveaux
                        const configNiveaux = document.getElementById('config_niveaux');
                        const memeHauteurSection = document.getElementById('meme-hauteur-section');
                        const hauteurVariableSection = document.getElementById('hauteur-variable-section');
                        const previewSection = document.getElementById('preview-section');

                        if (configNiveaux && memeHauteurSection && hauteurVariableSection) {
                            const config = configNiveaux.value;

                            // Afficher/masquer les sections selon la configuration
                            memeHauteurSection.style.display = config === 'meme-hauteur' ? 'block' : 'none';
                            hauteurVariableSection.style.display = config === 'hauteur-variable' ? 'block' : 'none';

                            // Masquer l'aperçu pour hauteur variable
                            if (previewSection) {
                                previewSection.style.display = config === 'meme-hauteur' ? 'block' : 'none';
                            }
                        }
                    }, 100);
                }
            }
        });
    });

    // Initialiser le formulaire de bâtiment si c'est le type sélectionné
    if (selectedType === 'batiment') {
        handleArchitectureChange();
        generateEtages();
        generateAiles();
        handleGlobalNiveauxChange();
        initJSONDataButton();

        // Activer le logging en temps réel
        setTimeout(function() {
            // Initialiser uniquement les fonctions nécessaires
            handleArchitectureChange();
            generateEtages();
            generateAiles();
            handleGlobalNiveauxChange();
            initJSONDataButton();
        }, 100);
    }
});

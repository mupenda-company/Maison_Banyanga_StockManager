<?php 
$pageTitle = 'Paramètres';
$zoneModel = new Zone();
$zones = $zoneModel->getActive();
ob_start();
?>

<div class="max-w-4xl mx-auto" x-data="settingsComponent()">
    <div class="card mb-6">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Informations de l'entreprise</h2>
        </div>
        <div class="card-body">
            <form @submit.prevent="saveSettings()">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Logo avec preview -->
                    <div class="md:col-span-2">
                        <label class="label">Logo</label>
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-20 rounded-lg border-2 border-gray-200 dark:border-gray-600 overflow-hidden bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
                                <img x-show="logoPreview" :src="logoPreview" class="w-12 h-12 object-contain">
                                <svg x-show="!logoPreview" class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01"/>
                                </svg>
                            </div>
                            <div>
                                <label class="btn btn-secondary cursor-pointer inline-flex items-center text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span x-text="logoPreview ? 'Changer' : 'Ajouter'"></span>
                                    <input type="file" accept="image/*" class="hidden" @change="handleLogoChange($event)">
                                </label>
                                <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF (max 2MB)</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="label">Nom de l'entreprise</label>
                        <input type="text" x-model="params.nom_entreprise" class="input" placeholder="Nom de l'entreprise">
                    </div>
                    <div>
                        <label class="label">Email de contact</label>
                        <input type="email" x-model="params.email_contact" class="input" placeholder="email@exemple.com">
                    </div>
                    <div>
                        <label class="label">Téléphone</label>
                        <input type="tel" x-model="params.telephone" class="input" placeholder="+243...">
                    </div>
                    <div class="md:col-span-2">
                        <label class="label">Adresse</label>
                        <textarea x-model="params.adresse" class="input" rows="2" placeholder="Adresse complète"></textarea>
                    </div>
                    <div>
                        <label class="label">Devise principale</label>
                        <select x-model="params.devise" class="input" @change="onDeviseChange()">
                            <option value="CDF">CDF - Franc congolais</option>
                            <option value="USD">USD - Dollar américain</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Taux de change (1 USD = ? CDF)</label>
                        <div class="flex items-center space-x-2">
                            <input type="number" x-model.number="params.taux_change" class="input flex-1" min="0" step="1">
                            <button type="button" @click="fetchTauxChange()" class="btn btn-secondary" :disabled="loadingTaux" title="Actualiser le taux du jour">
                                <svg class="w-4 h-4" :class="{ 'animate-spin': loadingTaux }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Cliquez sur le bouton pour actualiser le taux du jour</p>
                    </div>
                    <div>
                        <label class="label">Taux TVA (%)</label>
                        <input type="number" x-model.number="params.taux_tva" class="input" min="0" max="100" step="0.1">
                    </div>
                    <div>
                        <label class="label">Couleur primaire</label>
                        <div class="flex items-center space-x-3">
                            <input type="color" x-model="params.couleur_primaire" class="w-12 h-10 rounded cursor-pointer">
                            <input type="text" x-model="params.couleur_primaire" class="input flex-1" placeholder="#3B82F6">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <span x-text="loading ? 'Enregistrement...' : 'Enregistrer'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Zones -->
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Zones géographiques</h2>
            <a href="<?= url('zones') ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Gérer les zones
            </a>
        </div>
        <div class="card-body">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Configurez les zones géographiques pour organiser vos clients et analyser vos performances par région.
            </p>
        </div>
    </div>
</div>

<script>
function settingsComponent() {
    return {
        params: {
            nom_entreprise: '<?= htmlspecialchars($params['nom_entreprise'] ?? 'Bralima') ?>',
            adresse: '<?= htmlspecialchars($params['adresse'] ?? '') ?>',
            telephone: '<?= htmlspecialchars($params['telephone'] ?? '') ?>',
            email_contact: '<?= htmlspecialchars($params['email_contact'] ?? '') ?>',
            devise: '<?= htmlspecialchars($params['devise'] ?? 'CDF') ?>',
            taux_change: <?= floatval($params['taux_change'] ?? 2800) ?>,
            taux_tva: <?= floatval($params['taux_tva'] ?? 16) ?>,
            couleur_primaire: '<?= htmlspecialchars($params['couleur_primaire'] ?? '#3B82F6') ?>'
        },
        logoPreview: '<?= $params['logo'] ? asset('uploads/' . $params['logo']) : '' ?>',
        logoFile: null,
        loading: false,
        loadingTaux: false,

        init() {
            if (window.COULEUR_PRIMAIRE) {
                App.applyPrimaryColor(window.COULEUR_PRIMAIRE);
            }
            this.$watch('params.couleur_primaire', (val) => {
                if (val) App.applyPrimaryColor(val);
            });
        },
        
        handleLogoChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.logoFile = file;
            const reader = new FileReader();
            reader.onload = (e) => this.logoPreview = e.target.result;
            reader.readAsDataURL(file);
        },
        
        async fetchTauxChange() {
            this.loadingTaux = true;
            try {
                const result = await App.api('/api/admin/taux-change', 'GET');
                if (result?.taux) this.params.taux_change = result.taux;
            } catch (e) {
                App.notify('Erreur de récupération du taux', 'warning');
            } finally {
                this.loadingTaux = false;
            }
        },
        
        async uploadLogo() {
            if (!this.logoFile) return true;
            
            const formData = new FormData();
            formData.append('logo', this.logoFile);
            
            try {
                const response = await fetch((window.BASE_URL || '') + '/api/admin/logo', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    return result.data?.url;
                }
                throw new Error(result.message || 'Erreur lors de l\'upload du logo');
            } catch (e) {
                App.notify(e.message, 'error');
                return false;
            }
        },
        
        async saveSettings() {
            this.loading = true;
            try {
                // Upload du logo d'abord si un nouveau fichier a été choisi
                if (this.logoFile) {
                    const logoUrl = await this.uploadLogo();
                    if (logoUrl === false) {
                        this.loading = false;
                        return;
                    }
                }

                await App.api('/api/admin/settings', 'POST', this.params);
                window.DEVISE = this.params.devise;
                window.TAUX_CHANGE = this.params.taux_change;
                window.COULEUR_PRIMAIRE = this.params.couleur_primaire;
                App.notify('Paramètres enregistrés', 'success');
                setTimeout(() => location.reload(), 1000);
            } catch (e) {
                App.notify(e.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>

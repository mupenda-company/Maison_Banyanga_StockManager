<?php 
$pageTitle = 'Nouvelle mission de ristourne';
ob_start();
?>

<div class="max-w-5xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Créer une mission de ristourne</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Mission admin-only pour livrer une ristourne sous forme de produits, sans impacter les missions de vente.
            </p>
        </div>
        <div class="card-body">
            <form
                x-data="{
                    numeroMission: '<?= htmlspecialchars($numero_mission, ENT_QUOTES, 'UTF-8') ?>',
                    vehicule_id: '',
                    client_id: '',
                    ristourne_id: '',
                    produit_id: '',
                    zone_id: '',
                    date_depart: '<?= date('Y-m-d\TH:i') ?>',
                    notes: '',
                    loading: false,
                    vehicules: <?= htmlspecialchars(json_encode($vehicules ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    clients: <?= htmlspecialchars(json_encode($clients ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    ristournes: <?= htmlspecialchars(json_encode($ristournes ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    produits: <?= htmlspecialchars(json_encode($produits ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    zones: <?= htmlspecialchars(json_encode($zones ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    getClient() {
                        return this.clients.find(item => String(item.id) === String(this.client_id)) || null;
                    },
                    getRistournesClient() {
                        return this.ristournes.filter(item => String(item.client_id) === String(this.client_id));
                    },
                    getRistourne() {
                        return this.ristournes.find(item => String(item.id) === String(this.ristourne_id)) || null;
                    },
                    getProduit() {
                        return this.produits.find(item => String(item.id) === String(this.produit_id)) || null;
                    },
                    getPrixCaisse() {
                        const produit = this.getProduit();
                        if (!produit) return 0;
                        const bouteilles = parseInt(produit.bouteilles_par_caisses || 24, 10) || 24;
                        const prixCaisse = parseFloat(produit.prix_vente_caisses || 0);
                        if (prixCaisse > 0) return prixCaisse;
                        return (parseFloat(produit.prix_vente_unitaire || 0) || 0) * bouteilles;
                    },
                    getMontantRistourne() {
                        const ristourne = this.getRistourne();
                        return ristourne ? parseFloat(ristourne.montant_ristourne || 0) : 0;
                    },
                    getCaissesLivrables() {
                        const prixCaisse = this.getPrixCaisse();
                        const montant = this.getMontantRistourne();
                        if (prixCaisse <= 0) return 0;
                        return Math.floor(montant / prixCaisse);
                    },
                    getMontantLivre() {
                        return this.getCaissesLivrables() * this.getPrixCaisse();
                    },
                    getMontantRestantAdmin() {
                        return Math.max(this.getMontantRistourne() - this.getMontantLivre(), 0);
                    },
                    getBouteillesParCaisse() {
                        const produit = this.getProduit();
                        return produit ? (parseInt(produit.bouteilles_par_caisses || 24, 10) || 24) : 24;
                    },
                    getQuantiteBouteilles() {
                        return this.getCaissesLivrables() * this.getBouteillesParCaisse();
                    },
                    async submit() {
                        if (!vehicule_id || !client_id || !ristourne_id || !produit_id) {
                            App.notify('Veuillez sélectionner le véhicule, le client, la ristourne et le produit.', 'error');
                            return;
                        }

                        loading = true;
                        try {
                            await App.api('/api/missions/ristourne', 'POST', {
                                vehicule_id: parseInt(vehicule_id),
                                client_id: parseInt(client_id),
                                ristourne_id: parseInt(ristourne_id),
                                produit_id: parseInt(produit_id),
                                zone_id: zone_id ? parseInt(zone_id) : null,
                                date_depart: date_depart,
                                notes: notes
                            });

                            App.notify('Mission de ristourne créée avec succès');
                            window.location.href = (window.BASE_URL || '') + '/missions';
                        } catch (e) {
                            App.notify(e.message, 'error');
                        } finally {
                            loading = false;
                        }
                    }
                }"
            >
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="label">N° Mission</label>
                        <input type="text" class="input bg-gray-50" x-model="numeroMission" readonly>
                    </div>
                    <div>
                        <label class="label">Date de départ *</label>
                        <input type="datetime-local" class="input" x-model="date_depart" required>
                    </div>
                    <div>
                        <label class="label">Zone</label>
                        <select class="input" x-model="zone_id">
                            <option value="">Sélectionner</option>
                            <?php foreach ($zones as $zone): ?>
                            <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="label">Client *</label>
                        <select class="input" x-model="client_id" @change="ristourne_id = ''">
                            <option value="">Sélectionner un client</option>
                            <template x-for="client in clients" :key="client.id">
                                <option :value="client.id" x-text="client.nom + (client.numero_client ? ' (' + client.numero_client + ')' : '')"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Ristourne disponible *</label>
                        <select class="input" x-model="ristourne_id" :disabled="!client_id">
                            <option value="">Sélectionner une ristourne</option>
                            <template x-for="ristourne in getRistournesClient()" :key="ristourne.id">
                                <option :value="ristourne.id" x-text="ristourne.client_nom + ' - ' + App.formatMoneyConverted(parseFloat(ristourne.montant_ristourne || 0), window.BASE_DEVISE, window.DEVISE)"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="label">Véhicule *</label>
                        <select class="input" x-model="vehicule_id" required>
                            <option value="">Sélectionner un véhicule disponible</option>
                            <template x-for="vehicule in vehicules" :key="vehicule.id">
                                <option :value="vehicule.id" x-text="vehicule.immatriculation + ' - ' + (vehicule.agent_nom || 'Sans agent')"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Produit à livrer *</label>
                        <select class="input" x-model="produit_id" required>
                            <option value="">Sélectionner un produit</option>
                            <template x-for="produit in produits" :key="produit.id">
                                <option :value="produit.id" x-text="produit.nom + ' (' + produit.code + ')' "></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="p-4 rounded-lg border bg-blue-50 border-blue-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Montant ristourne</p>
                        <p class="text-xl font-bold text-blue-700" x-text="App.formatMoneyConverted(getMontantRistourne(), window.BASE_DEVISE, window.DEVISE)"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-green-50 border-green-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Caisses à livrer</p>
                        <p class="text-xl font-bold text-green-700" x-text="getCaissesLivrables() + ' cs'"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-purple-50 border-purple-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Montant livré</p>
                        <p class="text-xl font-bold text-purple-700" x-text="App.formatMoneyConverted(getMontantLivre(), window.BASE_DEVISE, window.DEVISE)"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-amber-50 border-amber-100">
                        <p class="text-xs uppercase tracking-wider text-gray-500">Reste administration</p>
                        <p class="text-xl font-bold text-amber-700" x-text="App.formatMoneyConverted(getMontantRestantAdmin(), window.BASE_DEVISE, window.DEVISE)"></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="p-4 rounded-lg border bg-gray-50 dark:bg-gray-900/50">
                        <p class="text-sm text-gray-500">Produit sélectionné</p>
                        <p class="font-semibold text-gray-900 dark:text-white" x-text="getProduit() ? getProduit().nom : '—'"></p>
                        <p class="text-sm text-gray-500 mt-1" x-text="getProduit() ? ('Prix caisse: ' + App.formatMoneyConverted(getPrixCaisse(), window.BASE_DEVISE, window.DEVISE)) : ''"></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-gray-50 dark:bg-gray-900/50">
                        <p class="text-sm text-gray-500">Quantité totale à charger</p>
                        <p class="font-semibold text-gray-900 dark:text-white" x-text="getQuantiteBouteilles() + ' bouteilles (' + getBouteillesParCaisse() + ' btl/caisse)'"></p>
                        <p class="text-sm text-gray-500 mt-1">Le reste non couvert par le produit reste côté administration.</p>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="label">Notes</label>
                    <textarea class="input" rows="3" x-model="notes" placeholder="Instructions particulières..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="<?= url('missions') ?>" class="btn btn-secondary">Annuler</a>
                    <button type="button" @click="submit()" class="btn btn-primary" :disabled="loading">
                        <span x-show="!loading">Créer la mission</span>
                        <span x-show="loading">Création...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>

<?php
$pageTitle = 'Sauvegarde';
$databaseInfo = $databaseInfo ?? [];
$downloadToken = $downloadToken ?? '';
$tailleOctets = max(0, (int) ($databaseInfo['taille_octets'] ?? 0));
$tailleLisible = $tailleOctets >= 1073741824
    ? number_format($tailleOctets / 1073741824, 2, ',', ' ') . ' Go'
    : ($tailleOctets >= 1048576
        ? number_format($tailleOctets / 1048576, 2, ',', ' ') . ' Mo'
        : number_format($tailleOctets / 1024, 2, ',', ' ') . ' Ko');
ob_start();
?>

<div class="max-w-5xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sauvegarde de la base de données</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Téléchargez une copie complète permettant de restaurer les données de l’application.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="card p-5">
            <p class="text-xs uppercase tracking-wider text-gray-500">Tables et vues</p>
            <p class="text-2xl font-bold text-primary-600 mt-1"><?= number_format((int) ($databaseInfo['nb_tables'] ?? 0), 0, ',', ' ') ?></p>
        </div>
        <div class="card p-5">
            <p class="text-xs uppercase tracking-wider text-gray-500">Lignes estimées</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1"><?= number_format((int) ($databaseInfo['nb_lignes_estime'] ?? 0), 0, ',', ' ') ?></p>
        </div>
        <div class="card p-5">
            <p class="text-xs uppercase tracking-wider text-gray-500">Taille estimée</p>
            <p class="text-2xl font-bold text-amber-600 mt-1"><?= htmlspecialchars($tailleLisible) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Créer une sauvegarde SQL</h2>
        </div>
        <div class="card-body space-y-5">
            <div class="p-4 rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800">
                <p class="font-semibold text-blue-900 dark:text-blue-200">Contenu de la sauvegarde</p>
                <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">
                    Le fichier contient la structure des tables, les données, les comptes utilisateurs,
                    les rôles, les permissions et l’historique enregistré dans la base.
                </p>
            </div>

            <div class="p-4 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800">
                <p class="font-semibold text-amber-900 dark:text-amber-200">Fichier confidentiel</p>
                <p class="text-sm text-amber-800 dark:text-amber-300 mt-1">
                    Conservez ce fichier dans un emplacement sécurisé. Il contient des informations sensibles
                    et ne doit pas être partagé avec une personne non autorisée.
                </p>
            </div>

            <form method="post"
                  action="<?= url('admin/backup/download') ?>"
                  onsubmit="return confirm('Créer et télécharger maintenant une sauvegarde complète ?');">
                <input type="hidden" name="backup_token" value="<?= htmlspecialchars($downloadToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Télécharger la sauvegarde
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>

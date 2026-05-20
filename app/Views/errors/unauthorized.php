<?php
$pageTitle = 'Accès refusé';
ob_start();
?>

<div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <svg class="w-20 h-20 text-red-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
    </svg>
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">Accès refusé</h1>
    <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md">
        Vous n'avez pas la permission d'accéder à cette page. Contactez votre administrateur si vous pensez qu'il s'agit d'une erreur.
    </p>
    <a href="<?= url(getDefaultRoute()) ?>" class="btn btn-primary">
        Retour à l'accueil
    </a>
</div>

<?php
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';

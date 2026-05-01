<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center p-4">
    <div class="text-center">
        <h1 class="text-9xl font-bold text-gray-300 dark:text-gray-700">404</h1>
        <p class="text-2xl font-semibold text-gray-700 dark:text-gray-300 mt-4">Page non trouvée</p>
        <p class="text-gray-500 dark:text-gray-400 mt-2">La page que vous recherchez n'existe pas ou a été déplacée.</p>
        <a href="<?= url('/') ?>" class="mt-6 inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Retour au tableau de bord
        </a>
    </div>
</body>
</html>

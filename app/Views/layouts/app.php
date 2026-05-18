<!DOCTYPE html>
<html lang="fr" :class="{ 'dark': $store.theme.dark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('favicon.ico') ?>">
    
    <!-- Tailwind CSS -->
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">

    <style>
        :root {
            --color-primary-50: 239 246 255;
            --color-primary-100: 219 234 254;
            --color-primary-200: 191 219 254;
            --color-primary-300: 147 197 253;
            --color-primary-400: 96 165 250;
            --color-primary-500: 59 130 246;
            --color-primary-600: 37 99 235;
            --color-primary-700: 29 78 216;
            --color-primary-800: 30 64 175;
            --color-primary-900: 30 58 138;
        }
    </style>
    
    <!-- Base URL pour les requêtes API -->
    <script>
        window.BASE_URL = '<?= APP_URL ?>';
        <?php 
        $parametreModel = new Parametre();
        $devise = $parametreModel->get('devise', 'CDF');
        $baseDevise = $parametreModel->get('devise_base', 'CDF');
        $tauxChange = $parametreModel->get('taux_change', '2800');
        $logo = $parametreModel->get('logo');
        $nomEntreprise = $parametreModel->get('nom_entreprise', APP_NAME);
        $couleurPrimaire = $parametreModel->get('couleur_primaire', '#3B82F6');
        ?>
        window.BASE_DEVISE = '<?= $baseDevise ?>';
        window.DEVISE = '<?= $devise ?>';
        window.TAUX_CHANGE = <?= floatval($tauxChange) ?>;
        window.LOGO_URL = '<?= $logo ? asset('uploads/' . $logo) : '' ?>';
        window.NOM_ENTREPRISE = '<?= htmlspecialchars($nomEntreprise) ?>';
        window.COULEUR_PRIMAIRE = '<?= htmlspecialchars($couleurPrimaire) ?>';
    </script>
    
    <!-- App JS -->
    <script src="<?= asset('js/app.js') ?>" defer></script>
    
    <script>
        document.addEventListener('alpine:init', () => {
            // Store pour le thème (initialisé ici pour éviter les erreurs "undefined")
            Alpine.store('theme', {
                dark: localStorage.getItem('theme') === 'dark' || 
                      (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
                toggle() {
                    this.dark = !this.dark;
                    localStorage.setItem('theme', this.dark ? 'dark' : 'light');
                    document.documentElement.classList.toggle('dark', this.dark);
                },
                init() {
                    document.documentElement.classList.toggle('dark', this.dark);
                }
            });
        });
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Heroicons -->
    <?php if (!isset($hideHeroicons)): ?>
    <style>
        .icon { display: inline-block; width: 1.25rem; height: 1.25rem; }
        .icon-sm { width: 1rem; height: 1rem; }
        .icon-lg { width: 1.5rem; height: 1.5rem; }
    </style>
    <?php endif; ?>
    
    <!-- Style personnalisé -->
    <?php if (isset($customStyle)): ?>
    <style><?= $customStyle ?></style>
    <?php endif; ?>
    
    <!-- Styles d'impression -->
    <style>
        @media print {
            /* Cacher les éléments de l'application */
            aside, header, .no-print, button, .btn, .sidebar, 
            .fixed, [x-data*="modal"], .notifications-container {
                display: none !important;
            }
            
            /* Afficher uniquement le contenu principal */
            body {
                background: white !important;
                font-size: 12pt;
            }
            
            main {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            
            .print-only {
                display: block !important;
            }
            
            /* Forcer les couleurs pour l'impression */
            .text-primary-600, .text-green-600, .text-red-600 {
                color: black !important;
            }
            
            /* En-tête d'impression */
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #333;
            }
            
            .print-header h1 {
                font-size: 18pt;
                margin: 0;
            }
            
            .print-header p {
                font-size: 10pt;
                color: #666;
                margin: 5px 0 0 0;
            }
            
            /* Tableaux d'impression */
            table {
                width: 100%;
                border-collapse: collapse;
            }
            
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            
            th {
                background: #f5f5f5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* Classe pour éléments visibles uniquement à l'impression */
        .print-only {
            display: none;
        }
    </style>
</head>
    <body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200" x-data="{ sidebarOpen: false, alertsOpen: false, userMenuOpen: false }">
        <!-- Notifications Container -->
        <div class="notifications-container fixed top-20 right-4 z-[9999] flex flex-col gap-2 pointer-events-none">
            <template x-for="item in ($store.notifications?.items || [])" :key="item.id">
                <div class="pointer-events-auto alert-animate flex items-start gap-3 rounded-md border px-4 py-3 shadow-lg"
                     :class="{
                        'bg-green-50 border-green-200 text-green-900': item.type === 'success',
                        'bg-red-50 border-red-200 text-red-900': item.type === 'error',
                        'bg-yellow-50 border-yellow-200 text-yellow-900': item.type === 'warning',
                        'bg-blue-50 border-blue-200 text-blue-900': item.type === 'info'
                     }">
                    <div class="flex-1 text-sm" x-text="item.message"></div>
                    <button type="button" class="opacity-70 hover:opacity-100" @click="$store.notifications.remove(item.id)" aria-label="Fermer">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <div x-cloak x-show="$store.confirm?.open" class="fixed inset-0 z-[9998] overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.confirm.cancel()"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6"
                     x-show="$store.confirm?.open"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95">
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
                             :class="{
                                'bg-red-100 dark:bg-red-900/30 text-red-600': ($store.confirm?.type || 'danger') === 'danger',
                                'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700': ($store.confirm?.type || 'danger') === 'warning',
                                'bg-blue-100 dark:bg-blue-900/30 text-blue-600': ($store.confirm?.type || 'danger') === 'info'
                             }">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2" x-text="$store.confirm?.title || 'Confirmation'"></h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6" x-text="$store.confirm?.message || ''"></p>
                        <div class="flex gap-3 justify-center">
                            <button type="button" @click="$store.confirm.cancel()" class="btn btn-secondary px-6" x-text="$store.confirm?.cancelText || 'Annuler'"></button>
                            <button type="button" @click="$store.confirm.confirm()" class="btn px-6"
                                    :class="{
                                        'btn-danger': ($store.confirm?.type || 'danger') === 'danger',
                                        'btn-warning': ($store.confirm?.type || 'danger') === 'warning',
                                        'btn-primary': ($store.confirm?.type || 'danger') === 'info'
                                    }"
                                    x-text="$store.confirm?.confirmText || 'Confirmer'"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside 
            class="fixed inset-y-0 left-0 z-30 w-64 bg-white dark:bg-gray-800 shadow-lg transform transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-auto flex flex-col h-full overflow-hidden"
            :class="{ '-translate-x-full': !sidebarOpen }"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200 dark:border-gray-700">
                <a href="<?= (isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_ADMIN) ? url('/') : url('ventes') ?>" class="flex items-center space-x-2">
                    <?php 
                    $params = new Parametre();
                    $logo = $params->get('logo');
                    ?>
                    <template x-if="window.LOGO_URL">
                        <img :src="window.LOGO_URL" alt="Logo" class="h-8 w-auto" id="app-logo">
                    </template>
                    <template x-if="!window.LOGO_URL">
                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </template>
                    <span class="text-xl font-bold text-gray-900 dark:text-white" x-text="window.NOM_ENTREPRISE || '<?= APP_NAME ?>'"></span>
                </a>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 min-h-0 px-4 py-4 space-y-1 overflow-y-auto overscroll-contain">
                <!-- Dashboard -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_ADMIN): ?>
                <a href="<?= url('/') ?>" class="sidebar-link <?= ($_SERVER['REQUEST_URI'] === '/' || strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false) ? 'active' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Tableau de bord
                </a>
                <?php endif; ?>
                
                <!-- Approvisionnements -->
                <a href="<?= url('approvisionnements') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/approvisionnements') !== false ? 'active' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Approvisionnements
                </a>

                <!-- Emballages -->
                <a href="<?= url('emballages') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/emballages') !== false ? 'active' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 00-1-1.732l-6-3.464a2 2 0 00-2 0l-6 3.464A2 2 0 004 7v6a2 2 0 001 1.732l6 3.464a2 2 0 002 0l6-3.464A2 2 0 0020 13z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l8-5M12 12L4 7m8 5v9"/>
                    </svg>
                    Emballages
                </a>
                
                <!-- Stocks -->
                <a href="<?= url('stocks') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/stocks') !== false ? 'active' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    Stocks
                </a>
                
                <!-- Ventes -->
                <a href="<?= url('ventes') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/ventes') !== false ? 'active' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    Ventes
                </a>
                
                <!-- Clients -->
                <a href="<?= url('clients') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/clients') !== false ? 'active' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Clients
                </a>
                
                <!-- Véhicules -->
                <a href="<?= url('vehicules') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/vehicules') !== false ? 'active' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Véhicules
                </a>
                
                <!-- Missions -->
                <a href="<?= url('missions') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/missions') !== false ? 'active' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m0 0a3 3 0 016 0v2m-6 0h6m-6 0H5a2 2 0 01-2-2V7a2 2 0 012-2h2m6 12h6m0 0a2 2 0 002-2v-5a2 2 0 00-2-2h-1m1 7v2m0-2h-1"/>
                    </svg>
                    Missions
                </a>
                
                <!-- Pertes -->
                <a href="<?= url('pertes') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/pertes') !== false ? 'active' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Pertes
                </a>
                
                <!-- Produits -->
                <a href="<?= url('produits') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/produits') !== false ? 'active' : '' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-1-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Produits
                </a>
                
                <!-- Admin (si admin) -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_ADMIN): ?>
                <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Administration</p>

                    <a href="<?= url('finance') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/finance') !== false ? 'active' : '' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0-1v6m0-6l9-3m-9 3v6m9-3l3 1m-3-1v-6m3 1v6m-9 6l3 1m0-1v-6m0 1l9-3m-9 3v6m9-3l3 1m-3-1v-6"/>
                        </svg>
                        Finance
                    </a>

                    <a href="<?= url('rapports') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/rapports') !== false ? 'active' : '' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3m2 3H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2z"/>
                        </svg>
                        Rapports
                    </a>

                    <a href="<?= url('admin/objectifs') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/objectifs') !== false ? 'active' : '' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V5m0 12v-1m8-5a8 8 0 11-16 0 8 8 0 0116 0z"/>
                        </svg>
                        Objectifs mensuels
                    </a>

                    <a href="<?= url('ristournes') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/ristournes') !== false ? 'active' : '' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Ristournes
                    </a>

                    <!-- <a href="<?= url('missions/ristourne/create') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/missions/ristourne') !== false ? 'active' : '' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Mission de ristourne
                    </a> -->
                    
                    <a href="<?= url('admin/users') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/users') !== false ? 'active' : '' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Utilisateurs
                    </a>
                    
                    <a href="<?= url('admin/settings') ?>" class="sidebar-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/settings') !== false ? 'active' : '' ?>">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Paramètres
                    </a>
                </div>
                <?php endif; ?>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow-sm z-20">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6">
                    <!-- Mobile menu button -->
                    <button 
                        @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    
                    <!-- Page Title -->
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white hidden sm:block">
                        <?= $pageTitle ?? 'Tableau de bord' ?>
                    </h1>
                    
                    <!-- Right side -->
                    <div class="flex items-center space-x-4">
                        <!-- Theme Toggle -->
                        <button 
                            @click="$store.theme.toggle()"
                            class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            <svg x-show="!$store.theme.dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <svg x-show="$store.theme.dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </button>
                        
                        <!-- Alerts -->
                        <div class="relative">
                            <button 
                                @click="alertsOpen = !alertsOpen"
                                class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 relative"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <?php 
                                $alerteModel = new Alerte();
                                $nbAlertes = $alerteModel->countNonLues();
                                ?>
                                <?php if ($nbAlertes > 0): ?>
                                <span class="absolute top-0 right-0 -mt-1 -mr-1 px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">
                                    <?= $nbAlertes ?>
                                </span>
                                <?php endif; ?>
                            </button>
                            
                            <!-- Alerts Dropdown -->
                            <div 
                                x-show="alertsOpen"
                                @click.away="alertsOpen = false"
                                x-transition
                                class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
                            >
                                <div class="p-4">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Alertes</h3>
                                    <div class="space-y-2 max-h-64 overflow-y-auto">
                                        <?php 
                                        $alertes = $alerteModel->getNonLues(5);
                                        if (empty($alertes)): 
                                        ?>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Aucune alerte</p>
                                        <?php else: ?>
                                            <?php foreach ($alertes as $alerte): ?>
                                            <div class="p-2 rounded bg-<?= $alerte['niveau'] === 'danger' ? 'red' : ($alerte['niveau'] === 'warning' ? 'yellow' : 'blue') ?>-50 dark:bg-<?= $alerte['niveau'] === 'danger' ? 'red' : ($alerte['niveau'] === 'warning' ? 'yellow' : 'blue') ?>-900/50">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($alerte['titre']) ?></p>
                                                <p class="text-xs text-gray-600 dark:text-gray-300"><?= htmlspecialchars($alerte['message']) ?></p>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Menu -->
                        <div class="relative">
                            <button 
                                @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center space-x-2 p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-medium">
                                        <?= strtoupper(substr($_SESSION['user_prenom'] ?? 'U', 0, 1) . substr($_SESSION['user_nom'] ?? '', 0, 1)) ?>
                                    </span>
                                </div>
                                <span class="hidden md:block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    <?= htmlspecialchars($_SESSION['user_prenom'] ?? 'Utilisateur') ?>
                                </span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <!-- User Dropdown -->
                            <div 
                                x-show="userMenuOpen"
                                @click.away="userMenuOpen = false"
                                x-transition
                                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
                            >
                                <div class="py-1">
                                    <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars(($_SESSION['user_nom'] ?? '') . ' ' . ($_SESSION['user_prenom'] ?? '')) ?></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($_SESSION['user_telephone'] ?? '') ?></p>
                                        <span class="badge badge-info mt-1"><?= ucfirst($_SESSION['user_role'] ?? 'user') ?></span>
                                    </div>
                                    <a href="<?= url('profile') ?>" class="dropdown-item">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        Mon profil
                                    </a>
                                    <a href="<?= url('logout') ?>" class="dropdown-item text-red-600 dark:text-red-400">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        Déconnexion
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <?= $content ?>
            </main>
        </div>
    </div>
    
    <!-- Mobile sidebar overlay -->
    <div 
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-20 bg-black/60 backdrop-blur-sm lg:hidden"
    ></div>

    <!-- Global Modal Overlay Background (for daughter views) -->
    <style>
        [x-cloak] { display: none !important; }
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            transition: all 0.3s ease-in-out;
        }
    </style>
</body>
</html>

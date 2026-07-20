<!DOCTYPE html>
<html lang="fr" x-data x-bind:class="{ 'dark': $store.theme.dark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php 
    $parametreModelHead = new Parametre();
    $logoHead = $parametreModelHead->get('logo');
    $nomEntrepriseHead = $parametreModelHead->get('nom_entreprise', APP_NAME);
    $pageTitle = "Connexion";
    ?>
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= htmlspecialchars($nomEntrepriseHead) ?></title>
    <!-- Favicon -->
    <?php if ($logoHead): ?>
    <link rel="icon" type="image/png" href="<?= asset('uploads/' . $logoHead) ?>">
    <link rel="apple-touch-icon" href="<?= asset('uploads/' . $logoHead) ?>">
    <?php else: ?>
    <link rel="icon" type="image/x-icon" href="<?= asset('favicon.ico') ?>">
    <?php endif; ?>
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
    <script>
        window.BASE_URL = '<?= defined("APP_URL") ? APP_URL : "" ?>';
        window.APP_URL  = window.BASE_URL;
        <?php 
        $parametreModel = new Parametre();
        $logo = $parametreModel->get('logo');
        $nomEntreprise = $parametreModel->get('nom_entreprise', APP_NAME);
        $couleurPrimaire = $parametreModel->get('couleur_primaire', '#3B82F6');
        ?>
        window.LOGO_URL = '<?= $logo ? asset('uploads/' . $logo) : '' ?>';
        window.NOM_ENTREPRISE = '<?= htmlspecialchars($nomEntreprise) ?>';
        window.COULEUR_PRIMAIRE = '<?= htmlspecialchars($couleurPrimaire) ?>';
    </script>
    <script>
        document.addEventListener('alpine:init', () => {
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
    <script src="<?= asset('js/app.js') ?>" defer></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <template x-if="window.LOGO_URL && window.LOGO_URL.length > 0">
                <img :src="window.LOGO_URL" alt="Logo" class="h-16 w-auto mx-auto mb-4" id="app-logo">
            </template>
            <template x-if="!window.LOGO_URL || window.LOGO_URL.length === 0">
                <svg class="h-16 w-auto mx-auto mb-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </template>
            <!-- <template x-if="window.LOGO_URL">
                <img :src="window.LOGO_URL" alt="Logo" class="h-16 w-auto mx-auto mb-4">
            </template>
            <template x-if="!window.LOGO_URL">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-full mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </template> -->
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="window.NOM_ENTREPRISE || '<?= APP_NAME ?>'"></h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">Gestion Logistique</p>
        </div>
        
        <!-- Login Form -->
        <div class="card">
            <div class="card-body">
                <form
                    x-data="{
                        username: '',
                        password: '',
                        loading: false,
                        error: ''
                    }"
                    @submit.prevent="async () => {
                        loading = true;
                        error = '';
                        try {
                            const result = await App.api('/api/auth/login', 'POST', {
                                username: username,
                                password: password
                            });
                            // Le résultat peut être result.redirect ou result.data.redirect
                            const redirectUrl = result.redirect || result.data?.redirect || '/dashboard';
                            window.location.href = redirectUrl;
                        } catch (e) {
                            error = e.message || 'Erreur de connexion';
                        } finally {
                            loading = false;
                        }
                    }"
                >
                    <!-- Error Message -->
                    <div x-show="error" class="alert-danger mb-4" x-text="error"></div>
                    
                    <!-- Username -->
                    <div class="mb-4">
                        <label for="username" class="label">Nom d'utilisateur</label>
                        <input 
                            type="text" 
                            id="username" 
                            x-model="username"
                            class="input"
                            placeholder="Entrez votre nom d'utilisateur"
                            required
                            autofocus
                        >
                    </div>
                    
                    <!-- Password -->
                    <div class="mb-6">
                        <label for="password" class="label">Mot de passe</label>
                        <input 
                            type="password" 
                            id="password" 
                            x-model="password"
                            class="input"
                            placeholder="Entrez votre mot de passe"
                            required
                        >
                    </div>
                    
                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="btn-primary w-full"
                        :disabled="loading"
                    >
                        <span x-cloak x-show="!loading">Se connecter</span>
                        <span x-cloak x-show="loading" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Connexion...
                        </span>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Theme Toggle -->
        <div class="text-center mt-4">
            <button 
                @click="$store.theme.toggle()"
                class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
            >
                <span x-show="!$store.theme.dark">Mode sombre</span>
                <span x-show="$store.theme.dark">Mode clair</span>
            </button>
        </div>

        <div class="text-center border-t mt-10 border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-center min-h-[56px] -mb-10">
                <img x-cloak x-show="!$store.theme.dark" src="<?= asset('uploads/Mupenda Company.png') ?>" alt="Mupenda Company" class="h-32 w-auto">
                <img x-cloak x-show="$store.theme.dark" src="<?= asset('uploads/White Mupenda Company.png') ?>" alt="Mupenda Company" class="h-32 w-auto">
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                &copy; <?= date('Y') ?> Mupenda Company.
                <a href="https://mupenda.cd/" target="_blank" rel="noopener noreferrer" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline underline-offset-2">mupenda.cd</a>
            </p>
        </div>
    </div>
</body>
</html>

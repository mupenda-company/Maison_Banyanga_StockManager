<?php
/**
 * Fonctions utilitaires pour l'application
 */

if (!function_exists('format_money')) {
    /**
     * Formater un montant dans la devise de l'application
     * 
     * @param float $montant Montant à formater
     * @param string $devise Devise (CDF ou USD), si null utilise la devise par défaut
     * @return string
     */
    function format_money($montant, $devise = null)
    {
        static $parametreModel = null;
        static $defaultDevise = 'CDF';
        
        if ($parametreModel === null) {
            $parametreModel = new Parametre();
            $defaultDevise = $parametreModel->get('devise', 'CDF');
        }
        
        $devise = $devise ?? $defaultDevise;
        
        if ($devise === 'USD') {
            return number_format($montant, 2, '.', ',') . ' USD';
        }
        
        return number_format($montant, 0, ',', ' ') . ' CDF';
    }
}

if (!function_exists('convert_money')) {
    /**
     * Convertir un montant entre USD et CDF selon le taux de change
     * 
     * @param float $montant Montant à convertir
     * @param string $from Devise source (USD ou CDF)
     * @param string $to Devise cible (USD ou CDF)
     * @return float
     */
    function convert_money($montant, $from = null, $to = null)
    {
        static $tauxChange = null;

        $from = $from ?? get_base_devise();
        $to = $to ?? get_devise();
        
        if ($tauxChange === null) {
            $parametreModel = new Parametre();
            $tauxChange = floatval($parametreModel->get('taux_change', '2800'));
        }
        
        if ($from === $to) {
            return $montant;
        }
        
        if ($from === 'USD' && $to === 'CDF') {
            return $montant * $tauxChange;
        }
        
        if ($from === 'CDF' && $to === 'USD') {
            return $montant / $tauxChange;
        }
        
        return $montant;
    }
}

if (!function_exists('get_base_devise')) {
    /**
     * Récupérer la devise de stockage (base) des montants en base de données.
     *
     * @return string
     */
    function get_base_devise()
    {
        static $baseDevise = null;

        if ($baseDevise === null) {
            $parametreModel = new Parametre();
            $baseDevise = $parametreModel->get('devise_base', 'CDF');
        }

        return $baseDevise;
    }
}

if (!function_exists('money_in_app_devise')) {
    /**
     * Convertir un montant vers la devise principale de l'application.
     * Par défaut, on considère que les montants stockés en base sont en CDF.
     */
    function money_in_app_devise($montant, $from = null)
    {
        $from = $from ?? get_base_devise();
        $to = get_devise();
        return convert_money($montant, $from, $to);
    }
}

if (!function_exists('format_money_converted')) {
    /**
     * Convertir un montant vers la devise principale puis le formater.
     */
    function format_money_converted($montant, $from = null)
    {
        $from = $from ?? get_base_devise();
        $to = get_devise();
        $converted = convert_money($montant, $from, $to);
        return format_money($converted, $to);
    }
}

if (!function_exists('format_money_dual')) {
    /**
     * Afficher un montant de base avec son equivalent CDF et USD.
     */
    function format_money_dual($montant, $from = null)
    {
        $from = $from ?? get_base_devise();
        $cdf = convert_money((float) $montant, $from, 'CDF');
        $usd = convert_money((float) $montant, $from, 'USD');

        return format_money($cdf, 'CDF') . ' / ' . format_money($usd, 'USD');
    }
}

if (!function_exists('get_taux_change')) {
    /**
     * Récupérer le taux de change actuel
     * 
     * @return float
     */
    function get_taux_change()
    {
        static $taux = null;
        
        if ($taux === null) {
            $parametreModel = new Parametre();
            $taux = floatval($parametreModel->get('taux_change', '2800'));
        }
        
        return $taux;
    }
}

if (!function_exists('get_devise')) {
    /**
     * Récupérer la devise principale de l'application
     * 
     * @return string
     */
    function get_devise()
    {
        static $devise = null;
        
        if ($devise === null) {
            $parametreModel = new Parametre();
            $devise = $parametreModel->get('devise', 'CDF');
        }
        
        return $devise;
    }
}

if (!function_exists('format_date')) {
    /**
     * Formater une date en français
     * 
     * @param string $date Date à formater
     * @param string $format Format de sortie
     * @return string
     */
    function format_date($date, $format = 'd/m/Y')
    {
        return date($format, strtotime($date));
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Formater une date et heure
     * 
     * @param string $datetime Date et heure à formater
     * @return string
     */
    function format_datetime($datetime)
    {
        return date('d/m/Y H:i', strtotime($datetime));
    }
}

if (!function_exists('truncate_text')) {
    /**
     * Tronquer un texte
     * 
     * @param string $text Texte à tronquer
     * @param int $length Longueur maximale
     * @return string
     */
    function truncate_text($text, $length = 100)
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
}

if (!function_exists('pagination_pages')) {
    /**
     * Construire une liste compacte de pages: 1 2 ... 8 9 10 ... 20.
     */
    function pagination_pages($currentPage, $lastPage, $window = 2)
    {
        $currentPage = max(1, (int) $currentPage);
        $lastPage = max(1, (int) $lastPage);
        $window = max(1, (int) $window);

        $pages = [];
        for ($i = 1; $i <= min(2, $lastPage); $i++) {
            $pages[$i] = $i;
        }
        for ($i = max(1, $currentPage - $window); $i <= min($lastPage, $currentPage + $window); $i++) {
            $pages[$i] = $i;
        }
        for ($i = max(1, $lastPage - 1); $i <= $lastPage; $i++) {
            $pages[$i] = $i;
        }

        ksort($pages);

        $result = [];
        $previous = 0;
        foreach (array_values($pages) as $page) {
            if ($previous > 0 && $page > $previous + 1) {
                $result[] = '...';
            }
            $result[] = $page;
            $previous = $page;
        }

        return $result;
    }
}

if (!function_exists('pagination_url')) {
    /**
     * Generer une URL de pagination en conservant les filtres actifs.
     */
    function pagination_url($page, $query = null)
    {
        $query = is_array($query) ? $query : $_GET;
        unset($query['print'], $query['export']);
        $query['page'] = max(1, (int) $page);

        return '?' . http_build_query($query);
    }
}

if (!function_exists('render_pagination')) {
    /**
     * Afficher une pagination numerique reutilisable.
     */
    function render_pagination($currentPage, $lastPage, $query = null, $options = [])
    {
        $currentPage = max(1, (int) $currentPage);
        $lastPage = max(1, (int) $lastPage);

        if ($lastPage <= 1) {
            return '';
        }

        $previousLabel = $options['previous_label'] ?? 'Precedent';
        $nextLabel = $options['next_label'] ?? 'Suivant';
        $buttonClass = $options['button_class'] ?? 'btn-secondary btn-sm';
        $activeClass = $options['active_class'] ?? 'btn-primary btn-sm font-bold';
        $disabledClass = $options['disabled_class'] ?? 'btn-secondary btn-sm opacity-50 cursor-not-allowed';

        ob_start();
        ?>
        <div class="flex flex-wrap items-center justify-end gap-1">
            <?php if ($currentPage > 1): ?>
            <a href="<?= htmlspecialchars(pagination_url($currentPage - 1, $query), ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($previousLabel) ?></a>
            <?php else: ?>
            <span class="<?= htmlspecialchars($disabledClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($previousLabel) ?></span>
            <?php endif; ?>

            <?php foreach (pagination_pages($currentPage, $lastPage) as $page): ?>
                <?php if ($page === '...'): ?>
                <span class="px-2 py-1 text-sm text-gray-400">...</span>
                <?php else: ?>
                <a href="<?= htmlspecialchars(pagination_url($page, $query), ENT_QUOTES, 'UTF-8') ?>"
                   class="<?= $page == $currentPage ? htmlspecialchars($activeClass, ENT_QUOTES, 'UTF-8') : htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8') ?>">
                    <?= (int) $page ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($currentPage < $lastPage): ?>
            <a href="<?= htmlspecialchars(pagination_url($currentPage + 1, $query), ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($nextLabel) ?></a>
            <?php else: ?>
            <span class="<?= htmlspecialchars($disabledClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($nextLabel) ?></span>
            <?php endif; ?>
        </div>
        <?php
        return trim(ob_get_clean());
    }
}

if (!function_exists('pagination_per_page')) {
    /**
     * Lire le nombre de lignes par page autorise.
     */
    function pagination_per_page($default = 5)
    {
        $allowed = [5, 10, 20, 50, 100];
        $perPage = (int) ($_GET['per_page'] ?? $default);

        return in_array($perPage, $allowed, true) ? $perPage : (int) $default;
    }
}

if (!function_exists('paginate_array')) {
    /**
     * Paginer une liste deja chargee en memoire.
     */
    function paginate_array(array $items, $page = null, $perPage = null)
    {
        $page = max(1, (int) ($page ?? ($_GET['page'] ?? 1)));
        $perPage = max(1, (int) ($perPage ?? pagination_per_page()));
        $total = count($items);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        return [
            'data' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }
}

if (!function_exists('render_per_page_selector')) {
    /**
     * Afficher le selecteur 5-100 lignes par page en conservant les filtres.
     */
    function render_per_page_selector($current = null, $query = null)
    {
        $current = (int) ($current ?? pagination_per_page());
        $query = is_array($query) ? $query : $_GET;
        unset($query['page'], $query['per_page'], $query['print'], $query['export']);
        $action = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '';

        ob_start();
        ?>
        <form method="GET" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <?php foreach ($query as $key => $value): ?>
                <?php if (is_array($value)) continue; ?>
                <input type="hidden" name="<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
            <label for="per_page_selector" class="whitespace-nowrap">Lignes par page</label>
            <select id="per_page_selector" name="per_page" class="input py-1.5 w-24" onchange="this.form.submit()">
                <?php foreach ([5, 10, 20, 50, 100] as $value): ?>
                <option value="<?= $value ?>" <?= $current === $value ? 'selected' : '' ?>><?= $value ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php
        return trim(ob_get_clean());
    }
}

if (!function_exists('render_pagination_footer')) {
    /**
     * Footer standard: resume, selecteur lignes par page et pagination.
     */
    function render_pagination_footer(array $pagination, $label = 'résultat(s)', $query = null, $options = [])
    {
        $total = (int) ($pagination['total'] ?? 0);
        $perPage = (int) ($pagination['per_page'] ?? pagination_per_page());
        $currentPage = (int) ($pagination['current_page'] ?? 1);
        $lastPage = (int) ($pagination['last_page'] ?? 1);
        $start = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $end = $total > 0 ? min($currentPage * $perPage, $total) : 0;

        $paginationOptions = array_merge([
            'previous_label' => 'Précédent',
            'button_class' => 'btn-secondary btn-sm',
            'active_class' => 'btn-primary btn-sm font-bold',
            'disabled_class' => 'btn-secondary btn-sm opacity-50 cursor-not-allowed',
        ], $options);

        ob_start();
        ?>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Affichage <?= $start ?> à <?= $end ?> sur <?= $total ?> <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-end">
                <?= render_per_page_selector($perPage, $query) ?>
                <?= render_pagination($currentPage, $lastPage, $query, $paginationOptions) ?>
            </div>
        </div>
        <?php
        return trim(ob_get_clean());
    }
}

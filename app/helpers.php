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

<?php
/**
 * Point d'entrée de l'application
 */

require_once __DIR__ . '/../config/config.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if (defined('BASE_PATH') && BASE_PATH !== '') {
    $requestPath = preg_replace('#^' . preg_quote(BASE_PATH) . '#', '', $requestPath);
}
$requestPath = str_replace('/index.php', '', $requestPath);
$requestPath = rtrim($requestPath, '/') ?: '/';

if (strpos($requestPath, '/api/mobile/') === 0) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Parser l'URL en tenant compte du chemin de base
$uri = $requestPath;
// Supprimer le chemin de base de l'URI

// Routes de l'application
$routes = [
    // Auth
    'GET::/login' => ['AuthController', 'login'],
    'POST::/api/auth/login' => ['AuthController', 'authenticate'],
    'GET::/logout' => ['AuthController', 'logout'],
    'GET::/profile' => ['AuthController', 'profile'],
    'POST::/api/auth/profile' => ['AuthController', 'updateProfile'],
    'POST::/api/auth/password' => ['AuthController', 'updatePassword'],
    
    // Dashboard
    'GET::/' => ['DashboardController', 'index'],
    'GET::/dashboard' => ['DashboardController', 'index'],
    'GET::/api/dashboard/stats' => ['DashboardController', 'apiStats'],
    'GET::/api/dashboard/alertes' => ['DashboardController', 'apiAlertes'],
    'POST::/api/dashboard/alertes/read' => ['DashboardController', 'markAlertsRead'],
    
    // Produits
    'GET::/produits' => ['ProduitController', 'index'],
    'GET::/api/produits' => ['ProduitController', 'apiList'],
    'GET::/api/produits/next-code' => ['ProduitController', 'nextCode'],
    'GET::/produits/(\d+)' => ['ProduitController', 'show'],
    'POST::/api/produits' => ['ProduitController', 'store'],
    'PUT::/api/produits/(\d+)' => ['ProduitController', 'update'],
    'DELETE::/api/produits/(\d+)' => ['ProduitController', 'delete'],
    
    // Stocks
    'GET::/stocks' => ['StockController', 'index'],
    'GET::/stocks/inventaire' => ['StockController', 'inventaire'],
    'GET::/stocks/inventaire-initial' => ['StockController', 'inventaireInitial'],
    'GET::/stocks/mouvements' => ['StockController', 'mouvements'],
    'GET::/api/stocks' => ['StockController', 'apiGlobal'],
    'GET::/api/stocks/emplacement/(\d+)' => ['StockController', 'apiEmplacement'],
    'POST::/api/stocks/transfert' => ['StockController', 'transfert'],
    'POST::/api/stocks/ajustement' => ['StockController', 'ajustement'],
    'POST::/api/stocks/inventaire-initial' => ['StockController', 'enregistrerInventaireInitial'],
    
    // Approvisionnements
    'GET::/approvisionnements' => ['ApprovisionnementController', 'index'],
    'GET::/approvisionnements/create' => ['ApprovisionnementController', 'create'],
    'GET::/approvisionnements/(\d+)' => ['ApprovisionnementController', 'show'],
    'POST::/api/approvisionnements' => ['ApprovisionnementController', 'store'],
    'POST::/api/approvisionnements/(\d+)/annuler' => ['ApprovisionnementController', 'annuler'],
    'GET::/approvisionnements/dettes' => ['ApprovisionnementController', 'dettes'],
    'POST::/api/dettes/(\d+)/rembourser' => ['ApprovisionnementController', 'rembourserDette'],
    
    // Retours emballages
    'GET::/retours-emballages' => ['RetourController', 'index'],
    'POST::/api/retours-emballages' => ['RetourController', 'store'],

    // Emballages
    'GET::/emballages' => ['EmballageController', 'index'],
    'GET::/emballages/suivi' => ['EmballageController', 'suivi'],
    
    // Ventes
    'GET::/ventes' => ['VenteController', 'index'],
    'GET::/ventes/create' => ['VenteController', 'create'],
    'GET::/ventes/(\d+)' => ['VenteController', 'show'],
    'POST::/api/ventes' => ['VenteController', 'store'],
    'POST::/api/ventes/(\d+)/annuler' => ['VenteController', 'annuler'],
    'GET::/ventes/(\d+)/print' => ['VenteController', 'print'],
    'GET::/ventes/stats' => ['VenteController', 'stats'],
    
    // Clients
    'GET::/clients' => ['ClientController', 'index'],
    'GET::/api/clients' => ['ClientController', 'apiList'],
    'GET::/clients/(\d+)' => ['ClientController', 'show'],
    'POST::/api/clients' => ['ClientController', 'store'],
    'PUT::/api/clients/(\d+)' => ['ClientController', 'update'],
    'DELETE::/api/clients/(\d+)' => ['ClientController', 'delete'],
    'GET::/clients/zones' => ['ClientController', 'byZone'],
    
    // Véhicules
    'GET::/vehicules' => ['VehiculeController', 'index'],
    'GET::/api/vehicules' => ['VehiculeController', 'apiList'],
    'GET::/vehicules/(\d+)' => ['VehiculeController', 'show'],
    'POST::/api/vehicules' => ['VehiculeController', 'store'],
    'PUT::/api/vehicules/(\d+)' => ['VehiculeController', 'update'],
    'DELETE::/api/vehicules/(\d+)' => ['VehiculeController', 'delete'],
    
    // Missions
    'GET::/missions' => ['MissionController', 'index'],
    'GET::/missions/en-cours' => ['MissionController', 'enCours'],
    'GET::/missions/create' => ['MissionController', 'create'],
    'GET::/missions/(\d+)' => ['MissionController', 'show'],
    'POST::/api/missions' => ['MissionController', 'store'],
    'POST::/api/missions/(\d+)/terminer' => ['MissionController', 'terminer'],
    'GET::/missions/(\d+)/print' => ['MissionController', 'print'],

    // API Mobile
    'POST::/api/mobile/login' => ['MobileController', 'login'],
    'GET::/api/mobile/branding' => ['MobileController', 'branding'],
    'GET::/api/mobile/mission/(\d+)/stock' => ['MobileController', 'getStock'],
    'GET::/api/mobile/mission/(\d+)/stats' => ['MobileController', 'getMissionStats'],
    'POST::/api/mobile/vente' => ['MobileController', 'storeVente'],
    'GET::/api/mobile/vente/(\d+)/facture' => ['MobileController', 'getVenteFacture'],
    'GET::/api/mobile/clients' => ['ClientController', 'apiList'],
    'GET::/api/mobile/ventes' => ['MobileController', 'listVentes'],
    
    // Pertes
    'GET::/pertes' => ['PerteController', 'index'],
    'GET::/pertes/create' => ['PerteController', 'create'],
    'POST::/api/pertes' => ['PerteController', 'store'],
    'DELETE::/api/pertes/(\d+)' => ['PerteController', 'delete'],
    'GET::/pertes/stats' => ['PerteController', 'stats'],
    
    // Zones
    'GET::/zones' => ['ZoneController', 'index'],
    'GET::/zones/(\d+)' => ['ZoneController', 'show'],
    'GET::/api/zones' => ['ZoneController', 'apiList'],
    'POST::/api/zones' => ['ZoneController', 'store'],
    'PUT::/api/zones/(\d+)' => ['ZoneController', 'update'],
    'DELETE::/api/zones/(\d+)' => ['ZoneController', 'delete'],
    
    // Rapports
    'GET::/rapports' => ['ReportController', 'index'],
    
    // Ristournes
    'GET::/ristournes' => ['RistourneController', 'index'],
    'GET::/ristournes/calculer' => ['RistourneController', 'calculer'],
    'POST::/api/ristournes' => ['RistourneController', 'store'],
    'POST::/api/ristournes/(\d+)/payer' => ['RistourneController', 'payer'],
    'GET::/ristournes/paliers' => ['RistourneController', 'paliers'],
    'POST::/api/ristournes/paliers' => ['RistourneController', 'storePalier'],
    'DELETE::/api/ristournes/paliers/(\d+)' => ['RistourneController', 'deletePalier'],
    
    // Admin
    'GET::/admin' => ['AdminController', 'index'],
    'GET::/admin/users' => ['AdminController', 'users'],
    'POST::/api/admin/users' => ['AdminController', 'storeUser'],
    'PUT::/api/admin/users/(\d+)' => ['AdminController', 'updateUser'],
    'DELETE::/api/admin/users/(\d+)' => ['AdminController', 'deleteUser'],
    'POST::/api/admin/users/(\d+)/reset-password' => ['AdminController', 'resetPassword'],
    'GET::/admin/settings' => ['AdminController', 'settings'],
    'POST::/api/admin/settings' => ['AdminController', 'updateSettings'],
    'POST::/api/admin/logo' => ['AdminController', 'uploadLogo'],
    'GET::/api/admin/taux-change' => ['AdminController', 'getTauxChange'],
];

// Fonction de routage
function matchRoute($uri, $routes) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    foreach ($routes as $route => $handler) {
        list($routeMethod, $pattern) = explode('::', $route, 2);
        
        if ($routeMethod !== $method) {
            continue;
        }
        
        // Convertir le pattern en regex
        $regex = '#^' . $pattern . '$#';
        
        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches); // Supprimer la correspondance complète
            
            return [
                'controller' => $handler[0],
                'action' => $handler[1],
                'params' => $matches
            ];
        }
    }
    
    return null;
}

// Trouver la route correspondante
$route = matchRoute($uri, $routes);

if ($route) {
    $controllerName = $route['controller'];
    $action = $route['action'];
    $params = $route['params'];
    
    try {
        // Instancier le contrôleur
        $controller = new $controllerName();
        
        // Appeler l'action avec les paramètres
        if (!empty($params)) {
            call_user_func_array([$controller, $action], $params);
        } else {
            $controller->$action();
        }
    } catch (Throwable $e) {
        // Erreur dans le contrôleur
        if (strpos($uri, '/api/') === 0) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } else {
            throw $e;
        }
    }
} else {
    // Route non trouvée
    http_response_code(404);
    
    if (strpos($uri, '/api/') === 0) {
        echo json_encode(['success' => false, 'message' => 'Route non trouvée']);
    } else {
        require_once ROOT_PATH . '/app/Views/errors/404.php';
    }
}

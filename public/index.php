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
    'GET::/unauthorized' => ['AuthController', 'unauthorized'],
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
    'GET::/stocks/correction' => ['StockController', 'correction'],
    'POST::/api/stocks/correction' => ['StockController', 'saveCorrection'],
    'GET::/stocks/ajustements' => ['StockController', 'historiqueCorrections'],
    
    // Approvisionnements
    'GET::/approvisionnements' => ['ApprovisionnementController', 'index'],
    'GET::/approvisionnements/create' => ['ApprovisionnementController', 'create'],
    'GET::/approvisionnements/(\d+)' => ['ApprovisionnementController', 'show'],
    'GET::/approvisionnements/(\d+)/print' => ['ApprovisionnementController', 'print'],
    'GET::/approvisionnements/(\d+)/export' => ['ApprovisionnementController', 'exportDetail'],
    'POST::/api/approvisionnements' => ['ApprovisionnementController', 'store'],
    'POST::/api/approvisionnements/(\d+)/annuler' => ['ApprovisionnementController', 'annuler'],
    'GET::/approvisionnements/dettes' => ['ApprovisionnementController', 'dettes'],
    'POST::/api/dettes/(\d+)/rembourser' => ['ApprovisionnementController', 'rembourserDette'],
    'GET::/approvisionnements/(\d+)/edit' => ['ApprovisionnementController', 'edit'],
    'PUT::/api/approvisionnements/(\d+)' => ['ApprovisionnementController', 'update'],
    
    // Retours emballages
    'GET::/retours-emballages' => ['RetourController', 'index'],
    'POST::/api/retours-emballages' => ['RetourController', 'store'],

    // Emballages
    'GET::/emballages' => ['EmballageController', 'index'],
    'GET::/emballages/inventaire-initial' => ['StockController', 'inventaireInitial'],
    'GET::/emballages/suivi' => ['EmballageController', 'suivi'],
    'GET::/emballages/emprunts' => ['EmpruntEmballageController', 'index'],
    'POST::/api/emballages/emprunts' => ['EmpruntEmballageController', 'store'],
    'POST::/api/emballages/emprunts/(\d+)/rembourser' => ['EmpruntEmballageController', 'rembourser'],
    
    // Ventes
    'GET::/ventes' => ['VenteController', 'index'],
    'GET::/ventes/create' => ['VenteController', 'create'],
    'GET::/ventes/(\d+)' => ['VenteController', 'show'],
    'POST::/api/ventes' => ['VenteController', 'store'],
    'POST::/api/ventes/(\d+)/annuler' => ['VenteController', 'annuler'],
    'GET::/ventes/(\d+)/print' => ['VenteController', 'print'],
    'GET::/ventes/stats' => ['VenteController', 'stats'],
    'GET::/ventes/par-vehicule' => ['VenteController', 'parVehicule'],
    'GET::/ventes/par-vehicule/print' => ['VenteController', 'printParVehicule'],
    'GET::/ventes/par-vehicule/export' => ['VenteController', 'exportParVehicule'],
    'GET::/ventes/export' => ['VenteController', 'exportAll'],
    'GET::/ventes/(\d+)/edit' => ['VenteController', 'edit'],
    'PUT::/api/ventes/(\d+)' => ['VenteController', 'update'],
    
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
    'GET::/vehicules/inventaire' => ['VehiculeController', 'inventaire'],
    'GET::/api/vehicules' => ['VehiculeController', 'apiList'],
    'GET::/api/vehicules/(\d+)' => ['VehiculeController', 'apiShow'],
    'GET::/vehicules/(\d+)' => ['VehiculeController', 'show'],
    'GET::/vehicules/(\d+)/print' => ['VehiculeController', 'print'],
    'POST::/api/vehicules' => ['VehiculeController', 'store'],
    'PUT::/api/vehicules/(\d+)' => ['VehiculeController', 'update'],
    'DELETE::/api/vehicules/(\d+)' => ['VehiculeController', 'delete'],
    'POST::/api/vehicules/transfert' => ['VehiculeController', 'transfertVehicule'],
    'POST::/api/vehicules/(\d+)/retour-emballages' => ['VehiculeController', 'retourEmballages'],
    
    // Missions
    'GET::/missions' => ['MissionController', 'index'],
    'GET::/missions/en-cours' => ['MissionController', 'enCours'],
    'GET::/missions/create' => ['MissionController', 'create'],
    'GET::/missions/ristourne/create' => ['MissionController', 'createRestourne'],
    'GET::/missions/synthese' => ['MissionController', 'synthese'],
    'GET::/missions/(\d+)/edit' => ['MissionController', 'edit'],
    'GET::/missions/(\d+)' => ['MissionController', 'show'],
    'POST::/api/missions/ristourne' => ['MissionController', 'storeRestourne'],
    'POST::/api/missions' => ['MissionController', 'store'],
    'PUT::/api/missions/(\d+)' => ['MissionController', 'update'],
    'POST::/api/missions/(\d+)/terminer' => ['MissionController', 'terminer'],
    'DELETE::/api/missions/(\d+)' => ['MissionController', 'annuler'],
    'GET::/missions/(\d+)/print' => ['MissionController', 'print'],
    'GET::/missions/(\d+)/facture' => ['MissionController', 'facture'],

    // API Mobile
    'POST::/api/mobile/login' => ['MobileController', 'login'],
    'GET::/api/mobile/branding' => ['MobileController', 'branding'],
    'GET::/api/mobile/mission/(\d+)/stock' => ['MobileController', 'getStock'],
    'GET::/api/mobile/mission/(\d+)/stats' => ['MobileController', 'getMissionStats'],
    'POST::/api/mobile/vente' => ['MobileController', 'storeVente'],
    'GET::/api/mobile/vente/(\d+)/facture' => ['MobileController', 'getVenteFacture'],
    'GET::/api/mobile/clients' => ['ClientController', 'apiList'],
    'GET::/api/mobile/ventes' => ['MobileController', 'listVentes'],
    'GET::/api/mobile/ventes-par-agent' => ['MobileController', 'ventesParAgent'],
    'POST::/api/mobile/ristournes/(\d+)/payer' => ['MobileController', 'payerRistourne'],
    'POST::/api/mobile/mission_ristournes/(\d+)/encaisser' => ['MobileController', 'encaisserMissionRistourne'],
    
    // Pertes
    'GET::/pertes' => ['PerteController', 'index'],
    'GET::/pertes/create' => ['PerteController', 'create'],
    'POST::/api/pertes' => ['PerteController', 'store'],
    'DELETE::/api/pertes/(\d+)' => ['PerteController', 'delete'],
    'GET::/pertes/stats' => ['PerteController', 'stats'],
    'GET::/pertes/(\d+)/edit' => ['PerteController', 'edit'],
    'PUT::/api/pertes/(\d+)' => ['PerteController', 'update'],
    
    // Manquants agents
    'GET::/manquants' => ['ManquantController', 'index'],
    'GET::/manquants/create' => ['ManquantController', 'create'],
    'POST::/api/manquants' => ['ManquantController', 'store'],
    'DELETE::/api/manquants/(\d+)' => ['ManquantController', 'delete'],
    'POST::/api/manquants/(\d+)/paiement' => ['ManquantController', 'payer'],
    'GET::/manquants/export' => ['ManquantController', 'export'],
    'GET::/manquants/(\d+)/edit' => ['ManquantController', 'edit'],
    'PUT::/api/manquants/(\d+)' => ['ManquantController', 'update'],

    // Dépenses
    'GET::/depenses' => ['DepenseController', 'index'],
    'GET::/depenses/create' => ['DepenseController', 'create'],
    'GET::/depenses/print' => ['DepenseController', 'printAll'],
    'GET::/depenses/(\d+)/print' => ['DepenseController', 'print'],
    'POST::/api/depenses' => ['DepenseController', 'store'],
    'DELETE::/api/depenses/(\d+)' => ['DepenseController', 'delete'],
    'GET::/depenses/(\d+)/edit' => ['DepenseController', 'edit'],
    'PUT::/api/depenses/(\d+)' => ['DepenseController', 'update'],
    
    // Zones
    'GET::/zones' => ['ZoneController', 'index'],
    'GET::/zones/(\d+)' => ['ZoneController', 'show'],
    'GET::/api/zones' => ['ZoneController', 'apiList'],
    'POST::/api/zones' => ['ZoneController', 'store'],
    'PUT::/api/zones/(\d+)' => ['ZoneController', 'update'],
    'DELETE::/api/zones/(\d+)' => ['ZoneController', 'delete'],
    
    // Rapports
    'GET::/rapports' => ['ReportController', 'index'],
    'GET::/rapports/ventes-par-agent' => ['ReportController', 'ventesParAgent'],
    'GET::/rapports/ventes-par-agent/export' => ['ReportController', 'exportVentesParAgent'],
    
    // Finance
    'GET::/finance' => ['FinanceController', 'index'],
    'GET::/finance/print' => ['FinanceController', 'print'],
    'GET::/finance/export' => ['FinanceController', 'export'],
    'GET::/api/finance/stats' => ['FinanceController', 'apiStats'],
    
    // Ristournes
    'GET::/ristournes' => ['RistourneController', 'index'],
    'GET::/ristournes/calculer' => ['RistourneController', 'calculer'],
    'POST::/api/ristournes' => ['RistourneController', 'store'],
    'POST::/api/ristournes/(\d+)/payer' => ['RistourneController', 'payer'],
    
    // Admin
    'GET::/admin' => ['AdminController', 'index'],
    'GET::/admin/users' => ['AdminController', 'users'],
    'GET::/admin/objectifs' => ['AdminController', 'objectifs'],
    'POST::/api/admin/users' => ['AdminController', 'storeUser'],
    'POST::/api/admin/objectifs' => ['AdminController', 'storeObjectifs'],
    'PUT::/api/admin/users/(\d+)' => ['AdminController', 'updateUser'],
    'DELETE::/api/admin/users/(\d+)' => ['AdminController', 'deleteUser'],
    'POST::/api/admin/users/(\d+)/reset-password' => ['AdminController', 'resetPassword'],
    'GET::/admin/settings' => ['AdminController', 'settings'],
    'POST::/api/admin/settings' => ['AdminController', 'updateSettings'],
    'POST::/api/admin/logo' => ['AdminController', 'uploadLogo'],
    'GET::/api/admin/taux-change' => ['AdminController', 'getTauxChange'],

    // Rôles et permissions
    'GET::/admin/roles' => ['RoleController', 'index'],
    'GET::/api/admin/roles' => ['RoleController', 'index'],
    'GET::/api/admin/permissions' => ['RoleController', 'permissions'],
    'POST::/api/admin/roles' => ['RoleController', 'store'],
    'PUT::/api/admin/roles/(\d+)' => ['RoleController', 'update'],
    'DELETE::/api/admin/roles/(\d+)' => ['RoleController', 'delete'],
    'PUT::/api/admin/users/(\d+)/roles' => ['RoleController', 'syncUserRoles'],
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

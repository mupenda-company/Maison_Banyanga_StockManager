<?php
/**
 * Controleur des Ristournes
 */

class RistourneController extends Controller
{
    private $ristourneModel;
    private $clientModel;
    private $produitModel;

    public function __construct()
    {
        parent::__construct();
        $this->ristourneModel = new Ristourne();
        $this->clientModel = new Client();
        $this->produitModel = new Produit();
    }

    /**
     * Liste des ristournes calculees
     */
    public function index()
    {
        $this->requirePermission('ristournes.voir');
        
        $filters = [
            'mois' => $_GET['mois'] ?? date('n'),
            'annee' => $_GET['annee'] ?? date('Y'),
            'client_id' => $_GET['client_id'] ?? null
        ];

        $recolteLocaleActive = $this->isRecolteLocaleActive();
        $avecRecolteLocale = isset($_GET['recolte_locale'])
            && (string) $_GET['recolte_locale'] === '1';
        if ($avecRecolteLocale && !$recolteLocaleActive) {
            http_response_code(404);
            exit('Le rapport avec recolte locale est desactive.');
        }

        $this->ristourneModel->synchroniserStatutsLivraison();
        $ristournes = $this->ristourneModel->getAllWithDetails($filters);
        $ristournesRapport = $avecRecolteLocale
            ? $this->applyRecolteLocaleToReport($ristournes)
            : $ristournes;
        $report = $this->buildLivraisonReport($ristournesRapport);
        $clients = $this->clientModel->all();
        $produits = $this->produitModel->getActive();
        $printMode = isset($_GET['print']) && (string) $_GET['print'] === '1';

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->requirePermission('ristournes.exporter');
            $this->exportExcel($report, $filters, $avecRecolteLocale);
            return;
        }

        if ($printMode) {
            $this->requirePermission('ristournes.imprimer');
            $this->view('ristournes/print', [
                'ristournes' => $ristournesRapport,
                'report' => $report,
                'filters' => $filters,
                'avec_recolte_locale' => $avecRecolteLocale,
            ]);
            return;
        }
        
        $pagination = paginate_array($ristournes, $_GET['page'] ?? 1, pagination_per_page(5));

        $this->view('ristournes/index', [
            'ristournes' => $pagination['data'],
            'clients' => $clients,
            'produits' => $produits,
            'report' => $report,
            'filters' => $filters,
            'pagination' => $pagination,
            'print_mode' => $printMode,
            'recolte_locale_active' => $recolteLocaleActive,
        ]);
    }

    private function isRecolteLocaleActive(): bool
    {
        return defined('APPLIQUER_RECOLTE_LOCALE') && APPLIQUER_RECOLTE_LOCALE === true;
    }

    private function applyRecolteLocaleToReport(array $ristournes): array
    {
        foreach ($ristournes as &$row) {
            $caBrut = max(0, (float) ($row['ca_total'] ?? 0));
            $deduction = $this->ristourneModel->calculerDeductionLocale(
                (int) ($row['total_caisses'] ?? 0)
            );
            $recolteLocale = max(0, (float) ($deduction['deduction_locale'] ?? 0));

            $row['ca_total_brut'] = $caBrut;
            $row['recolte_locale'] = $recolteLocale;
            $row['taux_recolte_locale'] = (float) ($deduction['taux_local'] ?? 0);
            $row['palier_recolte_locale'] = $deduction['palier_local'] ?? '';
            $row['ca_total_apres_recolte'] = max(0, $caBrut - $recolteLocale);
            $row['ca_total'] = $row['ca_total_apres_recolte'];
            $row['montant_ristourne_brut'] = max(0, (float) ($row['montant_ristourne'] ?? 0));
            $tauxRistourne = max(0, (float) ($row['taux_applique'] ?? 0));
            $row['montant_ristourne'] = round(
                ($row['ca_total_apres_recolte'] * $tauxRistourne) / 100,
                2
            );
        }
        unset($row);

        return $ristournes;
    }

    private function styleHeaderRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $nbCols): void
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nbCols);
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9'],
            ],
        ]);
        foreach (range(1, $nbCols) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
    }

    // Helper pour envoyer le fichier xlsx au navigateur
    private function sendXlsx(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $filename): void
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (headers_sent()) {
            throw new Exception('Impossible de generer le fichier Excel: des donnees ont deja ete envoyees au navigateur.');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        exit;
    }

    private function validateSelectedProducts(array $productIds, int $complementProductId): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($productIds)) {
            return ['success' => false, 'message' => 'Selectionnez au moins un produit a livrer comme ristourne.'];
        }
        if ($complementProductId <= 0) {
            return ['success' => false, 'message' => 'Selectionnez le produit de complement.'];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $products = $this->db->fetchAll(
            "SELECT id, nom, prix_vente_caisses, prix_vente_unitaire, bouteilles_par_caisses
             FROM produits WHERE actif = 1 AND id IN ({$placeholders})",
            $productIds
        );
        if (count($products) !== count($productIds)) {
            return ['success' => false, 'message' => 'Un des produits selectionnes est introuvable ou inactif.'];
        }

        $referencePriceCents = null;
        foreach ($products as &$product) {
            $bottles = max(1, (int) ($product['bouteilles_par_caisses'] ?? 24));
            $casePrice = (float) ($product['prix_vente_caisses'] ?? 0);
            if ($casePrice <= 0) {
                $casePrice = (float) ($product['prix_vente_unitaire'] ?? 0) * $bottles;
            }
            $priceCents = (int) round($casePrice * 100);
            if ($priceCents <= 0) {
                return ['success' => false, 'message' => 'Tous les produits selectionnes doivent avoir un prix caisse valide.'];
            }
            if ($referencePriceCents === null) {
                $referencePriceCents = $priceCents;
            } elseif ($priceCents !== $referencePriceCents) {
                return ['success' => false, 'message' => 'Les produits de ristourne selectionnes doivent avoir exactement le meme prix par caisse.'];
            }
            $product['prix_caisse'] = $casePrice;
        }
        unset($product);

        $complementProduct = $this->db->fetch(
            "SELECT id, nom, prix_vente_caisses, prix_vente_unitaire, bouteilles_par_caisses
             FROM produits WHERE actif = 1 AND id = :id LIMIT 1",
            ['id' => $complementProductId]
        );
        if (!$complementProduct) {
            return ['success' => false, 'message' => 'Le produit de complement est introuvable ou inactif.'];
        }
        $complementBottles = max(1, (int) ($complementProduct['bouteilles_par_caisses'] ?? 24));
        $complementCasePrice = (float) ($complementProduct['prix_vente_caisses'] ?? 0);
        if ($complementCasePrice <= 0) {
            $complementCasePrice = (float) ($complementProduct['prix_vente_unitaire'] ?? 0) * $complementBottles;
        }
        if ($complementCasePrice <= 0) {
            return ['success' => false, 'message' => 'Le produit de complement doit avoir un prix caisse valide.'];
        }
        $complementProduct['prix_caisse'] = $complementCasePrice;

        return [
            'success' => true,
            'products' => $products,
            'case_price' => ($referencePriceCents ?? 0) / 100,
            'complement_product' => $complementProduct,
            'complement_case_price' => $complementCasePrice,
        ];
    }

    private function exportExcel($report, $filters, bool $avecRecolteLocale = false)
    {
        $this->requireAuth();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Ristournes');

        $headers = ['Zone', 'Client', 'Total colis', 'Chiffre affaires', 'Ristourne'];
        foreach ($report['produits'] as $produit) {
            $headers[] = $produit['nom'];
        }
        $headers = array_merge($headers, ['Produit du complement', 'Montant restant', 'Montant a completer', 'Observation', 'Signature client']);
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($report['rows'] as $r) {
            $line = [
                $r['zone_nom'] ?? '',
                $r['client_nom'] ?? '',
                (int) ($r['total_caisses'] ?? 0),
                (float) ($r['ca_total'] ?? 0),
                (float) ($r['montant_ristourne'] ?? 0),
            ];
            foreach ($report['produits'] as $produit) {
                $line[] = (int) ($r['produits'][$produit['id']]['caisses'] ?? 0);
            }
            $line[] = $r['produit_complement_nom'] ?? '';
            $line[] = (float) ($r['montant_restant'] ?? 0);
            $line[] = (float) ($r['montant_a_completer'] ?? 0);
            $line[] = '';
            $line[] = '';
            $sheet->fromArray($line, null, 'A' . $row++);
        }

        $this->styleHeaderRow($sheet, count($headers));

        $mois = $filters['mois'] ?? date('n');
        $annee = $filters['annee'] ?? date('Y');
        $suffix = $avecRecolteLocale ? '_avec_recolte_locale' : '_normal';
        $this->sendXlsx($spreadsheet, 'ristournes_' . $mois . '_' . $annee . $suffix . '_' . date('Y-m-d_H-i') . '.xlsx');
    }

    private function buildLivraisonReport(array $ristournes): array
    {
        $productIds = [];
        $complementProductIds = [];
        foreach ($ristournes as $row) {
            $ids = json_decode((string) ($row['produits_ristourne'] ?? '[]'), true);
            if (!is_array($ids)) {
                $ids = [];
            }
            $ids = array_values(array_unique(array_map('intval', $ids)));
            $complementId = (int) ($row['produit_complement_id'] ?? 0);
            if ($complementId > 0) {
                $complementProductIds[$complementId] = true;
                if (count($ids) > 1) {
                    $ids = array_values(array_filter($ids, static fn($id) => (int) $id !== $complementId));
                }
            }
            foreach ($ids as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $productIds[$id] = true;
                }
            }
        }

        if (empty($productIds)) {
            return ['produits' => [], 'rows' => $ristournes];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $produits = $this->db->fetchAll(
            "SELECT id, nom, code, prix_vente_caisses, prix_vente_unitaire, bouteilles_par_caisses
             FROM produits
             WHERE id IN ({$placeholders})
             ORDER BY position_affichage ASC, nom ASC",
            array_keys($productIds)
        );

        $produitsById = [];
        foreach ($produits as $produit) {
            $btl = max(1, (int) ($produit['bouteilles_par_caisses'] ?? 24));
            $prixCaisse = (float) ($produit['prix_vente_caisses'] ?? 0);
            if ($prixCaisse <= 0) {
                $prixCaisse = (float) ($produit['prix_vente_unitaire'] ?? 0) * $btl;
            }
            $produit['prix_caisse'] = max(0, $prixCaisse);
            $produitsById[(int) $produit['id']] = $produit;
        }
        $missingComplementIds = array_diff(array_keys($complementProductIds), array_keys($produitsById));
        if (!empty($missingComplementIds)) {
            $complementPlaceholders = implode(',', array_fill(0, count($missingComplementIds), '?'));
            $complementProducts = $this->db->fetchAll(
                "SELECT id, nom, code, prix_vente_caisses, prix_vente_unitaire, bouteilles_par_caisses
                 FROM produits WHERE id IN ({$complementPlaceholders})",
                array_values($missingComplementIds)
            );
            foreach ($complementProducts as $produit) {
                $btl = max(1, (int) ($produit['bouteilles_par_caisses'] ?? 24));
                $prixCaisse = (float) ($produit['prix_vente_caisses'] ?? 0);
                if ($prixCaisse <= 0) {
                    $prixCaisse = (float) ($produit['prix_vente_unitaire'] ?? 0) * $btl;
                }
                $produit['prix_caisse'] = max(0, $prixCaisse);
                $produitsById[(int) $produit['id']] = $produit;
            }
        }

        $rows = [];
        foreach ($ristournes as $row) {
            $selected = json_decode((string) ($row['produits_ristourne'] ?? '[]'), true);
            if (!is_array($selected)) {
                $selected = [];
            }
            $selected = array_values(array_unique(array_map('intval', $selected)));
            $montant = (float) ($row['montant_ristourne'] ?? 0);
            $row['produits'] = [];
            $complementProductId = (int) ($row['produit_complement_id'] ?? 0);
            if ($complementProductId <= 0 || empty($produitsById[$complementProductId])) {
                $complementProductId = 0;
            }
            if ($complementProductId > 0 && count($selected) > 1) {
                $selected = array_values(array_filter($selected, static fn($id) => (int) $id !== $complementProductId));
            }

            $referencePrice = 0;
            foreach ($selected as $selectedId) {
                if (!empty($produitsById[$selectedId]['prix_caisse'])) {
                    $referencePrice = (float) $produitsById[$selectedId]['prix_caisse'];
                    break;
                }
            }
            $baseCases = $referencePrice > 0 ? (int) floor($montant / $referencePrice) : 0;
            $remaining = $referencePrice > 0 ? max(0, $montant - ($baseCases * $referencePrice)) : $montant;
            $complementPrice = (float) ($produitsById[$complementProductId]['prix_caisse'] ?? 0);
            $amountToComplete = $complementPrice > 0 ? max(0, $complementPrice - $remaining) : 0;
            $selectedEligible = array_values(array_filter($selected, static function ($selectedId) use ($produitsById) {
                return !empty($produitsById[$selectedId]['prix_caisse']);
            }));
            $distributedCases = [];
            if (!empty($selectedEligible) && $baseCases > 0) {
                $share = intdiv($baseCases, count($selectedEligible));
                $remainingCases = $baseCases % count($selectedEligible);
                foreach ($selectedEligible as $index => $selectedId) {
                    $distributedCases[$selectedId] = $share + ($index < $remainingCases ? 1 : 0);
                }
            }

            foreach ($produits as $produit) {
                $produitId = (int) $produit['id'];
                $prixCaisse = (float) ($produitsById[$produitId]['prix_caisse'] ?? 0);
                $isSelected = in_array($produitId, $selected, true);
                $caissesSansComplement = ($isSelected && $prixCaisse > 0) ? (int) ($distributedCases[$produitId] ?? 0) : 0;
                $row['produits'][$produitId] = [
                    'caisses' => $caissesSansComplement,
                    'caisses_sans_complement' => $caissesSansComplement,
                    'prix_caisse' => $prixCaisse,
                    'avec_complement' => false,
                ];
            }
            $row['produit_complement_id'] = $complementProductId ?: null;
            $row['produit_complement_nom'] = $produitsById[$complementProductId]['nom'] ?? '';
            $row['produit_complement_prix_caisse'] = $complementPrice;
            $row['caisses_complement'] = $complementProductId > 0 ? 1 : 0;
            $row['montant_restant'] = $remaining;
            $row['montant_a_completer'] = $amountToComplete;
            $rows[] = $row;
        }

        return ['produits' => $produits, 'rows' => $rows];
    }

    /**
     * Lancer ou actualiser le calcul des ristournes pour un mois donne.
     */
    public function calculer()
    {
        $this->requirePermission('ristournes.calculer');
        $this->ristourneModel->synchroniserStatutsLivraison();

        $input = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') ? $this->getJsonInput() : $_GET;
        $mois = $input['mois'] ?? date('n');
        $annee = $input['annee'] ?? date('Y');
        $rawProductIds = $input['produit_ids'] ?? [];
        if (!is_array($rawProductIds)) {
            $rawProductIds = explode(',', (string) $rawProductIds);
        }
        $produitIds = array_values(array_unique(array_filter(array_map('intval', $rawProductIds))));
        $produitComplementId = (int) ($input['produit_complement_id'] ?? 0);
        $complementAuto = filter_var($input['complement_auto'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (!$complementAuto && $produitComplementId > 0) {
            $produitIds = array_values(array_filter($produitIds, static fn($id) => (int) $id !== $produitComplementId));
        }
        if ($produitComplementId <= 0 && !empty($produitIds)) {
            $produitComplementId = (int) $produitIds[0];
        }

        $productValidation = $this->validateSelectedProducts($produitIds, $produitComplementId);
        if (!$productValidation['success']) {
            return $this->error($productValidation['message'], 422);
        }

        $clients = $this->clientModel->all();
        $nbCrees = 0;
        $nbMaj = 0;
        $nbVerrouilles = 0;
        $clientIdsRecalcules = [];

        foreach ($clients as $client) {
            $calcul = $this->ristourneModel->calculerRistourne($client['id'], $mois, $annee);
            if (!$calcul) {
                continue;
            }
            $clientIdsRecalcules[] = (int) $calcul['client_id'];

            $existantes = $this->db->fetchAll(
                "SELECT id, statut FROM ristournes
                 WHERE client_id = :cid
                   AND periode_debut = :debut
                   AND COALESCE(statut, '') != 'annulee'
                 ORDER BY id DESC",
                ['cid' => $client['id'], 'debut' => $calcul['periode_debut']]
            );

            $dataRistourne = [
                'client_id' => $calcul['client_id'],
                'periode_debut' => $calcul['periode_debut'],
                'periode_fin' => $calcul['periode_fin'],
                'ca_total' => $calcul['ca_total'],
                'palier_id' => $calcul['palier_id'],
                'taux_applique' => $calcul['taux_applique'],
                'montant_ristourne' => $calcul['montant_ristourne'],
                'total_caisses' => $calcul['total_caisses'],
                'produits_ristourne' => json_encode($produitIds),
                'produit_complement_id' => $produitComplementId,
                'statut' => 'calculee',
            ];

            if (empty($existantes)) {
                $this->ristourneModel->create($dataRistourne);
                $nbCrees++;
                continue;
            }

            $hasEditable = false;
            $hasLocked = false;
            foreach ($existantes as $existante) {
                $statut = (string) ($existante['statut'] ?? '');
                if (in_array($statut, ['en_livraison', 'payee'], true)) {
                    $hasLocked = true;
                } else {
                    $hasEditable = true;
                }
            }

            if ($hasEditable) {
                $nbMaj += (int) $this->db->update(
                    'ristournes',
                    $dataRistourne,
                    "client_id = :where_client_id
                     AND periode_debut = :where_periode_debut
                     AND COALESCE(statut, '') NOT IN ('en_livraison', 'payee', 'annulee')",
                    [
                        'where_client_id' => $calcul['client_id'],
                        'where_periode_debut' => $calcul['periode_debut'],
                    ]
                );
            }

            if ($hasLocked) {
                $nbVerrouilles++;
            }
        }

        $periodeDebut = sprintf('%04d-%02d-01', (int) $annee, (int) $mois);
        $whereObsoletes = "periode_debut = :where_periode_debut
            AND COALESCE(statut, '') NOT IN ('en_livraison', 'payee', 'annulee')";
        $paramsObsoletes = ['where_periode_debut' => $periodeDebut];
        if (!empty($clientIdsRecalcules)) {
            $placeholders = [];
            foreach (array_values(array_unique($clientIdsRecalcules)) as $index => $clientId) {
                $key = 'client_recalcule_' . $index;
                $placeholders[] = ':' . $key;
                $paramsObsoletes[$key] = $clientId;
            }
            $whereObsoletes .= " AND client_id NOT IN (" . implode(',', $placeholders) . ")";
        }
        $nbObsoletes = (int) $this->db->update(
            'ristournes',
            [
                'statut' => 'annulee',
                'date_paiement' => null,
                'notes' => 'Annulee automatiquement lors du recalcul du ' . date('Y-m-d H:i:s') . ' car le client n a plus de vente validee sur cette periode.'
            ],
            $whereObsoletes,
            $paramsObsoletes
        );

        $message = $nbCrees . ' ristourne(s) creee(s), ' . $nbMaj . ' recalculee(s)/corrigee(s) pour la periode.';
        if ($nbObsoletes > 0) {
            $message .= ' ' . $nbObsoletes . ' ancienne(s) ristourne(s) sans vente validee annulee(s).';
        }
        if ($nbVerrouilles > 0) {
            $message .= ' ' . $nbVerrouilles . ' deja en livraison/payee(s) non modifiee(s). Annulez d abord la mission ou le paiement si vous devez les recalculer.';
        }

        return $this->success(null, $message);
    }

    /**
     * Marquer une ristourne comme payee
     */
    public function payer($id)
    {
        $this->requirePermission('ristournes.payer');
        
        $result = $this->ristourneModel->marquerPayee($id);
        
        if ($result) {
            return $this->success(null, 'Ristourne marquee comme payee.');
        }
        
        return $this->error('Erreur lors de la mise a jour.');
    }

    /**
     * Gestion des paliers
     */
    public function paliers()
    {
        $this->requirePermission('ristournes.paliers');
        $paliers = $this->ristourneModel->getPaliers();
        $this->view('ristournes/paliers', ['paliers' => $paliers]);
    }

    /**
     * Creer/mettre a jour un palier
     */
    public function storePalier()
    {
        $this->requirePermission('ristournes.paliers');
        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'nom' => 'required',
            'ca_min' => 'required|numeric',
            'taux_ristourne' => 'required|numeric'
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        $fromDevise = get_devise();
        $toDevise = get_base_devise();
        $caMinBase = convert_money((float)($data['ca_min'] ?? 0), $fromDevise, $toDevise);
        $caMaxBase = !empty($data['ca_max']) ? convert_money((float)$data['ca_max'], $fromDevise, $toDevise) : null;

        $params = [
            'nom' => $data['nom'],
            'ca_min' => $caMinBase,
            'ca_max' => $caMaxBase,
            'taux_ristourne' => $data['taux_ristourne'],
            'actif' => 1
        ];

        if (!empty($data['id'])) {
            $this->db->update('paliers_ristourne', $params, 'id = :id', ['id' => $data['id']]);
        } else {
            $this->db->insert('paliers_ristourne', $params);
        }

        return $this->success(null, 'Palier enregistre avec succes.');
    }

    /**
     * Supprimer un palier
     */
    public function deletePalier($id)
    {
        $this->requirePermission('ristournes.paliers');
        $this->db->delete('paliers_ristourne', 'id = :id', ['id' => $id]);
        return $this->success(null, 'Palier supprime.');
    }
}

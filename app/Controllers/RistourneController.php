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
        $this->requirePermission('admin.voir');
        
        $filters = [
            'mois' => $_GET['mois'] ?? date('n'),
            'annee' => $_GET['annee'] ?? date('Y'),
            'client_id' => $_GET['client_id'] ?? null
        ];

        $ristournes = $this->ristourneModel->getAllWithDetails($filters);
        $report = $this->buildLivraisonReport($ristournes);
        $clients = $this->clientModel->all();
        $produits = $this->produitModel->getActive();
        $printMode = isset($_GET['print']) && (string) $_GET['print'] === '1';

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportExcel($report, $filters);
            return;
        }

        if ($printMode) {
            $this->view('ristournes/print', [
                'ristournes' => $ristournes,
                'report' => $report,
                'filters' => $filters
            ]);
            return;
        }
        
        $this->view('ristournes/index', [
            'ristournes' => $ristournes,
            'clients' => $clients,
            'produits' => $produits,
            'report' => $report,
            'filters' => $filters,
            'print_mode' => $printMode
        ]);
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

    private function exportExcel($report, $filters)
    {
        $this->requireAuth();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Ristournes');

        $headers = ['Zone', 'Client', 'Total colis', 'Chiffre affaires', 'Ristourne'];
        foreach ($report['produits'] as $produit) {
            $headers[] = $produit['nom'];
        }
        $headers = array_merge($headers, ['Montant restant', 'Montant a completer', 'Observation', 'Signature client']);
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
            $line[] = (float) ($r['montant_restant'] ?? 0);
            $line[] = (float) ($r['montant_a_completer'] ?? 0);
            $line[] = '';
            $line[] = '';
            $sheet->fromArray($line, null, 'A' . $row++);
        }

        $this->styleHeaderRow($sheet, count($headers));

        $mois = $filters['mois'] ?? date('n');
        $annee = $filters['annee'] ?? date('Y');
        $this->sendXlsx($spreadsheet, 'ristournes_' . $mois . '_' . $annee . '_' . date('Y-m-d_H-i') . '.xlsx');
    }

    private function buildLivraisonReport(array $ristournes): array
    {
        $productIds = [];
        foreach ($ristournes as $row) {
            $ids = json_decode((string) ($row['produits_ristourne'] ?? '[]'), true);
            if (!is_array($ids)) {
                $ids = [];
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

        $rows = [];
        foreach ($ristournes as $row) {
            $selected = json_decode((string) ($row['produits_ristourne'] ?? '[]'), true);
            if (!is_array($selected)) {
                $selected = [];
            }
            $montant = (float) ($row['montant_ristourne'] ?? 0);
            $row['produits'] = [];
            $firstSelectedProduct = null;

            foreach ($produits as $produit) {
                $produitId = (int) $produit['id'];
                $prixCaisse = (float) ($produitsById[$produitId]['prix_caisse'] ?? 0);
                $isSelected = in_array($produitId, array_map('intval', $selected), true);
                $caisses = ($isSelected && $prixCaisse > 0) ? (int) floor($montant / $prixCaisse) : 0;
                $row['produits'][$produitId] = [
                    'caisses' => $caisses,
                    'prix_caisse' => $prixCaisse,
                ];

                if ($isSelected && $firstSelectedProduct === null && $prixCaisse > 0) {
                    $firstSelectedProduct = ['caisses' => $caisses, 'prix_caisse' => $prixCaisse];
                }
            }

            if ($firstSelectedProduct) {
                $prix = (float) $firstSelectedProduct['prix_caisse'];
                $caisses = (int) $firstSelectedProduct['caisses'];
                $reste = max(0, $montant - ($caisses * $prix));
                $row['montant_restant'] = $reste;
                $row['montant_a_completer'] = $prix > 0 && $reste > 0 ? max(0, $prix - $reste) : 0;
            } else {
                $row['montant_restant'] = $montant;
                $row['montant_a_completer'] = 0;
            }
            $rows[] = $row;
        }

        return ['produits' => $produits, 'rows' => $rows];
    }

    /**
     * Lancer ou actualiser le calcul des ristournes pour un mois donne.
     */
    public function calculer()
    {
        $this->requirePermission('admin.voir');
        
        $mois = $_GET['mois'] ?? date('n');
        $annee = $_GET['annee'] ?? date('Y');
        $produitIds = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) ($_GET['produit_ids'] ?? ''))))));

        if (empty($produitIds)) {
            return $this->error('Selectionnez au moins un produit a livrer comme ristourne.', 422);
        }

        $clients = $this->clientModel->all();
        $nbCrees = 0;
        $nbMaj = 0;
        $nbVerrouilles = 0;

        foreach ($clients as $client) {
            $calcul = $this->ristourneModel->calculerRistourne($client['id'], $mois, $annee);
            if (!$calcul) {
                continue;
            }

            $existe = $this->db->fetch(
                "SELECT id, statut FROM ristournes WHERE client_id = :cid AND periode_debut = :debut AND statut != 'annulee' ORDER BY id DESC LIMIT 1",
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
            ];

            if (!$existe) {
                $dataRistourne['statut'] = 'calculee';
                $this->ristourneModel->create($dataRistourne);
                $nbCrees++;
                continue;
            }

            if (($existe['statut'] ?? '') === 'calculee') {
                $this->ristourneModel->update((int) $existe['id'], $dataRistourne);
                $nbMaj++;
                continue;
            }

            $nbVerrouilles++;
        }

        $message = $nbCrees . ' ristourne(s) creee(s), ' . $nbMaj . ' mise(s) a jour pour la periode.';
        if ($nbVerrouilles > 0) {
            $message .= ' ' . $nbVerrouilles . ' deja en livraison/payee(s) non modifiee(s).';
        }

        return $this->success(null, $message);
    }

    /**
     * Marquer une ristourne comme payee
     */
    public function payer($id)
    {
        $this->requirePermission('admin.voir');
        
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
        $this->requirePermission('admin.voir');
        $paliers = $this->ristourneModel->getPaliers();
        $this->view('ristournes/paliers', ['paliers' => $paliers]);
    }

    /**
     * Creer/mettre a jour un palier
     */
    public function storePalier()
    {
        $this->requirePermission('admin.voir');
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
        $this->requirePermission('admin.voir');
        $this->db->delete('paliers_ristourne', 'id = :id', ['id' => $id]);
        return $this->success(null, 'Palier supprime.');
    }
}

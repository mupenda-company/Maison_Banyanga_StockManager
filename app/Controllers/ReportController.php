<?php
/**
 * Controleur des Rapports et Statistiques
 */

class ReportController extends Controller
{
    private $venteModel;
    private $produitModel;
    private $clientModel;
    private $retourModel;
    private $detteModel;

    public function __construct()
    {
        parent::__construct();
        $this->venteModel = new Vente();
        $this->produitModel = new Produit();
        $this->clientModel = new Client();
        $this->retourModel = new RetourEmballage();
        $this->detteModel = new DetteEmballage();
    }

    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('rapports.voir');

        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');

        $statsVentes = $this->venteModel->getStatsGlobales($dateDebut, $dateFin);
        $topProduits = $this->produitModel->getTopVentes($dateDebut, $dateFin, 5);
        $ventesParZone = $this->venteModel->getVentesParZone($dateDebut, $dateFin);
        $statsEmballages = $this->retourModel->getStats($dateDebut, $dateFin, 5);
        $statsDettes = $this->detteModel->getStatsGlobales();

        $this->view('reports/index', [
            'statsVentes' => $statsVentes,
            'topProduits' => $topProduits,
            'ventesParZone' => $ventesParZone,
            'statsEmballages' => $statsEmballages,
            'statsDettes' => $statsDettes,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ]);
    }

    public function ventesParAgent()
    {
        $this->requireAuth();
        $this->requirePermission('rapports.voir');

        $this->view('reports/ventes_par_agent', $this->getVentesParAgentReportData());
    }

    public function syntheseAgents()
    {
        $this->requireAuth();
        $this->requirePermission('rapports.voir');

        $this->view('reports/synthese_agents', $this->getSyntheseAgentsData());
    }

    public function exportSyntheseAgents()
    {
        $this->requireAuth();
        $this->requirePermission('rapports.exporter');
        $data = $this->getSyntheseAgentsData();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Synthese agents');
        $headers = ['Agent', 'Caisses sorties', 'Caisses retournées', 'Caisses vendues', 'Montant des ventes'];
        $sheet->fromArray($headers, null, 'A1');
        $this->styleHeaderRow($sheet, count($headers));

        $row = 2;
        foreach ($data['lignes'] as $ligne) {
            $sheet->fromArray([
                $ligne['agent_nom'],
                (float) $ligne['sorti'],
                (float) $ligne['retourne'],
                (float) $ligne['vendu'],
                (float) $ligne['montant'],
            ], null, 'A' . $row++);
        }
        $sheet->fromArray([
            'TOTAL',
            (float) $data['totaux']['sorti'],
            (float) $data['totaux']['retourne'],
            (float) $data['totaux']['vendu'],
            (float) $data['totaux']['montant'],
        ], null, 'A' . $row);
        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);

        $this->sendXlsx(
            $spreadsheet,
            'synthese_agents_' . $data['dateDebut'] . '_' . $data['dateFin'] . '.xlsx'
        );
    }

    private function getSyntheseAgentsData(): array
    {
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-d');
        $dateFin = $_GET['date_fin'] ?? $dateDebut;
        $agentId = !empty($_GET['agent_id']) ? (int) $_GET['agent_id'] : null;

        $paramsSorties = ['date_debut' => $dateDebut, 'date_fin' => $dateFin];
        $filtreSorties = '';
        if ($agentId) {
            $filtreSorties = ' AND COALESCE(v.agent_responsable_id, m.created_by) = :agent_id';
            $paramsSorties['agent_id'] = $agentId;
        }

        $sorties = $this->db->fetchAll(
            "SELECT COALESCE(v.agent_responsable_id, m.created_by) AS agent_id,
                    CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, '')) AS agent_nom,
                    COALESCE(SUM(GREATEST(
                        COALESCE(mc.caisses_chargees, 0),
                        COALESCE(ms.caisses_mouvements, 0)
                    )), 0) AS caisses_sorties
             FROM missions m
             JOIN vehicules v ON v.id = m.vehicule_id
             LEFT JOIN users u ON u.id = COALESCE(v.agent_responsable_id, m.created_by)
             LEFT JOIN (
                SELECT mission_id, SUM(quantite_caisses) AS caisses_chargees
                FROM mission_chargements
                GROUP BY mission_id
             ) mc ON mc.mission_id = m.id
             LEFT JOIN (
                SELECT mv.reference_id AS mission_id,
                       SUM(ABS(mv.quantite) / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24)) AS caisses_mouvements
                FROM mouvements_stock mv
                JOIN produits p ON p.id = mv.produit_id
                WHERE mv.reference_type = 'mission'
                  AND mv.quantite < 0
                  AND (
                      mv.motif LIKE 'Chargement véhicule pour mission %'
                      OR mv.motif LIKE 'Modification mission %'
                  )
                GROUP BY mv.reference_id
             ) ms ON ms.mission_id = m.id
             WHERE DATE(m.date_depart) BETWEEN :date_debut AND :date_fin
               AND m.statut <> 'annulee'
               AND COALESCE(m.type_mission, 'vente') = 'vente'
               {$filtreSorties}
             GROUP BY COALESCE(v.agent_responsable_id, m.created_by), u.prenom, u.nom",
            $paramsSorties
        );

        $paramsVentes = ['date_debut' => $dateDebut, 'date_fin' => $dateFin];
        $filtreVentes = '';
        if ($agentId) {
            $filtreVentes = ' AND COALESCE(vh.agent_responsable_id, ve.created_by) = :agent_id';
            $paramsVentes['agent_id'] = $agentId;
        }
        $ventes = $this->db->fetchAll(
            "SELECT COALESCE(vh.agent_responsable_id, ve.created_by) AS agent_id,
                    CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, '')) AS agent_nom,
                    COALESCE(SUM(vd.caisses_vendues), 0) AS caisses_vendues,
                    COALESCE(SUM(ve.total_ttc), 0) AS montant_ventes
             FROM ventes ve
             LEFT JOIN missions m ON m.id = ve.mission_id
             LEFT JOIN vehicules vh ON vh.id = m.vehicule_id
             LEFT JOIN users u ON u.id = COALESCE(vh.agent_responsable_id, ve.created_by)
             LEFT JOIN (
                SELECT vente_id, SUM(quantite_caisses) AS caisses_vendues
                FROM vente_details
                GROUP BY vente_id
             ) vd ON vd.vente_id = ve.id
             WHERE DATE(ve.date_vente) BETWEEN :date_debut AND :date_fin
               AND ve.statut = 'validee'
               {$filtreVentes}
             GROUP BY COALESCE(vh.agent_responsable_id, ve.created_by), u.prenom, u.nom",
            $paramsVentes
        );

        $index = [];
        foreach ($sorties as $sortie) {
            $id = (int) ($sortie['agent_id'] ?? 0);
            $index[$id] = [
                'agent_id' => $id,
                'agent_nom' => trim((string) ($sortie['agent_nom'] ?? '')) ?: 'Système',
                'sorti' => (float) ($sortie['caisses_sorties'] ?? 0),
                'vendu' => 0,
                'retourne' => 0,
                'montant' => 0,
            ];
        }
        foreach ($ventes as $vente) {
            $id = (int) ($vente['agent_id'] ?? 0);
            if (!isset($index[$id])) {
                $index[$id] = [
                    'agent_id' => $id,
                    'agent_nom' => trim((string) ($vente['agent_nom'] ?? '')) ?: 'Système',
                    'sorti' => 0,
                    'vendu' => 0,
                    'retourne' => 0,
                    'montant' => 0,
                ];
            }
            $index[$id]['vendu'] = (float) ($vente['caisses_vendues'] ?? 0);
            $index[$id]['montant'] = (float) ($vente['montant_ventes'] ?? 0);
        }

        $lignes = array_values($index);
        foreach ($lignes as &$ligne) {
            $ligne['retourne'] = max(0, $ligne['sorti'] - $ligne['vendu']);
        }
        unset($ligne);
        usort($lignes, static fn($a, $b) => strcasecmp($a['agent_nom'], $b['agent_nom']));

        $totaux = ['sorti' => 0, 'retourne' => 0, 'vendu' => 0, 'montant' => 0];
        foreach ($lignes as $ligne) {
            foreach (array_keys($totaux) as $champ) {
                $totaux[$champ] += (float) $ligne[$champ];
            }
        }

        return [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'agentId' => $agentId,
            'agents' => $this->db->fetchAll(
                "SELECT id, CONCAT(COALESCE(prenom, ''), ' ', COALESCE(nom, '')) AS nom_complet
                 FROM users WHERE actif = 1 ORDER BY nom, prenom"
            ),
            'lignes' => $lignes,
            'totaux' => $totaux,
        ];
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

    public function exportVentesParAgent()
    {
        $this->requireAuth();
        $this->requirePermission('rapports.exporter');

        $data = $this->getVentesParAgentReportData();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Ventes par agent');

        // Résumé
        $sheet->fromArray(['Rapport ventes par agent'], null, 'A1');
        $sheet->fromArray(['Periode', $data['dateDebut'] . ' au ' . $data['dateFin']], null, 'A2');
        $sheet->fromArray(['Total ventes',    $data['totalVentes']], null, 'A3');
        $sheet->fromArray(['Agents concernes',$data['nbAgents']],    null, 'A4');
        $sheet->fromArray(['Total caisses',   (float) $data['totalCaisses']], null, 'A5');
        $sheet->fromArray(['CA total',        (float) $data['totalCa']],     null, 'A6');

        // En-têtes
        $sheet->fromArray(
            ['Date', 'Agent', 'Caisses sorties', 'Caisses vendues', 'Nombre de ventes', 'Montant vendu'],
            null,
            'A8'
        );
        $sheet->getStyle('A8:F8')->getFont()->setBold(true);
        $row = 9;
        foreach ($data['syntheseJournaliere'] as $ligne) {
            $sheet->fromArray([
                $ligne['date'],
                $ligne['agent_nom'],
                (float) $ligne['caisses_sorties'],
                (float) $ligne['caisses_vendues'],
                (int) $ligne['nombre_ventes'],
                (float) $ligne['montant_ventes'],
            ], null, 'A' . $row++);
        }

        $row += 2;
        $headers = ['Agent', 'Role', 'Date', 'Facture', 'Client', 'Emplacement', 'Caisses', 'Total TTC'];
        $headerRow = $row;
        $sheet->fromArray($headers, null, 'A' . $headerRow);
        $this->styleHeaderRow($sheet, count($headers));

        // Re-style header sur ligne 8 (styleHeaderRow cible ligne 1 — on duplique ici)
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
        ]);

        $row = $headerRow + 1;
        foreach ($data['ventesParAgent'] as $agent) {
            foreach ($agent['ventes'] as $vente) {
                $sheet->fromArray([
                    $agent['agent_nom'],
                    $agent['agent_role'],
                    !empty($vente['date_vente']) ? date('d/m/Y H:i', strtotime($vente['date_vente'])) : '',
                    $vente['numero_facture'] ?? '',
                    $vente['client_nom'] ?? 'N/A',
                    $vente['emplacement_nom'] ?? 'N/A',
                    (float)($vente['total_caisses'] ?? 0),
                    (float)($vente['total_ttc'] ?? 0),
                ], null, 'A' . $row++);
            }

            // Ligne sous-total en gras
            $sheet->fromArray([
                'Sous-total ' . $agent['agent_nom'],
                '', '', '', '', '',
                (float)($agent['total_caisses'] ?? 0),
                (float)($agent['total_ca'] ?? 0),
            ], null, 'A' . $row);
            $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
            $row += 2; // +1 ligne vide
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $filename = 'ventes_par_agent_' . $data['dateDebut'] . '_' . $data['dateFin'] . '.xlsx';
        $this->sendXlsx($spreadsheet, $filename);
    }



    private function getVentesParAgentReportData()
    {
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-d');
        $dateFin = $_GET['date_fin'] ?? $dateDebut;
        $filterAgentId = !empty($_GET['agent_id']) ? (int) $_GET['agent_id'] : null;

        $ventes = $this->venteModel->getVentesParAgent($dateDebut, $dateFin, $filterAgentId);

        $ventesParAgent = [];
        $totalCa = 0;
        $totalVentes = 0;
        $totalCaisses = 0;

        foreach ($ventes as $vente) {
            $agentId = $vente['created_by'] ?? 0;
            $agentNom = trim(($vente['agent_prenom'] ?? '') . ' ' . ($vente['agent_nom'] ?? ''));
            if ($agentNom === '') {
                $agentNom = 'Systeme';
            }

            if (!isset($ventesParAgent[$agentId])) {
                $ventesParAgent[$agentId] = [
                    'agent_id' => $agentId,
                    'agent_nom' => $agentNom,
                    'agent_role' => $vente['agent_role'] ?? '',
                    'ventes' => [],
                    'total_ca' => 0,
                    'total_caisses' => 0,
                ];
            }

            $ventesParAgent[$agentId]['ventes'][] = $vente;
            $ventesParAgent[$agentId]['total_ca'] += (float) ($vente['total_ttc'] ?? 0);
            $ventesParAgent[$agentId]['total_caisses'] += (float) ($vente['total_caisses'] ?? 0);
            $totalCa += (float) ($vente['total_ttc'] ?? 0);
            $totalCaisses += (float) ($vente['total_caisses'] ?? 0);
            $totalVentes++;
        }

        $ventesParAgent = array_values($ventesParAgent);

        // Synthèse journalière : une ligne par jour et par agent.
        $syntheseIndex = [];
        foreach ($ventes as $vente) {
            $date = date('Y-m-d', strtotime((string) ($vente['date_vente'] ?? $dateDebut)));
            $id = (int) ($vente['created_by'] ?? 0);
            $key = $date . ':' . $id;
            if (!isset($syntheseIndex[$key])) {
                $nom = trim(($vente['agent_prenom'] ?? '') . ' ' . ($vente['agent_nom'] ?? ''));
                $syntheseIndex[$key] = [
                    'date' => $date,
                    'agent_id' => $id,
                    'agent_nom' => $nom !== '' ? $nom : 'Système',
                    'caisses_sorties' => 0,
                    'caisses_vendues' => 0,
                    'montant_ventes' => 0,
                    'nombre_ventes' => 0,
                ];
            }
            $syntheseIndex[$key]['caisses_vendues'] += (float) ($vente['total_caisses'] ?? 0);
            $syntheseIndex[$key]['montant_ventes'] += (float) ($vente['total_ttc'] ?? 0);
            $syntheseIndex[$key]['nombre_ventes']++;
        }

        $missionParams = [
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ];
        $missionAgentClause = '';
        if ($filterAgentId) {
            $missionAgentClause = ' AND COALESCE(v.agent_responsable_id, m.created_by) = :agent_id';
            $missionParams['agent_id'] = $filterAgentId;
        }
        $sorties = $this->db->fetchAll(
            "SELECT DATE(m.date_depart) AS date_rapport,
                    COALESCE(v.agent_responsable_id, m.created_by) AS agent_id,
                    CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, '')) AS agent_nom,
                    COALESCE(SUM(GREATEST(
                        COALESCE(mc.caisses_chargees, 0),
                        COALESCE(ms.caisses_mouvements, 0)
                    )), 0) AS caisses_sorties
             FROM missions m
             JOIN vehicules v ON v.id = m.vehicule_id
             LEFT JOIN users u ON u.id = COALESCE(v.agent_responsable_id, m.created_by)
             LEFT JOIN (
                SELECT mission_id, SUM(quantite_caisses) AS caisses_chargees
                FROM mission_chargements
                GROUP BY mission_id
             ) mc ON mc.mission_id = m.id
             LEFT JOIN (
                SELECT ms.reference_id AS mission_id,
                       SUM(ABS(ms.quantite) / COALESCE(NULLIF(p.bouteilles_par_caisses, 0), 24)) AS caisses_mouvements
                FROM mouvements_stock ms
                JOIN produits p ON p.id = ms.produit_id
                WHERE ms.reference_type = 'mission'
                  AND ms.quantite < 0
                  AND (
                      ms.motif LIKE 'Chargement véhicule pour mission %'
                      OR ms.motif LIKE 'Mission de ristourne %'
                      OR ms.motif LIKE 'Modification mission %'
                  )
                GROUP BY ms.reference_id
             ) ms ON ms.mission_id = m.id
             WHERE DATE(m.date_depart) BETWEEN :date_debut AND :date_fin
               AND m.statut <> 'annulee'
               AND COALESCE(m.type_mission, 'vente') = 'vente'
               {$missionAgentClause}
             GROUP BY DATE(m.date_depart), COALESCE(v.agent_responsable_id, m.created_by),
                      u.prenom, u.nom",
            $missionParams
        );
        foreach ($sorties as $sortie) {
            $date = (string) ($sortie['date_rapport'] ?? $dateDebut);
            $id = (int) ($sortie['agent_id'] ?? 0);
            $key = $date . ':' . $id;
            if (!isset($syntheseIndex[$key])) {
                $nom = trim((string) ($sortie['agent_nom'] ?? ''));
                $syntheseIndex[$key] = [
                    'date' => $date,
                    'agent_id' => $id,
                    'agent_nom' => $nom !== '' ? $nom : 'Système',
                    'caisses_sorties' => 0,
                    'caisses_vendues' => 0,
                    'montant_ventes' => 0,
                    'nombre_ventes' => 0,
                ];
            }
            $syntheseIndex[$key]['caisses_sorties'] += (float) ($sortie['caisses_sorties'] ?? 0);
        }

        $syntheseJournaliere = array_values($syntheseIndex);
        usort($syntheseJournaliere, static function ($a, $b) {
            return [$a['date'], $a['agent_nom']] <=> [$b['date'], $b['agent_nom']];
        });
        $agentsConcernes = [];
        foreach ($syntheseJournaliere as $ligneSynthese) {
            $agentsConcernes[(int) ($ligneSynthese['agent_id'] ?? 0)] = true;
        }

        $agents = $this->db->fetchAll(
            "SELECT id, CONCAT(COALESCE(prenom, ''), ' ', COALESCE(nom, '')) AS nom_complet
             FROM users
             WHERE actif = 1
             ORDER BY nom, prenom"
        );

        return [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'agentId' => $filterAgentId,
            'agents' => $agents,
            'syntheseJournaliere' => $syntheseJournaliere,
            'ventesParAgent' => $ventesParAgent,
            'totalCa' => $totalCa,
            'totalVentes' => $totalVentes,
            'totalCaisses' => $totalCaisses,
            'nbAgents' => count($agentsConcernes),
        ];
    }
}

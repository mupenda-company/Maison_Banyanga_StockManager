<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Dépenses</title>
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .print-container {
                box-shadow: none !important;
                border: none !important;
            }
        }
        
        .print-container {
            max-width: 900px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #374151;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .company-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .document-title {
            font-size: 1.25rem;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .filters-info {
            background: #F9FAFB;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            color: #6B7280;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #F9FAFB;
            border-radius: 6px;
            border: 1px solid #E5E7EB;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
        }
        
        .stat-value.total {
            color: #DC2626;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        
        thead th {
            background: #F3F4F6;
            padding: 0.75rem 0.5rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            border-bottom: 2px solid #E5E7EB;
        }
        
        tbody td {
            padding: 0.75rem 0.5rem;
            font-size: 0.875rem;
            border-bottom: 1px solid #E5E7EB;
            color: #111827;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .amount-cell {
            text-align: right;
            font-weight: 600;
            color: #DC2626;
        }
        
        .date-cell {
            white-space: nowrap;
        }
        
        .category-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .category-Transport { background: #DBEAFE; color: #1E40AF; }
        .category-Carburant { background: #FEF3C7; color: #92400E; }
        .category-Maintenance { background: #FED7AA; color: #9A3412; }
        .category-Restauration { background: #D1FAE5; color: #065F46; }
        .category-Autres { background: #E5E7EB; color: #374151; }
        
        .footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            font-size: 0.75rem;
            color: #9CA3AF;
        }
        
        .description-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Action Buttons (hidden when printing) -->
    <div class="no-print flex justify-center gap-4 mb-6">
        <button onclick="window.print()" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2h2a2 2 0 002-2z"/>
            </svg>
            Imprimer
        </button>
        <a href="<?= url('depenses') ?>" class="btn btn-secondary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </a>
    </div>

    <!-- Print Content -->
    <div class="print-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name" x-text="window.NOM_ENTREPRISE || '<?= APP_NAME ?>'"><?= APP_NAME ?></div>
            <div class="document-title">Liste des Dépenses</div>
        </div>

        <!-- Filters Info -->
        <?php if (!empty($filters['date_debut']) || !empty($filters['date_fin']) || !empty($filters['categorie'])): ?>
        <div class="filters-info">
            <strong>Filtres appliqués :</strong>
            <?php if (!empty($filters['date_debut'])): ?>Du <?= date('d/m/Y', strtotime($filters['date_debut'])) ?> <?php endif; ?>
            <?php if (!empty($filters['date_fin'])): ?>Au <?= date('d/m/Y', strtotime($filters['date_fin'])) ?> <?php endif; ?>
            <?php if (!empty($filters['categorie'])): ?>Catégorie: <?= htmlspecialchars($filters['categorie']) ?> <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-label">Total</div>
                <div class="stat-value total"><?= format_money_dual($stats['total_depenses'] ?? 0) ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Nombre</div>
                <div class="stat-value"><?= $stats['nb_depenses'] ?? 0 ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Date</div>
                <div class="stat-value"><?= date('d/m/Y') ?></div>
            </div>
        </div>

        <!-- Table -->
        <?php if (empty($depenses)): ?>
        <p style="text-align: center; color: #6B7280; padding: 2rem;">Aucune dépense trouvée</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Catégorie</th>
                    <th>Description</th>
                    <th style="text-align: right;">Montant</th>
                    <th>Enregistré par</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grandTotal = 0;
                foreach ($depenses as $d): 
                    $grandTotal += $d['montant'];
                ?>
                <tr>
                    <td class="date-cell"><?= date('d/m/Y', strtotime($d['date_depense'])) ?></td>
                    <td>
                        <span class="category-badge category-<?= htmlspecialchars($d['categorie']) ?>">
                            <?= htmlspecialchars($d['categorie']) ?>
                        </span>
                    </td>
                    <td class="description-cell" title="<?= htmlspecialchars($d['description']) ?>">
                        <?= htmlspecialchars($d['description']) ?>
                    </td>
                    <td class="amount-cell"><?= format_money_dual($d['montant']) ?></td>
                    <td><?= htmlspecialchars(($d['created_by_prenom'] ?? '') . ' ' . ($d['created_by_nom'] ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>Document généré le <?= date('d/m/Y à H:i') ?> - Total: <?= format_money_dual($grandTotal) ?> - <?= count($depenses) ?> dépense(s)</p>
        </div>
    </div>

    <script>
        // Auto-print on page load (optional - remove if not desired)
        // window.addEventListener('load', function() { window.print(); });
    </script>
</body>
</html>

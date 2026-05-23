<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail Dépense #<?= $depense['id'] ?></title>
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
            max-width: 800px;
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
        
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .detail-item {
            padding: 1rem;
            background: #F9FAFB;
            border-radius: 6px;
            border: 1px solid #E5E7EB;
        }
        
        .detail-label {
            font-size: 0.875rem;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
        }
        
        .detail-value.amount {
            font-size: 1.5rem;
            color: #DC2626;
        }
        
        .description-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #F9FAFB;
            border-radius: 6px;
            border: 1px solid #E5E7EB;
        }
        
        .description-label {
            font-size: 0.875rem;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            margin-bottom: 0.5rem;
        }
        
        .description-text {
            font-size: 1rem;
            color: #111827;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .footer-info {
            font-size: 0.875rem;
            color: #6B7280;
        }
        
        .signature-section {
            text-align: center;
            min-width: 200px;
        }
        
        .signature-line {
            border-top: 2px solid #374151;
            padding-top: 0.5rem;
            margin-top: 2rem;
            font-size: 0.875rem;
            color: #6B7280;
        }
        
        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .category-Transport { background: #DBEAFE; color: #1E40AF; }
        .category-Carburant { background: #FEF3C7; color: #92400E; }
        .category-Maintenance { background: #FED7AA; color: #9A3412; }
        .category-Restauration { background: #D1FAE5; color: #065F46; }
        .category-Autres { background: #E5E7EB; color: #374151; }
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
            <div class="document-title">Fiche de Dépense</div>
        </div>

        <!-- Details Grid -->
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Numéro</div>
                <div class="detail-value">DEP-<?= str_pad($depense['id'], 5, '0', STR_PAD_LEFT) ?></div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Date de la dépense</div>
                <div class="detail-value"><?= date('d/m/Y', strtotime($depense['date_depense'])) ?></div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Catégorie</div>
                <div class="detail-value">
                    <span class="category-badge category-<?= htmlspecialchars($depense['categorie']) ?>">
                        <?= htmlspecialchars($depense['categorie']) ?>
                    </span>
                </div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Montant</div>
                <div class="detail-value amount"><?= format_money_converted($depense['montant']) ?></div>
            </div>
        </div>

        <!-- Description -->
        <div class="description-section">
            <div class="description-label">Description / Détails</div>
            <div class="description-text"><?= nl2br(htmlspecialchars($depense['description'])) ?></div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-info">
                <div>Enregistré par : <?= htmlspecialchars(($depense['created_by_prenom'] ?? '') . ' ' . ($depense['created_by_nom'] ?? '')) ?></div>
                <div>Date d'enregistrement : <?= date('d/m/Y à H:i', strtotime($depense['created_at'] ?? 'now')) ?></div>
            </div>
            <div class="signature-section">
                <div class="signature-line">Signature</div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print on page load (optional - remove if not desired)
        // window.addEventListener('load', () => { window.print(); });
    </script>
</body>
</html>
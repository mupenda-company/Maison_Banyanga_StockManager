<?php

class Billetage extends Model
{
    protected $table = 'billetages';
    protected $fillable = ['reference_type', 'reference_id', 'devise', 'coupure', 'quantite', 'montant_base', 'taux_change', 'created_by'];

    public function totalBase(array $billetage): float
    {
        $total = 0.0;
        foreach ($billetage as $devise => $lignes) {
            $devise = strtoupper((string) $devise);
            if (!in_array($devise, ['CDF', 'USD'], true) || !is_array($lignes)) {
                continue;
            }

            foreach ($lignes as $coupure => $quantite) {
                $montant = max(0, (float) $coupure) * max(0, (int) $quantite);
                $total += convert_money($montant, $devise, get_base_devise());
            }
        }

        return round($total, 2);
    }

    public function saveForReference(string $referenceType, int $referenceId, array $billetage, ?int $createdBy = null): void
    {
        $this->db->query(
            "DELETE FROM {$this->table} WHERE reference_type = :type AND reference_id = :id",
            ['type' => $referenceType, 'id' => $referenceId]
        );

        foreach ($billetage as $devise => $lignes) {
            $devise = strtoupper((string) $devise);
            if (!in_array($devise, ['CDF', 'USD'], true) || !is_array($lignes)) {
                continue;
            }

            foreach ($lignes as $coupure => $quantite) {
                $coupure = (float) $coupure;
                $quantite = (int) $quantite;
                if ($coupure <= 0 || $quantite <= 0) {
                    continue;
                }

                $montant = $coupure * $quantite;
                $this->create([
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'devise' => $devise,
                    'coupure' => $coupure,
                    'quantite' => $quantite,
                    'montant_base' => convert_money($montant, $devise, get_base_devise()),
                    'taux_change' => get_taux_change(),
                    'created_by' => $createdBy,
                ]);
            }
        }
    }
}

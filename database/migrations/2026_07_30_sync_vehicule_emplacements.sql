-- Synchronise les emplacements mobiles créés avant la correction du 30/07/2026.
-- Cette migration est idempotente et ne supprime aucune donnée historique.

START TRANSACTION;

UPDATE emplacements e
INNER JOIN vehicules v ON v.emplacement_id = e.id
SET e.code = CONCAT('VEH-', REPLACE(TRIM(v.immatriculation), ' ', '')),
    e.nom = CONCAT('Véhicule ', TRIM(v.immatriculation)),
    e.capacite = COALESCE(v.capacite, 0),
    e.actif = IF(v.actif = 1, 1, e.actif),
    e.updated_at = NOW()
WHERE e.type = 'mobile';

-- Les anciens emplacements d'un véhicule supprimé ne sont désactivés que
-- lorsqu'ils sont vides, afin de ne jamais masquer un ancien stock à régulariser.
UPDATE emplacements e
INNER JOIN vehicules v ON v.emplacement_id = e.id
SET e.actif = 0,
    e.updated_at = NOW()
WHERE e.type = 'mobile'
  AND v.actif = 0
  AND NOT EXISTS (
      SELECT 1
      FROM stocks s
      WHERE s.emplacement_id = e.id
        AND (
            COALESCE(s.quantite_pleine, 0) <> 0
            OR COALESCE(s.quantite_vide, 0) <> 0
            OR COALESCE(s.caisses_pleine, 0) <> 0
            OR COALESCE(s.caisses_vide, 0) <> 0
        )
  );

COMMIT;

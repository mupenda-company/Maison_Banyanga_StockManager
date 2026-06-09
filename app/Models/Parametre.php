<?php
/**
 * Modèle Parametre
 */

class Parametre extends Model
{
    protected $table = 'parametres';
    protected $fillable = ['cle', 'valeur', 'type'];
    
    /**
     * Récupérer un paramètre par clé
     */
    public function get($cle, $defaut = null)
    {
        $result = $this->db->fetchColumn(
            "SELECT valeur FROM {$this->table} WHERE cle = :cle",
            ['cle' => $cle]
        );
        
        return $result !== false ? $result : $defaut;
    }
    
    /**
     * Définir un paramètre
     */
    public function set($cle, $valeur, $type = 'text')
    {
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE cle = :cle",
            ['cle' => $cle]
        );
        
        if ($exists) {
            return $this->db->update(
                $this->table,
                ['valeur' => $valeur],
                'cle = :cle',
                ['cle' => $cle]
            );
        } else {
            return $this->create([
                'cle' => $cle,
                'valeur' => $valeur,
                'type' => $type
            ]);
        }
    }
    
    /**
     * Récupérer tous les paramètres
     */
    public function getAll()
    {
        $params = $this->all();
        $result = [];
        
        foreach ($params as $param) {
            $result[$param['cle']] = $param['valeur'];
        }
        
        return $result;
    }
    
    /**
     * Récupérer les paramètres de personnalisation
     */
    public function getPersonnalisation()
    {
        return [
            'nom_entreprise' => $this->get('nom_entreprise', 'Bralima'),
            'logo' => $this->get('logo', ''),
            'couleur_primaire' => $this->get('couleur_primaire', '#3B82F6'),
            'adresse' => $this->get('adresse', ''),
            'telephone' => $this->get('telephone', ''),
            'contact' => $this->get('contact', ''),
            'email_contact' => $this->get('email_contact', ''),
            'rccm' => $this->get('rccm', ''),
            'id_nat' => $this->get('id_nat', ''),
            'nif' => $this->get('nif', ''),
            'numero_compte' => $this->get('numero_compte', ''),
            'devise' => $this->get('devise', 'CDF'),
            'taux_change' => $this->get('taux_change', '2800'),
            'taux_tva' => $this->get('taux_tva', '16'),
            'autoriser_interchange_emballages' => $this->get('autoriser_interchange_emballages', '1')
        ];
    }
    
    /**
     * Convertir un montant selon la devise
     */
    public function convertMontant($montant, $fromDevise = 'USD', $toDevise = 'CDF')
    {
        $taux = floatval($this->get('taux_change', '2800'));
        
        if ($fromDevise === 'USD' && $toDevise === 'CDF') {
            return $montant * $taux;
        } elseif ($fromDevise === 'CDF' && $toDevise === 'USD') {
            return $montant / $taux;
        }
        
        return $montant;
    }
    
    /**
     * Formater un montant selon la devise principale
     */
    public function formatMontant($montant, $devise = null)
    {
        $devisePrincipal = $devise ?? $this->get('devise', 'CDF');
        
        if ($devisePrincipal === 'USD') {
            return number_format($montant, 2, '.', ',') . ' USD';
        }
        
        return number_format($montant, 0, ',', ' ') . ' CDF';
    }
}

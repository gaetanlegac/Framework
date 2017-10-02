<?php
namespace BDD\Requete {
    /**
     * Requête de sélection
     */
    class Get extends Base {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        const Type = "Get";
        public
            // Obligatoires
            $BDDs = [ \Config\BDD\Principale ],
            $Colonnes,
            // Optionnelles
            $Where, $Order, $Limite
        ;
        /* -----------------------------------------------------------------
        > CONSTRUCTEUR
        ----------------------------------------------------------------- */
        function __construct( $Table, $Colonnes, $Objet = false ) {
            $this->Table        = $Table;

            $this->Objet        = $Objet;
            if ($Objet) $this->BDDs = $Objet::GetBDDs();
            // Les requêtes de sélection ne doivent comporter qu'une seule base de données
            $this->BDDs = array_slice($this->BDDs, 0, 1);

            $this->Colonnes     = Formater\Select($Colonnes, $Objet);
        }
        /* -----------------------------------------------------------------
        > CLAUSES
        ----------------------------------------------------------------- */
        public function Quand( $Conditions, array $Valeurs = [] ) : Get {
            $this->Where    = Formater\Where($Conditions, $Valeurs, $this->Objet);
            $this->Valeurs  = $Valeurs;

            return $this;
        }
        public function LimiterA( int $Nombre ) : Get {
            $this->Limite = $Nombre; return $this; }

        /* -----------------------------------------------------------------
        > REQUETE
        ----------------------------------------------------------------- */
        public function GetRequete() {
            return $this->GenRequete([
                "SELECT" => $this->Colonnes, "FROM" => $this->Table, "WHERE" => $this->Where, "ORDER BY" => $this->Order, "LIMIT" => $this->Limite
            ]);
        }

        /**
         * Retourne un tableau des données retournées par les requetes Get
         */
        function Donnees() {
            $RetourReq = $this->Exec();

            // Récupèration des résultats sous forme d'un tableau associatif
            $Resultats = $RetourReq->fetchAll(\PDO::FETCH_ASSOC);
            // Conversion pour l'ORM
            if (is_array($Resultats))
                $this->FormaterResultats($Resultats);

            // Retour du tableau associatif, adapté à l'importation par objet
            return $Resultats;
        }

        public function FormaterResultats( &$Resultats ) {
            // Conversion des données de chaque résultat
            foreach ($Resultats as &$Donnees)
                $Donnees = ($this->Objet)
                    ? Formater\DonneesBDDversObjet($this->Objet, $Donnees)
                    : $Donnees;

            // Force le tableau associatif si on ne demande qu'un résultat
            if ($this->Limite === 1 && !TblAssociatif($Resultats) && count($Resultats) > 0)
                $Resultats = $Resultats[0];
        }
        /* -----------------------------------------------------------------
        > STATIQUE
        ----------------------------------------------------------------- */
        // Unifie plusieurs objets de requete Get
        public static function Union($Gets) {
            return new Union($Gets);
        }
    }

    /**
     * Requête de vérification de l'existance d'un élement dans la base de données
     */
    class Existance extends Base {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        const Type = "Existance";
        public
            // Obligatoires
            $BDDs = [ \Config\BDD\Principale ],
            $Where
        ;
        /* -----------------------------------------------------------------
        > CONSTRUCTEUR
        ----------------------------------------------------------------- */
        function __construct( $Table, $Conditions, $Objet = false ) {
            $this->Table        = $Table;
            $this->Objet        = $Objet;

            $this->Where    = Formater\Where($Conditions, $this->Valeurs, $Objet);

            if ($Objet) $this->BDDs = $Objet::GetBDDs();
            // Les requêtes de sélection ne doivent comporter qu'une seule base de données
            $this->BDDs = array_slice($this->BDDs, 0, 1);
        }

        /* -----------------------------------------------------------------
        > REQUETE
        ----------------------------------------------------------------- */
        public function GetRequete() {
            return "SELECT EXISTS(SELECT 1 FROM ". $this->Table ." WHERE ". $this->Where ." LIMIT 1) as Existant";
        }

        /**
         * Retourne un tableau des données retournées par les requetes Get
         */
        function Donnees() {
            $RetourReq = $this->Exec();

            // Récupèration des résultats sous forme d'un tableau associatif
            $Resultats = $RetourReq->fetchAll(\PDO::FETCH_ASSOC);
            // Conversion pour l'ORM
            if (is_array($Resultats))
                $this->FormaterResultats($Resultats);

            // Retour du tableau associatif, adapté à l'importation par objet
            return $Resultats;
        }
    }

    /**
     * Union de deux requêtes de sélection en fusionnant leur requete et leur données
     */
    // A REVOIR
    /*class Union extends BaseMulti {
        public function GetRequete() {
            return implode(" UNION ALL ", parent::GetRequete());
        }
    }*/

    /**
     * Union de deux requêtes de sélection en fusionnant leur requete et leur données
     */
    class Multi extends BaseMulti {
        public function GetRequete() {
            return implode(";", parent::GetRequete());
        }

        /**
         * Retourne un tableau des données retournées par les requetes Get
         */
        function Donnees( $ActionDonnees = false ) {
            $RetourReq = $this->Exec();

            $Retour = [];
            $IndexRequete = 0;
            // Parcours de chaque résultat de requete
            do {
                // Récupération de l'objet correspondant au résultat
                $NomRequete = $this->Get[$IndexRequete];

                if (isset( $NomRequete )) {
                    // Récupèration des résultats sous forme d'un tableau associatif
                    $Resultats = $RetourReq->fetchAll(\PDO::FETCH_ASSOC);
                    // Conversion pour l'ORM
                    if (is_array($Resultats)) {
                        // Détermine l'objet de requete correspoindant au résultat actuel
                        // Grâce à son nom correspondant à l'index précedement incrémenté
                        $Requete = &$this->Requetes[ $NomRequete ];

                        // Conversion des données de chaque résultat
                        switch ($Requete::Type) {
                            case "Get":
                                $Requete->FormaterResultats($Resultats); break;
                            case "Existance":
                                $Resultats = boolval($Resultats[0]["Existant"]);
                                break;
                        }

                        if ($ActionDonnees) $ActionDonnees( $NomRequete, $Resultats );
                        $Retour[$NomRequete] = $Resultats;
                    }
                    $IndexRequete++;
                }
            // Passage au prochain résultat de requete
            } while( $RetourReq->nextRowset() );

            // Retour du tableau associatif, adapté à l'importation par objet
            return $Retour;
        }
    }

    /**
     * Requête d'insertion
     */
    class Nouv extends Base {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        const Type = "Nouv";
        public
            // Obligatoires
            $BDDs = [ \Config\BDD\Principale ],
            $Donnees, $RefValeurs
        ;
        /* -----------------------------------------------------------------
        > CONSTRUCTEUR
        ----------------------------------------------------------------- */
        function __construct( $Table, $Donnees, $Objet = false ) {
            $this->Table        = $Table;
            $this->Objet        = $Objet;
            $this->RefValeurs   = Formater\Egalites($Donnees, $this->Valeurs, $Objet, true);
            $this->Donnees   = implode(", ", array_keys($this->Valeurs));

            if ($Objet) $this->BDDs = $Objet::GetBDDs();
        }

        /* -----------------------------------------------------------------
        > REQUETE
        ----------------------------------------------------------------- */
        function GetRequete() {
            return $this->GenRequete([
                "INSERT INTO" => $this->Table, "" => "(".$this->Donnees.")", "VALUES" => "(".$this->RefValeurs.")"
            ]);
        }
        function GetID() {
            $this->Exec();
            return \BDD\Connexions::$Liste[ $this->BDDs[0] ]->lastInsertId();
        }
    }

    /**
     * Requête de mise à jour
     */
    class Maj extends Base {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        const Type = "Maj";
        public
            // Obligatoires
            $BDDs = [ \Config\BDD\Principale ],
            $Donnees,
            // Optionnelles
            $Where
        ;
        /* -----------------------------------------------------------------
        > CONSTRUCTEUR
        ----------------------------------------------------------------- */
        function __construct( $Table, $Donnees, $Objet = false ) {
            $this->Table        = $Table;
            $this->Objet        = $Objet;
            $this->Donnees      = Formater\Egalites($Donnees, $this->Valeurs, $Objet, false);

            if ($Objet) $this->BDDs = $Objet::GetBDDs();
        }
        /* -----------------------------------------------------------------
        > CLAUSES
        ----------------------------------------------------------------- */
        public function Quand( $Conditions, array $Valeurs = [] ) : Maj {
            $this->Where    = Formater\Where($Conditions, $Valeurs, $this->Objet);
            $this->Valeurs  = array_merge($this->Valeurs, $Valeurs);

            return $this;
        }

        /* -----------------------------------------------------------------
        > REQUETE
        ----------------------------------------------------------------- */
        function GetRequete() {
            return $this->GenRequete([
                "UPDATE" => $this->Table, "SET" => $this->Donnees, "WHERE" => $this->Where
            ]);
        }
    }

    /**
     * Requête de suppression
     */
    class Suppr extends Base {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        const Type = "Suppr";
        public
            // Obligatoires
            $BDDs = [ \Config\BDD\Principale ],
            // Optionnelles
            $Where
        ;
        /* -----------------------------------------------------------------
        > CONSTRUCTEUR
        ----------------------------------------------------------------- */
        function __construct( $Table, $Criteres, $Objet = false ) {
            $this->Table    = $Table;
            $this->Objet    = $Objet;
            $this->Where    = Formater\Where($Criteres, $this->Valeurs, $this->Objet);

            if ($Objet) $this->BDDs = $Objet::GetBDDs();
        }

        /* -----------------------------------------------------------------
        > REQUETE
        ----------------------------------------------------------------- */
        function GetRequete() {
            return $this->GenRequete([
                "DELETE FROM " => $this->Table, "WHERE" => $this->Where
            ]);
        }
    }
}
?>

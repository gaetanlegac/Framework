<?php
namespace BDD\Requete {
    /**
     * Méthodes & informations essentielles pour créer une requête
     */
    class Base {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        public
            // Obligatoire
            $Table, $BDDs,
            // Optionnel
            $Objet = false, // False si on fait une requete sur une table. Sinon, contient le nom de la classe de l'objet concerné
            $Valeurs = []
        ;

        /* -----------------------------------------------------------------
        > MÉTHODES DE CONSTRUCTION DES CLAUSES DE REQUETE
        ----------------------------------------------------------------- */
        /**
         * Bases de données concernées
         * @param string|array $BDDs Nom ou liste des base de données concernées
         */
        public function Depuis($BDDs) {
            // Force $BDD à être un tableau
            if (is_string($BDDs)) $BDDs = [$BDDs];
            // Valorisations & retour
            $this->BDDs = $BDD; return $this;
        }

        /**
         * Génère la requête sous forme de chaine à partir d'une association de clauses
         * @param array     $Clauses Clauses spécifiques au type de requete demandé
         * @return string   Requete finale à executer
         */
        public function GenRequete(array $Clauses) : string {
            return implode_asso(" ", " ", array_filter($Clauses));
        }

        /**
         * Execute la requete demandée de manière préparée et récupère les valeurs brutes du résultat
         */
        public function Exec() {
            $Requete = $this->GetRequete();
            // Pas de BDD, pas de requete
            if (empty($this->BDDs))
                throw new \Exception("Au moins une base de donnée doit être indiquée pour la requete.");

            dbg($Requete . " avec les valeurs " . implode_asso("=", ", ", $this->Valeurs) . " sur " . implode(", ", $this->BDDs) );

            $Retour = false;

            // Parcours chaque BDD concernée par la requête
            foreach ($this->BDDs as $BDD) {
                // Ouverture de la connexion sur la BDD si pas déjà fait
                \BDD\Connexions::Ouvrir($BDD);

                // Gestion des erreurs PDO
                try {
                    $BddReq = &\BDD\Connexions::$Liste[$BDD];

                    // Execution de la requete
                    $RetourReq = $BddReq->prepare($Requete);
                    $RetourReq->execute($this->Valeurs);

                    // Si une valeur de retour n'a pas encore été définie
                    if (!$Retour) $Retour = $RetourReq;

                } catch (\PDOException $e) {
                    throw new \Exception ("Une erreur s'est produite sur la base de données $IdBDD :<br>" . $e->getMessage());
                }
            }

            // Retour
            return $Retour;
        }
    }
    /**
     * Unifie un tableau de plusieurs requete en une seule
     */
    class BaseMulti extends Base {
        //use DonneesGet;
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        protected   $Requetes = [], // Liste des objets de requete indéxés par un libellé
                    $Get = [];      // Liste des index des objets de requete de type Get dans $Requetes

        /* -----------------------------------------------------------------
        > CONSTRUCTEUR
        ----------------------------------------------------------------- */
        function __construct( array $requetes ) {
            $this->Requetes = $requetes;
        }

        public function GetRequete() {
            $RequetesSQL = [];

            // Parcours de chaque requete
            foreach ($this->Requetes as $Nom => $Requete) {
                // Référencement si Get
                if (in_array( $Requete::Type, ["Get", "Existance"] ))
                    $this->Get[] = $Nom;

                // BDDs
                if (empty($this->BDDs))
                    $this->BDDs = $Requete->BDDs;
                elseif ($this->BDDs !== $Requete->BDDs)
                    throw new Exception("Les bases de données ciblées par les requetes doivent êtes identiques dans un ensemble de requêtes.");

                // Requete
                $RequetesSQL[] = $Requete->GetRequete();
                // Valeurs
                $this->Valeurs = array_merge( $this->Valeurs, $Requete->Valeurs );
            }

            return $RequetesSQL;
        }
    }
}

/**
 * Outils de formatage des différents composants posssibles dans une requête MySQL
 */
namespace BDD\Requete\Formater {
    /**
     * Formate la clause select d'une requete à partir d'une liste de propriétés ou d'une chaine de références
     * @param  [type]  $Colonnes Liste des colonnes à sélectionner. Si vide ou défini à false, on sélectionnera toutes les colonnes
     * @param  boolean $Objet    Nom de l'objet concerné
     * @return string            Clause de selection avec les noms de colonne
     */
    function Select($Colonnes, $Objet = false) : string {
        // Aucune propriété spécifiée, on les sélectionne toutes
        if (empty($Colonnes) || !$Colonnes)
            $Colonnes = "*";
        // Chaine avec références de propriétés:    "{ID}, UPPER({Titre})"
        elseif (is_string($Colonnes) && $Objet !== false )
            $Colonnes = TraiterReferences($Colonnes, $Objet);
        // Tableau linéaire de propriétés:          ["ID", "Titre"]
        elseif (is_array($Colonnes) && !TblAssociatif($Colonnes)) {
            // Si on traite un objet, remplacement des noms de propriétés par leur équivalent en colonne
            if ($Objet !== false)
                foreach ($Colonnes as &$Propriete)
                    $Propriete = $Objet::PropVersCol($Propriete);
            // Conversion en chaine
            $Colonnes = implode(", ", $Colonnes);
        } else
            throw new \Exception("Le format des colonnes est incorrect.");
        // Retour
        return $Colonnes;
    }

    /**
     * Trouve les références de propriété d'un objet et les remplace par le nom de colonne correspondant
     * @param  string $Chaine Chaine contenant les références
     * @param  string $Objet  Nom de l'objet contenant les propriétés
     * @return string         Chaine avec le noms de colonnes
     */
    function TraiterReferences(string $Chaine, string $Objet) : string {
        // Supprime les espaces parasites
        $Chaine = trim($Chaine, " \t\n\r\0\x0B,");
        // Extraction de chaque proprieté sous forme de tableau afin de les parcourir un à un
        $Proprietes = [];
        preg_match_all("/({){1}([^{}])+(}){1}/", $Chaine, $Proprietes);
        $Proprietes = $Proprietes[0];
        // Parcours des proprietes
        foreach ($Proprietes as $Propriete)
            $Chaine = str_replace($Propriete, $Objet::PropVersCol(trim($Propriete, "{}")), $Chaine);
        // Retour
        return $Chaine;
    }

    /**
     * Formate la clause WHERE
     * @param array|string  $Conditions Association d'égalités ($Valeurs valorisé automatiquement) ou une chaine de conditions avec références
     * @param array         $Valeurs    Liste des valeurs associées à la chaine de conditions. Sera transformé en association :Propriété => Valeur
     * @param bool|string   $Objet      Objet concerné
     */
    function Where($Conditions, array &$Valeurs, $Objet) {
        $Where = "";
        // Si des égalités sont définies
        if (!empty($Conditions)) {
            // Chaine avec références de propriétés.    Ex: "{Propriete} LIKE %:Valeur%"
            if ( is_string($Conditions) && TblAssociatif($Valeurs) && $Objet !== false ) {
                // Traitement des références
                $Where = TraiterReferences($Conditions, $Objet);
                // Filtrage de chaque valeur
                foreach ($Valeurs as $Propriete => $Valeur) {
                    $Valeurs[/*":".*/$Propriete] = $Objet::EntreeBDD(trim($Propriete, ":"), $Valeur);
                    //unset($Valeurs[$Propriete]);
                }

            // Tableau associatif d'égalités            Ex: ["Propriete" => "Valeur"]
            // $Valeurs sera un tableau linéaire        Ex: ["Valeur"]
            } else
                $Where = Egalites($Conditions, $Valeurs, $Objet, false, " AND ");
        }
        // Retour
        return $Where;
    }

    function Egalites($Egalites, &$Valeurs, $Objet, $Insert = false, $Separateur = ", ") {
        $Retour = [];
        // Si des égalités sont définies
        if (TblAssociatif($Egalites)) {
            // Parcours de chaque égalité
            foreach ($Egalites as $Propriete => $Valeur) {
                // Si on est dans le contexte d'un objet, on converti le nom de la propriété en colonne
                // Sinon, $Propriete est déjà le nom de la colonne
                $Colonne = $Objet
                    ? $Objet::PropVersCol($Propriete)
                    : $Propriete;

                // S'il s'agit d'une requete d'insertion
                if ($Insert)
                    // On référence la colonne dans un tableau linéaire
                    $Retour[] = ":" . $Colonne;
                else
                    // Sinon on référence la colonne associée à une référence de valeur
                    $Retour[$Colonne] = ":" . $Propriete;

                // Référencement de la valeur
                // Si on est dans le contexte d'un objet, on applique les règles de filtrage propre à cet objet
                $Valeurs[/*":".*/$Propriete] = $Objet
                    ? $Objet::EntreeBDD($Propriete, $Valeur)
                    : $Valeur;
            }

            // S'il s'agit d'une requete insert
            if ($Insert)
                $Retour = implode(", ", $Retour);
            // Sinon
            else
                $Retour = implode_asso("=", $Separateur, $Retour);
        } else
            throw new \Exception("Le format de la requête est incorrect.");

        // Retour
        return $Retour;
    }

    // Converti une association de Colonnes => Valeur vers une association Propriete => Valeur, ou inversement
    function DonneesBDDversObjet($Objet, $Donnees) {
        $Retour = [];
        // Parcours de chaque donnée
        foreach ($Donnees as $Propriete => $Valeur) {
            $Propriete = $Objet::ColVersProp($Propriete);
            $Retour[$Propriete] = $Objet::SortieBDD($Propriete, $Valeur);
        }
        // Retour
        return $Retour;
    }
}
?>

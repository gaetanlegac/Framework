<?php
namespace ORM\Action {
    const
        Lister      = 1,
        Lire        = 2,
        Modifier    = 3,
        Creer       = 4,
        Supprimer   = 5
    ;
}

namespace ORM {
    use \Erreur\Objet as Erreur;

    abstract class Objet {
        /* -----------------------------------------------------------------
        > METHODES PRINCIPALES
        ----------------------------------------------------------------- */
        // Identification des données. Varient selon l'objet instancié
        protected           $id         = null,     // Identifiant unique de l'objet, utilisé pour faire le lien avec la ligne correspondante dans la base de données
                            $Maj        = []        // Liste des propriétés à mettre à jour dans la base de données
        ;
        // Propriétés INDISPENSABLES dont la valeur est OBLIGATOIREMENT initialisée dans CHAQUE CLASSE
        public static       $NomBDD         = "*",  // Dénomination de la base de données (définie dans config) sur laquelle la table correspondante se situe.
                                                    // Si la valeur est *, les enregistrements seront propagés sur toutes les bases de données
                            $TableBDD       = "";   // Nom de la table correspondante dans la base de données
        protected static    $PrefixeBDD     = "",   // Préfixe le nom de chaque colonne dans la base de données
                            $Proprietes     = [],    // Association des propriétés de l'objet à leurs options
                                /*  Options possibles sur les propriétés:
                                    - Type:         Correspond au nom du type de donnée qu'on vérifiera et forcera: Chaine, Entier, Decimal, Objet
                                    - Index:        Spécifie la priorité de la propriété dans la recherche. 1 premier, 0 ou false = pas de recherche
                                    - Obligatoire:  Si la propriété doit obligatoirement possèder une valeur (true ou false)
                                    - Unique:       Indique si la valeur de cette propriété est unique. Si oui, elle sera utilisée pour les vérifications d'existance
                                                    La valeur d'au moins 1 propriété unique devra  être renseignée
                                    - Tri:          Si la propriété doit être utilisée par défaut pour trier les résultats d'un listage ou d'une recherche
                                */
                            $RegleUnicite = ""
        ;
        // Etat de l'objet instancié
        public              $Existant   = false;    // Si l'objet est existant dans la base de données

        /* -----------------------------------------------------------------
        > FONCTIONS MAGIQUES
        ----------------------------------------------------------------- */
        // Si $Donnees est numérique ou une chaine, on le considère comme un identifiant qu'on utilisera pour importer les données via la BDD
        // Si $Donnees est un tableau associant:
        //      - Un nom de propriété à une valeur, on considère que c'est une tentative de création, on vérifie donc son existance.
        //      - Un nom de colonne à une valeur, on considère que c'est une importation via la BDD.
        // Si $ChargerViaDonnees = true, on importe les données de l'objet dont le champs unique correspond à celui spécifié dans $Donnees
        function __construct( $Donnees = NULL, $ViaBDD = false ) {
            // Importation des propriétés selon la donnée fournie
            if (is_array($Donnees) && !empty($Donnees))
                // Importation via un tableau de propriétés
                $this->ImporterDonnees($Donnees, $ViaBDD);
        }

        function __toString() {
            return strval($this->id);
        }

        /* -----------------------------------------------------------------
        > TYPES DE REQUETES POSSIBLES SUR LA BDD
        ----------------------------------------------------------------- */
        /**
         * Recherche les données des objets du même type que l'actuel dans la BDD
         * @param  array            $Colonnes Données  récupérer
         * @return BDDRequeteGet           Requete
         */
        final public static function Chercher( $Proprietes = [] ) : \BDD\Requete\Get {
            return new \BDD\Requete\Get( static::$TableBDD, $Proprietes, get_called_class() ); }

        final public static function Existance( $Conditions ) : \BDD\Requete\Existance {
           return new \BDD\Requete\Existance( static::$TableBDD, $Conditions, get_called_class() ); }

        final public static function Nouveau( $Donnees ) : \BDD\Requete\Nouv {
           return new \BDD\Requete\Nouv( static::$TableBDD, $Donnees, get_called_class() ); }

        final public static function Maj( $Donnees ) : \BDD\Requete\Maj {
           return new \BDD\Requete\Maj( static::$TableBDD, $Donnees, get_called_class() ); }

        final public static function Suppr( $Criteres ) : \BDD\Requete\Suppr {
           return new \BDD\Requete\Suppr( static::$TableBDD, $Criteres, get_called_class() ); }
        /* -----------------------------------------------------------------
        > FILTRES BDD
        ----------------------------------------------------------------- */
        // Corrige les données avant de les enregistrer dans la BDD
        final public static function EntreeBDD($Propriete, $Valeur) {
            // Récupère le type de la propriété (false = pas de type spécifique)
            $Type = static::GetOptionProp("Type", $Propriete);

            // Type prédéfini
            switch ($Type) {
                case "Tableau":
                    $Valeur = implode("+", array_filter($Valeur));
                    break;
                case "TableauAsso":
                    $Valeur = implode_asso(":", "+", $Valeur);
                    break;
            }
           // Retourne la valeur corrigée
           return $Valeur;
        }
        // Corrige les données venant de la BDD
        final public static function SortieBDD($Propriete, $Valeur) {
           // Récupère le type de la propriété (false = pas de type spécifique)
           $Type = static::GetOptionProp("Type", $Propriete);
           // Type prédéfini
           switch ($Type) {
               case "Tableau":
                   $Valeur = array_filter(explode("+", $Valeur));
                   break;
               case "TableauAsso":
                   $Valeur = explode_asso(":", "+", $Valeur);
                   break;
           }
           // Retourne la valeur corrigée
           return $Valeur;
        }
        /* -----------------------------------------------------------------
        > RECHERCHE SUR LA BDD
        ----------------------------------------------------------------- */
        static public function TrouverDonnees( $Critere, bool $Strict = true ) {
            // Si $Critere est un ID, on le converti en tableau
            if (!is_array($Critere))
                $Critere = ["id" => $Critere];
            // Execution de la requete
            $Trouve = static::Chercher()->Quand($Critere)->LimiterA(1)->Donnees();

            // Aucun résultat, erreur
            if ( !$Trouve )
                throw new Erreur\Introuvable();
            // Sinon, instanciation
            else
                return $Trouve;
        }

        static public function Trouver( $Critere ) {
            $Trouve = static::TrouverDonnees($Critere);
            return new static($Trouve, true);
        }

        public function Charger( $ID ) {
            $this->ImporterDonnees( static::TrouverDonnees($ID), true );
        }

        /**
         * Vérifie si un objet de la BDD possède une propriété unique dont la valeur est commune avec l'objet actuel, via les règles d'unicité.
         * @param array $DonneesCommunes Liste des propriétée en commun entre l'objet actuel et l'objet existant dans la BDD
         * @return mixed    False si inexistant
         *                  Un tableau associatif des données de l'objet existant
         */
        final protected function Existant( $ViaID = true, $ViaDonnees = true ) {
            /* ---------- Données de la requete ---------- */
            $Requetes = [];

            /* ---------- Construction de la requête ---------- */
            // Via l'ID
            if ($ViaID && !empty($this->id))
                $Requetes["ViaID"] = static::Existance([ "id" => $this->id ]);

            // Via les données
            if ($ViaDonnees && !empty( static::$RegleUnicite )) {
                $Conditions = static::$RegleUnicite;
                $ValeursCond = []; // Valeurs de comparaison

                // Transformation de la règle d'unité en requête SQL
                foreach (array_unique(explode(" ", $Conditions)) as $ElementCond) {
                    $ElementCondSQL;
                    switch ($ElementCond) {
                        // Opérateur logique
                        case "+": $ElementCondSQL = "AND"; break;
                        case "|": $ElementCondSQL = "OR"; break;
                        // Proprieté
                        default:
                            $ElementCondSQL = "({". $ElementCond ."}=:". $ElementCond ." AND {". $ElementCond ."} <> '' AND {". $ElementCond ."} IS NOT NULL)";
                            $ValeursCond[$ElementCond] = $this->{$ElementCond};
                            break;
                    }
                    $Conditions = str_replace($ElementCond, $ElementCondSQL, $Conditions);
                }
                // Ignore l'objet actuel s'il et existant dans la BDD
                if (!empty($this->id)) {
                    $Conditions = "(". $Conditions .") AND {id} <> :id";
                    $ValeursCond["id"] = $this->id;
                }
                // Construction de la requete
                $Requetes["ViaDonnees"] = static::Chercher()->Quand($Conditions, $ValeursCond)->LimiterA(1);
            }

            /* ---------- Execution & retour ---------- */
            // Execution de la requete
            $Existant = \BDD\Requetes($Requetes)->Donnees();

            // Existance via l'ID
            if ( $ViaID && !$Existant["ViaID"] )
                return false;
            // Existance via les données
            elseif ( $ViaDonnees ) {
                if (empty($Existant["ViaDonnees"]))
                    return false;
                else
                    return $Existant["ViaDonnees"];
            } else
                return true;
        }

        // Parcours et effectue une action sur chaque objet dépendant de l'objet actuel
        final public function Dependances(callable $Action) {
            // Parcours les classes associées à l'ORM
            foreach(get_declared_classes() as $Classe) {
                if (Objet::ReconnaitreObjet($Classe, false)) {
                    $Egalites = []; // Egalites de sélection des objets dépendants

                    // Parcours de chaque propriété de la classe ayant pour type la classe actuelle
                    foreach ($Classe::GetPropsViaOption("Type", get_called_class()) as $PropDep)
                        array_push($Egalites, "{".$PropDep."}=:id");

                    // Si des propriétés dépendantes sont trouvées
                    if (count($Egalites) > 0) {
                        // Récupère les données de chaque objet dépendant
                        $Egalites = implode(" OR ", $Egalites);
                        $Deps = $Classe::Chercher()->Quand( $Egalites, [":id" => $this->id] )->Donnees();
                        foreach ($Deps as $Dep) {
                            // Instanciation
                            $ObjetDep = new $Classe($Dep, true);
                            // Lancement de l'action
                            $Action($ObjetDep);
                        }
                    }
                }
            }
        }

        /* -----------------------------------------------------------------
        > CHARGEMENT DES DONNÉES
        ----------------------------------------------------------------- */
        // Importation d'un tableau associatif des propriétés
        final public function ImporterDonnees($Donnees, $DepuisBDD = false) {
            // Si on importe les données depuis la BDD
            if ($DepuisBDD)
                // On sait que l'objet est existant
                $this->Existant = true;
            // Sinon, si un ID a été spécifié dans les données, on charge d'abord les données de la BDD
            elseif (array_key_exists("id", $Donnees))
                if (!empty($Donnees["id"]))
                    $this->Charger( $Donnees["id"] );

            // Parcours & allocation des données
            foreach ($Donnees as $Propriete => $Valeur) { // Parcours de chaque cle du tableau
                // Si la propriété existe dans la classe
                if (property_exists(get_called_class(), $Propriete)) {
                    // Si la propriété est référencée dans $Proprietes
                    if (array_key_exists($Propriete, static::$Proprietes)) {
                        // Et que sa valeur est non-nulle
                        if ( isset($Valeur) ) {
                            // Correction en entrée
                            $Valeur = $this->FiltreEntree($Propriete, $Valeur);
                            // Donnée conforme aux contraintes et différente de l'actuelle
                            if (($DepuisBDD || static::VerifDonnee($Propriete, $Valeur)) && $this->{$Propriete} !== $Valeur) {
                                // Allocation
                                $this->{$Propriete} = $Valeur;
                                // Si les données ne proviennent pas de la bdd
                                if (!$DepuisBDD)
                                    // Ajout dans le tableau de maj. Penser à la faire pour chaque changement de données provenant de l'exterieur
                                    array_push($this->Maj, $Propriete);
                            } // VerifDonnee retournera une exception détaillée en cas de problème
                        }
                    } else // Sinon ça va pas
                        throw new \Exception("La propriété $Propriete n'est pas référencée dans \$Proprietes situé dans la classe " . get_called_class());
                } else{ // Sinon erreur
                     throw new \Exception("La propriété $Propriete n'est pas présente dans la classe " . get_called_class()); }
            }

            // Vérification des permissions
            if ( !$this->ObtenirPermission( Action\Lire ) )
                throw new Erreur\Permission( Action\Lire );
        }

        /* -----------------------------------------------------------------
        > CONVERSION DES DONNÉES
        ----------------------------------------------------------------- */
        // Converti le nom d'une propriété de classe en nom de colonne BDD
        final public static function PropVersCol($Propriete) {
            // Vérification de l'existance de la propriété
            if (array_key_exists($Propriete, static::$Proprietes)) {
                $ParamsProp = static::$Proprietes[$Propriete];
                return static::$PrefixeBDD . (array_key_exists("Colonne", $ParamsProp) ? $ParamsProp["Colonne"] : $Propriete);
            } else {
                throw new \Exception("La propriété $Propriete n'existe pas dans la classe " . get_called_class());
                return false;
            }
        }
        // Converti le nom d'une colonne BDD en nom de propriété de classe
        final public static function ColVersProp($Colonne) {
            // Retire le préfixe BDD
            $ColonneBrute = str_replace(static::$PrefixeBDD, "", $Colonne);

            // Colonne portant un nom de propriété différent
            $Propriete = static::GetPropsViaOption("Colonne", $ColonneBrute);

            // Si une propriété a été reconnue à partir de la colonne brute
            if (count($Propriete) > 0)
                $Propriete = $Propriete[0];
            // Sinon, peut-être qu'une propriété du même nom existe ?
            elseif (property_exists(get_called_class(), $ColonneBrute))
                $Propriete = $ColonneBrute;
            else
                throw new \Exception("Impossible d'associer la colonne $Colonne de la table ". static::$TableBDD ." à la propriété correspondante dans la classe " . get_called_class());

            // Retour
            return $Propriete;
        }
        // Prend en charge les chemins composés de propriété/sous-propriété et les converties en jointure sql si besoin
        // Ex: Materiel.Client.ID: On veut l'ID du Client correspondant au Materiel rattaché à l'objet actuel
        final public static function CheminVersSQL($Chemin) {
            die("CheminVersSQL a revoir");
            // Sépare chaque élement du chemin
            // Logiquement, le premier élement est la propriété de l'objet actuel et le dernier est la propriété donc la valeur est recherchée
            $Elements = explode(".", $Chemin);
            $Propriete = $Elements[0];
            // Vérif si la récursion (=requete imbriquée) est nécessairee (la propriete est associée à un chemin)
            if (count($Elements) >= 2) { // Constitution d'une requete imbriquée
                // Inversement de l'ordre
                $Elements = array_reverse($Elements);
                // Construction des sous requetes si besoin. Sinon on retourne tout simplement le nom de la colonne sur la table concernée
                $Requete = "";
                foreach ($Elements as $Index => $Element) {
                    if ($Index > 0) { // Parcours des propriétés faisant référence à un objet seulement (tous les élements sauf le dernier)
                        // Récupère l'élement suivant afin de faire référence à son ID dans dans la requete
                        $Suivant = $Elements[$Index - 1];
                        // Ajout de la sous-requete
                        $Requete .= "(SELECT " . $Element::PropVersCol($Suivant) . " FROM " . $Element::$TableBDD . " WHERE " . $Element::PropVersCol("ID") . "=";
                        // S'il s'agit de la dernière, finalisation avec la propriété de l'objet actuel qui réfère à l'element actuellement parcouru
                        if ($Index+1 === count($Elements))
                            $Requete .= static::PropVersCol($Element);
                    }
                }
                // Fermeture de toutes les parenthèses
                $Requete .= str_repeat(")", count($Elements)-1);
                // Retour
                return $Requete;
            } else
                return static::PropVersCol($Propriete);
        }

        /* -----------------------------------------------------------------
        > CORRECTION DES DONNÉES
        ----------------------------------------------------------------- */
        // Corrige les données entrantes dans la classe
        protected static function FiltreEntree($Propriete, $Valeur) {
            // Récupère le type de la propriété (false = pas de type spécifique)
            $Type = static::GetOptionProp("Type", $Propriete);

            // Si c'est un objet reconnu par l'ORM
            if (static::ReconnaitreObjet($Type, false))
                // Instanciation
                $Valeur = $Type::Trouver($Valeur);

            // Retourne la valeur corrigée
            return $Valeur;
        }
        // Corrige les données sortantes de la classe
        protected static function FiltreSortie($Propriete, $Valeur) {


            // Retourne la valeur corrigée
            return $Valeur;
        }

        /* -----------------------------------------------------------------
        > OPÉRATIONS DIRECTES SUR LES PROPRIÉTÉS
        ----------------------------------------------------------------- */
        // Retourne une liste des propriétés dont l'option $Option est définie à $Valeur
        final public static function GetPropsViaOption($Option, $Valeur = true) {
            // Liste des propriétés correspondantes
            $ListeProps = [];
            // Parcours de chaque propriété
            foreach (static::$Proprietes as $Propriete => $Infos)
                if (static::GetOptionProp($Option, $Propriete) == $Valeur)
                    array_push($ListeProps, $Propriete);
            // Retour
            return $ListeProps;
        }

        // Retourne la valeur d'une option d'une propriétés
        final public static function GetOptionProp(string $Option, string $Propriete) {
            //var_dump($Option);var_dump($Propriete);echo "<br>";
            // Récupère les options de la propriété
            $OptionsProp = static::$Proprietes[$Propriete];
            // Vérifie l'existance de l'option
            if (array_key_exists($Option, $OptionsProp))
                // Retourne la valeur de l'option
                return $OptionsProp[$Option];
            else
                return false;
        }

        // Vérifie si les données requises sont bien présentes (un des champs unique + tous les champs obligatoires)
        // Si $Tableau est renseigné, la vérification portera sur ce tableau de données.
        // Sinon ($Tableau = false), elle s'effectuera sur les propriétés de l'objet
        final protected function VerifIntegrite($Tableau = false) {
            // Si aucun tableau de données est passé, on en exporte un depuis les propriétés de l'objet
            if (!$Tableau) $Tableau = $this->GetDonnees();

            // Vérification des propriétés OBLIGATOIRES
            foreach (static::GetPropsViaOption("Obligatoire") as $PropObligatoire) {
                // Si la propriété obligatoire ne se trouve pas dans le tableau ou qu'elle est vide
                if ( @!isset($Tableau[$PropObligatoire]) || $Tableau[$PropObligatoire] === "" )
                    // Erreur
                    throw new \Exception("Le champ $PropObligatoire doit obligatoirement être renseigné pour l'objet " . get_called_class() . " .");
            }
        }

        /**
         * Associe des propriétés de l'objet associées à leur valeur dans un tableau
         * @param  array   $Proprietes Liste des propriétés à retourner. Si vide, on retournera toutes les propriétés de l'objet
         * @param  boolean $Recursif   Détermine si les objets en propriétés doivent être convertis en tableau (true) ou si on doit récupérer leur ID uniquement (false)
         * @return array               Association des propriétés demandées à leur valeur
         */
        public function GetDonnees( array $Proprietes = [], bool $Recursif = false ) : array {
            // Si $Proprietes est vide, on récupère toutes les propriétés de l'objet
            if (empty($Proprietes))
                $Proprietes = array_keys(static::$Proprietes);

            // Récupèration de la valeur de chaque propriété
            $Donnees = [];
            foreach ($Proprietes as $Propriete) {
                $Valeur = $this->{$Propriete};
                if (isset($Valeur)) {
                    $Donnees[$Propriete] = $this->FiltreSortie($Propriete, $Valeur);
                    // S'il s'agit d'un objet
                    if (is_object($Valeur))
                        $Donnees[$Propriete] = $Recursif ? $Valeur->GetDonnees($Proprietes, $Recursif) : $Valeur->GetID();
                }
            }
            // Retour
            return $Donnees;
        }

        /**
         * Trouve les données communes l'instance actuelle et un autre objet du même type
         * @param  Objet/array  $Objet Objet/données à comparer
         * @return array        Donnees en commun
         */
        public function CommunAvec($Objet) : array {
            // Conversion en tableau de données si objet
            if (is_object($Objet))
                $Objet = $Objet->GetDonnees();
            // Retour des données en commun
            return array_intersect($this->GetDonnees(), $Objet);
        }

        // Accesseur dynamique
        final public function Get(string $Propriete) {
            if (array_key_exists($Propriete, static::$Proprietes))
                return $this->FiltreSortie($Propriete, $this->{$Propriete});
            else
                throw new \Exception("La propriété $Propriete n'est pas définie dans la classe " . get_called_class());
        }

        // Alloue une valeur à une propriete
        final public function Set($Propriete, $Valeur) {
            if (array_key_exists($Propriete, static::$Proprietes)) {
                $this->{$Propriete} = $this->FiltreEntree($Propriete, $Valeur);
                array_push($this->Maj, $Propriete);
            } else
                throw new \Exception("La propriété $Propriete n'est pas définie dans la classe " . get_called_class());
        }

        /* -----------------------------------------------------------------
        > EXPORTATION DES DONNÉES
        ----------------------------------------------------------------- */
        // Mise à jour si $Maj est non-nul et si:
        //      - L'objet possède un ID
        //          OU
        //      - Qu'il possède des identificateurs reconnues dans la bdd
        // Nouvel objet si aucun ID n'et défini et:
        //      - Qu'aucun objet existant ne partage les mêmes identificateurs
        // Sinon, l'objet existant sera retourné.
        public function Enregistrer() {
            // Traitement selon l'état d'existance
            if ( !empty($this->id) ) {
                // ===============================> MISE À JOUR <===============================
                // Vérification des permissions
                if ( !$this->ObtenirPermission( Action\Modifier ) )
                    throw new Erreur\Permission( Action\Modifier );

                // Mise à jour des données si on n'a pas demandé d'en créer un nouveau
                if (count($this->Maj) > 0) { // Si des données sont à mettre à jour
                    // On les récupère avec leurs valeurs
                    $DonneesMaj = $this->GetDonnees( $this->Maj );
                    // Vérification de l'intégrité des données
                    $this->VerifIntegrite();
                    // Vérification de l'existance
                    $Existant = $this->Existant();
                    if (!$Existant) {
                        // Execution de la requete. Les données à mettre à jour seulement sont passées, et filtrées pour l'entrée BDD
                        static::Maj($DonneesMaj)->Quand([ "id" => $this->id ])->Exec();
                        $this->Maj = [];
                    } else
                        throw new Erreur\DejaExistant( $this->CommunAvec($Existant) );
                } else
                    NOYAU::Debug("Aucune donnée à mettre à jour pour pour l'enregistrement ". get_called_class() ." N°". $this->id);
            } else { // Inexistant, on créé une nouvelle entrée dans la BDD.
                // =================================> CREATION <=================================
                // Vérification des permissions
                if ( !$this->ObtenirPermission( Action\Creer ) )
                    throw new Erreur\Permission( Action\Creer );

                // Vérification de l'integrité des données
                $this->VerifIntegrite();
                // Vérification de l'existance
                $Existant = $this->Existant();
                if (!$Existant) {
                    // Récupèration des données
                    $Donnees = $this->GetDonnees();
                    // Suppression de l'ID des données à insérer
                    unset($Donnees["id"]);
                    // Création de l'entrée
                    $this->id = static::Nouveau($Donnees)->GetID();
                    $this->Existant = true;
                } else
                    throw new Erreur\DejaExistant( $this->CommunAvec($Existant) );
            }

            return true;
        }

        // Vérifie si le format de chaque paramètre correspond
        // Retourne un booléen et déclenche une \Exception si une valeur n'est pas correcte
        protected function VerifDonnee($Propriete, $Valeur) : bool { return true; }

        // Supprime l'objet de la base de données en tenant compte des dépendances
        final public function Supprimer() {
            // Vérification des permissions
            if ( !$this->ObtenirPermission( Action\Supprimer ) )
                throw new Erreur\Permission( Action\Supprimer );

            // S'asssure de l'existance de l'objet
            if ( $this->Existant(true, false) ) {
                // Suppression des dépendances existantes
                $this->Dependances(function($Dep) {
                    $Dep->Supprimer();
                });
                // Suppression de l'objet en lui-même
                static::Suppr([ "id" => $this->id ])->Exec();
            } else
                throw new Erreur\Introuvable();
        }

        // #################################> ACCESSEURS <#################################
        // ================================> GET <================================
        // Retourne l'identifiant BDD de l'objet courant
        final public function GetID() { return $this->Get("id"); }
        // Retourne le nom de la base de données rattachée à la classe instanciée
        final static public function GetTableBDD() { return static::$TableBDD; }
        // Retourne la liste des noms des BDDs rattachés à l'objet courant
        final static public function GetBDDs() {
            // Toutes les bases disponibles
            if (static::$NomBDD == "*")
                return array_keys(\Config\BDD\Liste);
            // Bases spécifiques
            elseif (is_array(static::$NomBDD))
                return static::$NomBDD;
            // Base principale
            else
                return [ \Config\BDD\Principale ];

        }

        // #################################> AUTRES <#################################
        // Vérifie si une classe est prise en charge par l'ORM
        final public static function ReconnaitreObjet($Objet, $AffErreur = true) {
            if (!class_exists($Objet, false) || !is_subclass_of($Objet, "\\ORM\\Objet")) {
                if ($AffErreur)
                    throw new \Exception("Ce type d'objet n'est pas repertorié dans l'ORM");
                return false;
            } else
                return true;
        }

        // Vérifie si l'utilisateur actuel a le droit d'effectuer une action sur l'objet chargé
        protected function ObtenirPermission(int $Action) : bool { return true; }
    }
}
?>

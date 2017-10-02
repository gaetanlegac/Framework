<?php
namespace Route {
    // Constantes
    const   // Paramètrage
            RegexChemin = "._/-",  // Caractères autorisés dans le chemin de la route
            PrefixeUrl = "./?"
    ;

    // Namespaces
    use \Requete\Types      as Action;
    use \Controleur\Infos   as Controleur;

    // Fonction de raccourci
    function VersContenu( $Adresse ) {
        return ( new Infos($Adresse) )->Contenu;
    }

    // Informations
    // Quand utilisé en tant que fonction, retourne directement le controleur
    class Infos {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        public
            // Propriétés de la route actuelle
            //$Parametres = [],   // [array]      Parametres fournis dans l'adresse
            $Itineraire,        // [array]      Chemin de la route transposé en itinéraire
            $Adresse,           // [string]     Chemin de la route. Ex: /Article/10/Supprimer
            $Donnees,           // [array]      Données envoyées en post

            // Progression du parcours
            $Erreur = false,
            $Parcouru,          // [string]     Chemin parcouru par le résolveur de controleur

            $Contenu            //  [Controleur]    Objet de controleur qui a été déduit à partir des infos
                                //                  Ci dessus
        ;

        /* -----------------------------------------------------------------
        > METHODES PRINCIPALES
        ----------------------------------------------------------------- */
        function __construct( string $adresse, array $donnees = [], string $action = Action\GET ) {
            // Mémorisation des données principales
            $this->Adresse     = Filtres\Adresse( $adresse );
            $this->Itineraire  = GenItineraire( $this->Adresse );
            $this->Donnees     = Filtres\Donnees( $donnees );

            // En déduit le controleur
            $this->Contenu = $this->GetControleur( $action )->GetContenu();
        }

        /* -----------------------------------------------------------------
        > METHODES UTILISABLES DANS LES CONTROLEURS
        ----------------------------------------------------------------- */
        // Appelé dans un controleur via Rendre(), Réécrit une route
        // Récupère le controleur de la nouvelle route, relance le constructeur de $this->Contenu avec les données de ce constructeur
        // Redéfini toutes les propriétés de l'objet contenu actuel

        // Stockera eventuellement l'historique des routes précédentes en session
        function Deviation( $adresse ) {
            $ContenuDevia = (new Infos($adresse))->Contenu;
            $this->Contenu->Importer( $ContenuDevia );
            return call_user_func_array( $ContenuDevia->Generer, [] );
        }

        private function RedirigerVers( $adresse ) {
            if ( \Requete\Infos::$Ajax )
                return \Javascript\Redirection( PrefixeUrl . $adresse);
            else
                header("location: " . PrefixeUrl . $adresse);
        }

        /* -----------------------------------------------------------------
        > METHODES DE TRANSPOSITION VERS LE CONTROLEUR ADAPTÉ
        ----------------------------------------------------------------- */
        function GetControleur( string $TypeAction ) {
            /* ---------- Initialisation des données ---------- */
            $ActionLancement = NULL;

            // Trouve la carte comprenant le controleur
            $InfosBranche = $this->TrouverBranche( $this->Itineraire[0] );
            if (!empty($InfosBranche)) {
//var_dump($InfosBranche);die();
                // Extraction des données de la branche correspondante
                $Actions = $InfosBranche["Actions"];
                $Config = $InfosBranche["Config"];

                /* ------------ Recherche de la fonction appropriée ------------ */
                // Si on a trouvé un controleur direct (non-typé)
                if ( is_callable($Actions) ) {
                    if ( $this->FiltrerControleur($Actions) )
                        $ActionLancement = $Actions;
                // Sinon si la carte a été trouvée
                } elseif ( !empty($Actions) ) {
                    // Force $Carte à être un tableau
                    if (!is_array($Actions))
                        $Actions = [$Actions];
//var_dump($Actions);
                    // Si au moins 1 controlleur existe pour le type de la requete actuelle
                    if ( isset( $Actions[$TypeAction] ) ) {
                        // Entrée correspondant au type de requete
                        // Force le tableau
                        $Appels = is_array( $Actions[$TypeAction] )
                            ? $Actions[$TypeAction]
                            : [$Actions[$TypeAction]];
//var_dump($Appels);
                        // Recherche l'appel le plus pertinent par rapport à route
                        foreach ($Appels as $Appel) {
//var_dump($Appel);
                            if ( $this->FiltrerControleur($Appel) ) {
                                // Mémorisation de l'appel
                                $ActionLancement = $Appel;
                                // Fin de la boucle
                                break;
                            }
                        }
                    }
                }
            }
            /* ------------------- Retour ------------------- */
            return new Controleur( $ActionLancement, $this->Donnees, $Config );
        }

        /**
         * Trouve la branche comprenant l'action demandée
         * Et référence les données passées dans l'URL
         * @param  string $Nom Nom de la carte principale
         * @return array       Tableau composé d'un premier élement pouvant être:
         *                          - Un controleur GET direct si aucune configuration n'est définie
         *                          - Un tableau pouvant comprendre une configuration et
         *                              - Des controleurs
         *                              - Des tableaux surcharges de controleurs
         *                     Et d'un second représentant la configuration du controleur
         */
        function TrouverBranche( string $Nom ) {
            $CheminCarte = \Base\Controleurs . "/" . $Nom . ".php";

            // Vérification de l'existance de la carte de route
            if ( ! file_exists($CheminCarte) ) {
                $this->Parcouru .= "/" . $Nom;
                dbg("Le fichier de controleurs pour la carte ". $Nom . " est introuvable.");
                // Inexistant
                return false;
            }

            // Chargement du fichier de carte de la route
            $Actions = include( \Base\Controleurs . "/" . $Nom . ".php" );
            // Stoquera la somme récursive de la configuration
            $Config = [];

            // Recherche de la bonne route
            foreach ($this->Itineraire as $Branche) {
//echo "<b>Carte actuelle</b>: ";var_dump($Actions);echo "<br>";
                // Si une entrée directe n'est pas trouvée
                if (!@isset( $Actions[$Branche] )) {
//echo "Branche $Branche non-trouvée<br>";
                    // Peut-être qu'on souhaite accèder à une référence de paramètre ?
                    $RefTrouvee = false;
                    foreach ($Actions as $Entree => $SousCarte) {
//echo "Vérifications si $Entree correspond a une reference<br>";
                        // Vérification s'il s'agit d'une référence de paramètre
                        preg_match(\Templating\Regex\Reference, $Entree, $Correspondance);
                        if ($Entree == @$Correspondance[0]) {
//echo "Référence trouvée:$Entree";
                            // Association de la référence au nom de la branche actuelle
                            $this->Donnees[ $Correspondance[1] ] = \Route\Filtres\Parametre($Branche);
                            // Détermination de la carte actuelle via la référence
                            $Branche = $Entree;
                            $RefTrouvee = true;
                            break;
                        } // Sinon on continue de parcourir les entrées
                    }
                    // Si finalement aucune référence n'a été trouvée
                    // La route n'existe pas
                    if (!$RefTrouvee)
                        $this->Erreur = true;
                }

                // Pas d'erreur, on continue de parcourir les branches
                if (!$this->Erreur) {
                    // Parcours
                    $Actions = $Actions[$Branche];
                    $this->Parcouru .= "/" . $Branche;
                // Sinon, carte vide
                } else
                    $Actions = [];

                // Si on a encore affaire à un tableau
                if ( is_array($Actions) ) {
                    // Configuration (permissions, etc ...).
                    // Fusion et suppression de la carte de route
                    if (@isset( $Actions[\Controleur\Config]) ) {
                        $Config = array_merge($Config, $Actions[\Controleur\Config]);
                        unset($Actions[\Controleur\Config]);
                    }
                    // Gestionnaire d'erreurs
                    // Remplace l'existant
                    if (@isset( $Actions[\Controleur\Master]) ) {
                        $Config["Master"] = $Actions[\Controleur\Master];
                        unset($Actions[\Controleur\Master]);
                    }
                }
//echo "<b>Parcouru</b>: ".$this->Parcouru."<br><br>";
            }

            // Retour de la carte fusionnée avec la somme de la config
            return ["Actions" => $Actions, "Config" => $Config];
        }

        function FiltrerControleur(callable $Appel) : bool {
            // Récupèration des paramètres de la fonction
            $InfosAppel = new \ReflectionFunction($Appel);
            $ParametresAppel = $InfosAppel->getParameters();

            // Vérification du nombre de paramètres
            if (count($this->Donnees) <= count($ParametresAppel)) {
                $DonneesAppel = []; // Données dans l'ordre des paramètres de l'appel actuellement parcouru
                // Parcours de chaque paramètre
                foreach ($ParametresAppel as $Parametre) {
                    // Si le paramètre actuel est répertorié dans les données passées dans l'adresse ou en post
                    $ValeurInit = $ValeurParam = @$this->Donnees[ $Parametre->getName() ];
                    if ( isset($ValeurParam) ) {

                        // Si le paramètre a un type spécifique
                        if ($Parametre->hasType()) {
                            // On tente de convetir la valeur vers ce type.
                            $TypeOK = setType($ValeurParam, $Parametre->getType());
                            // Si la conversion a échoué, ou que la valeur convertie ne correspond pas à son équivalent en chaine ( = valeur initiale)
                            if (!$TypeOK || (empty($ValeurParam) && strval($ValeurParam) !== $ValeurInit) )
                                // Fonction rejetée
                                return false;
                        }

                        // Référencement de la valeur correspondante
                        $DonneesAppel[] = $ValeurParam;

                    // Sinon, s'il est Optionnel
                    } elseif ( $Parametre->isOptional() )
                        $DonneesAppel[] = $Parametre->getDefaultValue();
                    // Sinon, l'appel est rejeté
                    else return false;
                }
                // Aucun problème au niveau des paramètres
                // On réssigne les données dans l'ordre attendu par le controleur
                $this->Donnees = $DonneesAppel;
                // Et on valide le controleur
                return true;
            }
            // Appel ne correspondant pas à la requete
            return false;
        }

        public function ViaFichier(string $Chemin = NULL) {
            // Aucun chemin n'a été défini, on détermine le chemin du fichier automatiquement
            if (empty($Chemin))
                $Chemin = $this->Adresse;

            // Completion du chemin
            $Chemin = \Base\UI\Contenu . $Chemin;
            // Si un fichier direct existe
            if (file_exists( $Chemin . ".php" ))
                $Chemin .= ".php";
            // Sinon, s'il s'agit d'un répertoire
            elseif (file_exists( $Chemin . "/index.php" ))
                $Chemin .= "/index.php";
            // Sinon, On relance la fonction avec les données 404
            else
                return new \Dialogue\Erreur("Erreur technique", "Le fichier correspondant au contenu demandé n'a pas été trouvé sur le serveur.");

            // Chargement du contenu du fichier
            ob_start();
            require($Chemin);
            $ContenuFichier = ob_get_contents();
            ob_end_clean();

            return $ContenuFichier;
        }
    }

    /**
     * Décompose un chemin sous forme de chaine en tableau
     * @param  string $chemin Chemin à tansformer
     * @return array          Itineraire
     */
    function GenItineraire(string $adresse) : array {
        return explode("/", trim($adresse, "/"));
    }
}

/* -----------------------------------------------------------------
> METHODES DE FILTRAGE
----------------------------------------------------------------- */
namespace Route\Filtres {
    /**
     * Filtre & conformise l'adresse
     */
    function Adresse($adresse) : string {
        // Suppression des parasites en get (produits par jquery & certains hebergeurs)
        $PosParasite = @strpos("&", $adresse);
        if ($PosParasite > -1)
            $adresse = substr(0, $PosParasite);
        // Supprime les caractères autres que ceux autorisés
        $adresse = preg_replace("#[^a-zA-Z0-9". \Route\RegexChemin ."]#", "", $adresse);
        // Supprime les occurences interdites
        $adresse = preg_replace("#[(..)]#", "", $adresse);
        // Force le slash au début
        if (substr($adresse, 0, 1) !== "/")
            $adresse = "/" . $adresse;
        // Supprime les parasite (espaces, slashs, ...) en fin de chaine
        $adresse = rtrim($adresse, " /");
        // Renvoi sur la route par défaut si la requete est vide
        return (!empty($adresse) ? $adresse : \Config\Pages\Defaut);
    }

    function Donnees($donnees) : array {
        if (!empty($donnees) && !TblAssociatif($donnees))
            throw new Exception("Les données passées dans la route sont incorrectes.");
        else
            return $donnees;
    }

    function Parametre($valeur) {
        // Détermine un type approprié
        //settype($valeur, "integer");

        // Retour
        return $valeur;
    }
}
?>

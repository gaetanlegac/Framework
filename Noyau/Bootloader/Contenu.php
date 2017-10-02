<?php
namespace Contenu {
    class Infos {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        public
            // Informations principales
            $Titre, $Description,
            $Type = Type\Resultat,
            // Ressources
            $JS, $CSS,

            // Informations sur le contenu
            $Widgets = [],

            $Master,
            $Generer, $Donnees = [],

            // Information sur le rendu
            $ContenuBrut,
            $Erreurs = [] // Erreurs liées au contenu
        ;
        /* -----------------------------------------------------------------
        > CONSTRUCTEUR
        ----------------------------------------------------------------- */
        function __construct(callable $generer, array $donnees = [], array $config = []) {
//echo "<b>Config:</b><br>";var_dump($config);echo "<hr>";
//echo "Contenu: ";var_dump(func_get_args());echo "<hr>";

            // Importation de la configuration
            foreach ($config as $Propriete => $Valeur)
                // Si la propriété existe dans la classe, on mémorise
                if (property_exists($this, $Propriete))
                    $this->{$Propriete} = $Valeur;

            // Mémorisation des informations de lancement
            $this->Generer = $generer;
            $this->Donnees = $donnees;
        }

        public function __toString() {
            ob_start();
            $this->Rendre();
            $Rendu = ob_get_contents();
            ob_end_clean();

            return $Rendu;
        }

        public function Rendre($Format = Format\Brut, $ForcerType = false) {
            /* ---------- Execution du controleur ---------- */
            // Pré-Rendu (Chargement du contenu brut et gestion des dépendances de resources)
            try {
                // Execution du controleur
                // Si un objet est retourné, la méthode magique __toString() sera executée
                if (isset($this->Master))
                    $this->ContenuBrut = call_user_func_array($this->Master, [$this->Generer, $this->Donnees]);
                else
                    $this->ContenuBrut = call_user_func_array($this->Generer, $this->Donnees);

            } catch (\Exception $Exception) {
                // Erreur bloquante
                $this->Erreur("Une erreur de type ". get_class($Exception) ." s'est produite et n'a pas pu être gérée par le controleur: " . $Exception->getMessage() . "<br><br>" . $Exception->getTraceAsString(), true);
            }

            /* ---------- Rendu d'objets ---------- */
            // Forcage du type de rendu
            if ($ForcerType)
                switch ($ForcerType) {
                    case Type\Dialogue:
                        $this->ContenuBrut = new \Dialogue\Infos($this->Titre, $this->ContenuBrut);
                        break;
                }

            // Si le contenu retourné est un objet, on lance un
            if ( is_object($this->ContenuBrut) ) {
                $NomObjet = (get_parent_class($this->ContenuBrut)) ? get_parent_class($this->ContenuBrut) : get_class($this->ContenuBrut);
                // Traitement selon le type
                switch ($NomObjet) {
                    case "Dialogue\\Infos":
                        $this->ContenuBrut = $this->ContenuBrut->Rendre($Format);
                        $this->Type = \Contenu\Type\Dialogue;
                        break;
                    case "Javascript\\Code":
                        $this->ContenuBrut = $this->ContenuBrut->Rendre($Format);
                        $this->Type = \Contenu\Type\Javascript;
                        break;
                    default:
                        // Rendu en chaine via __toString
                        $this->ContenuBrut = strval( $this->ContenuBrut );
                        break;
                }
            }
            /* ---------- Formatage ---------- */
            // Rendu final
            switch ( $Format ) {
                case Format\Page:
                    // Extension du titre
                    if ( empty($this->Titre) )  $this->Titre = \Config\General\Titre;
                    else                        $this->Titre .= " - " . \Config\General\Titre;
                    // L'affichage sera appelé dans la structure de page
                    require(\Base\UI . "/Page.php");
                    break;
                case Format\Brut:
                    // Affichage direct
                    $this->Afficher();
                    break;
                case Format\Json:
                    $this->RendreJSON();
                    break;
            }

            // Nettoyage mémoire
            $this->ContenuBrut = NULL;
        }

        /**
         * Importe les propriétés d'un autre objet contenu
         * @param Contenu $NouveauContenu Objet dont les propriétés seront importées
         */
        public function Importer( \Contenu\Infos $NouveauContenu ) {
            foreach ( get_object_vars($NouveauContenu) as $Propriete => $Valeur )
                $this->{$Propriete} = $Valeur;
        }

        /* -----------------------------------------------------------------
        > METHODES DE RENDU
        ----------------------------------------------------------------- */
        // Imprime directement le contenu
        public function Afficher() {
            echo $this->ContenuBrut;
        }

        // Imprime le contenu au format json
        public function RendreJSON() {
            // Si le contenu est un objet, on le converti en chaine grâce à __toString
            echo json_encode(array_filter([
                /* ---------- Réponse principale ---------- */
                $this->Type => $this->ContenuBrut,

                /* -------- Données complémentaires ------- */
                "titre" => $this->Titre,
                "css"   => $this->Ressources("CSS", true),
                "js"    => $this->Ressources("JS", true)
            ]));
        }

        /* -----------------------------------------------------------------
        > METHODES DE RENDU HTML
        ----------------------------------------------------------------- */
        public function Ressources($Type, $Tableau = false) {
            $Retour = $Tableau ? [] : "";

            if (!empty($this->{$Type})) {
                // Suppression des doublons
                $this->{$Type} = array_unique($this->{$Type});
                // Parcours de chaque fichier css
                foreach ($this->{$Type} as $FichierRes) {
                    // Détermine le chemin complet
                    $CheminRes = constant("\\Base\\Res\\" . $Type) . "/" . $FichierRes .".". strtolower($Type);
                    // Création de la balise si le fichier est existant
                    if (file_exists(RACINE . $CheminRes)) {
                        if ($Tableau)
                            $Retour[] = $CheminRes;
                        else
                            switch ($Type) {
                                case "CSS":
                                    $Retour .= <<<EOT
<link rel="stylesheet" type="text/css" href="./{$CheminRes}"/>
EOT;
                                    break;
                                case "JS":
                                    $Retour .= <<<EOT
<script type="text/javascript" src="{$CheminRes}"></script>
EOT;
                                    break;
                            }
                    } else
                        $this->Erreur("Le fichier ". $Type ." ". $CheminRes ." associé à ce contenu est introuvable.");
                }
            }

            return $Retour;
        }

        // Référence ou Imprime un widget
        public function Widget( $Nom, $Route = false ) {
            // Référencement du widget
            if ($Route)
                $this->Widgets[$Nom] = $Route;
            // Rendu du widget
            else {
                $RouteWidget = $this->Widgets[$Nom];
                $Route = new \Route\Infos( $RouteWidget );
                $Route->Contenu->Rendre( \Contenu\Format\Brut );
            }
        }

        /* -----------------------------------------------------------------
        > METHODES D'ERREUR
        ----------------------------------------------------------------- */
        /**
         * Gère une erreur liée au contenu
         * @param mixed   $Message   Descriptif de l'erreur: Chaine ou Exception
         * @param boolean $Bloquante Défini l'importance de l'erreur, si celle-ci doit bloquer l'execution.
         *                           false : l'erreur est référencée dans la porpriété $Erreurs et sera affichée via la méthode AffErreurs()
         *                           true : le contenu est remplacé par un message d'erreur graphique et son type est référencé en tant qu'erreur
         */
        public function Erreur($Message, bool $Bloquante = false) {
            if ( $Bloquante ) {
                $this->Type = Type\Dialogue;
                $this->ContenuBrut = new \Dialogue\Erreur("Erreur", $Message);
            } else
                array_push($this->Erreurs, $Message);
        }

        // Affiche les erreurs
        public function AffErreurs() {
            // Parcours de chaque erreur
            foreach ($this->Erreurs as $Erreur)
                echo \UI::Erreur($Erreur);

            // Reinit des erreurs
            $this->Erreurs = [];
        }
    }
}

namespace Contenu\Format {
    const   Brut = 1,
            Json = 2,
            Page = 3;
}

namespace Contenu\Type {
    const   Dialogue    = "dialogue",
            Javascript  = "javascript",
            Resultat    = "resultat";
}

namespace Contenu\Modeles {
    /* -----------------------------------------------------------------
    > Page introuvable
    ----------------------------------------------------------------- */
    class Introuvable extends \Contenu\Infos {
        function __construct() {
            $this->Generer = function() {
                return new \Dialogue\Erreur("Contenu Introuvable", self::Fichier("Erreurs/404"));
            };
        }
    }
    /* -----------------------------------------------------------------
    > Page introuvable
    ----------------------------------------------------------------- */
    class Refuse extends \Contenu\Infos {
        function __construct() {
            if ( \Requete\Infos::$Ajax )
            // Requete AJAX : On retourne une fenetre de dialogue
                $this->Generer = function() {
                    return new \Dialogue\Infos("Se connecter", self::Fichier( \Config\Pages\Login ));
                };
            else {
            // Sinon, on retourne l'erreur sous forme brute
                $this->Titre = "Se connecter";
                $this->CSS = ["Login"];
                $this->Generer = function() {
                    return self::Fichier( \Config\Pages\Login );
                };
            }
        }
    }
}
?>

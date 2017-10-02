<?php
namespace {
    class Utilisateur extends \ORM\Objet {
        /* -----------------------------------------------------------------
        > PROPRIETES
        ----------------------------------------------------------------- */
        // Données statiques sur la classe
        protected static    $Proprietes = [  // Associe les propriétés de l'objet à leurs options
                                // Un ID est OBLIGATOIRE pour chaque objet, peut importe le type
                                "id" => [],
                                // Obligatoires
                                "login" => [
                                    "Obligatoire"   => true
                                ],
                                "mdp" => [
                                    "Obligatoire"   => true
                                ],
                                // Objets
                                "groupe" => [
                                    "Type"          => "Groupe"
                                ]
                            ],
                            $PrefixeBDD         = ""           // Préfixe du nom de chaque colonne dans la base de données
        ;                   // Informations BDD
        public static       $NomBDD         = [ \Config\BDD\Principale ],
                            $TableBDD       = "Utilisateurs"
        ;
        // Valeurs des propriétés
        protected           $Connecte   = false,        // Si l'utilisateur est celui actuellement connecté
                            $login,                     // Login de l'utilisateur
                            $mdp                        // Mot de passe hashé en sha1
        ;
        public              $groupe = false;       // Objet de permissions de l'utilisateur

        /* -----------------------------------------------------------------
        > MÉTHODES
        ----------------------------------------------------------------- */
        public static function Trouver($ID, bool $Strict = true) {
            // Si l'ID est 0, on instancie un visiteur
            if (empty($ID))
                return new Visiteur();
            else
                return parent::Trouver($ID, $Strict);
        }

        // Fonction de correction des propriétés en entrée
        // Fonction de vérification des données
        protected function VerifDonnee($Propriete, $Valeur) : bool {
            $Erreur = false;
            // Vérification selon la propriété
            switch ($Propriete) {
                case "mdp":
                    if (strlen($Valeur) < 8)
                        $Erreur = "Le mot de passe doit comporter au moins 8 caractères";
                    elseif (!preg_match("#[0-9]+#", $Valeur))
                        $Erreur = "Le mot de passe doit comporter au moins 1 chiffre";
                    elseif (!preg_match("#[a-zA-Z]+#", $Valeur))
                        $Erreur = "Le mot de passe doit comporter au moins 1 lettre";
                        break;
            }

            // Gestion du retour
            if ($Erreur) {
                throw new Exception($Erreur);
                return false;
            } else // Retourne BON si aucune exception n'est pas survenue
                return true;
        }

        protected function ObtenirPermission(int $Action) : bool {
            /*switch ($Action) {
                case \ORM\Action\Lire:
                    return $this->id
                    break
            }*/
            return true;
        }

        public function Connecte() : bool { return $this->Connecte; }
    }

    class Visiteur extends Utilisateur {
        function __construct() {
            $this->login = "Visiteur";
            $this->groupe = Groupe::Trouver(5);
        }
    }
}
?>

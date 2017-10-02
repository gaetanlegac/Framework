<?php namespace Controleur;

use \Requete\Types  as Action;
use \Requete        as Requete;

use \Contenu\Widgets\Formulaire as Formulaire;
use \Erreur\Objet   as Erreur;

return [
    "Login" => [
        Config => [
            "Titre" => "Se connecter",
            "CSS"   => ["General", "Login"],
            "Widgets" => [
                "FormulaireLogin" => "/Login/Formulaire"
            ]
        ],
        Action\GET => function() {
            return $this->ViaFichier();
        },
        "Formulaire" => [
            Action\GET => function() {
               return new Formulaire\Simple("FormulaireLogin", [
                   "login"  => [],
                   "mdp"    => [
                       "Label" => "Mot de passe",
                       "Type" => \Donnees\Type\MotDePasse
                   ]
               ],[
                   "Route"  => $this->Adresse,
                   "Objet"  => "Utilisateur"
               ]);
            },
            Action\POST => function($login, $mdp) {
                // Récupèration des informations de l'utilisateur demandé
                try {
                    $Retour = \Utilisateur::Trouver([
                       "login"  => $login,
                       "mdp"    => md5($mdp)
                    ]);
                } catch ( Erreur\Introuvable $Introuvable ) {
                    return new \Dialogue\Erreur("Connexion refusée", "Vos identifiants sont incorrects.");
                }

                $_SESSION["IdUtilisateur"] = $Retour->Get("id");
                return $this->RedirigerVers("/");
            }
        ],
        "off" => function() {
            \Session::Vider();
            $this->RedirigerVers("/");
        }
    ]
]
?>

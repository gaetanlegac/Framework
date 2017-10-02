<?php namespace Controleur;

use \Requete\Types  as Action;
use \Requete\Infos  as Requete;

return [
    "Erreurs" => [
        "401" => function() {
            header("HTTP/1.0 401 Authentification requise");
            // Déviation sur la page de login
            return $this->Deviation("/Login");
        },
        "404" => function() {
            header("HTTP/1.0 404 Introuvable");
            // Requete Ajax, on affiche un message d'erreur simple
            if ( Requete::$Ajax )
                return new \Dialogue\Erreur("Contenu Introuvable", "Le contenu demandé est introuvable: ");
            // Sinon, on affiche la page dédiée
            else
                return $this->ViaFichier();
        }
    ]
]
?>

<?php
/* ------------------------------------------------------
> GENERALITÉS DU SITE
------------------------------------------------------ */
namespace Config\General {
const
    // Détermine si les erreurs doient être affichées sur le site (true)
    // Ou si elles doivent être notifiées par email (false)
    Debug = true,
    // Titre général du site
    Titre = "Portfolio",
    // Adresse email sur laquelle seront envoyés les rapports d'erreur en mode production
    EmailAdmin = "contact@gaetan-legac.fr";
}

/* ------------------------------------------------------
> PAGES
------------------------------------------------------ */
namespace Config\Pages {
const
    // Page affichée par défaut si aucune n'est spécifiée dans l'URL
    Defaut = "/Panel",
    // Chemin de la page de login sur laquelle sera redirigé l'utilisateur s'il ne possède pas les permissions nécessaires pour visualiser la page demandée
    Login = "Login";
}

/* ------------------------------------------------------
> BASES DE DONNÉES
------------------------------------------------------ */
namespace Config\BDD {
const
    Liste = [
        "Portfolio" => [
            "Hote"      => "",
            "Nom"       => "",
            "Login"     => "",
            "Mdp"       => ""
        ]
    ],
    // Dénomination de la base de données qui sera utilisée par défaut pour les opérations de lecture (Listages, authentification, ...)
    Principale = "Portfolio";
}
?>

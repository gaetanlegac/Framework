<?php namespace Controleur;

use \Requete\Types  as Action;
use \Requete  as Requete;

return [
    "Admin" => [
        Config => [
            // Controleur
            "Permissions" => "Administrateur",
            // Contenu
            "CSS"    => ["Admin"],
            "Titre" => "Administration"
        ],
        "Logs" => [
            Config => [
                // Contenu
                "JS"   => ["Logs"]
            ],
            Action\GET => function() {
                return $this->ViaFichier();
            }
        ]
    ]
]
?>

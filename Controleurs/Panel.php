<?php namespace Controleur;
// Objets ORM
use \ORM\Objets\Categorie   as Categorie;
use \ORM\Objets\Article     as Article;
// Widgets
use \Contenu\Widgets\Liste  as Liste;
// Constantes de routage
use \Requete\Types  as Action;
use \Requete        as Requete;

return [
    "Panel" => [
        Config => [
            // Controleur
            "Permissions" => "Utilisateur",
            // Contenu
            "JS"    => ["Libs/trumbowyg", "Panel", "MagieBleu"],
            "CSS"   => ["Libs/trumbowyg", "General", "Panel", "Icones"]
        ],
        Action\GET => function() {
            // Importation des dépendances javascript des listes est indiqué par l'instanciation de widget
            $this->Contenu->Widget("ListeNavigation", "/Panel/ListeNavigation");
            // Chargement du fichier de vue correspondant
            // Le fichier est automatiquement déterminé à partir de l'adresse de la route
            return $this->ViaFichier();
        },
        "ListeNavigation" => function( $selection = [], $criteres = [], $conteneur = false ) {
            // Critères par défaut
            if (array_key_exists("categorie", $criteres))
                $IdCategorie = intval($criteres["categorie"]);
            else
                $IdCategorie = 0;

            // Message à afficher s'il n'y a aucun résultat
            $MsgVide = "<br>Faites un clic droit ici<br>pour créer un élement";
            if ($IdCategorie > 0) $MsgVide .= "<br>dans cette catégorie";

            // Création de la liste
            return new Liste\Simple("NavCategorie".$IdCategorie, [
                [Categorie::Chercher()->Quand(["categorie" => $IdCategorie])/*->LimiterA( Page(50, 2) )*/, [
                    "nom"   => true
                ]],
                [Article::Chercher()->Quand(["categorie" => $IdCategorie]), [
                    "titre" => true
                ]]
            ],[
                // Données à passer dans l'élement HTML
                "ajax"      => $this->Adresse,
                // Données seulement utilisées lors du rendu de la liste
                "Conteneur" => $conteneur,
                "MsgVide"   => $MsgVide,
                "Selection" => $selection
            ]);
        }
    ]
]
?>

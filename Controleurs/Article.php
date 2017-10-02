<?php namespace Controleur;

use \ORM\Objets\Article as Article;

use \Contenu\Widgets\Formulaire as Formulaire;

use \Requete\Types  as Action;
use \Erreur\Objet   as Erreur;

return [
    "Article" => [
        Master => function( callable $Controleur, $Donnees ) {
            try {
                return call_user_func_array($Controleur, $Donnees);
            } catch ( Erreur\Introuvable $Introuvable ) {
                return new \Dialogue\Erreur("Article introuvable", "L'article demandé est introuvable.");
            } catch ( Erreur\DejaExistant $Existant ) {
                return new \Dialogue\Erreur("Erreur de conflit", "Un article nommé « ". $Existant->Commun["titre"] ." » existe déjà dans cette catégorie.");
            }
        },
        "Nouveau" => [
            Action\POST => function($categorie) {
                $this->Contenu->Titre = "Nouvel article";
                return new Formulaire\Simple("NouvelArticle", [
                    "categorie" => [ "Type" => "caché", "Valeur" => $categorie ],
                    "titre" => []
                ], [
                    "Objet" => "Article",
                    "Route" => "/Article/Enregistrer"
                ]);
            }
        ],
        "Enregistrer" => [
            Action\POST => function($titre, $contenu = null, $id = null, $categorie = null) {
                // Instanciation de l'objet avec les données reçues en paramètre
                $Article = new Article( get_defined_vars() );
                // Enregistrement
                $Article->Enregistrer();
                return $Article->GetDonneesEditeur();
            }
        ],
        "{ID}" => [
            Action\GET => function(int $ID) {
                $Article = Article::Trouver($ID);
                return $Article->GetDonneesEditeur();
            },
            "Supprimer" => [
                Action\POST => function($ID) {
                    $Article = Article::Trouver($ID); // Instanciation de l'objet
                    $Article->Supprimer(); // Suppression
                }
            ],
            "Renommer" => [
                Action\GET => function($ID) {
                    $this->Contenu->Titre = "Renommer l'article";
                    $Article = Article::Trouver($ID);

                    return new Formulaire\Simple("ModifierArticle", [
                        "titre" => [ "Valeur" => $Article->Get("titre") ]
                    ],[
                        "Objet" => "Article"
                    ]);
                },
                Action\POST => function($ID, $titre) {
                    $Article = Article::Trouver($ID);
                    $Article->Set("titre", $titre);
                    return $Article->Enregistrer();
                }
            ],
            "Couper" => [
                Action\POST => function($ID, $Destination) {
                    $Article = Article::Trouver($ID);
                    $Article->Set("categorie", $Destination);
                    $Article->Enregistrer();
                    return $Article->GetDonnees();
                }
            ]
        ]
    ]
]
?>

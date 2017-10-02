<?php namespace Controleur;

use \ORM\Objets\Categorie as Categorie;
use \Contenu\Widgets\Formulaire as Formulaire;

use \Requete\Types  as Action;
use \Erreur\Objet   as Erreur;

return [
    "Categorie" => [
        "{ID}" => [
            "Nouvelle" => [
                Action\GET => function($ID) {
                    $this->Contenu->Titre = "Nouvelle catégorie";
                    return new Formulaire\Simple("NouvelleCategorie", ["nom"], [
                        "Objet" => "Categorie"
                    ]);
                },
                Action\POST => function($ID, $nom) {
                    $NouvCategorie = new Categorie([ "nom" => $nom, "categorie" => $ID ]);
                    $NouvCategorie->Enregistrer();
                    return $NouvCategorie->GetID();
                }
            ],
            "Supprimer" => [
                Action\POST => function($ID) {
                    $Categorie = Categorie::Trouver($ID); // Instanciation de l'objet
                    $Categorie->Supprimer(); // Enregistrement
                }
            ],
            "Renommer" => [
                // "Renommer la catégorie",
                Action\GET => function($ID) {
                    $this->Contenu->Titre = "Renommer la catégorie";
                    $Categorie = Categorie::Trouver($ID);

                    return new Formulaire\Simple("ModifierCategorie", [
                        "nom" => [ "Valeur" => $Categorie->Get("nom") ]
                    ],[
                        "Objet" => "Categorie"
                    ]);
                },
                Action\POST => function($ID, $nom) {
                    $Categorie = Categorie::Trouver($ID);
                    $Categorie->Set("nom", $nom);
                    return $Categorie->Enregistrer();
                }
            ],
            "Couper" => [
                Action\POST => function($ID, $Destination) {
                    $Categorie = Categorie::Trouver($ID);
                    $Categorie->Set("categorie", $Destination);
                    $Categorie->Enregistrer();

                    // Interpretation du retour
                    return $Categorie->GetDonnees();
                }
            ]
        ],
    ]
]
?>

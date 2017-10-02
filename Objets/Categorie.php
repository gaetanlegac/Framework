<?php namespace ORM\Objets;

class Categorie extends \ORM\Objet {
    /* -----------------------------------------------------------------
    > PROPRIÉTÉS
    ----------------------------------------------------------------- */
    // Données statiques sur la classe
    protected static    $Proprietes = [  // Associe les propriétés de l'objet à leurs options
                            // Un ID est OBLIGATOIRE pour chaque objet, peut importe le type
                            "id" => [],
                            "categorie" => [
                                "Type" => "Categorie"
                            ],
                            // Obligatoires
                            "nom" => [
                                "Obligatoire"   => true
                            ]
                        ],
                        $PrefixeBDD         = "",
                        // Une catégorie est unique quand aucun autre ne possède le même nom dans la même catégorie
                        // Le Nom ET la catégorie ne peuvent avoir la même valeur qu'un autre objet
                        $RegleUnicite = "nom + categorie"
    ;                   // Informations BDD
    public static       $NomBDD         = [ \Config\BDD\Principale ],
                        $TableBDD       = "Categories"
    ;
    // Valeurs des propriétés
    protected           $nom, // Nom de la catégorie
                        $categorie
    ;

    /* -----------------------------------------------------------------
    > MÉTHODES
    ----------------------------------------------------------------- */
    public static function Trouver($ID, bool $Strict = true) {
        // Si l'ID est 0, on instancie un visiteur
        if (empty($ID))
            return new Categorie([ "id" => 0 ]);
        else
            return parent::Trouver($ID, $Strict);
    }
}
?>

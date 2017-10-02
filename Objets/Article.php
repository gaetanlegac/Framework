<?php namespace ORM\Objets;

class Article extends \ORM\Objet {
    /* -----------------------------------------------------------------
    > PROPRIÉTÉS
    ----------------------------------------------------------------- */
    // Données statiques sur la classe
    protected static    $Proprietes = [  // Associe les propriétés de l'objet à leurs options
                            // Un ID est OBLIGATOIRE pour chaque objet, peut importe le type
                            "id" => [],
                            // Obligatoires
                            "titre" => [
                                "Obligatoire"   => true
                            ],
                            "contenu" => [],
                            "categorie" => [
                                "Obligatoire"   => true,
                                "Type"          => "Categorie"
                            ],
                            "creation" => [
                                "Type"          => "Temps"
                            ],
                            "modification" => [
                                "Type"          => "Temps"
                            ]
                        ],
                        $PrefixeBDD         = "",
                        // Un article est unique quand aucun autre ne possède le même nom dans la même catégorie
                        $RegleUnicite = "titre + categorie"
    ;                   // Informations BDD
    public static       $NomBDD         = [ \Config\BDD\Principale ],
                        $TableBDD       = "Articles"
    ;
    // Valeurs des propriétés
    protected           $titre,     // Titre de l'article
                        $contenu,   // Contenu de l'article
                        $categorie,  // Categorie de l'article
                        $creation,
                        $modification
    ;

    /* -----------------------------------------------------------------
    > METHODES
    ----------------------------------------------------------------- */
    public function Enregistrer() {
        // Remplissage automatique de la date de modification
        $this->Set("modification", \Donnees\Temps\Maintenant() );
        // Si on en créé un nouveau
        if (empty($this->id))
            // Définition de ladate de création
            $this->Set("creation", \Donnees\Temps\Maintenant() );

        // Enregistrement
        return parent::Enregistrer();
    }

    public function GetDonneesEditeur() {
        return array_merge(
            $this->GetDonnees(), [
                "creation" => \Donnees\Temps\FR( $this->creation ),
                "modification" => \Donnees\Temps\FR( $this->modification )
            ]
        );
    }
}
?>

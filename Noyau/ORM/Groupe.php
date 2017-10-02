<?php
namespace {
    // Classe de traitement des permissions d'un groupe d'utilisateurs
    class Groupe extends \ORM\Objet {
        /* -----------------------------------------------------------------
        > PROPRIETES
        ----------------------------------------------------------------- */
        // Données statiques sur la classe
        protected static    $Proprietes = [  // Associe les propriétés de l'objet à leurs options
                                // Un ID est OBLIGATOIRE pour chaque objet, peut importe le type
                                "id" => [
                                    "Colonne"       => "rang"
                                ],
                                // Obligatoires
                                "nom" => [
                                    "Obligatoire"   => true
                                ]
                            ],
                            $PrefixeBDD     = ""           // Préfixe du nom de chaque colonne dans la base de données
        ;                   // Informations BDD
        public static       $NomBDD         = [ \Config\BDD\Principale ],
                            $TableBDD       = "Groupes"
        ;
        // Valeurs des propriétés
        protected           $nom
        ;

        /* -----------------------------------------------------------------
        > ACCESSEURS
        ----------------------------------------------------------------- */
        // Retourne le rang associé aux permissions
        public function GetRang() { return intval($this->id); }
    }
}
?>

<?php
namespace Requete\Types {
const
    GET     = "/GET/",  // Type Get (Affichage classique)
    POST    = "/POST/"; // Type post (envoi de données. Ex: formulaire)
}

namespace Requete {
    // Informations
    class Infos {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        public static
            // Données principales de la requete
            $Adresse,           // [string] Adresse du contenu demandé. Comprend les paramètres GET
            $Donnees,           // [array]  Paramètres passés en post (vide si aucun
            $Type,              // [int]    Type de la requête (GET ou POST) paramètre)

            // Drapeaux
            $Ajax,          // [bool]   S'il s'agit d'une requete AJAX

            // Objets
            $Route,         // [Route]
            $Contenu        // [Contenu]
        ;

        /* -----------------------------------------------------------------
        > METHODES
        ----------------------------------------------------------------- */
        /**
         * Récupère les informations de la requtee spécifiée dans l'URL
         * Lance le routage pour en en déduire le controleur
         * Qui sera utilisé pour en déduire le contenu
         */
        public static function Traiter() {
            // Charge les information de la requete
            self::Charger();
            // Lance le routage
            self::$Route = new \Route\Infos( self::$Adresse, self::$Donnees, self::$Type );
            // Rendu final
            self::$Route->Contenu->Rendre( self::$Ajax
                ? \Contenu\Format\Json
                : \Contenu\Format\Page
            );
        }

        public static function Charger() {
            /* ---------- Adresse ---------- */
            global $_GET;
            $Requete = array_keys($_GET);
            // Récupère la clé de cette association
            self::$Adresse = (count($Requete) > 0) ? $Requete[0] : "";

            /* ----------- Type ----------- */
            self::$Type = constant("Requete\\Types\\" . $_SERVER['REQUEST_METHOD']);

            /* ----------- Ajax ----------- */
            self::$Ajax = ( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

            /* -------- Parametres -------- */
            if (self::$Type === Types\POST) {
                if ( self::$Ajax )
                    self::$Donnees = json_decode(file_get_contents("php://input"), true);
                else
                    self::$Donnees = $_POST;
            } else
                self::$Donnees = [];

            // Force le tableau
            if ( ! is_array( self::$Donnees ) )
                self::$Donnees = [];
        }
    }
}
?>

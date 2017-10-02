<?php
/**
 * Outils généralistes sur les bases de données
 */
namespace BDD {
    /**
     * Gère les connexions aux bases de données
     */
    class Connexions {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        public static $Liste = [];  // Liste des objets PDO

        /* -----------------------------------------------------------------
        > CONNEXIONS
        ----------------------------------------------------------------- */
        /**
         * Créé une instance PDO pour un libellé de base de données (si ce n'est pas déjà fait)
         * @param string $BDD Nom de la base de données sur laquelle il faut ouvrir la connexion
         */
        public static function Ouvrir( string $BDD ) {
            // Vérification de son existance dans la configuration
            if (!array_key_exists( $BDD, \Config\BDD\Liste ))
                throw new \Exception("Aucune base de données ne correspond au libellé « ". $BDD ." » dans la configuration.");

            // Si la connexion n'est pas déjà ouverte
            if (!array_key_exists( $BDD, Connexions::$Liste )) {
                dbg("Ouverture de la connexion sur la base de données « ". $BDD ." »");
                $Parametres = \Config\BDD\Liste[$BDD];

                // Chargement des données de connexion
                $Hote   = &$Parametres["Hote"];
                $Nom    = &$Parametres["Nom"];
                $Login  = &$Parametres["Login"];
                $Mdp    = &$Parametres["Mdp"];

                // Connexion persistante à la base de données et répertorisation dans la classe
                try {
                    Connexions::$Liste[$BDD] = new \PDO("mysql:host=$Hote; dbname=$Nom; charset=utf8", $Login, $Mdp, [
                        \PDO::ATTR_EMULATE_PREPARES => 1,
                        \PDO::ATTR_ERRMODE          => \PDO::ERRMODE_EXCEPTION
                    ]);
                } catch (\PDOException $e) {
                    die("Une erreur s'est produite lors de la connexion à la base de données libellée $ID : " . $e->getMessage() . "<br/>");
                }
            }
        }
    }

    class Table {
        private $Nom;

        function __construct($nom) {
            $this->Nom = $nom;
        }

        public function Get( $Proprietes = [] ) : \BDD\Requete\Get {
            return new \BDD\Requete\Get( $this->Nom, $Proprietes );
        }
        public function Nouv( $Donnees ) : \BDD\Requete\Nouv {
           return new \BDD\Requete\Nouv( $this->Nom, $Donnees );
        }
        public function Maj( $Donnees ) : \BDD\Requete\Maj {
           return new \BDD\Requete\Maj( $this->Nom, $Donnees );
        }
        public function Suppr( $Criteres ) : \BDD\Requete\Suppr {
           return new \BDD\Requete\Suppr( $this->Nom, $Criteres );
        }
    }

    /**
     * Raccourci d'instanciation d'une requête
     * @param string $Table Nom de la table concernée par la future requête
     */
    function BDD( string $NomTable ) {
        return new Table($NomTable);
    }

    /**
     * Raccourci d'instanciation d'un ensemble de requetes
     * @param string $Table Nom de la table concernée par la future requête
     */
    function Requetes( array $Requetes ) {
        return new Requete\Multi($Requetes);
    }
}
?>

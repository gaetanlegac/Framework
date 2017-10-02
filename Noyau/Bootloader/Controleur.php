<?php
namespace Controleur {
    // Entrées de configuration
    const   Config = "/CONFIG/",
            Master = "/MASTER/"
    ;

    // Classe de gestion des informations du controleur
    class Infos {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        private     $Lanceur, $Donnees,
                    $Permissions;

        /* -----------------------------------------------------------------
        > CONSTRUCTEUR
        ----------------------------------------------------------------- */
        /**
         * Mémorise la configuration dans les propriétés, vérifie les permissions
         * Et instancie un objet Contenu
         * @param mixed  $Lanceur Appel du controleur. S'il est null, il n'a pas été trouvé dans la route.
         * @param array  $Donnees Données à passer dans le lanceur
         * @param array  $Config  Configuration du constructeur
         */
        function __construct($lanceur, array $donnees = [], array $config = []) {
            // Allocation des propriétés
            $this->Lanceur      = $lanceur;
            $this->Donnees      = $donnees;
            $this->Config       = $config;

            // Importation de la configuration
            foreach ($this->Config as $Propriete => $Valeur)
                // Si la propriété existe dans la classe, on mémorise
                if (property_exists($this, $Propriete)) {
                    // Traitement de la valeur si besoin
                    switch ($Propriete) {
                        case "Permissions":
                            $Valeur = \Groupe::Trouver([ "nom" => $Valeur ]);
                            break;
                    }
                    $this->{$Propriete} = $Valeur;

                    // Exclusion de la propriété du tableau de configuration
                    // Pour ensuite le faire passer à la classe Contenu
                    unset($this->Config[$Propriete]);
                } // Sinon on laisse pour le contenu
        }

        /* -----------------------------------------------------------------
        > METHODES
        ----------------------------------------------------------------- */
        public function GetContenu() {
            if (!empty($this->Lanceur)) {
                // Détermine le contenu selon les permissions de l'utilisateur
                if ( $this->VerifierPermissions() ) {
                    // Chargement du contenu demandé
                    return new \Contenu\Infos( $this->Lanceur, $this->Donnees, $this->Config );
                } else
                    return \Route\VersContenu("/Erreurs/401");
            } else
                return \Route\VersContenu("/Erreurs/404");
        }

        private function VerifierPermissions() : bool {
            if ( isset( $this->Permissions ) )
                // Si les permissions de l'utilisateur sont insuffisantes
                if ( \Session::$Utilisateur->groupe->GetRang() > $this->Permissions->GetRang() ) {
                    \NOYAU::Debug("Accès refusé pour le contenu demandé");
                    // Fin de la méthode actuelle
                    return false;
                } else
                    return true;
            else
                return true;
        }
    }
}
?>

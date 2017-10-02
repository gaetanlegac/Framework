<?php
namespace {
    session_start();

    use function \BDD\BDD as BDD;

    class Session {
        /* -----------------------------------------------------------------
        > PROPRIÉTÉS
        ----------------------------------------------------------------- */
        public static
            $Utilisateur;   // Utilisateur actuellement connecté

        /* -----------------------------------------------------------------
        > METHODES
        ----------------------------------------------------------------- */
        public static function Demarrer() {
            $UtilisateurSession = @$_SESSION["IdUtilisateur"];
            try {
                self::$Utilisateur = \Utilisateur::Trouver($UtilisateurSession);
            } catch (Exception $e) {
                self::$Utilisateur = new Visiteur();
            }
        }

        public static function Vider() {
            session_unset();
            session_destroy();
        }
    }

    // Démarrage de la session
    Session::Demarrer();
}
?>

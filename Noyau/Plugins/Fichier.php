<?php namespace Plugins;

class Fichier {
    /* ------------------------------------------------------
    > STATIQUE
    ------------------------------------------------------ */
    public static function TrouverOuCreer($Chemin) {
        $Fichier = new Fichier($Chemin);

        if (!$Fichier->Existant())
            $Fichier->Ecrire();

        $Fichier->Ouvrir();
        return $Fichier;
    }

    /* ------------------------------------------------------
    > OBJET
    ------------------------------------------------------ */
    // Propriétés
    private $Chemin, $Instance;

    // Constructeur
    function __construct($chemin) {
        $this->Chemin = $chemin;
    }

    public function Existant() : bool {
        return file_exists($this->Chemin);
    }

    public function Ouvrir() {

        return $this;
    }

    public function Ecrire( $Contenu = "" ) {

        return $this;
    }

    public function Fermer() {

    }
}
?>

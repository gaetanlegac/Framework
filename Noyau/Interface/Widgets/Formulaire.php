<?php namespace Contenu\Widgets\Formulaire;
/**
 * Widget de formulaire
 */
abstract class Base {
    /* -----------------------------------------------------------------
    > PROPRIÉTÉS
    ----------------------------------------------------------------- */
    public  // Obligatoires
            $Nom,
            $Champs,
            $Route,

            // Optionnelles
            $Objet,
            $Ajax = false,
            $TexteValidation = "Valider",
            $InfosChampDefaut = [   // Informations des champs par défaut
                "Type"      => \Donnees\Type\Chaine,
                "Valeur"    => ""
            ]
    ;
    /* -----------------------------------------------------------------
    > CONSTRUCTEUR & METHODES MAGIQUES
    ----------------------------------------------------------------- */
    function __construct(string $Nom, array $Champs, $Options = []) {
        /* ---------- Allocation des propriétés ---------- */
        // Nom du formulaire
        $this->Nom          = $Nom;
        // Liste des champs, eventuellement associés à un surnom
        $this->Champs       = $Champs;
        // Options
        foreach ($Options as $Propriete => $Valeur)
            if (property_exists($this, $Propriete))
                $this->{$Propriete} = $Valeur;
            else
                throw new Exception("La propriete « ". $Propriete ." » n'est pas reconnue par le widget Liste");

        /* ----------- Traitement des options ------------ */
        // Données vide, sélection automatique des Champs si un type d'objet est rattaché
        if (empty($this->Champs) && !empty($this->Objet))
            $Champs = $Objet::GetPropsViaOption("Obligatoire");

        // Sélection automatique de la route
        if (empty($this->Route))
            $this->Route = \Requete\Infos::$Route->Adresse;
    }

    public function __toString()
    {
        ob_start();
        $this->Rendre();
        $Rendu = ob_get_contents();
        ob_end_clean();

        return $Rendu;
    }

    /* -----------------------------------------------------------------
    > METHODES DE RENDU
    ----------------------------------------------------------------- */

    public function Rendre() {
        $this->GenConteneur();
    }

    /* -----------------------------------------------------------------
    > METHODES DE PARCOURS
    ----------------------------------------------------------------- */
    public function GenConteneur() {
        // Dépendances Javascript
        \Requete\Infos::$Route->Contenu->JS[] = "Widgets/Formulaire";
        // Impression
        $this->Conteneur( $this->Nom, $this->Route );
    }

    public function GenChamps() {
        // Si les champs sont sous forme de tableau simple, on les convertis en association pour obtenir le nom en clé
        if ( !TblAssociatif($this->Champs) )
            $this->Champs = array_flip($this->Champs);

        // Parcours de chaque champ
        foreach ( $this->Champs as $Champ => $Infos ) {
            // Attributs de la balise
            $Classe     = $Champ;

            // Fusion des infos du champ avec les infos par défaut
            if (is_array($Infos))
                $Infos = array_merge( $this->InfosChampDefaut, $Infos );
            else
                $Infos = $this->InfosChampDefaut;

            // Définition du label
            if ( ! array_key_exists("Label", $Infos) )
                $Infos["Label"] = $Champ;

            // Affichage de l'élement
            $this->Champ($Infos["Label"], $Champ, $Infos["Type"], $Classe, $Infos["Valeur"]);
        }
    }

    /* -----------------------------------------------------------------
    > METHODES D'IMPRESSION
    ----------------------------------------------------------------- */
    abstract function Conteneur($Nom, $Route);
    abstract function Champ($Surnom, $Nom, $Type, $Classe, $Valeur = "");
}
?>

<?php namespace Contenu\Widgets\Liste;
/**
 * Widget de liste
 */
abstract class Base {
    /* -----------------------------------------------------------------
    > PROPRIÉTÉS
    ----------------------------------------------------------------- */
    public
        // Obligatoires
        $Nom,       // Nom de la liste = ID HTML
        $Elements,  // -

        // Optionnelles
        $Options = [    // Options qui seront accèssible au JS via les attributs data-*
            "criteres"      => [],      // Valeurs des clauses de contraintes des requetes
            "ajax"          => false,   // Contient la route de rafraichissement si on souhaite des intéractions AJAX
            "vide"          => null
        ],
        $MsgVide    = false,    // Message à afficher lorsque la liste est vide
        $Conteneur  = true,      // Si on doit rendre le conteneur (true) ou juste les élements (false)
        $Selection  = []
    ;
    /* -----------------------------------------------------------------
    > CONSTRUCTEUR & METHODES MAGIQUES
    ----------------------------------------------------------------- */
    /**
     * Construit une liste avec des intéractions ajax natives
     * @param string $Nom       Nom du widget de liste
     * @param array  $Elements  Groupes d'élements contenant chacun des données
     * @param array  $Options Options générales s'appliquant à tout type d'objet
     */
    function __construct(string $Nom, array $Elements, $Options = []) {
        $this->Nom = $Nom;

        // Allocation des options
        foreach ($Options as $Propriete => $Valeur)
            if (property_exists($this, $Propriete))
                $this->{$Propriete} = $Valeur;
            elseif (array_key_exists($Propriete, $Options))
                $this->Options[$Propriete] = $Valeur;
            else
                throw new \Exception("L'option' « ". $Propriete ." » n'est pas reconnue par le widget Liste");

        // S'il y a des objets de requete, on les remplace par leur résultat
        if (nb_dim($Elements) === 2)
            $this->Elements = $this->TransfoRequetes($Elements);
        else
            throw new \Exception("Le format des élements spécifiés pour la liste est incorrect.");
    }

    public function __toString() {
        ob_start();
        $this->Rendre();
        $Rendu = ob_get_contents();
        ob_end_clean();

        return $Rendu;
    }

    /* -----------------------------------------------------------------
    > TRAITEMENT & CONVERSIONS DE DONNÉES
    ----------------------------------------------------------------- */
    private function TransfoRequetes( array $Sources ) : array {
        /*
        $Elements = [
            [
                Objet de requete \BDD\Requete\Get,
                [
                    Donnée => Afficher ou non,
                    ...
                ]
            ],
            ...
        ]
         */
        $Requetes = [];
        $Elements = [];

        // Référencement des requetes
        foreach ($Sources as $InfosGrp) {
            // Extraction des infos du groupe d'élements
            $Requete = $InfosGrp[0];
            $Donnees = $InfosGrp[1];

            // On précise les données à récupérer
            $Requete->Colonnes = \BDD\Requete\Formater\Select(array_merge(
                ["id"], array_keys($Donnees)
            ));

            // Récupérer l'association where, la référencer dans $criteres[]
            $this->Options["criteres"] = array_merge($this->Options["criteres"], $Requete->Valeurs);

            // On prend le nom de l'objet concerné
            $NomGrp = nom_classe($Requete->Objet);
            // On référence le nom des données à afficher
            $Elements[$NomGrp][1] = array_keys($Donnees, true);
            // Et on référence la requete sous ce nom pour l'execution
            $Requetes[$NomGrp] = $Requete;
        }

        // Execution des requètes, remplacement de l'objet de requete par son résultat
        $ElementsReq = \BDD\Requetes($Requetes)->Donnees(function( $NomObj, $Donnees ) use( &$Elements ) {
            $Elements[$NomObj][0] = $Donnees;
        });
        /*
        $Elements = [
            [
                [
                    [
                        Donnée => Valeur,
                        ...
                    ],
                    ...
                ],
                [
                    Donnée à afficher,
                    ...
                ]
            ],
            ...
        ]
         */
        return $Elements;
    }

    /* -----------------------------------------------------------------
    > METHODES DE RENDU
    ----------------------------------------------------------------- */
    public function Rendre() {
        if ( \Requete\Infos::$Ajax && $this->Conteneur !== true )
            // On n'affiche que les élements
            $this->GenElements();
        else
            // Sinon on affiche la liste entière
            $this->GenConteneur();
    }

    public function GenAttribDonnees(array $Attributs) : string {
        $Retour = "";
        foreach ($Attributs as $Propriete => $Valeur)
            $Retour .= " data-". $Propriete .'="'. $Valeur .'"';
        return $Retour;
    }

    public function GenConteneur() {
        // Dépendances Javascript
        \Requete\Infos::$Route->Contenu->JS[] = "Widgets/Liste";

        // Attributs de données
        $Attributs = [ "nom" => $this->Nom ];
        foreach ($this->Options/*Brutes*/ as $Propriete => $Valeur)
            if (!empty($Valeur)) {
                if (is_array($Valeur))
                    $Valeur = htmlspecialchars(json_encode($Valeur));
                $Attributs[$Propriete] = $Valeur;
            }

        // Impression
        $this->Conteneur( $this->Nom, $this->GenAttribDonnees($Attributs) );
    }

    public function GenElements() {
        $AucunResultat = true;

        // Parcours de chaque groupe d'élements
        foreach ($this->Elements as $NomGrp => &$InfosGrp) {
            // Extractions des infos du groupe
            $ElementsGrp        = &$InfosGrp[0];
            $DonneesVisibles    = &$InfosGrp[1];

            // Si des élements sont présents dans ce groupe
            if (!empty($ElementsGrp)) {
                $AucunResultat = false;
                // Parcours de chaque élement
                $NumElem = 1;
                foreach ($ElementsGrp as $Element) {
                    // Si aucune propriété n'a été définie
                    if (empty($Element))
                        throw new Exception("Au moins une donnée doit-être présente dans une liste.");

                    // Attributs de la balise
                    $IdElem     = array_key_exists("id", $Element) ? $Element["id"] : $NumElem;
                    $ID         = $NomGrp . $IdElem;
                    $Classe     = "element " . $NomGrp;
                    $Attributs  = $this->GenAttribDonnees( array_merge(["type" => $NomGrp ], $Element) );

                    // Si on a indiqué un élement à sélectionner pour le type d'objet partouru
                    if ( array_key_exists($NomGrp, $this->Selection) )
                        if ( $IdElem == $this->Selection[$NomGrp] )
                            $Classe .= " actuel";

                    // Affichage de l'élement
                    $this->Element($Element, $ID, $Classe, $Attributs, $DonneesVisibles);

                    // Incrémentation de l'ID provisoire
                    $NumElem++;
                }
            }
        }

        // Message si aucun résultat
        if ($AucunResultat && !empty($this->MsgVide))
            echo $this->MsgVide;
    }

    public function Donnees($Element, array $Visibles, callable $Impression) {
        foreach ($Element as $Propriete => $Valeur) {
            if (in_array( $Propriete, $Visibles )) {
                // Attributs de la balise
                $Classe = "donnee " . $Propriete;
                // Affichage de l'élement
                $Impression($Classe, $Valeur);
            }
        }
    }

    /* -----------------------------------------------------------------
    > METHODES D'IMPRESSION
    ----------------------------------------------------------------- */
    abstract function Conteneur($Nom, $AttrDonnees);
    abstract function Element($Element, $ID, $Classe, $AttrDonnees, $DonneesVisibles);
}
?>

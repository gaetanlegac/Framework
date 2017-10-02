<?php
namespace Contenu\Widgets {

    use \Requete as Requete;
    use \Route\Infos as Route;

    function Rendre($Nom) {
        // Charger la route pour générer le composant
        // Les erreurs seront affichées à l'emplacement du widget
        $Route = new Route( Requete::$Contenu->Widgets[$Nom] );
        $Route->Contenu->Rendre( \Contenu\Format\Brut );
    }

}
?>

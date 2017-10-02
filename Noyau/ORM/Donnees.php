<?php
namespace Donnees\Type {
const
    Chaine      = "text",
    MotDePasse  = "password"
;
}

namespace Donnees\Temps {
    function Depuis( string $ChaineDate ) : string {
        $Date = new \DateTime($ChaineDate);
        $Difference = $Date->diff( new \DateTime(), false );

        // Teste la dénomination la plus appropriée
        $Retour = "";
        foreach ([ // Associe une référence de temps à un tableau composé de la dénomination + un booléem indiquant si on doit appliquer le pluriel
            "y" => ["an", true],
            "m" => ["mois", false],
            "d" => ["jour", true],
            "h" => ["heure", true],
            "i" => ["minute", true],
            "s" => ["seconde", true]
        ] as $Ref => $Nomination) {
            $Nombre = intval( $Difference->format("%".$Ref) );
            if ($Nombre > 0) {
                $Pluriel = $Nomination[1];
                $Nomination = $Nomination[0];
                $Retour = $Nombre . " " . $Nomination . (($Pluriel && $Nombre > 1) ? "s" : "");
                break;
            }
        }
        return $Retour;
    }

    function Maintenant() : string {
        return date("Y-m-d H:i");
    }

    function FR( string $Date ) : string {
        return date("d/m/Y H:i", strtotime($Date));
    }
}
?>

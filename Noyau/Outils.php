<?php
namespace {
    /* -----------------------------------------------------------------
    > TRAITEMENT DES TABLEAUX
    ----------------------------------------------------------------- */

    // Détermine si un tableau est associatif
    function TblAssociatif($Tableau) {
        if (is_array($Tableau))
            return count(array_filter(array_keys($Tableau), 'is_string')) > 0;
        else
            return false;
    }

    // Compte les dimensions d'un tableau
    function nb_dim( array $Tableau ) : int
    {
        $Reset = reset($Tableau);
        if (is_array($Reset))
            $Nombre = nb_dim($Reset) + 1;
        else
            $Nombre = 1;

        return $Nombre;
    }

    // Même rôle que la fonction implode, mais pour les tableaux associatifs
    function implode_asso(string $GlueCouple, string $GlueFinale, array $Tableau) : string {
        $Couples = [];
        if (empty($Tableau))
            return "";

        foreach ($Tableau as $Cle => $Val)
            array_push($Couples, $Cle.$GlueCouple.$Val);
        return implode($GlueFinale, $Couples);
    }

    // Même rôle que la fonction explode, mais pour les tableaux associatifs
    function explode_asso($SepCouple, $SepFinal, $Chaine) {
        $Couples = array_filter(explode("+", $Chaine));
        $Tableau = [];
        foreach ($Couples as $Couple) {
            $Sep = explode(":", $Couple);
            $Tableau[$Sep[0]] = $Sep[1];
        }
        return $Tableau;
    }

    /* -----------------------------------------------------------------
    > TRAITEMENT DES OBJETS
    ----------------------------------------------------------------- */

    function nom_classe($Objet) {
        return (new \ReflectionClass( $Objet ))->getShortName();
    }
}
?>

<?php namespace Contenu\Widgets\Formulaire;

use \Donnees\Type as Type;

class Simple extends Base {
    function Conteneur($Nom, $Route) { ?>
        <form id="<?= $Nom ?>" method="post" action="./?<?= $Route ?>" class="Formulaire">
            <?php $this->GenChamps(); ?>
            <div class="boutons">
                <input type="submit" class="bouton" value="<?= $this->TexteValidation ?>" />
            </div>
        </form>
    <?php }

    function Champ($Surnom, $Nom, $Type, $Classe, $Valeur = "") {
        echo '<div class="cont-'. $Nom .'">';
        // Selection du type de champ
        switch ($Type) {
            case Type\Chaine:
            case Type\MotDePasse: ?>
                <input type="<?= $Type ?>" name="<?= $Nom ?>" value="<?= $Valeur ?>" placeholder="<?= $Surnom ?>" />
                <?php break;
            case "caché": ?>
                <input type="hidden" name="<?= $Nom ?>" value="<?= $Valeur ?>" />
                <?php break;
        }

        echo '</div>';
    }
}
?>

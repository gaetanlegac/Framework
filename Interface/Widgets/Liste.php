<?php namespace Contenu\Widgets\Liste;

class Simple extends Base {
    function Conteneur($Nom, $AttrDonnees) { ?>
        <ul id="<?= $Nom ?>" class="Liste"<?= $AttrDonnees ?>>
            <?php $this->GenElements(); ?>
        </ul>
    <?php }

    function Element($Donnees, $ID, $Classe, $AttrDonnees, $DonneesVisibles) { ?>
        <li id="<?= $ID ?>" class="<?= $Classe ?>"<?= $AttrDonnees ?>>
        <?php $this->Donnees( $Donnees, $DonneesVisibles, function($Classe, $Texte) { ?>
            <a class="<?= $Classe ?>"><?= $Texte ?></a>
        <?php }); ?>
        </li>
    <?php }
}
?>

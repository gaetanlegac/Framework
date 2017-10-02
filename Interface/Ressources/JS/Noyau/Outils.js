// Retourne les données des champs d'un formulaire sous forme de tableau associatif (objet)
$.fn.ExporterDonnees = function() {
    // Ne s'applique qu'aux formulaires
    if ($(this).is("form")) {
        // Récupèration sous forme de tableau
        var TblDonnees = $(this).serializeArray();
        // Remplissage de l'objet
        var ObjDonnees = {};
        $.each(TblDonnees, function(index, donnees) {
            ObjDonnees[donnees.name] = donnees.value;
        });
        // Retour
        return ObjDonnees;
    } else
        return false;
}

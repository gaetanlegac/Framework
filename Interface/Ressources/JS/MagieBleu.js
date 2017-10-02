var CommandeListe = false;
var DepartClic = 0;

var pasEffectif;

/* -----------------------------------------------------------------
> Actions sur la panneau de navigation
----------------------------------------------------------------- */
var PanneauNav = {
    largeurMax: 0,
    largeurListe: 0,

    agrandir: function(max = false) {
        // S'il reste des élements à afficher
        if ($("#Listes nav.masque").length == 0)
            return;

        // Si la barre n'a pas encore été étendue
        if ( !$("#BarreListes").hasClass("etendu") ) {
            // Ajout des classes
            $("#BarreListes").addClass("etendu");
            $("#Focus").addClass("BarreNav");
        }

        // Affiche du prochain élement
        if (max)
            $("#Listes nav.masque").removeClass("masque");
        else
            $("#Listes nav.masque:last").removeClass("masque");
    },
    reduire: function(max = false) {
        // S'il en reste 2, on en conclus qu'il faut réduire au max
        if ($("#Listes nav:not(.masque)").length == 2)
            max = true;

        // Si la barre n'a pas encore été étendue
        if ( $("#BarreListes").hasClass("etendu") ) {
            // Masque le premier élement
            if (max) {
                $("#BarreListes").removeClass("etendu");
                $("#Focus").removeClass("BarreNav");

                $("#Listes nav:not(.masque):not(:last)").addClass("masque");
            } else
                $("#Listes nav:not(.masque):first").addClass("masque");
        }
    },

    minimiser: function() { PanneauNav.reduire(true); },
    etendre: function() { PanneauNav.agrandir(true); },

    mvtDroite: function() {
        // Si le panneau a atteint sa largeur max
        if ( $("#BarreListes").width() >= PanneauNav.largeurMax ) {
            // On s'assure que les listes non-visibles ne sont plus masquées
            $("#Listes nav:not(.masque):first").prevAll("nav.masque").removeClass("masque");
            // Si des listes dépassent du panneau
            if ( $("#Listes").width() >= PanneauNav.largeurMax + PanneauNav.largeurListe )
                // On masque la dernière liste à droite pour afficher une liste de la gauche
                $("#Listes nav:not(.masque):last").addClass("masque");
        // Sinon, on continue à agrandir le panneau
        } else
            PanneauNav.agrandir();
    },
    mvtGauche: function() {
        // Si les derniers panneaux à droite sont masqués
        if ( $("#Listes nav:not(.masque):first").nextAll("nav.masque").length > 0 ) {
            // Affichage de ces derniers
            $("#Listes nav.masque:first").removeClass("masque");
        } else
        // Sinon, on réduit la largeur du panneau
            PanneauNav.reduire();
    },

    majDimensions: function() {
        pasEffectif = $(document).width() * 0.10;

        PanneauNav.largeurListe = $("#Listes nav:first").width();
        PanneauNav.largeurMax = Math.floor( $(document).width() / PanneauNav.largeurListe ) * PanneauNav.largeurListe;

        $("#BarreListes").css("max-width", PanneauNav.largeurMax + "px");
    }
}

/* -----------------------------------------------------------------
> Evenements
----------------------------------------------------------------- */
$(document).ready(function() {
    /* -------- Panneau de navigation -------- */
    $("#BarreListes")
        .on("mousedown", function(e) {
            switch (e.which) {
                case 1: // Clic gauche
                    // Début des commandes sur le panneau de navigation
                    CommandeListe = true;
                    DepartClic = Curseur.x;
                    e.preventDefault();
                    break;
                case 2: // clic molette
                    var navClic = $(e.target).closest("nav");
                    if (navClic.length > 0) {
                        $(navClic).nextAll("nav").remove();
                        PanneauNav.minimiser();
                    }
                    break;
            }
        })
        // Controle par la molette
        .on("mousewheel", function(e) {
            if (CommandeListe) {
                // Dimensionnement selon l'action de la molette
                if ( e.originalEvent.wheelDelta /120 > 0 )
                    PanneauNav.mvtDroite();
                else
                    PanneauNav.mvtGauche();
                // Enregistrement du départ de déplacement
                DepartClic = $(this).width() - Curseur.x;
            }
        });

    var pas = 0;
    $(document)
        // Controle par déplacement de curseur
        .mousemove(function() {
            if (CommandeListe) {
                // Détermination des valeurs de base
                pas = Curseur.x - DepartClic;
                // Dimentionnement selon le sens de la souris
                if (pas >= pasEffectif) {
                    DepartClic = Curseur.x;
                    PanneauNav.mvtDroite();
                } else if (pas <= -pasEffectif) {
                    DepartClic = Curseur.x;
                    PanneauNav.mvtGauche();
                }
            }
        // Fin de controle
        }).mouseup(function() {
            CommandeListe = false;
        }).mouseleave(function() {
            CommandeListe = false;
        });

    // Mise à jour des dimentions des élements
    PanneauNav.majDimensions();

    /* -------- Arrière-plan focus -------- */
    $("#Focus").mousedown(PanneauNav.minimiser);
}).resize(PanneauNav.majDimensions);

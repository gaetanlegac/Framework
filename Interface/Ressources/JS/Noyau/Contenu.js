// Liste des widgets
var Widgets = {};
// Liste des boites de dialogue
var Dialogues = {};
//

/**
 * Récupère et traite des données à partir du système de routage
 * @param       {string}  Adresse               Adresse de la route
 * @param       {object}  [Options]             Objet d'options post. Si vide, on fera une requete get
 * @param       {mixed}   [ActionEnsuite]       Fonction à lancer après la requete
 * @param       {mixed}   [ElementChargement]   Element qui contiendra l'indicateur de chargement
 * @constructor
 */
function Route (Adresse, Post = null, ActionEnsuite = false, ElementChargement = false) {
    // Corrections
    var Type = (Post == null) ? "get" : "post";

    // Requête
    $.ajax("?"+Adresse, {
        // Proprietes
        type:           Type,
        data:           Type == "post" ? JSON.stringify(Post) : null,
        contentType:    'application/json',
        dataType:       "json",
        cache:          false,
        // Evenements
        beforeSend: function() {
            // Indicateur de chargement
            if (ElementChargement)
                $(ElementChargement).addClass("chargement");
        },
        success: function(Retour) {
            // Interprétation des commandes spéciales
            try {
                // Parcours des entrées
                $.each(Retour, function(entree, donnees) {
                    var commandeReconnue = true;
                    switch (entree) {
                        case "dialogue":
                            Dialogue(Retour.dialogue.titre, Retour.dialogue.contenu, Retour.dialogue.boutons, Retour.dialogue.type);
                            break;
                        case "javascript":
                            eval(Retour.javascript);
                            break;
                        case "css":
                            break;
                        case "js":
                            for (var i in Retour.js) {
                                var fichierJs = Retour.js[i];
                                if ( $("head script[src='"+ fichierJs +"']").length == 0 )
                                    $("head").append('<script type="text/javascript" src="'+ fichierJs +'"></script>');
                            }
                            break;
                        default:
                            commandeReconnue = false;
                            break;
                    }
                    // Commande traitée, on peut la retirer des données en retour
                    if (commandeReconnue)
                        delete Retour[entree];
                });
            } catch (e) {
                Dialogue("Erreur", "Oups ... Impossible de traiter les données reçues par le serveur:<br>" + Retour, false, "erreur");
            }

            // Action de traitement du résultat si spécifié
            if (ActionEnsuite) {
                // Nettoyage de la réponse
                var nbEntrees = Object.keys(Retour).length;
                // Si l'entrée resultat est la seule, on réduit Retour à la valeur de cette entrée
                if ( nbEntrees == 1 && "resultat" in Retour )
                    Retour = Retour.resultat;
                // Sinon, s'il n'y a pas de résultat si aucune autre entrée, on retourne false
                else if (nbEntrees == 0)
                    Retour = false;

                // Execution
                ActionEnsuite(Retour);
            }
        },
        error: function(Erreur) {
            if (Erreur.status === 200)
                Dialogue("Erreur", "Une erreur s'est produite dans la réponse du serveur<br>"+ Erreur.responseText, false, "erreur");
        },
        complete: function() {
            // Indicateur de chargement
            if (ElementChargement)
                $(ElementChargement).removeClass("chargement");
        }
    });
}
function RouteVersDialogue(Adresse, Post = null, ActionEnsuite = false) {
    Route(Adresse, Post, function(Reponse) {
        if (Reponse)
            Dialogue(Reponse.titre, Reponse.resultat, {}, "info", ActionEnsuite);
    }, $("body"));
}

$.fn.Initialiser = function() {
    $(this).InitEvents();
    $(this).InitWidgets();
}

$.fn.InitEvents = function() {
    // Liens dynamiques
    $(".BarreActions").LiensAjax("Dialogue", function() {
        // Action après la fermeture de la boite de dialogue
        Widgets.ListeCategories.Actualiser();
    });
}

$.fn.InitWidgets = function() {
    // Listes
    $(this).find(".Liste").each(function() {
        var IdListe = $(this).attr("id");
        //if (!( IdListe in Widgets ))
            Widgets[IdListe] = new Liste($(this));
    });
    // Formulaires
    $(this).find(".Formulaire").each(function() {
        var IdFormulaire = $(this).attr("id");
        //if (!( IdFormulaire in Widgets ))
            Widgets[IdFormulaire] = new Formulaire($(this));
    });
}

// Dynamisation des liens se situant dans le conteneur spécifié
$.fn.LiensAjax = function(Destination, ActionEnsuite) {
    $(this).find("a[href^='./?']").off("click").on("click", function(e) {
        // Interruption de l'action du lien
        e.preventDefault();
        // Chemin à charger
        var Adresse = $(this).attr("href").substring(3);
        if (Adresse) {
            // Si une action n'est pas déjà en cours
            if (!$("body").hasClass("chargement")) {
                // Etat de chargement
                $("body").addClass("chargement");
                // Requete
                Route(Adresse, null, function(Retour) {
                    // Si la destination est une boite de dialogue
                    if ( Destination == "Dialogue" )
                        Dialogue(Retour.titre, Retour.resultat, [], "info", ActionEnsuite);

                    // Etat de chargement
                    $("body").removeClass("chargement");
                });
            }
        }
    });
}

var DernierContexte;

$.fn.MenuContexte = function(Actions) {
    $(this).off("contextmenu").on("contextmenu", function(e) {
        // Annule l'évenement par défzut
        e.preventDefault();
        // Si le contexte en cours ne correspond pas à un enfant
        if ( !$(this).find(DernierContexte).length ) {
            $("#MenuContextuel").html(""); // Vidage du menu
            DernierContexte = null;
        }

        // Séparateur s'il y a déjà des élements
        if ( !$("#MenuContextuel").is(":empty") )
            $("#MenuContextuel").append("<hr>");

        // Suppression de la classe de contexte
        $(".context").not(DernierContexte).removeClass("context");

        // Récupération des données de l'élement concerné par les actions
        var donnees = $(this).data();
        donnees.Element = DernierContexte = this;

        // Affichage des actions
        $.each(Actions, function(Nom, Action) {
            // Par défaut, on affiche toutes les actions
            var Afficher = true;

            // Si Action est un tableau
            if ($.isArray(Action)) {
                // Alors il contient une condition d'affichage en plus de l'action au clic
                Afficher = Action[0]();
                Action = Action[1];
            } // Sinon, il ne s'agit de l'action seule

            // Affichage
            if (Afficher) {
                var elementAction = $("<a>"+ Nom +"</a>");
                $("#MenuContextuel").append(elementAction);
                elementAction.click(function() {
                    Action(donnees);
                });
            }
        });

        // Ajout de la classe de contexte
        $(this).addClass("context");

        // Positionnement & affichage du menu
        $("#MenuContextuel").css({
            top: Curseur.y, left: Curseur.x
        }).show();
    });
};

// Active l'évenement de suppression d'un élement DOM
(function($){
  $.event.special.destroyed = {
    remove: function(o) {
      if (o.handler) {
        o.handler()
      }
    }
  }
})(jQuery);

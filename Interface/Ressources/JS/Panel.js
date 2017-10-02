// Variables globales
var ArticleActuel = new Objet("Article");  // Article vide. Contiendra les données de l'article chargé

/* -----------------------------------------------------------------
> INITIALISATION D'UN PANNEAU DE NAVIGATION
----------------------------------------------------------------- */
function InitPanneau(Nom) {
    // Actions de la liste
    Widgets[Nom].SetActions({
        // Action à la sélection d'une catégorie
        Apres: {
            Selection: function(Type, ID) {
                switch (Type) {
                    case "Article":
                        // Chargement de l'article
                        ArticleActuel.Charger(ID);
                        // Déselection des anciens
                        $("#Listes .element.Article.actuel:not(#Article"+ID+")").removeClass("actuel");
                        // Supprime les panneaux après celui de la catégorie de l'article chargé
                        Widgets[Nom].Element.parent().nextAll().remove();
                        // Fermeture du panneau
                        PanneauNav.minimiser();
                        // Affichage de l'éditeur si ce n'est pas djéà fait
                        if ($("#Editeur").hasClass("invisible"))
                            $("#Editeur").removeClass("invisible")

                        break;
                    case "Categorie":
                        // Récupèration de la liste
                        Route("/Panel/ListeNavigation", {
                            selection:  {},
                            criteres:   { categorie: ID },
                            conteneur:  true
                        }, function(Liste) {
                            // Creation de la liste
                            var NomCat = $("#Categorie"+ID).data().nom;
                            var NouvelleListe = $('<nav id="cont'+ Nom +'"><header><a title="Voir les catégories précédentes" class="bouton etendre"><</a><h2>'+ NomCat +'</h2></header>'+ Liste +'</nav>');

                            // Préparation de l'espace
                            var ElementsSuivants = $(Widgets[Nom].Element).parent("nav").nextAll("nav");
                            if (ElementsSuivants.length > 0)
                                ElementsSuivants.remove();

                            // Affichage de la liste
                            $("#Listes").append(NouvelleListe);
                            $(NouvelleListe).addClass("masque").find(".bouton.etendre").click(PanneauNav.etendre);

                            // Initialisation des évenements
                            $(NouvelleListe).InitWidgets();
                            InitPanneau( $(NouvelleListe).children(".Liste").attr("id") );

                            // Extension
                            PanneauNav.agrandir(true, true);
                        }, $("#BarreListes"));

                        break;
                }
            },
            Chargement: function(Liste) {
                // Menu contextuel
                // Actions sur les élements des listes
                $(Liste).children("li").MenuContexte({
                    Renommer: function(Selection) {
                        RouteVersDialogue("/"+ Selection.type +"/"+ Selection.id +"/Renommer", null, function() {
                            Widgets[ $(Selection.Element).parents(".Liste").attr("id") ].Actualiser();
                        });
                    },
                    Supprimer: function(Selection) {
                        // Définition des textes du Dialogue en fonction du type d'élement à supprimer
                        switch (Selection.type) {
                            case "Categorie":
                                var titre = "Supprimer la catégorie";
                                var texte = "Êtes-vous certain de supprimer définitivement cette catégorie ainsi que tous ses articles ?";
                                break;
                            case "Article":
                                var titre = "Supprimer l'article'";
                                var texte = "Êtes-vous certain de supprimer définitivement cet article ?";
                                break;
                        }
                        // Affichage du message
                        Dialogue(titre, texte, {
                            Oui: function() {
                                // Nouvel article si on supprime celui actuellement ouvert
                                if (
                                    Selection.type == "Categorie" && ArticleActuel.categorie == Selection.id
                                    || Selection.type == "Article" && ArticleActuel.id == Selection.id
                                ) {
                                    ArticleActuel.Nouveau();
                                    $("#Editeur").addClass("invisible");
                                }
                                // Fermeture du panneau de la catégorie concernée
                                if (Selection.type == "Categorie" && ("NavCategorie"+Selection.id) in Widgets ) {
                                    var PanneauCat = Widgets["NavCategorie"+Selection.id].Element.parent("nav");
                                    PanneauCat.nextAll().remove();
                                    PanneauCat.remove();
                                }
                                // Requete au serveur
                                Route("/"+ Selection.type +"/"+ Selection.id +"/Supprimer", {}, function() {
                                    Widgets[ $(Selection.Element).parents(".Liste").attr("id") ].Actualiser();
                                });
                            },
                            Non: null
                        }, "question");
                    },
                    Couper: function(Selection) {
                        PressePapier.Set({
                            type:       Selection.type,
                            id:         Selection.id,
                            provenance: $(Selection.Element).parents(".Liste").data().nom,
                            action:     "Couper"
                        });
                    }
                });
                // Actions sur les listes
                $(Liste).MenuContexte({
                    "Coller": [ function() { return !PressePapier.vide; },
                        function(Liste) {
                            var ElementPP = PressePapier.Get();
                            Route("/"+ ElementPP.type +"/"+ ElementPP.id +"/"+ ElementPP.action, {
                                Destination: Liste.criteres.categorie
                            }, function(Reponse) {
                                // Actualise les listes concernées
                                Widgets[Liste.nom].Actualiser();
                                Widgets[ElementPP.provenance].Actualiser();
                            }, $("#BarreListes"));
                        },
                    ],
                    "Nouvel Article": function(Liste) {
                        RouteVersDialogue("/Article/Nouveau", {
                            categorie: Liste.criteres.categorie
                        }, function(NouvArticle) {
                            Widgets[Liste.nom].Actualiser({}, function() {
                                if (ArticleActuel.id == null) {
                                    ArticleActuel.ImporterDonnees(NouvArticle);
                                    // Affichage de l'éditeur si ce n'est pas djéà fait
                                    if ($("#Editeur").hasClass("invisible"))
                                        $("#Editeur").removeClass("invisible")
                                }
                            });
                        });
                    },
                    "Nouvelle catégorie": function(Liste) {
                        var CategorieListe = Liste.criteres.categorie;
                        RouteVersDialogue("/Categorie/"+ CategorieListe +"/Nouvelle", null, function(NouvCategorie) {
                            Widgets[Liste.nom].Actualiser({}, function() {
                                Widgets[Liste.nom].Selectionner("Categorie", NouvCategorie);
                            });
                        });
                    }
                });
            }
        }
    });
}

var PressePapier = {
    donnees: {
        type:       null,
        id:         null,
        provenance: null,
        action:     null
    },
    vide: true,

    Get: function() {
        if (PressePapier.vide) {
            Dialogue("Oups", "Le presse-papier est vide");
            return false;
        } else {
            var DonneesPP = PressePapier.donnees;
            PressePapier.vide = true;
            return DonneesPP;
        }
    },
    Set: function(DonneesPP) {
        PressePapier.donnees = DonneesPP;
        PressePapier.vide = false;
    }
};

$(document).ready(function() {
    /* -----------------------------------------------------------------
    > BARRE DE NAVIGATION
    ----------------------------------------------------------------- */
    // Initialisaiton de la première
    InitPanneau("NavCategorie0");
    // bouton de déconnexion
    $(".bouton.deconnexion").click(function() {
        Dialogue("Se déconnecter ?", "Etes-vous certain de vouloir vous déconnecter ?<br>Les données non-enregistrées seront perdues.", {
            Oui: function() { window.location = './?Login/off'; },
            Non: null
        });
    });
    // bouton de fermeture de l'éditeur
    $("#Editeur .bouton.fermer").click(function() {
        // Retire la sélection
        Widgets["NavCategorie"+ArticleActuel.categorie].Selectionner(false);
        // Reinitialisation & masquage de l'éditeur
        ArticleActuel.Nouveau(function() {
            $("#Editeur").addClass("invisible");
        });
    });

    /* -----------------------------------------------------------------
    > EDITEUR
    ----------------------------------------------------------------- */
    // Initialisation de la zone de texte enrichis
    $("#Editeur .contenu").trumbowyg({
        lang: "fr"
    });
    // Rattachement de l'article à son formulaire
    ArticleActuel.SetFormulaire("#Editeur");
    ArticleActuel.SetActionsApres({
        Chargement: function() {
            // Charge le contenu dans l'éditeur Trumbowyg
            ArticleActuel.GetChamp("contenu").siblings(".trumbowyg-editor").html(ArticleActuel.contenu);
        },
        Enregistrement: function() {
            // Recharge la liste des articles
            Widgets["NavCategorie"+ArticleActuel.categorie].Actualiser({
                selection: { Article: ArticleActuel.id  }
            });
        },
        Nouveau: function() {
            // Réinitialise l'éditeur Trumbowyg
            $("#Editeur .trumbowyg-editor").html(ArticleActuel.contenu);
        }
    });
    // Rattachement des infos de l'article à la barre d'état
    ArticleActuel.SetZoneDonnees("#BarreEtat");
});

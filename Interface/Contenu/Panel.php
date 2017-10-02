<div id="Conteneur">
    <div class="contBarreListes">
        <aside id="BarreListes" class="Donnees_ListeCategories">
            <div class="contListes">
                <div id="Listes">
                    <nav>
                        <header class="Utilisateur">
                            <h2 class="Login"><?php echo Session::$Utilisateur->Get("login"); ?></h2>
                            <a title="Se déconnecter" class="bouton deconnexion">x</a>
                        </header>
                        <?php $this->Contenu->Widget("ListeNavigation"); ?>
                    </nav>
                </div>
            </div>
        </aside>
    </div>
    <div class="contEditeur">
        <div id="Bonjour">
            <span class="texte">Sélectionnez un article<br>ou créez-en un nouveau<br>à partir de votre classeur à gauche !</span>
        </div>
        <form method="post" id="Editeur" class="invisible">
            <input class="id invisible" name="id" />
            <header class="BarreActions">
                <div class="gauche">
                    <input type="submit" title="Enregistrer les modifications" class="bouton Enregistrer-Article" value="Enregistrer" />
                </div>
                <input class="titre" name="titre" type="text" value="Article sans nom" autocomplete="off" />
                <div>
                    <a class="bouton fermer">Fermer</a>
                </div>
            </header>
            <textarea class="contenu" name="contenu"></textarea>
            <div id="BarreEtat">
                <div>
                    Creation: <span class="creation">-</span>
                </div>
                <div>
                    Dernier enregistrement: <span class="modification">-</span>
                </div>
            </div>
        </form>
    </div>
</div>

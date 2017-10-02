<div id="conteneur">
	<aside id="FichiersLogs">
		<header class="actions">

		</header>
		<nav id="ListeFichiers">
			<?php Debug::ListeLogs(); ?>
		</nav>
	</aside>
	<div id="Logs">
        <header class="actions">
            <a onclick="actualiser();">Recharger</a>
			<div class="filtres">

			</div>
            <span id="heureMaj">--</span>
        </header>
        <div id="messages">
			<table></table>
		</div>
	</div>
</div>
<div id="cache"></div>

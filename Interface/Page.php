<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title><?php echo $this->Titre; ?></title>
		<!-- Polices -->
		<link rel="stylesheet" type="text/css" href="<?php echo \Base\Res; ?>/Polices/OpenSans/stylesheet.css"/>
		<link rel="stylesheet" type="text/css" href="<?php echo \Base\Res; ?>/Polices/OpenSansSB/stylesheet.css"/>
		<link rel="stylesheet" type="text/css" href="<?php echo \Base\Res; ?>/Polices/OpenSansLight/stylesheet.css"/>
		<!-- Design -->
		<link rel="stylesheet" type="text/css" href="<?php echo \Base\Res; ?>/CSS/Noyau.css"/>
		<?php echo $this->Ressources("CSS"); ?>
		<!-- Scripts -->
		<script type="text/javascript" src="<?php echo \Base\Res; ?>/JS/Libs/jquery.js"></script>
		<script type="text/javascript" src="<?php echo \Base\Res; ?>/JS/Libs/jquery.deserialize.js"></script>

		<script type="text/javascript" src="<?php echo \Base\Res; ?>/JS/Noyau/Outils.js"></script>
		<script type="text/javascript" src="<?php echo \Base\Res; ?>/JS/Noyau/Contenu.js"></script>
		<script type="text/javascript" src="<?php echo \Base\Res; ?>/JS/Noyau/Page.js"></script>
		<script type="text/javascript" src="<?php echo \Base\Res; ?>/JS/Noyau/Objets.js"></script>
		<?php echo $this->Ressources("JS"); ?>
	</head>
	<body>
		<div id="Focus"></div>
		<?php $this->Afficher(); ?>
		<div id="MenuContextuel"></div>
	</body>
</html>

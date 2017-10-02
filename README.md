# Micro-framework PHP 7 / jQuery
Expérimentation d'un micro-framework PHP/JS déstiné à un usage personnel, built from scratch.
J'ai trouvé intéressant de prendre pour base une petite application de base de connaissance en ajax, que j'ai réalisé il y a peu de temps. Cela permet d'avoir un aperçu du fonctionnement sur un projet concret tout au long du développement du framework.

# Attention
Ne pas utiliser en production. Je n'assurerais pas la mise à jour régulière du Git.

Certaines parties sont en plein développement ou seront altérées / supprimées dans les jours à venir, c'est pourquoi vous pourrez trouvez des zones de code commentées, inutilisées ou tout simplement incomplètes.

L'ensemble est néanmoins fonctionnel.

# Performances

Jusqu'à présent, l'emprunte sur les performance est relativement conséquente, nottamment sur:
- Les nombreuses entrées / sorties sur le disque
- Le système de routage

Je travaille actuellement sur un système de « compilation ». 

Les gains de performances actuelles sont de l'ordre de 20-25%

# Sécurité

Certaines failles n'ont pas encore été étudiées.
Les rapports d'OWASP serveront de référence à l'avenir.

# Incomplet

- Gestion des différentes pages
- Système de widgets
- Templating
- Gestion des exceptions

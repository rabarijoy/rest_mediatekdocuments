<h1>Présentation de l'API</h1>
Cette API, écrite en PHP, est basée sur la structure de l'API présentée dans le dépôt suivant :<br>
https://github.com/CNED-SLAM/rest_chocolatein<br>
Le readme de ce dépôt présente la structure de la base de l'API (rôle de chaque fichier) et comment l'exploiter.<br>
Les ajouts faits dans cette API ne concernent que les fichiers '.env' (qui contient les données sensibles d'authentification et d'accès à la BDD) et 'MyAccessBDD.php' (dans lequel de nouvelles fonctions ont été ajoutées pour répondre aux demandes de l'application).<br>
Cette API permet d'exécuter des requêtes SQL sur la BDD Mediatek86 créée avec le SGBDR MySQL.<br>
Elle est accessible via une authentification "basique" (avec login="admin", pwd="adminpwd").<br>
Sa vocation actuelle est de répondre aux demandes de l'application MediaTekDocuments, mise en ligne sur le dépôt :<br>
https://github.com/CNED-SLAM/MediaTekDocuments<br>
<br>
Ce dépôt est un fork du dépôt d'origine :<br>
https://github.com/CNED-SLAM/rest_mediatekdocuments<br>
Le README du dépôt d'origine présente la version initiale de l'API avec ses endpoints de base.

<h1>Installation de l'API en local</h1>
Pour tester l'API REST en local, voici le mode opératoire (similaire à celui donné dans le dépôt d'API de base) :
<ul>
   <li>Installer les outils nécessaires (WampServer ou équivalent, NetBeans ou équivalent pour gérer l'API dans un IDE, Postman pour les tests).</li>
   <li>Télécharger le zip du code de l'API et le dézipper dans le dossier www de wampserver (renommer le dossier en "rest_mediatekdocuments", donc en enlevant "_master").</li>
   <li>Si 'Composer' n'est pas installé, le télécharger avec ce lien et l'insstaller : https://getcomposer.org/Composer-Setup.exe </li>
   <li>Dans une fenêtre de commandes ouverte en mode admin, aller dans le dossier de l'API et taper 'composer install' puis valider pour recréer le vendor.</li>
   <li>Récupérer le script metiak86.sql en racine du projet puis, avec phpMyAdmin, créer la BDD mediatek86 et, dans cette BDD, exécuter le script pour remplir la BDD.</li>
   <li>Ouvrir l'API dans NetBeans pour pouvoir analyser le code et le faire évoluer suivant les besoins.</li>
   <li>Pour tester l'API avec Postman, ne pas oublier de configurer l'authentification (onglet "Authorization", Type "Basic Auth", Username "admin", Password "adminpwd".</li>
</ul>
<h1>Exploitation de l'API</h1>
Adresse de l'API (en local) : http://localhost/rest_mediatekdocuments/ <br>
Voici les différentes possibilités de sollicitation de l'API, afin d'agir sur la BDD, en ajoutant des informations directement dans l'URL (visible) et éventuellement dans le body (invisible) suivant les besoins : 
<h2>Récupérer un contenu (select)</h2>
Méthode HTTP : <strong>GET</strong><br>
http://localhost/rest_mediatekdocuments/table/champs (champs optionnel)
<ul>
   <li>'table' doit être remplacé par un nom de table (caractères acceptés : alphanumériques et '_')</li>
   <li>'champs' (optionnel) doit être remplacé par la liste des champs (nom/valeur) qui serviront à la recherche (au format json)</li>
</ul>

<h2>Insérer (insert)</h2>
Méthode HTTP : <strong>POST</strong><br>
http://localhost/rest_mediatekdocuments/table <br>
'table' doit être remplacé par un nom de table (caractères acceptés : alphanumériques et '_')<br>
Dans le body (Dans Postman, onglet 'Body', cocher 'x-www-form-urlencoded'), ajouter :<br>
<ul>
   <li>Key : 'champs'</li>
   <li>Value : liste des champs (nom/valeur) qui serviront à l'insertion (au format json)</li>
</ul>

<h2>Modifier (update)</h2>
Méthode HTTP : <strong>PUT</strong><br>
http://localhost/rest_mediatekdocuments/table/id (id optionnel)<br>
<ul>
   <li>'table' doit être remplacé par un nom de table (caractères acceptés : alphanumériques et '_')</li>
   <li>'id' (optionnel) doit être remplacé par l'identifiant de la ligne à modifier (caractères acceptés : alphanumériques)</li>
</ul>
Dans le body (Dans Postman, onglet 'Body', cocher 'x-www-form-urlencoded'), ajouter :<br>
<ul>
   <li>Key : 'champs'</li>
   <li>Value : liste des champs (nom/valeur) qui serviront à la modification (au format json)</li>
</ul>

<h2>Supprimer (delete)</h2>
Méthode HTTP : <strong>DELETE</strong><br>
http://localhost/rest_mediatekdocuments/table/champs (champs optionnel)<br>
<ul>
   <li>'table' doit être remplacé par un nom de table (caractères acceptés : alphanumériques et '_')</li>
   <li> 'champs' (optionnel) doit être remplacé par la liste des champs (nom/valeur) qui serviront déterminer les lignes à supprimer (au format json</li>
</ul>

<h1>Les fonctionnalités ajoutées</h1>
Dans MyAccessBDD, plusieurs fonctions ont été ajoutées pour répondre aux demandes actuelles de l'application C# MediaTekDocuments :<br>
<ul>
   <li><strong>selectTableSimple : </strong>récupère les lignes des tables simples (genre, public, rayon, etat) contenant juste 'id' et 'libelle', dans l'ordre alphabétique sur 'libelle'. Cette fonction est appelée pour  remplir les combos correspondants.</li>
   <li><strong>selectAllLivres : </strong>récupère la liste des livres avec les informations correspondantes (d'où nécessité de jointures).</li>
   <li><strong>selectAllDvd : </strong>même chose pour les dvd.</li>
   <li><strong>selectAllRevues : </strong>même chose pour les revues.</li>
   <li><strong>selectExemplairesRevue : </strong>récupère les exemplaires d'une revue dont l'id sera donné.</li>
</ul>

Les fonctions et endpoints suivants ont été ajoutés dans le cadre de l'Atelier Professionnel :

<h2>CRUD des documents (livres, DVD, revues)</h2>
Chaque opération d'ajout et de suppression s'effectue dans une transaction PDO afin de maintenir la cohérence des tables liées par héritage (<code>document</code>, <code>livres_dvd</code>, <code>livre</code> / <code>dvd</code> / <code>revue</code>). La suppression vérifie au préalable l'absence d'exemplaires ou de commandes rattachés.
<ul>
   <li><strong>POST /livre</strong> — insère un livre dans les tables <code>document</code>, <code>livres_dvd</code> et <code>livre</code> (transaction).</li>
   <li><strong>PUT /livre/{id}</strong> — modifie un livre existant.</li>
   <li><strong>DELETE /livre/{"id":"X"}</strong> — supprime un livre après vérification des contraintes.</li>
   <li><strong>POST /dvd</strong>, <strong>PUT /dvd/{id}</strong>, <strong>DELETE /dvd/{"id":"X"}</strong> — mêmes opérations pour les DVD.</li>
   <li><strong>POST /revue</strong>, <strong>PUT /revue/{id}</strong>, <strong>DELETE /revue/{"id":"X"}</strong> — mêmes opérations pour les revues.</li>
</ul>

<h2>Gestion des commandes de livres et DVD</h2>
Support complet des tables <code>commande</code>, <code>commandedocument</code> et <code>suivi</code>.
<ul>
   <li><strong>GET /commandedocument/{"idLivreDvd":"X"}</strong> — retourne les commandes d'un livre ou DVD avec la date, le montant, le nombre d'exemplaires, l'étape de suivi et son libellé.</li>
   <li><strong>GET /suivi</strong> — retourne toutes les étapes de suivi.</li>
   <li><strong>POST /commandedocument</strong> — insère une commande dans <code>commande</code> puis <code>commandedocument</code> (transaction).</li>
   <li><strong>PUT /commandedocument/{id}</strong> — met à jour l'étape de suivi (<code>idSuivi</code>) d'une commande.</li>
   <li><strong>DELETE /commandedocument/{"id":"X"}</strong> — supprime une commande de <code>commandedocument</code> puis <code>commande</code> (transaction), après vérification que l'étape le permet.</li>
</ul>

<h2>Gestion des abonnements de revues</h2>
<ul>
   <li><strong>GET /abonnement/{"idRevue":"X"}</strong> — retourne les abonnements d'une revue, triés par date décroissante.</li>
   <li><strong>GET /abonnementexpiration</strong> — retourne les abonnements dont la date de fin est dans les 30 prochains jours.</li>
   <li><strong>POST /abonnement</strong> — insère un abonnement dans <code>commande</code> puis <code>abonnement</code> (transaction).</li>
   <li><strong>DELETE /abonnement/{"id":"X"}</strong> — supprime un abonnement, uniquement si aucune parution n'a été reçue pendant sa période.</li>
</ul>

<h2>Gestion des exemplaires</h2>
<ul>
   <li><strong>GET /exemplaire/{"id":"X"}</strong> — retourne les exemplaires d'un document (livre, DVD ou revue) avec le libellé de l'état (jointure sur <code>etat</code>), triés par <code>dateAchat</code> décroissant. Fonctionne pour tous les types de documents.</li>
   <li><strong>PUT /exemplaire/{idDocument}</strong> — modifie l'état (<code>idEtat</code>) d'un exemplaire identifié par <code>idDocument</code> (URL) et <code>numero</code> (body).</li>
   <li><strong>DELETE /exemplaire/{"id":"X","numero":N}</strong> — supprime un exemplaire identifié par l'id du document et son numéro.</li>
</ul>

<h2>Authentification utilisateur</h2>
<ul>
   <li><strong>GET /utilisateur/{"login":"X","pwd":"Y"}</strong> — vérifie le couple login/mot de passe et retourne les informations de l'utilisateur (id, login, idService, libelleService via jointure sur <code>service</code>). Retourne le code 400 si les identifiants sont absents ou incorrects.</li>
</ul>

<h2>Sécurité</h2>
<ul>
   <li>Un accès direct à la racine de l'API sans route (ex : <code>GET /rest_mediatekdocuments/</code>) retourne désormais HTTP 400 grâce à une règle <code>.htaccess</code> dédiée.</li>
   <li>En-têtes <code>X-Auth-User</code> et <code>X-Auth-Pass</code> lus en alternative à <code>PHP_AUTH_USER</code> / <code>PHP_AUTH_PW</code> pour assurer la compatibilité avec les hébergeurs qui perdent l'en-tête <code>Authorization</code> lors de la réécriture d'URL.</li>
</ul>

<h1>Accès à l'API en ligne</h1>
L'API est déployée sur AwardSpace (offre gratuite) et accessible à l'adresse suivante :<br>
<br>
<code>https://apirestmediatekdocuments.medianewsonline.com/rest_mediatekdocuments/</code><br>
<br>
L'authentification Basic Auth est requise pour toutes les routes :<br>
<ul>
   <li>Username : <code>admin</code></li>
   <li>Password : <code>adminpwd</code></li>
</ul>
Dans Postman, configurer l'onglet <strong>Authorization</strong> avec le type <strong>Basic Auth</strong> et les identifiants ci-dessus.<br>
<br>
Exemple de requête de test :<br>
<code>GET https://apirestmediatekdocuments.medianewsonline.com/rest_mediatekdocuments/genre</code><br>
Réponse attendue : <code>{"code":"200","result":[...]}</code>

<h1>Technologies utilisées</h1>
<ul>
   <li><strong>PHP</strong> — langage principal de l'API, sans framework.</li>
   <li><strong>PDO MySQL</strong> — accès à la base de données avec gestion des transactions et du mode strict (<code>ERRMODE_EXCEPTION</code>).</li>
   <li><strong>Apache / .htaccess</strong> — réécriture d'URL, protection de la racine, compatibilité HTTP Authorization.</li>
   <li><strong>vlucas/phpdotenv</strong> — chargement des variables d'environnement depuis le fichier <code>src/.env</code> (credentials BDD et API non versionnés).</li>
   <li><strong>Composer</strong> — gestion des dépendances PHP.</li>
   <li><strong>Postman</strong> — tests des endpoints (collection <code>MediaTekDocuments_API_Tests.postman_collection.json</code> incluse dans le dépôt).</li>
   <li><strong>phpDocumentor 3</strong> — génération de la documentation HTML depuis les commentaires phpDoc (résultat dans <code>docs/</code>).</li>
</ul>

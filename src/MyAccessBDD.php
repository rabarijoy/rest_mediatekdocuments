<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){  
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "commandedocument" :
                if (!empty($champs)) {
                    return $this->selectCommandesLivreDvd($champs);
                }
                return $this->selectTuplesOneTable($table, $champs);
            case "suivi" :
                return $this->selectSuivi();
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }	
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){
            case "livre" :
                return $this->ajouterLivre($champs ?? []);
            case "dvd" :
                return $this->ajouterDvd($champs ?? []);
            case "revue" :
                return $this->ajouterRevue($champs ?? []);
            case "commandedocument" :
                return $this->ajouterCommandeDocument($champs ?? []);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
            case "livre" :
                return $this->modifierLivre($id ?? '', $champs ?? []);
            case "dvd" :
                return $this->modifierDvd($id ?? '', $champs ?? []);
            case "revue" :
                return $this->modifierRevue($id ?? '', $champs ?? []);
            case "commandedocument" :
                return $this->modifierEtapeSuivi($id ?? '', $champs ?? []);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }	
    }  
    
    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */	
    protected function traitementDelete(string $table, ?array $champs) : ?int{
        switch($table){
            case "livre" :
                return $this->supprimerLivre($champs ?? []);
            case "dvd" :
                return $this->supprimerDvd($champs ?? []);
            case "revue" :
                return $this->supprimerRevue($champs ?? []);
            case "commandedocument" :
                return $this->supprimerCommandeDocument($champs ?? []);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);	
        }
    }

    // =========================================================================
    // LIVRE : ajout, modification, suppression multi-tables
    // =========================================================================

    /**
     * insère un livre dans les tables document, livres_dvd et livre (transaction)
     * @param array $champs
     * @return int|null 1 si succès, null si erreur ou champ manquant
     */
    private function ajouterLivre(array $champs): ?int {
        $required = ['id', 'titre', 'image', 'idRayon', 'idPublic', 'idGenre', 'ISBN', 'auteur', 'collection'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $champs)) {
                return null;
            }
        }
        try {
            $this->conn->beginTransaction();

            $resDoc = $this->conn->updateBDD(
                "INSERT INTO document (id, titre, image, idRayon, idPublic, idGenre) "
                . "VALUES (:id, :titre, :image, :idRayon, :idPublic, :idGenre)",
                [
                    'id'       => $champs['id'],
                    'titre'    => $champs['titre'],
                    'image'    => $champs['image'],
                    'idRayon'  => $champs['idRayon'],
                    'idPublic' => $champs['idPublic'],
                    'idGenre'  => $champs['idGenre'],
                ]
            );
            if ($resDoc === null) {
                $this->conn->rollBack();
                return null;
            }

            $resLd = $this->conn->updateBDD(
                "INSERT INTO livres_dvd (id) VALUES (:id)",
                ['id' => $champs['id']]
            );
            if ($resLd === null) {
                $this->conn->rollBack();
                return null;
            }

            $resLivre = $this->conn->updateBDD(
                "INSERT INTO livre (id, ISBN, auteur, collection) VALUES (:id, :ISBN, :auteur, :collection)",
                [
                    'id'         => $champs['id'],
                    'ISBN'       => $champs['ISBN'],
                    'auteur'     => $champs['auteur'],
                    'collection' => $champs['collection'],
                ]
            );
            if ($resLivre === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * modifie un livre dans les tables document et livre (transaction)
     * @param string $id
     * @param array $champs
     * @return int|null somme des rowCount ou null si erreur
     */
    private function modifierLivre(string $id, array $champs): ?int {
        if (empty($id) || empty($champs)) {
            return null;
        }
        try {
            $this->conn->beginTransaction();

            $resDoc = $this->conn->updateBDD(
                "UPDATE document SET titre=:titre, image=:image, idRayon=:idRayon, "
                . "idPublic=:idPublic, idGenre=:idGenre WHERE id=:id",
                [
                    'titre'    => $champs['titre']    ?? null,
                    'image'    => $champs['image']    ?? null,
                    'idRayon'  => $champs['idRayon']  ?? null,
                    'idPublic' => $champs['idPublic'] ?? null,
                    'idGenre'  => $champs['idGenre']  ?? null,
                    'id'       => $id,
                ]
            );
            if ($resDoc === null) {
                $this->conn->rollBack();
                return null;
            }

            $resLivre = $this->conn->updateBDD(
                "UPDATE livre SET ISBN=:ISBN, auteur=:auteur, collection=:collection WHERE id=:id",
                [
                    'ISBN'       => $champs['ISBN']       ?? null,
                    'auteur'     => $champs['auteur']     ?? null,
                    'collection' => $champs['collection'] ?? null,
                    'id'         => $id,
                ]
            );
            if ($resLivre === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return $resDoc + $resLivre;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * supprime un livre des tables livre, livres_dvd et document (transaction)
     * Refuse si des exemplaires ou commandes existent pour ce livre.
     * @param array $champs doit contenir 'id'
     * @return int|null 1 si succès, null si contrainte non respectée ou erreur
     */
    private function supprimerLivre(array $champs): ?int {
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $id = $champs['id'];

        $resExemplaires = $this->conn->queryBDD(
            "SELECT COUNT(*) as nb FROM exemplaire WHERE id=:id",
            ['id' => $id]
        );
        if ($resExemplaires === null || (int)$resExemplaires[0]['nb'] > 0) {
            return null;
        }

        $resCommandes = $this->conn->queryBDD(
            "SELECT COUNT(*) as nb FROM commandedocument WHERE idLivreDvd=:id",
            ['id' => $id]
        );
        if ($resCommandes === null || (int)$resCommandes[0]['nb'] > 0) {
            return null;
        }

        try {
            $this->conn->beginTransaction();

            $resLivre = $this->conn->updateBDD(
                "DELETE FROM livre WHERE id=:id",
                ['id' => $id]
            );
            if ($resLivre === null) {
                $this->conn->rollBack();
                return null;
            }

            $resLd = $this->conn->updateBDD(
                "DELETE FROM livres_dvd WHERE id=:id",
                ['id' => $id]
            );
            if ($resLd === null) {
                $this->conn->rollBack();
                return null;
            }

            $resDoc = $this->conn->updateBDD(
                "DELETE FROM document WHERE id=:id",
                ['id' => $id]
            );
            if ($resDoc === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    // =========================================================================
    // DVD : ajout, modification, suppression multi-tables
    // =========================================================================

    /**
     * insère un dvd dans les tables document, livres_dvd et dvd (transaction)
     * @param array $champs
     * @return int|null 1 si succès, null si erreur ou champ manquant
     */
    private function ajouterDvd(array $champs): ?int {
        $required = ['id', 'titre', 'image', 'idRayon', 'idPublic', 'idGenre', 'synopsis', 'realisateur', 'duree'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $champs)) {
                return null;
            }
        }
        try {
            $this->conn->beginTransaction();

            $resDoc = $this->conn->updateBDD(
                "INSERT INTO document (id, titre, image, idRayon, idPublic, idGenre) "
                . "VALUES (:id, :titre, :image, :idRayon, :idPublic, :idGenre)",
                [
                    'id'       => $champs['id'],
                    'titre'    => $champs['titre'],
                    'image'    => $champs['image'],
                    'idRayon'  => $champs['idRayon'],
                    'idPublic' => $champs['idPublic'],
                    'idGenre'  => $champs['idGenre'],
                ]
            );
            if ($resDoc === null) {
                $this->conn->rollBack();
                return null;
            }

            $resLd = $this->conn->updateBDD(
                "INSERT INTO livres_dvd (id) VALUES (:id)",
                ['id' => $champs['id']]
            );
            if ($resLd === null) {
                $this->conn->rollBack();
                return null;
            }

            $resDvd = $this->conn->updateBDD(
                "INSERT INTO dvd (id, synopsis, realisateur, duree) VALUES (:id, :synopsis, :realisateur, :duree)",
                [
                    'id'          => $champs['id'],
                    'synopsis'    => $champs['synopsis'],
                    'realisateur' => $champs['realisateur'],
                    'duree'       => (int)$champs['duree'],
                ]
            );
            if ($resDvd === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * modifie un dvd dans les tables document et dvd (transaction)
     * @param string $id
     * @param array $champs
     * @return int|null somme des rowCount ou null si erreur
     */
    private function modifierDvd(string $id, array $champs): ?int {
        if (empty($id) || empty($champs)) {
            return null;
        }
        try {
            $this->conn->beginTransaction();

            $resDoc = $this->conn->updateBDD(
                "UPDATE document SET titre=:titre, image=:image, idRayon=:idRayon, "
                . "idPublic=:idPublic, idGenre=:idGenre WHERE id=:id",
                [
                    'titre'    => $champs['titre']    ?? null,
                    'image'    => $champs['image']    ?? null,
                    'idRayon'  => $champs['idRayon']  ?? null,
                    'idPublic' => $champs['idPublic'] ?? null,
                    'idGenre'  => $champs['idGenre']  ?? null,
                    'id'       => $id,
                ]
            );
            if ($resDoc === null) {
                $this->conn->rollBack();
                return null;
            }

            $resDvd = $this->conn->updateBDD(
                "UPDATE dvd SET synopsis=:synopsis, realisateur=:realisateur, duree=:duree WHERE id=:id",
                [
                    'synopsis'    => $champs['synopsis']    ?? null,
                    'realisateur' => $champs['realisateur'] ?? null,
                    'duree'       => isset($champs['duree']) ? (int)$champs['duree'] : null,
                    'id'          => $id,
                ]
            );
            if ($resDvd === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return $resDoc + $resDvd;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * supprime un dvd des tables dvd, livres_dvd et document (transaction)
     * Refuse si des exemplaires ou commandes existent pour ce dvd.
     * @param array $champs doit contenir 'id'
     * @return int|null 1 si succès, null si contrainte non respectée ou erreur
     */
    private function supprimerDvd(array $champs): ?int {
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $id = $champs['id'];

        $resExemplaires = $this->conn->queryBDD(
            "SELECT COUNT(*) as nb FROM exemplaire WHERE id=:id",
            ['id' => $id]
        );
        if ($resExemplaires === null || (int)$resExemplaires[0]['nb'] > 0) {
            return null;
        }

        $resCommandes = $this->conn->queryBDD(
            "SELECT COUNT(*) as nb FROM commandedocument WHERE idLivreDvd=:id",
            ['id' => $id]
        );
        if ($resCommandes === null || (int)$resCommandes[0]['nb'] > 0) {
            return null;
        }

        try {
            $this->conn->beginTransaction();

            $resDvd = $this->conn->updateBDD(
                "DELETE FROM dvd WHERE id=:id",
                ['id' => $id]
            );
            if ($resDvd === null) {
                $this->conn->rollBack();
                return null;
            }

            $resLd = $this->conn->updateBDD(
                "DELETE FROM livres_dvd WHERE id=:id",
                ['id' => $id]
            );
            if ($resLd === null) {
                $this->conn->rollBack();
                return null;
            }

            $resDoc = $this->conn->updateBDD(
                "DELETE FROM document WHERE id=:id",
                ['id' => $id]
            );
            if ($resDoc === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    // =========================================================================
    // REVUE : ajout, modification, suppression multi-tables
    // =========================================================================

    /**
     * insère une revue dans les tables document et revue (transaction)
     * @param array $champs
     * @return int|null 1 si succès, null si erreur ou champ manquant
     */
    private function ajouterRevue(array $champs): ?int {
        $required = ['id', 'titre', 'image', 'idRayon', 'idPublic', 'idGenre', 'periodicite', 'delaiMiseADispo'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $champs)) {
                return null;
            }
        }
        try {
            $this->conn->beginTransaction();

            $resDoc = $this->conn->updateBDD(
                "INSERT INTO document (id, titre, image, idRayon, idPublic, idGenre) "
                . "VALUES (:id, :titre, :image, :idRayon, :idPublic, :idGenre)",
                [
                    'id'       => $champs['id'],
                    'titre'    => $champs['titre'],
                    'image'    => $champs['image'],
                    'idRayon'  => $champs['idRayon'],
                    'idPublic' => $champs['idPublic'],
                    'idGenre'  => $champs['idGenre'],
                ]
            );
            if ($resDoc === null) {
                $this->conn->rollBack();
                return null;
            }

            $resRevue = $this->conn->updateBDD(
                "INSERT INTO revue (id, periodicite, delaiMiseADispo) VALUES (:id, :periodicite, :delaiMiseADispo)",
                [
                    'id'              => $champs['id'],
                    'periodicite'     => $champs['periodicite'],
                    'delaiMiseADispo' => $champs['delaiMiseADispo'],
                ]
            );
            if ($resRevue === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * modifie une revue dans les tables document et revue (transaction)
     * @param string $id
     * @param array $champs
     * @return int|null somme des rowCount ou null si erreur
     */
    private function modifierRevue(string $id, array $champs): ?int {
        if (empty($id) || empty($champs)) {
            return null;
        }
        try {
            $this->conn->beginTransaction();

            $resDoc = $this->conn->updateBDD(
                "UPDATE document SET titre=:titre, image=:image, idRayon=:idRayon, "
                . "idPublic=:idPublic, idGenre=:idGenre WHERE id=:id",
                [
                    'titre'    => $champs['titre']    ?? null,
                    'image'    => $champs['image']    ?? null,
                    'idRayon'  => $champs['idRayon']  ?? null,
                    'idPublic' => $champs['idPublic'] ?? null,
                    'idGenre'  => $champs['idGenre']  ?? null,
                    'id'       => $id,
                ]
            );
            if ($resDoc === null) {
                $this->conn->rollBack();
                return null;
            }

            $resRevue = $this->conn->updateBDD(
                "UPDATE revue SET periodicite=:periodicite, delaiMiseADispo=:delaiMiseADispo WHERE id=:id",
                [
                    'periodicite'     => $champs['periodicite']     ?? null,
                    'delaiMiseADispo' => $champs['delaiMiseADispo'] ?? null,
                    'id'              => $id,
                ]
            );
            if ($resRevue === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return $resDoc + $resRevue;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * supprime une revue des tables revue et document (transaction)
     * Refuse si des exemplaires ou abonnements existent pour cette revue.
     * @param array $champs doit contenir 'id'
     * @return int|null 1 si succès, null si contrainte non respectée ou erreur
     */
    private function supprimerRevue(array $champs): ?int {
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $id = $champs['id'];

        $resExemplaires = $this->conn->queryBDD(
            "SELECT COUNT(*) as nb FROM exemplaire WHERE id=:id",
            ['id' => $id]
        );
        if ($resExemplaires === null || (int)$resExemplaires[0]['nb'] > 0) {
            return null;
        }

        $resAbonnements = $this->conn->queryBDD(
            "SELECT COUNT(*) as nb FROM abonnement WHERE idRevue=:id",
            ['id' => $id]
        );
        if ($resAbonnements === null || (int)$resAbonnements[0]['nb'] > 0) {
            return null;
        }

        try {
            $this->conn->beginTransaction();

            $resRevue = $this->conn->updateBDD(
                "DELETE FROM revue WHERE id=:id",
                ['id' => $id]
            );
            if ($resRevue === null) {
                $this->conn->rollBack();
                return null;
            }

            $resDoc = $this->conn->updateBDD(
                "DELETE FROM document WHERE id=:id",
                ['id' => $id]
            );
            if ($resDoc === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    // =========================================================================
    // COMMANDE DOCUMENT : sélection, ajout, modification de l'étape, suppression
    // =========================================================================

    /**
     * récupère toutes les commandes d'un livre ou DVD avec le libellé de l'étape de suivi
     * @param array $champs doit contenir 'idLivreDvd'
     * @return array|null tableau de résultats ou null si champ manquant
     */
    private function selectCommandesLivreDvd(array $champs): ?array {
        if (!array_key_exists('idLivreDvd', $champs)) {
            return null;
        }
        $requete = "SELECT cd.id, c.dateCommande, c.montant, cd.nbExemplaire, cd.idSuivi, "
                 . "s.libelle as libelleEtape, cd.idLivreDvd "
                 . "FROM commandedocument cd "
                 . "JOIN commande c ON cd.id COLLATE utf8mb4_0900_ai_ci = c.id "
                 . "LEFT JOIN suivi s ON cd.idSuivi = s.id "
                 . "WHERE cd.idLivreDvd = :idLivreDvd "
                 . "ORDER BY c.dateCommande DESC";
        return $this->conn->queryBDD($requete, ['idLivreDvd' => $champs['idLivreDvd']]);
    }

    /**
     * insère une commande de document dans les tables commande et commandedocument (transaction)
     * L'étape de suivi est initialisée à '00001' (en cours de traitement).
     * @param array $champs doit contenir : idCommande, dateCommande, montant, nbExemplaire, idLivreDvd
     * @return int|null 1 si succès, null si champ manquant ou erreur
     */
    private function ajouterCommandeDocument(array $champs): ?int {
        $required = ['idCommande', 'dateCommande', 'montant', 'nbExemplaire', 'idLivreDvd'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $champs)) {
                return null;
            }
        }
        $idSuivi = $champs['idSuivi'] ?? '00001';
        try {
            $this->conn->beginTransaction();

            $resCommande = $this->conn->updateBDD(
                "INSERT INTO commande (id, dateCommande, montant) "
                . "VALUES (:id, :dateCommande, :montant)",
                [
                    'id'           => $champs['idCommande'],
                    'dateCommande' => $champs['dateCommande'],
                    'montant'      => $champs['montant'],
                ]
            );
            if ($resCommande === null) {
                $this->conn->rollBack();
                return null;
            }

            $resDoc = $this->conn->updateBDD(
                "INSERT INTO commandedocument (id, nbExemplaire, idLivreDvd, idSuivi) "
                . "VALUES (:id, :nbExemplaire, :idLivreDvd, :idSuivi)",
                [
                    'id'           => $champs['idCommande'],
                    'nbExemplaire' => (int)$champs['nbExemplaire'],
                    'idLivreDvd'   => $champs['idLivreDvd'],
                    'idSuivi'      => $idSuivi,
                ]
            );
            if ($resDoc === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * met à jour l'étape de suivi d'une commande de document
     * @param string $id identifiant de la commande
     * @param array $champs doit contenir 'idSuivi'
     * @return int|null rowCount ou null si champ manquant ou erreur
     */
    private function modifierEtapeSuivi(string $id, array $champs): ?int {
        if (empty($id) || !array_key_exists('idSuivi', $champs)) {
            return null;
        }
        return $this->conn->updateBDD(
            "UPDATE commandedocument SET idSuivi = :idSuivi WHERE id = :id",
            [
                'idSuivi' => $champs['idSuivi'],
                'id'      => $id,
            ]
        );
    }

    /**
     * supprime une commande de document des tables commandedocument et commande (transaction)
     * Refuse si la commande est déjà livrée ('00003') ou réglée ('00004').
     * @param array $champs doit contenir 'id'
     * @return int|null 1 si succès, null si contrainte non respectée ou erreur
     */
    private function supprimerCommandeDocument(array $champs): ?int {
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $id = $champs['id'];

        $resSuivi = $this->conn->queryBDD(
            "SELECT idSuivi FROM commandedocument WHERE id = :id",
            ['id' => $id]
        );
        if ($resSuivi === null || empty($resSuivi)) {
            return null;
        }
        $idSuivi = $resSuivi[0]['idSuivi'];
        if (in_array($idSuivi, ['00003', '00004'])) {
            return null;
        }

        try {
            $this->conn->beginTransaction();

            $resCd = $this->conn->updateBDD(
                "DELETE FROM commandedocument WHERE id = :id",
                ['id' => $id]
            );
            if ($resCd === null) {
                $this->conn->rollBack();
                return null;
            }

            $resC = $this->conn->updateBDD(
                "DELETE FROM commande WHERE id = :id",
                ['id' => $id]
            );
            if ($resC === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    // =========================================================================
    // SUIVI : sélection
    // =========================================================================

    /**
     * récupère toutes les étapes de suivi triées par identifiant
     * @return array|null
     */
    private function selectSuivi(): ?array {
        return $this->conn->queryBDD("SELECT * FROM suivi ORDER BY id");
    }

    // =========================================================================
    // Méthodes génériques (inchangées)
    // =========================================================================

    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);  
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);	          
            return $this->conn->queryBDD($requete, $champs);
        }
    }	

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);				
        $champs["id"] = $id;
        $requete .= " where id=:id;";		
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);   
        return $this->conn->updateBDD($requete, $champs);	        
    }
 
    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";		
        return $this->conn->queryBDD($requete);	    
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";		
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";	
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs 
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }		    
    
}

<?php
/**
 * backup_trigger.php — Script de sauvegarde BDD déclenché par cron-job.org
 *
 * Appelé quotidiennement par une tâche cron-job.org via une requête HTTP GET :
 *   https://[domaine]/rest_mediatekdocuments/scripts/backup_trigger.php?token=VOTRE_TOKEN_SECRET
 *
 * Le token protège le script contre les appels non autorisés.
 * Les sauvegardes sont stockées dans le dossier savebdd/ au même niveau que scripts/.
 */

// ─── Protection par token ─────────────────────────────────────────────────────
$token = $_GET['token'] ?? '';
if ($token !== 'tokenmedia') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['code' => 403, 'message' => 'Accès refusé']);
    exit;
}

// ─── Configuration BDD (identique au .env de l'API) ──────────────────────────
$host = 'fdb1033.awardspace.net';
$user = '4744744_mediatek86';
$pwd  = 'mediatekpwdpwd1';
$db   = '4744744_mediatek86';

// ─── Chemin de sauvegarde ─────────────────────────────────────────────────────
$date      = date('Y-m-d');
$saveDir   = __DIR__ . '/savebdd';
$file      = $saveDir . '/bddbackup_' . $date . '.sql';
$fileGz    = $file . '.gz';

// Créer le dossier de sauvegarde si nécessaire
if (!is_dir($saveDir)) {
    if (!mkdir($saveDir, 0755, true)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['code' => 500, 'message' => 'Impossible de créer le dossier savebdd/']);
        exit;
    }
}

// Supprimer la sauvegarde du jour si elle existe déjà (re-exécution)
if (file_exists($fileGz)) {
    unlink($fileGz);
}

// ─── Exécuter mysqldump ───────────────────────────────────────────────────────
$cmd = sprintf(
    'mysqldump -h %s -u %s -p%s --databases %s --single-transaction > %s 2>&1',
    escapeshellarg($host),
    escapeshellarg($user),
    escapeshellarg($pwd),
    escapeshellarg($db),
    escapeshellarg($file)
);

exec($cmd, $output, $returnCode);

header('Content-Type: application/json');

if ($returnCode !== 0) {
    // mysqldump a échoué (commande bloquée ou credentials incorrects)
    http_response_code(500);
    echo json_encode([
        'code'    => 500,
        'message' => 'Erreur mysqldump — sauvegarde impossible sur cet hébergeur',
        'detail'  => implode("\n", $output),
        'note'    => 'Si mysqldump est bloqué, effectuer une export manuelle via phpMyAdmin.'
    ]);
    exit;
}

// Compresser le fichier SQL
exec('gzip ' . escapeshellarg($file), $gzOutput, $gzCode);

if ($gzCode !== 0 || !file_exists($fileGz)) {
    // Compression échouée : conserver le .sql non compressé
    echo json_encode([
        'code'    => 200,
        'message' => 'Sauvegarde OK (non compressée — gzip indisponible)',
        'fichier' => 'bddbackup_' . $date . '.sql',
        'date'    => $date
    ]);
    exit;
}

// ─── Succès ───────────────────────────────────────────────────────────────────
echo json_encode([
    'code'    => 200,
    'message' => 'Sauvegarde OK',
    'fichier' => 'bddbackup_' . $date . '.sql.gz',
    'date'    => $date
]);

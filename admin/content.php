<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

// 1. Configuration des tables et de leurs champs (Mapping)
$tablesConfig = [
    'services' => [
        'name' => 'Services',
        'fields' => [
            'title' => ['label' => 'Titre', 'type' => 'text'],
            'icon' => ['label' => 'Icône (FontAwesome)', 'type' => 'text'],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'display_order' => ['label' => 'Ordre', 'type' => 'number']
        ]
    ],
    'pricing' => [
        'name' => 'Tarifs',
        'fields' => [
            'service_type' => ['label' => 'Code Service', 'type' => 'text'],
            'price_htva' => ['label' => 'Prix HTVA', 'type' => 'number', 'step' => '0.01'],
            'description' => ['label' => 'Description', 'type' => 'text']
        ]
    ],
    'team' => [
        'name' => 'Équipe',
        'fields' => [
            'name' => ['label' => 'Nom', 'type' => 'text'],
            'role' => ['label' => 'Rôle', 'type' => 'text'],
            'bio' => ['label' => 'Biographie', 'type' => 'textarea'],
            'image_url' => ['label' => 'Photo', 'type' => 'file']
        ]
    ],
    'certifications' => [
        'name' => 'Agréments',
        'fields' => [
            'region' => ['label' => 'Région', 'type' => 'text'],
            'title' => ['label' => 'Intitulé', 'type' => 'text'],
            'number' => ['label' => 'Numéro', 'type' => 'text']
        ]
    ],
    'projects' => [
        'name' => 'Réalisations (Portfolio)',
        'fields' => [
            'title' => ['label' => 'Titre du chantier', 'type' => 'text'],
            'city' => ['label' => 'Ville', 'type' => 'text'],
            'category' => ['label' => 'Catégorie (ex: Chauffage)', 'type' => 'text'],
            'date_completion' => ['label' => 'Date Fin', 'type' => 'date'],
            'image_url' => ['label' => 'Photo du chantier', 'type' => 'file'],
            'description' => ['label' => 'Description détaillée', 'type' => 'textarea']
        ]
    ],
    'users' => [
        'name' => 'Administrateurs',
        'fields' => [
            'username' => ['label' => 'Identifiant', 'type' => 'text'],
            'email' => ['label' => 'Email', 'type' => 'email'],
            'password' => ['label' => 'Mot de passe (Laisser vide si inchangé)', 'type' => 'password']
        ]
    ]
];

// Récupération de la table courante
$currentTable = $_GET['table'] ?? 'services';
if (!array_key_exists($currentTable, $tablesConfig)) {
    die("Table non autorisée.");
}
$config = $tablesConfig[$currentTable];
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$msg = '';

// --- TRAITEMENT POST (Ajout / Modif / Suppression) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // === VÉRIFICATION CSRF GLOBALE POUR CE FICHIER ===
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
        die("Erreur de sécurité CSRF : L'action a été bloquée.");
    }
    // =================================================

    if (isset($_POST['delete_id'])) {
        // SUPPRESSION
        $stmt = $pdo->prepare("DELETE FROM $currentTable WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $msg = "Élément supprimé avec succès.";
    } else {
        // AJOUT OU ÉDITION
        $data = [];
        $fields = array_keys($config['fields']);
        $placeholders = [];
        $updateStr = [];
        $insertFields = [];

        // Récupération de l'item existant
        $existingItem = [];
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM $currentTable WHERE id = ?");
            $stmt->execute([$id]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        foreach ($fields as $f) {
            $fieldConfig = $config['fields'][$f];

            // 1. GESTION MOT DE PASSE (USERS)
            if ($currentTable === 'users' && $f === 'password') {
                if (!empty($_POST[$f])) {
                    $data[] = password_hash($_POST[$f], PASSWORD_DEFAULT);
                    $insertFields[] = $f;
                    $placeholders[] = '?';
                    $updateStr[] = "$f = ?";
                } elseif (!$id) {
                    die("Mot de passe requis pour nouvel utilisateur");
                }
                continue;
            }

            // 2. GESTION SÉCURISÉE UPLOAD FICHIER (IMAGES)
            if ($fieldConfig['type'] === 'file') {
                $val = $existingItem[$f] ?? '';

                if (isset($_FILES[$f]) && $_FILES[$f]['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES[$f]['tmp_name'];

                    // --- VÉRIFICATION DE SÉCURITÉ ---
                    // a. Vérification basique (extension)
                    $fileName = $_FILES[$f]['name'];
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                    // b. Vérification avancée (Type MIME réel)
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);

                    $allowedMimeTypes = [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'image/gif'
                    ];

                    // On s'assure que l'extension ET le type MIME sont valides
                    if (in_array($ext, $allowedExtensions) && in_array($mimeType, $allowedMimeTypes)) {

                        // Nom unique, long et sécurisé
                        $newName = 'portfolio_' . uniqid('', true) . '.' . $ext;

                        // Dossier de destination : public/img/portfolio/
                        $targetDir = __DIR__ . '/../public/img/portfolio/';

                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }

                        $destination = $targetDir . $newName;

                        if (move_uploaded_file($tmpName, $destination)) {
                            $val = 'img/portfolio/' . $newName;
                        } else {
                            $msg = "Erreur lors de l'enregistrement de l'image sur le serveur.";
                        }
                    } else {
                        $msg = "Le fichier uploadé n'est pas une image valide (falsification détectée ou format non supporté).";
                    }
                } elseif (isset($_FILES[$f]) && $_FILES[$f]['error'] !== UPLOAD_ERR_NO_FILE) {
                    $msg = "Erreur lors du téléchargement de l'image (Code d'erreur: " . $_FILES[$f]['error'] . ").";
                }

                $data[] = $val;
                $insertFields[] = $f;
                $placeholders[] = '?';
                $updateStr[] = "$f = ?";

                continue;
            }

            // 3. GESTION NORMALE (TEXTE, DATE, ETC.)
            $val = $_POST[$f] ?? null;
            $data[] = $val;
            $insertFields[] = $f;
            $placeholders[] = '?';
            $updateStr[] = "$f = ?";
        }

        // Si l'upload n'a pas généré d'erreur (sinon $msg serait rempli et empêcherait l'insertion si on gérait mieux, mais on va simplement afficher l'erreur si besoin)
        if (empty($msg) || $msg === "Élément supprimé avec succès.") {
            try {
                if ($id) {
                    // UPDATE
                    $data[] = $id;
                    $sql = "UPDATE $currentTable SET " . implode(', ', $updateStr) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data);
                    $msg = "Modification enregistrée.";
                    $action = 'list';
                } else {
                    // INSERT
                    $sql = "INSERT INTO $currentTable (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($data);
                    $msg = "Nouvel élément ajouté.";
                    $action = 'list';
                }
            } catch (Exception $e) {
                $msg = "Erreur lors de l'enregistrement en base de données : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion : <?= htmlspecialchars($config['name']) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <h2>Gestion : <?= htmlspecialchars($config['name']) ?></h2>

    <?php if($msg): ?>
        <div style="background: <?= strpos($msg, 'Erreur') !== false || strpos($msg, 'valide') !== false ? '#f8d7da' : '#d4edda' ?>;
                color: <?= strpos($msg, 'Erreur') !== false || strpos($msg, 'valide') !== false ? '#721c24' : '#155724' ?>;
                padding:15px; border-radius:5px; margin-bottom:20px;
                border:1px solid <?= strpos($msg, 'Erreur') !== false || strpos($msg, 'valide') !== false ? '#f5c6cb' : '#c3e6cb' ?>;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'edit' || $action === 'create'):
        $item = [];
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM $currentTable WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        ?>
        <div class="crud-form">
            <h3 style="color:var(--primary); border-bottom:2px solid var(--secondary); padding-bottom:10px; margin-bottom:20px;">
                <?= $id ? 'Modifier' : 'Ajouter' ?>
            </h3>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">

                <?php foreach ($config['fields'] as $key => $fieldConfig): ?>
                    <div class="form-group">
                        <label><?= htmlspecialchars($fieldConfig['label']) ?></label>

                        <?php if ($fieldConfig['type'] === 'textarea'): ?>
                            <textarea name="<?= $key ?>" rows="5"><?= htmlspecialchars($item[$key] ?? '') ?></textarea>

                        <?php elseif ($fieldConfig['type'] === 'file'): ?>
                            <?php if (!empty($item[$key])): ?>
                                <div style="margin-bottom: 10px; padding: 5px; border: 1px solid #ddd; display: inline-block; border-radius: 5px;">
                                    <img src="../public/<?= htmlspecialchars($item[$key]) ?>" style="max-height: 150px; display: block;" alt="Image actuelle">
                                    <small style="color: #666;">Actuelle : <?= htmlspecialchars($item[$key]) ?></small>
                                </div>
                                <br>
                            <?php endif; ?>

                            <input type="file" name="<?= $key ?>" accept="image/*">
                            <small style="display:block; margin-top:5px; color:#666;">Formats: JPG, PNG, WEBP. Laissez vide pour conserver l'image actuelle.</small>

                        <?php else: ?>
                            <input type="<?= $fieldConfig['type'] ?>"
                                   name="<?= $key ?>"
                                   value="<?= $fieldConfig['type'] !== 'password' ? htmlspecialchars($item[$key] ?? '') : '' ?>"
                                <?= ($fieldConfig['type'] == 'number' && isset($fieldConfig['step'])) ? 'step="'.$fieldConfig['step'].'"' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top:30px; text-align:right;">
                    <a href="?table=<?= htmlspecialchars($currentTable) ?>" style="margin-right:15px; color:var(--text-muted); font-size:0.9rem;">Annuler</a>
                    <button type="submit" class="btn-admin" style="width:auto; padding:10px 30px;">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>

    <?php else: // LIST VIEW ?>

        <a href="?table=<?= htmlspecialchars($currentTable) ?>&action=create" class="btn-add">
            <i class="fas fa-plus"></i> Ajouter un élément
        </a>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <?php
                    $i=0;
                    foreach($config['fields'] as $k => $v) {
                        if($i < 3 && $v['type'] != 'password') {
                            echo "<th>" . htmlspecialchars($v['label']) . "</th>";
                        }
                        $i++;
                    }
                    ?>
                    <th style="width:120px; text-align:right;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $rows = $pdo->query("SELECT * FROM $currentTable ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                foreach($rows as $row): ?>
                    <tr>
                        <?php
                        $i=0;
                        foreach($config['fields'] as $k => $v) {
                            if($i < 3 && $v['type'] != 'password') {
                                $val = $row[$k] ?? '';

                                if ($v['type'] === 'file' && !empty($val)) {
                                    echo "<td><img src='../public/" . htmlspecialchars($val) . "' style='height: 50px; width: auto; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'></td>";
                                } else {
                                    if (strlen($val) > 50) $val = substr($val, 0, 50) . '...';
                                    echo "<td>" . htmlspecialchars($val) . "</td>";
                                }
                            }
                            $i++;
                        }
                        ?>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="?table=<?= htmlspecialchars($currentTable) ?>&action=edit&id=<?= $row['id'] ?>" class="btn-action btn-edit" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet élément ?');">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">
                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn-action btn-delete" title="Supprimer" style="cursor:pointer;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
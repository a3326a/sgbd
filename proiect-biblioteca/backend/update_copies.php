<?php

session_start();


if (!isset($_SESSION['id_user']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.html');
    exit;
}


$id_carte = intval($_POST['id_carte'] ?? 0);
$action   = $_POST['action'] ?? ''; 

if ($id_carte <= 0 || !in_array($action, ['increase', 'decrease'])) {
    header('Location: ../admin_dashboard.php?error=Date invalide.');
    exit;
}

$username = 'student';
$password = 'STUDENT';
$tns = '(DESCRIPTION=
           (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))
           (CONNECT_DATA=(SID=XE))
         )';
$dsn = "oci:dbname=" . $tns . ";charset=AL32UTF8";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    
    $sqlCountRez = "
      SELECT COUNT(*) AS cnt
      FROM Rezervari
      WHERE id_carte = :id_carte
        AND status = 'activ'
    ";
    $stmtCountRez = $pdo->prepare($sqlCountRez);
    $stmtCountRez->bindParam(':id_carte', $id_carte, PDO::PARAM_INT);
    $stmtCountRez->execute();
    $rezCount = (int)$stmtCountRez->fetch(PDO::FETCH_ASSOC)['CNT'];

    
    $sqlGet = "
      SELECT nr_exemplare
      FROM Carti
      WHERE id_carte = :id_carte
      FOR UPDATE
    ";
    $stmtGet = $pdo->prepare($sqlGet);
    $stmtGet->bindParam(':id_carte', $id_carte, PDO::PARAM_INT);
    $stmtGet->execute();
    $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        header('Location: ../admin_dashboard.php?error=Carte inexistentă.');
        exit;
    }

    $nrCurent = (int)$row['NR_EXEMPLARE'];

    
    if ($action === 'increase') {
        $nrNou = $nrCurent + 1;
    } else {
        
        if ($nrCurent - 1 < $rezCount) {
            header('Location: ../admin_dashboard.php?error=Nu poți scădea sub numărul de rezervări active (' . $rezCount . ').');
            exit;
        }
        $nrNou = $nrCurent - 1;
    }

    
    $sqlUpdate = "
      UPDATE Carti
      SET nr_exemplare = :nr_nou
      WHERE id_carte = :id_carte
    ";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':nr_nou',   $nrNou,   PDO::PARAM_INT);
    $stmtUpdate->bindParam(':id_carte', $id_carte, PDO::PARAM_INT);
    $stmtUpdate->execute();

    
    header('Location: ../admin_dashboard.php');
    exit;

} catch (PDOException $e) {
    $msg = 'Eroare la actualizare: ' . $e->getMessage();
    header('Location: ../admin_dashboard.php?error=' . urlencode($msg));
    exit;
}

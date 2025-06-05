<?php

session_start();


if (!isset($_SESSION['id_user'])) {
    header('Location: login.html');
    exit;
}

$id_user  = $_SESSION['id_user'];
$id_carte = $_POST['id_carte'] ?? null;

if (!$id_carte) {
   
    header('Location: user_dashboard.php?error=no_book_id');
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

    
    $sqlPL = "
      BEGIN
        Rezerva_Carte(:p_id_user, :p_id_carte);
      END;
    ";
    $stmt = $pdo->prepare($sqlPL);
    $stmt->bindParam(':p_id_user',  $id_user,   PDO::PARAM_INT);
    $stmt->bindParam(':p_id_carte', $id_carte, PDO::PARAM_INT);

    
    try {
        $stmt->execute();
        
        header('Location: user_dashboard.php?success=rezervare_ok');
        exit;
    } catch (PDOException $ePL) {
        
        $msg = $ePL->getMessage();
        
        if (preg_match('/ORA-(\d+):\s*(.*)/', $msg, $m)) {
            $oraCode = intval($m[1]);
            $oraMsg  = trim($m[2]);
           
            if ($oraCode === 20001 || $oraCode === 20002) {
                $errParam = ($oraCode === 20001) ? 'already_reserved' : 'no_exemplare';
                header("Location: user_dashboard.php?error=$errParam&msg=" . urlencode($oraMsg));
                exit;
            }
        }
        
        header("Location: user_dashboard.php?error=other&msg=" . urlencode($msg));
        exit;
    }

} catch (PDOException $e) {
    echo "Eroare la conectare/interogare: " . htmlspecialchars($e->getMessage());
    exit;
}

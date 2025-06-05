<?php

session_start();


if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$id_user = $_SESSION['id_user'];


$email_nou       = trim($_POST['email_nou'] ?? '');
$parola_curenta  = trim($_POST['parola_curenta'] ?? '');


if ($email_nou === '' || $parola_curenta === '') {
    header('Location: ../user_dashboard.php?error=Toate câmpurile sunt obligatorii.');
    exit;
}

if (!filter_var($email_nou, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../user_dashboard.php?error=Email invalid.');
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

   
    $sqlFetch = "SELECT parola FROM Utilizatori WHERE id_user = :id_user";
    $stmtFetch = $pdo->prepare($sqlFetch);
    $stmtFetch->bindParam(':id_user', $id_user, PDO::PARAM_INT);
    $stmtFetch->execute();
    $row = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
       
        header('Location: ../user_dashboard.php?error=Cont inexistent.');
        exit;
    }

    $parola_in_db = $row['PAROLA']; 

    
    if ($parola_curenta !== $parola_in_db) {
        header('Location: ../user_dashboard.php?error=Parola curentă nu coincide.');
        exit;
    }

    
    $sqlCheck = "
      SELECT COUNT(*) AS cnt 
      FROM Utilizatori 
      WHERE email = :email_nou 
        AND id_user != :id_user
    ";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindParam(':email_nou', $email_nou, PDO::PARAM_STR);
    $stmtCheck->bindParam(':id_user',   $id_user,   PDO::PARAM_INT);
    $stmtCheck->execute();
    $rezCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($rezCheck['CNT'] > 0) {
        header('Location: ../user_dashboard.php?error=Email-ul este deja folosit de un alt utilizator.');
        exit;
    }

    
    $sqlUpdate = "
      UPDATE Utilizatori
      SET email = :email_nou
      WHERE id_user = :id_user
    ";
    $stmtUp = $pdo->prepare($sqlUpdate);
    $stmtUp->bindParam(':email_nou', $email_nou, PDO::PARAM_STR);
    $stmtUp->bindParam(':id_user',   $id_user,   PDO::PARAM_INT);
    $stmtUp->execute();

    
    header('Location: ../user_dashboard.php?success=Email-ul a fost actualizat cu succes.');
    exit;

} catch (PDOException $e) {
    
    $msg = "Eroare la actualizare: " . $e->getMessage();
    header('Location: ../user_dashboard.php?error=' . urlencode($msg));
    exit;
}

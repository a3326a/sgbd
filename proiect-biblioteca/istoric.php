<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.html');
    exit;
}

$id_user = $_SESSION['id_user'];
$nume    = $_SESSION['nume'];

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

    
    $sql = "
      SELECT actiune, timp
      FROM IstoricActiuni
      WHERE id_user = :id_user
      ORDER BY timp DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_user', $id_user, PDO::PARAM_INT);
    $stmt->execute();
    $istoric_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $istoric = [];
    $seen = [];
    foreach ($istoric_raw as $linie) {
        
        $cheie = $linie['ACTIUNE'] . '|' . $linie['TIMP'];
        if (!isset($seen[$cheie])) {
            $seen[$cheie] = true;
            $istoric[] = $linie;
        }
    }

} catch (PDOException $e) {
    echo "Eroare la interogare istoricului: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Istoric Acțiuni – Biblioteca</title>
  <link rel="stylesheet" href="css/istoric.css">
</head>
<body>
  <header>
    <h2>Istoricul tău, <?php echo htmlspecialchars($nume); ?></h2>
    <nav>
      <a href="user_dashboard.php">Dashboard</a> |
      <a href="backend/logout.php">Logout</a>
    </nav>
  </header>

  <main>
    <h3>Acțiuni efectuate</h3>
    <?php if (count($istoric) === 0): ?>
      <p>Nu există acțiuni în istoric.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Timp</th>
            <th>Acțiune</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($istoric as $linie): ?>
          <tr>
            <td><?php echo htmlspecialchars($linie['TIMP']); ?></td>
            <td><?php echo htmlspecialchars($linie['ACTIUNE']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>
</body>
</html>

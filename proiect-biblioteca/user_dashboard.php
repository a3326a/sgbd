<?php

session_start();


if (!isset($_SESSION['id_user'])) {
    header('Location: login.html');
    exit;
}


$id_user = $_SESSION['id_user'];
$nume    = $_SESSION['nume'];


$errorMsg   = $_GET['error']   ?? '';
$successMsg = $_GET['success'] ?? '';


$q = trim($_GET['q'] ?? '');


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

    
    if ($q !== '') {
        
        $sql = "
          SELECT
            C.id_carte,
            C.titlu,
            C.autor,
            C.nr_exemplare,
            Exemplare_Disponibile(C.id_carte) AS disponibile
          FROM Carti C
          WHERE LOWER(C.titlu) LIKE :q
             OR LOWER(C.autor) LIKE :q
          ORDER BY C.titlu
        ";
        $stmt = $pdo->prepare($sql);
        $searchParam = '%' . mb_strtolower($q, 'UTF-8') . '%';
        $stmt->bindParam(':q', $searchParam, PDO::PARAM_STR);
    } else {
        
        $sql = "
          SELECT
            C.id_carte,
            C.titlu,
            C.autor,
            C.nr_exemplare,
            Exemplare_Disponibile(C.id_carte) AS disponibile
          FROM Carti C
          ORDER BY C.titlu
        ";
        $stmt = $pdo->prepare($sql);
    }

    $stmt->execute();
    $carti = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Eroare la interogare: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Utilizator – Biblioteca</title>
  <link rel="stylesheet" href="css/user_dashboard.css">
  <script>
    
    <?php if ($errorMsg !== ''): ?>
    window.addEventListener('DOMContentLoaded', function() {
      alert("<?php echo addslashes(htmlspecialchars($errorMsg)); ?>");
    });
    <?php endif; ?>

    
    <?php if ($errorMsg === '' && $successMsg !== ''): ?>
    window.addEventListener('DOMContentLoaded', function() {
      alert("<?php echo addslashes(htmlspecialchars($successMsg)); ?>");
    });
    <?php endif; ?>
  </script>
</head>
<body>
  <header>
    <h2>Bine ai venit, <?php echo htmlspecialchars($nume); ?>!</h2>
    <nav>
      <a href="istoric.php">Istoric</a> |
       <a href="schimba.html">Modifică Date</a> |
      <a href="backend/logout.php">Logout</a>
    </nav>
  </header>

  <main>
    <h3>Lista Cărților Disponibile</h3>

    <!-- Formular de căutare -->
    <form method="GET" action="user_dashboard.php" class="search-form">
      <input
        type="text"
        name="q"
        value="<?php echo htmlspecialchars($q); ?>"
        placeholder="Caută după titlu sau autor..."
        class="search-input"
      >
      <button type="submit" class="search-button">Caută</button>
      <?php if ($q !== ''): ?>
        <a href="user_dashboard.php" class="clear-link">Șterge filtru</a>
      <?php endif; ?>
    </form>

    <table>
      <thead>
        <tr>
          <th>ID Carte</th>
          <th>Titlu</th>
          <th>Autor</th>
          <th>Total Ex.</th>
          <th>Disponibile</th>
          <th>Acțiune</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($carti) === 0): ?>
          <tr>
            <td colspan="6" class="no-results">Nu s-au găsit cărți.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($carti as $carte): ?>
          <tr>
            <td><?php echo htmlspecialchars($carte['ID_CARTE']); ?></td>
            <td><?php echo htmlspecialchars($carte['TITLU']); ?></td>
            <td><?php echo htmlspecialchars($carte['AUTOR']); ?></td>
            <td><?php echo htmlspecialchars($carte['NR_EXEMPLARE']); ?></td>
            <td><?php echo htmlspecialchars($carte['DISPONIBILE']); ?></td>
            <td>
              <?php if ($carte['DISPONIBILE'] > 0): ?>
                <!-- Formular de rezervare, trimite la rezerva.php -->
                <form method="POST" action="rezerva.php" style="margin:0;">
                  <input type="hidden" name="id_carte" value="<?php echo $carte['ID_CARTE']; ?>">
                  <button type="submit" class="reserve-button">Rezervă</button>
                </form>
              <?php else: ?>
                <span class="unavailable">Indisponibilă</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</body>
</html>

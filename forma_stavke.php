<?php
require "db.php";
$poruka = "";

// Dropdown narudžbi
$narudzbe = $pdo->query("SELECT narudzba_id, status, datum_narudzbe FROM narudzba ORDER BY narudzba_id")->fetchAll(PDO::FETCH_ASSOC);

// Dropdown proizvoda
$proizvodi = $pdo->query("SELECT proizvod_id, naziv_proizvoda, jed_cijena FROM proizvod ORDER BY naziv_proizvoda")->fetchAll(PDO::FETCH_ASSOC);

$narudzba_id = isset($_GET["narudzba_id"]) ? (int)$_GET["narudzba_id"] : 0;

// Ako se šalje forma, uzmi narudzba_id iz POST-a (da ostane ista)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $narudzba_id = (int)($_POST["narudzba_id"] ?? 0);
}

// DODAJ stavku (INSERT) – cijena iz proizvoda
if (isset($_POST["dodaj_stavku"])) {
    try {
        $proizvod_id = (int)$_POST["proizvod_id_new"];
        $kolicina    = (int)$_POST["kolicina_new"];

        if ($kolicina < 1) throw new Exception("Količina mora biti najmanje 1.");

        // povuci cijenu iz proizvoda
        $stmt = $pdo->prepare("SELECT jed_cijena FROM proizvod WHERE proizvod_id = ?");
        $stmt->execute([$proizvod_id]);
        $cijena = $stmt->fetchColumn();
        if ($cijena === false) throw new Exception("Proizvod ne postoji.");

        // INSERT (okidači: zaliha + ukupno)
        $stmt = $pdo->prepare("
            INSERT INTO stavke_narudzbe (proizvod_id, narudzba_id, kolicina, cijena_u_trenutku)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$proizvod_id, $narudzba_id, $kolicina, $cijena]);

        $poruka = "Stavka dodana (INSERT) – aktivirani okidači.";
    } catch (Exception $e) {
        $poruka = "Greška: " . $e->getMessage();
    }
}

// AŽURIRAJ stavku (UPDATE) – mijenjamo količinu
if (isset($_POST["azuriraj_stavku"])) {
    try {
        $proizvod_id = (int)$_POST["proizvod_id"];
        $kolicina    = (int)$_POST["kolicina"];

        if ($kolicina < 1) throw new Exception("Količina mora biti najmanje 1.");

        $stmt = $pdo->prepare("
            UPDATE stavke_narudzbe
            SET kolicina = ?
            WHERE narudzba_id = ? AND proizvod_id = ?
        ");
        $stmt->execute([$kolicina, $narudzba_id, $proizvod_id]);

        $poruka = "Stavka ažurirana (UPDATE) – aktiviran okidač za ukupno.";
    } catch (Exception $e) {
        $poruka = "Greška: " . $e->getMessage();
    }
}

// OBRIŠI stavku (DELETE)
if (isset($_POST["obrisi_stavku"])) {
    try {
        $proizvod_id = (int)$_POST["proizvod_id"];

        $stmt = $pdo->prepare("
            DELETE FROM stavke_narudzbe
            WHERE narudzba_id = ? AND proizvod_id = ?
        ");
        $stmt->execute([$narudzba_id, $proizvod_id]);

        $poruka = "Stavka obrisana (DELETE) – aktiviran okidač za ukupno.";
    } catch (Exception $e) {
        $poruka = "Greška: " . $e->getMessage();
    }
}

// Dohvati ukupno narudžbe
$ukupno = null;
if ($narudzba_id > 0) {
    $stmt = $pdo->prepare("SELECT ukupno FROM narudzba WHERE narudzba_id = ?");
    $stmt->execute([$narudzba_id]);
    $ukupno = $stmt->fetchColumn();
}

// Dohvati stavke narudžbe
$stavke = [];
if ($narudzba_id > 0) {
    $stmt = $pdo->prepare("
        SELECT sn.proizvod_id, p.naziv_proizvoda, sn.kolicina, sn.cijena_u_trenutku
        FROM stavke_narudzbe sn
        JOIN proizvod p ON p.proizvod_id = sn.proizvod_id
        WHERE sn.narudzba_id = ?
        ORDER BY p.naziv_proizvoda
    ");
    $stmt->execute([$narudzba_id]);
    $stavke = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Stavke narudžbe</title>
</head>
<body>

<nav style="margin-bottom:20px; padding:10px; border-bottom:1px solid #ccc;">
    <a href="zaliha.php">Zalihe po skladištima</a> |
    <a href="forma_stavke.php">Stavke narudžbe</a>
</nav>
<h2>Stavke narudžbe</h2>
<p><strong><?= htmlspecialchars($poruka) ?></strong></p>

<form method="get">
    Odaberi narudžbu:
    <select name="narudzba_id" required>
        <option value="">-- odaberi --</option>
        <?php foreach ($narudzbe as $n): ?>
            <option value="<?= $n["narudzba_id"] ?>" <?= ($narudzba_id === (int)$n["narudzba_id"]) ? "selected" : "" ?>>
                #<?= $n["narudzba_id"] ?> | <?= htmlspecialchars($n["status"]) ?> | <?= htmlspecialchars($n["datum_narudzbe"]) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Prikaži</button>
</form>

<?php if ($narudzba_id > 0): ?>

    <h3>Ukupna cijena narudžbe: <?= htmlspecialchars((string)$ukupno) ?> EUR</h3>

    <h3>Dodaj novu stavku</h3>
    <form method="post">
        <input type="hidden" name="narudzba_id" value="<?= $narudzba_id ?>">

        Proizvod:
        <select name="proizvod_id_new" required>
            <?php foreach ($proizvodi as $p): ?>
                <option value="<?= $p["proizvod_id"] ?>">
                    <?= htmlspecialchars($p["naziv_proizvoda"]) ?> (<?= htmlspecialchars($p["jed_cijena"]) ?> EUR)
                </option>
            <?php endforeach; ?>
        </select>

        Količina:
        <input type="number" name="kolicina_new" min="1" required>

        <button type="submit" name="dodaj_stavku">Dodaj</button>
    </form>

    <h3>Trenutne stavke (uređivanje po retku)</h3>
    <?php if (!$stavke): ?>
        <p>Nema stavki.</p>
    <?php else: ?>
        <table border="1" cellpadding="5">
            <tr>
                <th>Proizvod</th>
                <th>Količina</th>
                <th>Cijena u trenutku</th>
                <th></th>
            </tr>

            <?php foreach ($stavke as $s): ?>
                <tr>
                    <form method="post">
                        <input type="hidden" name="narudzba_id" value="<?= $narudzba_id ?>">
                        <input type="hidden" name="proizvod_id" value="<?= (int)$s["proizvod_id"] ?>">

                        <td><?= htmlspecialchars($s["naziv_proizvoda"]) ?></td>
                        <td>
                            <input type="number" name="kolicina" min="1" value="<?= (int)$s["kolicina"] ?>" required>
                        </td>
                        <td><?= htmlspecialchars($s["cijena_u_trenutku"]) ?> EUR</td>
                        <td>
                            <button type="submit" name="azuriraj_stavku">Ažuriraj</button>
                            <button type="submit" name="obrisi_stavku" onclick="return confirm('Obrisati stavku?')">Obriši</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>

        </table>
    <?php endif; ?>

<?php endif; ?>

</body>
</html>

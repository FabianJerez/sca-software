<?php
$modo_embebido = $modo_embebido ?? false;

if (!$modo_embebido) {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
}
require_once __DIR__ . '/includes/utils.php';

requireLogin();
if (getUserRole() !== 'admin') {
    exit("Acceso denegado.");
}

if (isset($_GET['limpiar'])) {
    header("Location: ?seccion=newsletter&sub=suscriptos");
    exit;
}

// --- Parámetros de búsqueda y paginación ---
$msg = $_GET['msg'] ?? null;
$campo = $_GET['campo'] ?? 'usuario';
$buscar = $_GET['buscar'] ?? '';
$condiciones = [];
$parametros = [];

if (!empty($buscar)) {
    if ($campo === 'usuario') {
        $condiciones[] = "usuario LIKE ?";
        $parametros[] = "%$buscar%";
    } elseif ($campo === 'email') {
        $condiciones[] = "email LIKE ?";
        $parametros[] = "%$buscar%";
    }
}

// --- Paginación ---
$por_pagina = 10;
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $por_pagina;

// --- Conteo total ---
$sqlTotal = "SELECT COUNT(*) FROM newsletter";
if (!empty($condiciones)) {
    $sqlTotal .= " WHERE " . implode(" AND ", $condiciones);
}
$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->execute($parametros);
$total_registros = $stmtTotal->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// --- Consulta principal con paginación ---
$sql = "SELECT id, usuario, email, activo, fecha_suscripcion FROM newsletter";
if (!empty($condiciones)) {
    $sql .= " WHERE " . implode(" AND ", $condiciones);
}
$sql .= " ORDER BY fecha_suscripcion DESC LIMIT $por_pagina OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($parametros);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Listado de suscripciones al newsletter</h2>

<?php if ($msg): ?>
    <p style="color: green;"><strong><?= htmlspecialchars($msg) ?></strong></p>
<?php endif; ?>

<form method="GET" action="" style="margin-bottom: 15px;">
    <input type="hidden" name="seccion" value="newsletter">
    <input type="hidden" name="sub" value="suscriptos">

    <label for="campo">Buscar por:</label>
    <select name="campo" id="campo">
        <option value="usuario" <?= $campo === 'usuario' ? 'selected' : '' ?>>Usuario</option>
        <option value="email" <?= $campo === 'email' ? 'selected' : '' ?>>Email</option>
    </select>

    <input type="text" name="buscar" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar..." required>
    <button type="submit">Buscar</button>
    <button type="submit" name="limpiar" value="1">Limpiar</button>
</form>

<table border="1" cellpadding="5">
    <tr>
        <th>Usuario</th>
        <th>Email</th>
        <th>Fecha de suscripción</th>
        <th>Estado</th>
        <th>Alta/Baja</th>
    </tr>
    <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['usuario']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= $u['fecha_suscripcion'] ? date("d/m/Y", strtotime($u['fecha_suscripcion'])) : '-' ?></td>
            <td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td>
            <td>
                <?php if ($u['activo']): ?>
                    <a href="newsletter/dar_baja_newsletter.php?id=<?= $u['id'] ?>&msg=Usuario+desuscripto+correctamente"
                       onclick="return confirm('¿Dar de baja a este usuario del newsletter?')">Desuscribir</a>
                <?php else: ?>
                    <a href="newsletter/dar_alta_newsletter.php?id=<?= $u['id'] ?>&msg=Usuario+suscripto+correctamente"
                       onclick="return confirm('¿Reactivar la suscripción al newsletter?')">Suscribir</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<!-- Navegación de páginas -->
<div style="margin-top: 15px;">
    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <a href="?seccion=newsletter&sub=suscriptos&campo=<?= urlencode($campo) ?>&buscar=<?= urlencode($buscar) ?>&pagina=<?= $i ?>"
           style="margin-right: 5px; <?= $i == $pagina ? 'font-weight:bold;' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>



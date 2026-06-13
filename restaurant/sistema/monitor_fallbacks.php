<?php
/**
 * monitor_fallbacks.php — Bitácora de Fallbacks y Errores SQL
 * Acceso directo: /agua/admin/saneamiento/monitor_fallbacks.php
 * Sin link en menús de la webapp — solo acceso por URL directa.
 * Target: Host C exclusivamente (requiere tabla fallback_log).
 */

require_once("../../config/Conexion.php");
$y = new Conexion();
$y->conectarBaseDatos();

// --- Filtros ---
$nivel     = isset($_GET['nivel'])     ? $_GET['nivel']     : '';
$origen    = isset($_GET['origen'])    ? trim($_GET['origen'])    : '';
$fecha_ini = isset($_GET['fecha_ini']) ? $_GET['fecha_ini'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$page      = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
$per_page  = 50;
$offset    = ($page - 1) * $per_page;

$where = array("1=1");
if (in_array($nivel, ['ERROR','FALLBACK','WARN'])) {
    $where[] = "nivel = '" . $y->real_escape_string($nivel) . "'";
}
if ($origen !== '') {
    $where[] = "origen LIKE '%" . $y->real_escape_string($origen) . "%'";
}
if ($fecha_ini !== '') {
    $where[] = "fecha >= '" . $y->real_escape_string($fecha_ini) . " 00:00:00'";
}
if ($fecha_fin !== '') {
    $where[] = "fecha <= '" . $y->real_escape_string($fecha_fin) . " 23:59:59'";
}
$where_sql = implode(' AND ', $where);

// Totales por nivel para resumen
$q_resumen = "SELECT nivel, COUNT(*) AS cnt FROM fallback_log WHERE $where_sql GROUP BY nivel";
$res_resumen = $y->q($q_resumen);
$resumen = ['ERROR'=>0, 'FALLBACK'=>0, 'WARN'=>0];
while ($r = $y->fetch_assoc($res_resumen)) {
    $resumen[$r['nivel']] = (int)$r['cnt'];
}
$total_filtrado = array_sum($resumen);

// Top 5 orígenes con más registros (sin filtros de origen para contexto global)
$q_top = "SELECT origen, COUNT(*) AS cnt FROM fallback_log GROUP BY origen ORDER BY cnt DESC LIMIT 5";
$res_top = $y->q($q_top);

// Paginación
$q_count = "SELECT COUNT(*) AS total FROM fallback_log WHERE $where_sql";
$res_count = $y->q($q_count);
$row_count = $y->fetch_assoc($res_count);
$total_rows = (int)$row_count['total'];
$total_pages = max(1, ceil($total_rows / $per_page));

// Registros de la página actual
$q_main = "SELECT id, fecha, nivel, origen, funcion, query_type, query_hash,
                  query_text, filas_afect, error_msg, usuario_ses, numcontrato
           FROM fallback_log
           WHERE $where_sql
           ORDER BY id DESC
           LIMIT $per_page OFFSET $offset";
$res_main = $y->q($q_main);

// Helper: construir URL con filtros actuales + override
function urlFiltro($overrides = []) {
    $params = [];
    foreach (['nivel','origen','fecha_ini','fecha_fin'] as $k) {
        $v = isset($_GET[$k]) ? $_GET[$k] : '';
        $params[$k] = $v;
    }
    foreach ($overrides as $k => $v) {
        $params[$k] = $v;
    }
    $qs = http_build_query(array_filter($params, function($v){ return $v !== ''; }));
    return '?' . ($qs ?: 'page=1');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Monitor Fallbacks — Host C</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f1f5f9;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
            --muted: #64748b;
            --primary: #2563eb;
            --error: #dc2626;
            --warn: #d97706;
            --fallback: #7c3aed;
            --ok: #059669;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 1.5rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { font-size: 1.6rem; margin: 0 0 0.25rem; color: var(--primary); }
        .subtitle { color: var(--muted); font-size: 0.85rem; margin-bottom: 1.5rem; }

        /* Resumen chips */
        .chips { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .chip { padding: 0.5rem 1.2rem; border-radius: 9999px; font-weight: 700; font-size: 0.85rem; cursor: pointer; text-decoration: none; border: 2px solid transparent; }
        .chip-error    { background: #fee2e2; color: var(--error); border-color: #fca5a5; }
        .chip-fallback { background: #ede9fe; color: var(--fallback); border-color: #c4b5fd; }
        .chip-warn     { background: #fef3c7; color: var(--warn); border-color: #fcd34d; }
        .chip-all      { background: #e0e7ff; color: var(--primary); border-color: #a5b4fc; }
        .chip.active   { box-shadow: 0 0 0 3px rgba(37,99,235,0.35); }

        /* Filtros */
        .filtros { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 1rem 1.2rem; margin-bottom: 1.5rem; display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end; }
        .filtros label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--muted); display: block; margin-bottom: 3px; }
        .filtros input, .filtros select { border: 1px solid var(--border); border-radius: 6px; padding: 0.35rem 0.6rem; font-family: inherit; font-size: 0.85rem; color: var(--text); background: #f8fafc; }
        .filtros button { padding: 0.4rem 1rem; background: var(--primary); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-family: inherit; font-size: 0.85rem; }
        .filtros a.reset { font-size: 0.8rem; color: var(--muted); text-decoration: none; align-self: center; padding: 0.4rem 0.5rem; }

        /* Top orígenes */
        .top-box { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 0.8rem 1.2rem; margin-bottom: 1.5rem; }
        .top-box h3 { font-size: 0.8rem; text-transform: uppercase; color: var(--muted); margin: 0 0 0.5rem; }
        .top-row { display: flex; justify-content: space-between; font-size: 0.8rem; padding: 3px 0; border-bottom: 1px solid var(--border); }
        .top-row:last-child { border-bottom: none; }

        /* Tabla */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.82rem; background: var(--card); border-radius: 10px; overflow: hidden; border: 1px solid var(--border); }
        thead tr { background: var(--bg); }
        th { padding: 0.6rem 0.8rem; text-align: left; font-size: 0.72rem; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid var(--border); white-space: nowrap; }
        td { padding: 0.55rem 0.8rem; border-bottom: 1px solid var(--border); vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .nivel-badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .nivel-ERROR    { background: #fee2e2; color: var(--error); }
        .nivel-FALLBACK { background: #ede9fe; color: var(--fallback); }
        .nivel-WARN     { background: #fef3c7; color: var(--warn); }

        .qtype { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 0.68rem; font-weight: 700; background: #f1f5f9; color: var(--muted); }

        .query-cell { font-family: monospace; font-size: 0.75rem; max-width: 380px; word-break: break-all; }
        .query-short { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px; display: block; cursor: pointer; color: var(--primary); }
        .query-full  { display: none; white-space: pre-wrap; background: #f8fafc; padding: 6px; border-radius: 4px; border: 1px solid var(--border); margin-top: 4px; }

        .origen-cell { font-size: 0.75rem; color: var(--muted); max-width: 200px; word-break: break-all; }
        .error-cell  { font-size: 0.75rem; color: var(--error); max-width: 200px; }

        /* Paginación */
        .paginacion { display: flex; gap: 0.5rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap; }
        .paginacion a { padding: 0.35rem 0.75rem; border-radius: 6px; border: 1px solid var(--border); font-size: 0.82rem; text-decoration: none; color: var(--text); background: var(--card); }
        .paginacion a.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .paginacion a:hover:not(.active) { background: var(--bg); }

        .empty { text-align: center; padding: 3rem; color: var(--muted); background: var(--card); border-radius: 10px; border: 2px dashed var(--border); }
        .info-row { font-size: 0.8rem; color: var(--muted); margin-bottom: 0.75rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>Monitor de Fallbacks y Errores SQL</h1>
    <div class="subtitle">Host C — Bitácora de estabilización · Solo acceso directo por URL · Sin link en menús</div>

    <!-- Chips resumen -->
    <div class="chips">
        <a href="<?= urlFiltro(['nivel'=>'','page'=>1]) ?>" class="chip chip-all<?= ($nivel==='' ? ' active' : '') ?>">
            Todos (<?= $total_rows ?>)
        </a>
        <a href="<?= urlFiltro(['nivel'=>'ERROR','page'=>1]) ?>" class="chip chip-error<?= ($nivel==='ERROR' ? ' active' : '') ?>">
            ERROR (<?= $resumen['ERROR'] ?>)
        </a>
        <a href="<?= urlFiltro(['nivel'=>'FALLBACK','page'=>1]) ?>" class="chip chip-fallback<?= ($nivel==='FALLBACK' ? ' active' : '') ?>">
            FALLBACK (<?= $resumen['FALLBACK'] ?>)
        </a>
        <a href="<?= urlFiltro(['nivel'=>'WARN','page'=>1]) ?>" class="chip chip-warn<?= ($nivel==='WARN' ? ' active' : '') ?>">
            WARN (<?= $resumen['WARN'] ?>)
        </a>
    </div>

    <!-- Top orígenes -->
    <?php if ($res_top): ?>
    <div class="top-box">
        <h3>Top 5 Orígenes</h3>
        <?php while ($t = $y->fetch_assoc($res_top)): ?>
            <div class="top-row">
                <span><?= htmlspecialchars($t['origen']) ?></span>
                <strong><?= $t['cnt'] ?></strong>
            </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="GET" action="">
        <div class="filtros">
            <div>
                <label>Nivel</label>
                <select name="nivel">
                    <option value="">Todos</option>
                    <option value="ERROR"    <?= $nivel==='ERROR'    ? 'selected' : '' ?>>ERROR</option>
                    <option value="FALLBACK" <?= $nivel==='FALLBACK' ? 'selected' : '' ?>>FALLBACK</option>
                    <option value="WARN"     <?= $nivel==='WARN'     ? 'selected' : '' ?>>WARN</option>
                </select>
            </div>
            <div>
                <label>Origen (contiene)</label>
                <input type="text" name="origen" value="<?= htmlspecialchars($origen) ?>" placeholder="ej. cargos.php" style="width:160px;">
            </div>
            <div>
                <label>Desde</label>
                <input type="date" name="fecha_ini" value="<?= htmlspecialchars($fecha_ini) ?>">
            </div>
            <div>
                <label>Hasta</label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">
            </div>
            <input type="hidden" name="page" value="1">
            <button type="submit">Filtrar</button>
            <a href="?" class="reset">Limpiar</a>
        </div>
    </form>

    <?php if ($total_rows === 0): ?>
        <div class="empty">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">✅</div>
            No se encontraron registros con los filtros actuales.
        </div>
    <?php else: ?>
        <div class="info-row">Mostrando <?= min($per_page, $total_rows - $offset) ?> de <?= $total_rows ?> registros · Página <?= $page ?> de <?= $total_pages ?></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Nivel</th>
                        <th>Tipo</th>
                        <th>Origen</th>
                        <th>Función</th>
                        <th>Query</th>
                        <th>Error</th>
                        <th>Filas</th>
                        <th>Usuario</th>
                        <th>Contrato</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $y->fetch_assoc($res_main)): ?>
                    <?php
                        $fecha_fmt = date('d/m/y H:i:s', strtotime($row['fecha']));
                        $query_preview = htmlspecialchars(substr($row['query_text'], 0, 100));
                        $query_full    = htmlspecialchars($row['query_text']);
                        $hash = $row['query_hash'];
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td style="white-space:nowrap;"><?= $fecha_fmt ?></td>
                        <td><span class="nivel-badge nivel-<?= $row['nivel'] ?>"><?= $row['nivel'] ?></span></td>
                        <td><span class="qtype"><?= $row['query_type'] ?></span></td>
                        <td class="origen-cell"><?= htmlspecialchars($row['origen']) ?></td>
                        <td style="font-size:0.72rem;color:var(--muted);"><?= htmlspecialchars($row['funcion'] ?: '—') ?></td>
                        <td class="query-cell">
                            <span class="query-short" onclick="toggleQuery('q<?= $row['id'] ?>')" title="Click para ver completo"><?= $query_preview ?>…</span>
                            <div class="query-full" id="q<?= $row['id'] ?>"><?= $query_full ?></div>
                        </td>
                        <td class="error-cell"><?= htmlspecialchars($row['error_msg'] ?: '') ?></td>
                        <td style="text-align:center;"><?= $row['filas_afect'] !== null ? $row['filas_afect'] : '—' ?></td>
                        <td style="font-size:0.75rem;"><?= htmlspecialchars($row['usuario_ses'] ?: '—') ?></td>
                        <td style="font-size:0.75rem;"><?= htmlspecialchars($row['numcontrato'] ?: '—') ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_pages > 1): ?>
        <div class="paginacion">
            <?php if ($page > 1): ?>
                <a href="<?= urlFiltro(['page'=>1]) ?>">«</a>
                <a href="<?= urlFiltro(['page'=>$page-1]) ?>">‹</a>
            <?php endif; ?>
            <?php
            $start_p = max(1, $page - 3);
            $end_p   = min($total_pages, $page + 3);
            for ($p = $start_p; $p <= $end_p; $p++):
            ?>
                <a href="<?= urlFiltro(['page'=>$p]) ?>" class="<?= ($p == $page ? 'active' : '') ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="<?= urlFiltro(['page'=>$page+1]) ?>">›</a>
                <a href="<?= urlFiltro(['page'=>$total_pages]) ?>">»</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <footer style="margin-top: 2rem; text-align: center; color: var(--muted); font-size: 0.75rem; border-top: 1px solid var(--border); padding-top: 0.75rem;">
        Monitor Fallbacks — Host C · <?= date('d/m/Y H:i:s') ?> · Solo para diagnóstico interno
    </footer>
</div>
<script>
function toggleQuery(id) {
    var el = document.getElementById(id);
    if (el) {
        el.style.display = el.style.display === 'block' ? 'none' : 'block';
    }
}
</script>
</body>
</html>
<?php
$y->cerrarConexion();
?>

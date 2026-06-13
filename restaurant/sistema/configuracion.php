<?php
require_once('../../login/usuario.php');
session_start();
if (!isset($_SESSION['usuario'])) {
    print "<script>window.location='../../login/index.php'</script>";
    exit();
}

require_once('../../config/Conexion.php');
$y = new Conexion();
$y->conectarBaseDatos();

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config'])) {
    $configs = $_POST['config'];
    foreach ($configs as $clave => $valor) {
        $clave_segura = $y->real_escape_string($clave);
        $valor_seguro = $y->real_escape_string($valor);
        $y->q("UPDATE config_sistema SET valor='$valor_seguro' WHERE clave='$clave_segura'");
    }
    $mensaje = "<div class='success-msg'>&#10003; Configuraci&oacute;n actualizada correctamente.</div>";
}

$res = $y->q("SELECT clave, valor, descripcion FROM config_sistema ORDER BY clave ASC");
$opciones = array();
while ($row = $y->fetch_array($res)) {
    $opciones[$row['clave']] = $row;
}

// ── Agrupación temática ──────────────────────────────────────────────────────
$grupos = array(
    'Recargos Moratorios' => array(
        'desc_grupo' => 'Generación de mora y mecanismos de protección (Límite Bomba). Incluye alcance retroactivo de paridad y umbral de reversas.',
        'readonly'   => false,
        'claves'     => ['recargo_mes_inicio','recargo_porcentaje','paridad_anios_max_recargo','reversal_threshold_enable','reversal_threshold'],
    ),
    'Operación y Contratos' => array(
        'desc_grupo' => 'Ciclo de vida: reglas de transición de estado, exenciones iniciales y límites de infraestructura por contrato.',
        'readonly'   => false,
        'claves'     => ['susptemp_mes_permitido','nuevo_contrato_exento_default','max_tomas_por_contrato','max_domicilios_por_contrato'],
    ),
    'Estados de Contrato' => array(
        'desc_grupo' => 'Códigos numéricos del catálogo de estados. <strong>Solo lectura</strong> — no modificar.',
        'readonly'   => true,
        'claves'     => ['estado_activo','estado_susp_temporal','estado_susp_administrativa','estado_susp_definitiva'],
    ),
);

// Campos editables (max_tomas_por_contrato: solo lectura)
$editables = ['recargo_mes_inicio','recargo_porcentaje','recargo_categoria_agua','recargo_categoria_drenaje',
              'susptemp_mes_permitido','paridad_anios_max_recargo',
              'reversal_threshold_enable','reversal_threshold','nuevo_contrato_exento_default',
              'max_domicilios_por_contrato'];

header('Content-Type: text/html; charset=UTF-8');

function get_mes_nombre($n) {
    $meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    return isset($meses[$n]) ? $meses[$n] : "Mes $n";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuraci&oacute;n del Sistema</title>
    <link rel="stylesheet" href="../../web-assets/css/paxstyle2.css">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; background: #f5f6fa; }
        h1 { font-size: 22px; color: #2c3e50; margin-bottom: 6px; font-style: normal; text-shadow: none; text-align: left; }
        .subtitle { color: #7f8c8d; font-size: 13px; margin-bottom: 20px; }
        .back-link { display: inline-block; margin-bottom: 16px; color: #0674B7; text-decoration: none; font-weight: bold; font-size: 13px; }
        .back-link:hover { text-decoration: underline; }
        .success-msg { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px 14px; margin-bottom: 18px; border-radius: 4px; font-weight: bold; font-size: 13px; }

        /* Layout dos columnas */
        .grupos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 20px; }
        /* .g-estados ya no es full-width para permitir la columna de referencia al lado */

        /* Tarjeta de grupo */
        .grupo-card { background: #fff; border: 1px solid #dde3ec; border-radius: 6px; overflow: hidden; }
        .grupo-header { padding: 10px 14px; font-weight: bold; font-size: 12px; text-transform: uppercase;
                        letter-spacing: .5px; color: #fff; }
        .g-estados    .grupo-header { background: #7f8c8d; }
        .g-recargos   .grupo-header { background: #2980b9; }
        .g-paridad    .grupo-header { background: #8e44ad; }
        .g-ciclo      .grupo-header { background: #27ae60; }
        .g-reversa    .grupo-header { background: #c0392b; }
        .g-referencia .grupo-header { background: #2c3e50; }
        .grupo-desc { font-size: 11px; color: #666; padding: 6px 14px 8px; border-bottom: 1px solid #eee; background: #fafbfc; }
        .grupo-body { padding: 10px 14px 14px; }

        /* Fila de config */
        .cfg-row { margin-bottom: 12px; }
        .cfg-grouped-top { background: #fdfdfd; padding: 10px 14px; border: 1px solid #d0d5dd; border-bottom: none; border-radius: 6px 6px 0 0; margin-bottom: 0; }
        .cfg-grouped-middle { background: #fdfdfd; padding: 10px 14px; border-left: 1px solid #d0d5dd; border-right: 1px solid #d0d5dd; border-top: 1px dashed #ccc; border-bottom: none; border-radius: 0; margin-top: 0; margin-bottom: 0; }
        .cfg-grouped-bottom { background: #fdfdfd; padding: 10px 14px 10px 34px; border: 1px solid #d0d5dd; border-top: none; border-radius: 0 0 6px 6px; margin-top: 0; position: relative; }
        .cfg-grouped-bottom::before { content: '\21B3'; position: absolute; left: 14px; top: 10px; color: #999; font-size: 16px; font-weight: bold; }
        .cfg-grouped-top .cfg-key, .cfg-grouped-middle .cfg-key, .cfg-grouped-bottom .cfg-key { color: #c0392b; }
        .cfg-row:last-child { margin-bottom: 0; }
        .cfg-key { font-family: monospace; font-size: 12px; font-weight: bold; color: #2c3e50; display: block; margin-bottom: 3px; }
        .cfg-desc { font-size: 11px; color: #777; margin-bottom: 5px; display: block; line-height: 1.5; white-space: pre-line; }
        .cfg-input { width: 100%; padding: 7px 9px; border: 1px solid #ccc; border-radius: 4px;
                     box-sizing: border-box; font-family: monospace; font-size: 13px; background: #fff; }
        .cfg-input:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 2px rgba(52,152,219,.2); }
        .cfg-input[readonly] { background: #f0f0f0; color: #999; cursor: not-allowed; }
        .readonly-badge { display: inline-block; font-size: 10px; background: #ecf0f1; color: #7f8c8d;
                          border: 1px solid #bdc3c7; border-radius: 3px; padding: 1px 5px; margin-left: 6px; vertical-align: middle; }

        /* Botón guardar */
        .footer-bar { text-align: right; padding-top: 4px; }
        .submit-btn { background: #0674B7; color: #fff; padding: 10px 24px; border: none; border-radius: 4px;
                      cursor: pointer; font-size: 14px; font-weight: bold; }
        .submit-btn:hover { background: #045a8d; }

        /* Modal confirmación de cambios */
        #cfg-overlay { position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:9998; }
        #cfg-modal   { position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;
                       background:#f4f6f8;border:1px solid #aaa;border-radius:6px;padding:0;
                       min-width:480px;max-width:680px;width:90%;box-shadow:0 4px 18px rgba(0,0,0,0.25); }
        #cfg-modal .m-header { background:#0E4F8E;color:#fff;padding:9px 14px;border-radius:5px 5px 0 0;
                               font-weight:bold;font-size:12px;text-transform:uppercase; }
        #cfg-modal .m-body   { padding:14px 18px;max-height:55vh;overflow-y:auto; }
        #cfg-modal .m-footer { text-align:right;padding:0 18px 14px; }
        .cfg-diff-table { width:100%;border-collapse:collapse;font-size:12px;margin-bottom:12px; }
        .cfg-diff-table th { background:#0E4F8E;color:#fff;padding:5px 8px;text-align:left;font-size:11px; }
        .cfg-diff-table td { padding:5px 8px;border-bottom:1px solid #e0e4ea;vertical-align:top; }
        .cfg-diff-table tr:last-child td { border-bottom:none; }
        .cfg-diff-table .col-key  { font-family:monospace;font-weight:bold;color:#2c3e50;white-space:nowrap; }
        .cfg-diff-table .col-ant  { color:#888;text-decoration:line-through; }
        .cfg-diff-table .col-new  { color:#1a7a1a;font-weight:bold; }
        .cfg-diff-table .col-imp  { font-size:10px;color:#555;line-height:1.4; }
        .no-cambios { color:#7f8c8d;font-size:13px;text-align:center;padding:14px 0; }
        #cfg-modal input[type=button] { padding:7px 18px;border:none;border-radius:4px;cursor:pointer;
                                        font-size:13px;font-weight:bold; }
        #cfg-cancelar-modal { background:#888;color:#fff;margin-right:8px; }
        #cfg-confirmar-modal { background:#0674B7;color:#fff; }

        @media (max-width: 700px)  { .grupos-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <a href="../../index2.php" class="back-link">&larr; Volver al Men&uacute; Principal</a>
    <h1>Configuraci&oacute;n Global del Sistema</h1>
    <p class="subtitle">Par&aacute;metros operativos agrupados por tema funcional. Los campos <span class="readonly-badge">solo lectura</span> son valores fijos del cat&aacute;logo.</p>

    <?= $mensaje ?>

    <form method="POST" action="configuracion.php">
        <div class="footer-bar" style="margin-bottom:14px;">
            <button type="button" class="submit-btn" onclick="abrirModalConfirmacion()">
                Guardar Configuraci&oacute;n
            </button>
        </div>
        <div class="grupos-grid">
        <?php
        $clases_grupo = [
            'Estados de Contrato'  => 'g-estados',
            'Recargos Moratorios'  => 'g-recargos',
            'Operación y Contratos'=> 'g-paridad',
        ];
        foreach ($grupos as $nombre_grupo => $gdef):
            if ($nombre_grupo === 'Estados de Contrato') continue; // Se renderiza aparte al final
            $clase = isset($clases_grupo[$nombre_grupo]) ? $clases_grupo[$nombre_grupo] : 'g-ciclo';
        ?>
        <div class="grupo-card <?= $clase ?>">
            <div class="grupo-header">
                <?= htmlspecialchars($nombre_grupo) ?>
                <?php if ($gdef['readonly']): ?><span class="readonly-badge">solo lectura</span><?php endif; ?>
            </div>
            <div class="grupo-desc"><?= $gdef['desc_grupo'] ?></div>
            <div class="grupo-body">
            <?php
            $separadores_grupo = [
                'Recargos Moratorios' => [
                    'paridad_anios_max_recargo' => 'Límite Bomba (Recargos)',
                ],
                'Operación y Contratos' => [
                    'susptemp_mes_permitido' => 'Ciclo de Vida',
                    'max_tomas_por_contrato' => 'Infraestructura',
                ],
            ];
            $seps = isset($separadores_grupo[$nombre_grupo]) ? $separadores_grupo[$nombre_grupo] : [];
            foreach ($gdef['claves'] as $clave):
                if (!isset($opciones[$clave])) continue;
                $opt = $opciones[$clave];
                $es_editable = in_array($clave, $editables) && !$gdef['readonly'];
                if (isset($seps[$clave])): ?>
                <div style="border-top:1px dashed #d0d5dd;margin:10px 0 10px;position:relative;">
                    <span style="position:absolute;top:-8px;left:50%;transform:translateX(-50%);background:#fff;padding:0 6px;font-size:10px;color:#999;text-transform:uppercase;letter-spacing:.5px;"><?= $seps[$clave] ?></span>
                </div>
                <?php endif; ?>
                <?php
                $isGroupedTop = ($clave === 'paridad_anios_max_recargo');
                $isGroupedMiddle = ($clave === 'reversal_threshold_enable');
                $isGroupedBottom = ($clave === 'reversal_threshold');
                $rowClass = "cfg-row";
                if ($isGroupedTop) $rowClass .= " cfg-grouped-top";
                elseif ($isGroupedMiddle) $rowClass .= " cfg-grouped-middle";
                elseif ($isGroupedBottom) $rowClass .= " cfg-grouped-bottom";
                ?>
                <div class="<?= $rowClass ?>">
                    <span class="cfg-key"><?= htmlspecialchars($clave) ?></span>
                    <?php 
                        $desc = $opt['descripcion'] ?: '';
                        if ($clave === 'susptemp_mes_permitido') {
                            $mes_nombre = get_mes_nombre(intval($opt['valor']));
                            $desc .= " <strong style='color:#2980b9;'>(Actual: $mes_nombre)</strong>";
                        }
                        
                        // Poka-yoke: No mostrar descripción para recargo_porcentaje ya que tiene su guía técnica dedicada
                        if ($clave !== 'recargo_porcentaje'):
                    ?>
                        <span class="cfg-desc"><?= $desc ?></span>
                    <?php endif; ?>
                    <?php if ($clave === 'reversal_threshold_enable'): ?>
                        <div style="display: flex; align-items: center; margin-top: 6px; gap: 8px;">
                            <input type="hidden" name="config[<?= htmlspecialchars($clave) ?>]" value="0">
                            <input type="checkbox"
                                   class="cfg-input"
                                   style="width: 18px; height: 18px; cursor: pointer; accent-color: #c0392b;"
                                   name="config[<?= htmlspecialchars($clave) ?>]"
                                   value="1"
                                   data-original="<?= htmlspecialchars($opt['valor']) ?>"
                                   <?= $opt['valor'] == '1' ? 'checked' : '' ?>
                                   <?= $es_editable ? '' : 'disabled' ?>>
                            <span style="font-size: 13px; font-weight: bold; color: <?= $opt['valor'] == '1' ? '#c0392b' : '#7f8c8d' ?>;">
                                <?= $opt['valor'] == '1' ? 'Límite Activo' : 'Límite Desactivado' ?>
                            </span>
                        </div>
                    <?php else: ?>
                    <input type="text"
                           class="cfg-input"
                           name="config[<?= htmlspecialchars($clave) ?>]"
                           value="<?= htmlspecialchars($opt['valor']) ?>"
                           data-original="<?= htmlspecialchars($opt['valor']) ?>"
                           <?= $es_editable ? '' : 'readonly' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <!-- Sección de Referencia Técnica y Catálogos (Dos Columnas) -->
        <div class="grupos-grid" style="margin-top: 18px;">
            
            <!-- Columna Izquierda: Estados de Contrato (Manual) -->
            <?php 
            $g_est = $grupos['Estados de Contrato'];
            ?>
            <div class="grupo-card g-estados">
                <div class="grupo-header">Estados de Contrato <span class="readonly-badge">solo lectura</span></div>
                <div class="grupo-desc"><?= $g_est['desc_grupo'] ?></div>
                <div class="grupo-body">
                    <?php foreach ($g_est['claves'] as $clave): 
                        $opt = $opciones[$clave]; ?>
                        <div class="cfg-row">
                            <span class="cfg-key"><?= htmlspecialchars($clave) ?></span>
                            <span class="cfg-desc"><?= $opt['descripcion'] ?></span>
                            <input type="text" class="cfg-input" value="<?= htmlspecialchars($opt['valor']) ?>" readonly>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Columna Derecha: Guía de Referencia Técnica (Recargos) -->
            <div class="grupo-card g-referencia">
                <div class="grupo-header">Gu&iacute;a de Referencia T&eacute;cnica (Recargos)</div>
                <div class="grupo-desc">L&oacute;gica interna del motor de recargos (Referencia para <strong>recargo_porcentaje</strong>).</div>
                <div class="grupo-body" style="font-size: 11px; color: #444; line-height: 1.6;">
                    <div style="background: #fffbe6; border: 1px solid #ffe58f; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                        <strong style="color: #b05a00;">&Aacute;mbitos de aplicaci&oacute;n:</strong><br>
                        &bull; <strong>Reactivaci&oacute;n de contratos (3&rarr;1 y 2&rarr;1):</strong> dispara sincronizaci&oacute;n de paridad, recorre cada cargo pendiente y genera los recargos mensuales faltantes.<br>
                        &bull; <strong>Aplicaci&oacute;n manual:</strong> al insertar un cargo de cat&aacute;logo, se genera la mora acumulada desde el mes de inicio hasta hoy.<br>
                        &bull; <strong>Rec&aacute;lculo hist&oacute;rico (paridad):</strong> procesa registros en <em>ligacargos_historico</em> (&le;2025) seg&uacute;n el l&iacute;mite de a&ntilde;os configurado.
                    </div>
                    
                    <p><strong>C&aacute;lculo:</strong> monto_original &times; <strong>recargo_porcentaje</strong> (<?= $opciones['recargo_porcentaje']['valor'] ?>%) / 100 por cada mes vencido.</p>
                    <p style="font-size: 10px; margin-top: -8px; color: #7f8c8d;">Nota: <strong>recargo_porcentaje</strong> es el &uacute;nico par&aacute;metro de tasa global; no existen montos fijos de mora por cat&aacute;logo.</p>
                    
                    <p><strong>Conceptos que S&Iacute; generan recargo:</strong><br>
                    &bull; <strong>2-AGUA:</strong> Solo anualidades &rarr; genera cat. <?= $opciones['recargo_categoria_agua']['valor'] ?>-RECARGO AGUA.<br>
                    &bull; <strong>3-DRENAJE:</strong> Solo anualidades &rarr; genera cat. <?= $opciones['recargo_categoria_drenaje']['valor'] ?>-RECARGO DRENAJE.<br>
                    <small>El backend fuerza recargo=0 en cualquier otra categor&iacute;a.</small></p>

                    <p><strong>Dos rutas de c&aacute;lculo:</strong><br>
                    &bull; <strong>Manual:</strong> Respeta el flag <em>recargo</em> del cat&aacute;logo.<br>
                    &bull; <strong>Paridad:</strong> Usa categor&iacute;a IN (2,3) como criterio (omite flag para deudas hist&oacute;ricas).</p>

                    <p style="color: #c0392b; font-weight: bold; margin-top: 10px;">&#9888; RIESGO DE EXPLOSI&Oacute;N DE DEUDA:</p>
                    <p>Anualidad AGUA $600 desde 2005 &rarr; 240 cargos de mora ($14,400). Se recomienda usar <strong>paridad_anios_max_recargo</strong> para limitar este retroactivo.</p>

                    <p><strong>No aplica cuando:</strong><br>
                    &bull; El contrato est&aacute; en Susp. Temporal (2) o Definitiva (4).<br>
                    &bull; Es el a&ntilde;o de creaci&oacute;n con exenci&oacute;n activa.</p>
                </div>
            </div>

        </div>

    </form>

    <div id="cfg-nara"></div>

    <script>
    // Descripciones leídas desde config_sistema.descripcion (BD)
    var impactos = <?= json_encode(array_map(function($o){ return $o['descripcion'] ?: ''; }, $opciones), JSON_UNESCAPED_UNICODE) ?>;

    function abrirModalConfirmacion() {
        var cambios = [];
        var inputs  = document.querySelectorAll('form input.cfg-input:not([readonly]):not([disabled])');

        inputs.forEach(function(inp) {
            var m = inp.name.match(/^config\[(.+)\]$/);
            if (!m) return;
            var clave = m[1];
            var valOrig = inp.getAttribute('data-original');
            var valNuevo = inp.type === 'checkbox' ? (inp.checked ? '1' : '0') : inp.value;
            if (valOrig !== valNuevo) {
                cambios.push({ clave: clave, antes: valOrig, despues: valNuevo });
            }
        });

        var bodyHtml;
        if (cambios.length === 0) {
            bodyHtml = '<div class="no-cambios">No se detectaron cambios respecto a los valores actuales.</div>';
        } else {
            bodyHtml  = '<p style="font-size:12px;color:#555;margin:0 0 10px;">Se modificar&aacute;n <strong>' + cambios.length + '</strong> par&aacute;metro(s). Revise antes de confirmar:</p>';
            bodyHtml += '<table class="cfg-diff-table">';
            bodyHtml += '<tr><th>Par&aacute;metro</th><th>Antes</th><th>Despu&eacute;s</th><th>Impacto</th></tr>';
            cambios.forEach(function(c) {
                var imp = impactos[c.clave] || 'Afecta el comportamiento del m&oacute;dulo correspondiente de forma inmediata.';
                bodyHtml += '<tr>' +
                    '<td class="col-key">' + c.clave + '</td>' +
                    '<td class="col-ant">' + escHtml(c.antes) + '</td>' +
                    '<td class="col-new">' + escHtml(c.despues) + '</td>' +
                    '<td class="col-imp">' + imp + '</td>' +
                    '</tr>';
            });
            bodyHtml += '</table>';
            bodyHtml += '<p style="font-size:11px;color:#b05a00;background:#fffbe6;border:1px solid #e0cc80;border-radius:4px;padding:6px 10px;margin:0;">&#9888; Los cambios son efectivos de forma inmediata y afectan el comportamiento global del sistema.</p>';
        }

        var hayCambios = cambios.length > 0;
        var html =
            '<div id="cfg-overlay"></div>' +
            '<div id="cfg-modal">' +
                '<div class="m-header">Confirmar Cambios en Configuraci&oacute;n Global</div>' +
                '<div class="m-body">' + bodyHtml + '</div>' +
                '<div class="m-footer">' +
                    '<input type="button" id="cfg-cancelar-modal" value="Cancelar">' +
                    (hayCambios ? '<input type="button" id="cfg-confirmar-modal" value="Guardar Cambios">' : '') +
                '</div>' +
            '</div>';

        document.getElementById('cfg-nara').innerHTML = html;

        document.getElementById('cfg-cancelar-modal').addEventListener('click', cerrarModal);
        document.getElementById('cfg-overlay').addEventListener('click', cerrarModal);
        if (hayCambios) {
            document.getElementById('cfg-confirmar-modal').addEventListener('click', function() {
                cerrarModal();
                document.querySelector('form').submit();
            });
        }
    }

    function cerrarModal() {
        document.getElementById('cfg-nara').innerHTML = '';
    }

    function escHtml(s) {
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    </script>
</body>
</html>

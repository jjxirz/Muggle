<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../../controllers/UserController.php';

$controller = new UserController();
$data       = $controller->handle();

$usuarios = $data['usuarios'];
$planes   = $data['planes'];
$flash    = $data['flash'];

$tab = $_GET['tab'] ?? 'usuarios';

$activePage = 'usuarios';
include __DIR__ . '/../layouts/sidebar.php';
?>

<!-- ===== TOPBAR ===== -->
<div class="admin-topbar">
    <div>
        <h1 class="topbar-title">Usuarios y planes</h1>
        <p class="topbar-sub">Gestión de cuentas y membresías</p>
    </div>
    <div class="topbar-actions">
        <?php if ($tab === 'usuarios'): ?>
        <button class="btn-admin btn-admin--primary" onclick="abrirModalUsuario()">
            <i class="fas fa-user-plus"></i> <span>Nuevo usuario</span>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- flash -->
<?php if ($flash): ?>
<div style="padding:0 24px">
    <div class="alert <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-danger' ?>"
         style="font-size:13px; border-radius:8px; padding:10px 14px; margin-bottom:0">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
</div>
<?php endif; ?>

<div class="admin-content">

    <div class="admin-tabs">
        <a href="?tab=usuarios" class="admin-tab <?= $tab === 'usuarios' ? 'active' : '' ?>">Usuarios</a>
        <a href="?tab=planes"   class="admin-tab <?= $tab === 'planes'   ? 'active' : '' ?>">Planes de suscripción</a>
    </div>

    <?php if ($tab === 'usuarios'): ?>
    <div class="admin-card">
        <div class="admin-card__toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="buscadorUsuarios" placeholder="Buscar usuario o email…">
            </div>
            <div class="toolbar-filters">
                <select class="filter-select" id="filtroPlanUsr">
                    <option value="">Todos los planes</option>
                    <?php foreach ($planes as $p): ?>
                    <option value="<?= strtolower($p['nombre']) ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="filter-select" id="filtroEstadoUsr">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                    <option value="baneado">Baneado</option>
                </select>
            </div>
        </div>

        <?php if (empty($usuarios)): ?>
            <div style="padding:40px 16px; text-align:center; color:var(--color-gris-mid); font-size:13px;">
                <i class="fas fa-users" style="font-size:32px; margin-bottom:10px; display:block; opacity:0.25"></i>
                No hay usuarios registrados aún.
                <br>
                <button class="btn-admin btn-admin--primary" style="margin-top:14px" onclick="abrirModalUsuario()">
                    <i class="fas fa-user-plus"></i> Crear el primero
                </button>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>Usuario</th><th>Email</th><th>Plan</th>
                        <th>Estado</th><th>Registro</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usr):
                        $planSlug  = strtolower(str_replace(['á','é','í','ó','ú'],['a','e','i','o','u'], $usr['plan_nombre'] ?? 'free'));
                        $partes    = explode(' ', trim($usr['nombre']));
                        $iniciales = mb_strtoupper(mb_substr($partes[0], 0, 1)) .
                                     (isset($partes[1]) ? mb_strtoupper(mb_substr($partes[1], 0, 1)) : '');
                        $estClase  = match($usr['estado']) { 'activo' => 'activo', 'baneado' => 'suspendido', default => 'inactivo' };
                    ?>
                    <tr data-plan="<?= htmlspecialchars($planSlug) ?>"
                        data-estado="<?= htmlspecialchars($usr['estado']) ?>">
                        <td>
                            <div class="book-cell">
                                <div class="user-avatar"><?= htmlspecialchars($iniciales) ?></div>
                                <div class="book-name"><?= htmlspecialchars($usr['nombre']) ?></div>
                            </div>
                        </td>
                        <td class="text-secondary small"><?= htmlspecialchars($usr['email']) ?></td>
                        <td><span class="badge-plan badge-plan--<?= $planSlug ?>"><?= strtoupper($planSlug) ?></span></td>
                        <td><span class="badge-estado badge-estado--<?= $estClase ?>"><?= ucfirst($usr['estado']) ?></span></td>
                        <td class="text-secondary small"><?= date('d/m/y', strtotime($usr['fecha_registro'])) ?></td>
                        <td>
                            <div class="action-btns">
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action"        value="toggle_estado">
                                    <input type="hidden" name="id_usuario"    value="<?= $usr['id_usuario'] ?>">
                                    <input type="hidden" name="estado_actual" value="<?= $usr['estado'] ?>">
                                    <input type="hidden" name="tab"           value="usuarios">
                                    <?php if ($usr['estado'] === 'activo'): ?>
                                    <button type="submit" class="action-btn action-btn--danger" title="Desactivar"
                                            onclick="return confirm('Desactivar a <?= htmlspecialchars(addslashes($usr['nombre'])) ?>?')">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="submit" class="action-btn action-btn--success" title="Reactivar">
                                        <i class="fas fa-user-check"></i>
                                    </button>
                                    <?php endif; ?>
                                </form>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action"     value="delete_usuario">
                                    <input type="hidden" name="id_usuario" value="<?= $usr['id_usuario'] ?>">
                                    <input type="hidden" name="tab"        value="usuarios">
                                    <button type="submit" class="action-btn action-btn--danger" title="Eliminar"
                                            onclick="return confirm('Eliminar permanentemente a <?= htmlspecialchars(addslashes($usr['nombre'])) ?>?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($tab === 'planes'): ?>
    <div class="row">
        <?php foreach ($planes as $plan):
            $ps = strtolower(str_replace(['á','é','í','ó','ú'],['a','e','i','o','u'], $plan['nombre']));
        ?>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="plan-card <?= $ps === 'premium' ? 'plan-card--destacado' : '' ?>">
                <?php if ($ps === 'premium'): ?><div class="plan-badge">Popular</div><?php endif; ?>
                <div class="plan-nombre"><?= htmlspecialchars($plan['nombre']) ?></div>
                <div class="plan-precio">
                    <?= $plan['precio'] == 0 ? 'Sin costo' : '$' . number_format($plan['precio'], 2) . ' / mes' ?>
                </div>
                <div class="plan-sep"></div>
                <?php if ($plan['descripcion']): ?>
                <div style="font-size:12px; color:var(--color-gris-dark); margin-bottom:10px">
                    <?= htmlspecialchars($plan['descripcion']) ?>
                </div>
                <?php endif; ?>
                <div class="plan-usuarios">
                    <i class="fas fa-users"></i> <?= (int)$plan['total_usuarios'] ?> usuarios activos
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- ============================================================
     MODAL NUEVO USUARIO
     ============================================================ -->
<div class="log-modal-overlay" id="modalUsuario">
    <div class="log-modal" style="max-width:520px; width:95%; max-height:90vh; overflow-y:auto">
        <div class="log-modal__header">
            <span><i class="fas fa-user-plus" style="margin-right:6px"></i>Nuevo usuario</span>
            <button class="log-modal__close" onclick="cerrarModalUsuario()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="log-modal__body">
            <form method="POST">
                <input type="hidden" name="action" value="create_usuario">
                <input type="hidden" name="tab"    value="usuarios">

                <div class="form-section-label">Datos personales</div>

                <div class="form-group mb-3">
                    <label class="form-label">Nombre completo <span class="required">*</span></label>
                    <input type="text" name="nombre" class="form-control admin-input"
                           placeholder="Ej. María López" required>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Correo electrónico <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control admin-input"
                           placeholder="usuario@correo.com" required>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Contraseña <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control admin-input"
                           placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>

                <div class="form-section-label" style="margin-top:16px">Configuración de cuenta</div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Rol</label>
                            <select name="id_rol" class="form-control admin-input">
                                <option value="2">Usuario</option>
                                <option value="1">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group mb-3">
                            <label class="form-label">Estado inicial</label>
                            <select name="estado" class="form-control admin-input">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section-label" style="margin-top:4px">Plan de suscripción</div>

                <div class="row" id="planCards" style="margin:0 -6px">
                    <?php foreach ($planes as $p):
                        $ps = strtolower(str_replace(['á','é','í','ó','ú'],['a','e','i','o','u'], $p['nombre']));
                    ?>
                    <div class="col-6 col-sm-3" style="padding:0 6px; margin-bottom:10px">
                        <label id="lbl-plan-<?= $p['id_plan'] ?>" style="
                            display:block; border:1.5px solid var(--color-crema-mid);
                            border-radius:8px; padding:10px 8px; text-align:center;
                            cursor:pointer; transition:border-color .15s, background .15s;
                            font-size:12px;">
                            <input type="radio" name="id_plan" value="<?= $p['id_plan'] ?>"
                                   style="display:none"
                                   <?= $p['precio'] == 0 ? 'checked' : '' ?>
                                   onchange="resaltarPlan(this)">
                            <div style="font-weight:600; font-size:13px; margin-bottom:3px">
                                <?= htmlspecialchars($p['nombre']) ?>
                            </div>
                            <div style="color:var(--color-gris-mid); font-size:11px">
                                <?= $p['precio'] == 0 ? 'Gratis' : '$' . number_format($p['precio'], 2) ?>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:16px">
                    <button type="button" class="btn-admin btn-admin--secondary"
                            onclick="cerrarModalUsuario()">Cancelar</button>
                    <button type="submit" class="btn-admin btn-admin--primary">
                        <i class="fas fa-save"></i> Crear usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.action-btn {
    background: none;
    border: 0.5px solid var(--color-crema-mid);
    border-radius: 6px;
    padding: 5px 9px;
    cursor: pointer;
    font-size: 12px;
    color: var(--color-gris-dark);
    transition: all .15s;
}
.action-btn:hover { background: var(--color-crema-light); }
.action-btn--danger  { color: var(--color-danger-dark); }
.action-btn--success { color: var(--color-success-dark); }
</style>

<script>
function abrirModalUsuario() {
    document.getElementById('modalUsuario').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function cerrarModalUsuario() {
    document.getElementById('modalUsuario').classList.remove('open');
    document.body.style.overflow = '';
}
function resaltarPlan(radio) {
    document.querySelectorAll('#planCards label').forEach(l => {
        l.style.borderColor = 'var(--color-crema-mid)';
        l.style.background  = '';
    });
    radio.closest('label').style.borderColor = 'var(--color-black)';
    radio.closest('label').style.background  = 'var(--color-crema-light)';
}
document.addEventListener('DOMContentLoaded', () => {
    const checked = document.querySelector('#planCards input:checked');
    if (checked) resaltarPlan(checked);
});
document.getElementById('modalUsuario').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalUsuario();
});

// Filtros
const buscador     = document.getElementById('buscadorUsuarios');
const filtroPlan   = document.getElementById('filtroPlanUsr');
const filtroEstado = document.getElementById('filtroEstadoUsr');
function filtrarTabla() {
    const q = buscador ? buscador.value.toLowerCase() : '';
    const p = filtroPlan   ? filtroPlan.value   : '';
    const e = filtroEstado ? filtroEstado.value : '';
    document.querySelectorAll('#tablaUsuarios tbody tr').forEach(tr => {
        const visible = tr.innerText.toLowerCase().includes(q) &&
                        (!p || tr.dataset.plan   === p) &&
                        (!e || tr.dataset.estado === e);
        tr.style.display = visible ? '' : 'none';
    });
}
if (buscador)     buscador.addEventListener('input',   filtrarTabla);
if (filtroPlan)   filtroPlan.addEventListener('change', filtrarTabla);
if (filtroEstado) filtroEstado.addEventListener('change', filtrarTabla);
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

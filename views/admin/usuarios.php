<?php
$activePage = 'usuarios';

$tab = $_GET['tab'] ?? 'usuarios';
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
        <a href="#" class="btn-admin btn-admin--primary">
            <i class="fas fa-user-plus"></i> Nuevo usuario
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ===== CONTENIDO ===== -->
<div class="admin-content">

    <!-- Tabs -->
    <div class="admin-tabs">
        <a href="?tab=usuarios" class="admin-tab <?= $tab === 'usuarios' ? 'active' : '' ?>">Usuarios</a>
        <a href="?tab=planes"   class="admin-tab <?= $tab === 'planes'   ? 'active' : '' ?>">Planes de suscripción</a>
    </div>

    <!-- ── TAB: USUARIOS ── -->
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
                    <option value="free">Free</option>
                    <option value="basico">Básico</option>
                    <option value="plus">Plus</option>
                    <option value="premium">Premium</option>
                </select>
                <select class="filter-select" id="filtroEstadoUsr">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="suspendido">Suspendido</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="admin-table" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $usuarios = $usuarios ?? [
                        ['id' => 1, 'nombre' => 'María R.',   'email' => 'maria@mail.com', 'plan' => 'premium', 'estado' => 'activo',     'registro' => '02/05/26', 'iniciales' => 'MR'],
                        ['id' => 2, 'nombre' => 'Juan P.',    'email' => 'juan@mail.com',  'plan' => 'plus',    'estado' => 'activo',     'registro' => '28/04/26', 'iniciales' => 'JP'],
                        ['id' => 3, 'nombre' => 'Ana L.',     'email' => 'ana@mail.com',   'plan' => 'free',    'estado' => 'suspendido', 'registro' => '10/03/26', 'iniciales' => 'AL'],
                        ['id' => 4, 'nombre' => 'Pedro M.',   'email' => 'pedro@mail.com', 'plan' => 'basico',  'estado' => 'activo',     'registro' => '01/04/26', 'iniciales' => 'PM'],
                    ];
                    foreach ($usuarios as $usr): ?>
                    <tr data-plan="<?= htmlspecialchars($usr['plan']) ?>"
                        data-estado="<?= htmlspecialchars($usr['estado']) ?>">
                        <td>
                            <div class="book-cell">
                                <div class="user-avatar"><?= htmlspecialchars($usr['iniciales']) ?></div>
                                <div class="book-name"><?= htmlspecialchars($usr['nombre']) ?></div>
                            </div>
                        </td>
                        <td class="text-secondary small"><?= htmlspecialchars($usr['email']) ?></td>
                        <td><span class="badge-plan badge-plan--<?= $usr['plan'] ?>"><?= strtoupper($usr['plan']) ?></span></td>
                        <td><span class="badge-estado badge-estado--<?= $usr['estado'] ?>"><?= ucfirst($usr['estado']) ?></span></td>
                        <td class="text-secondary small"><?= htmlspecialchars($usr['registro']) ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="#" class="action-btn" title="Ver perfil">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="#" class="action-btn" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <?php if ($usr['estado'] === 'activo'): ?>
                                <a href="#"
                                   class="action-btn action-btn--danger"
                                   title="Suspender"
                                   onclick="return confirm('¿Suspender a <?= htmlspecialchars($usr['nombre']) ?>?')">
                                    <i class="fas fa-user-slash"></i>
                                </a>
                                <?php else: ?>
                                <a href="#"
                                   class="action-btn action-btn--success"
                                   title="Reactivar">
                                    <i class="fas fa-user-check"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── TAB: PLANES ── -->
    <?php if ($tab === 'planes'): ?>
    <div class="row">
        <?php
        $planes = $planes ?? [
            ['nombre' => 'Free',    'precio' => 'Sin costo',  'usuarios' => 218, 'destacado' => false,
             'beneficios' => ['Libros gratuitos', 'Vista pública', 'Sin audiolibros']],
            ['nombre' => 'Básico',  'precio' => '$4.99 / mes','usuarios' => 279, 'destacado' => false,
             'beneficios' => ['Catálogo limitado', 'Sin audiolibros', 'Sin contenido exclusivo']],
            ['nombre' => 'Plus',    'precio' => '$8.99 / mes','usuarios' => 389, 'destacado' => false,
             'beneficios' => ['Más categorías', 'Audiolibros parcial', 'Sin contenido exclusivo']],
            ['nombre' => 'Premium', 'precio' => '$13.99 / mes','usuarios' => 318, 'destacado' => true,
             'beneficios' => ['Catálogo completo', 'Audiolibros completos', 'Contenido exclusivo']],
        ];
        foreach ($planes as $plan):
            $slug = strtolower($plan['nombre']);
        ?>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="plan-card <?= $plan['destacado'] ? 'plan-card--destacado' : '' ?>">
                <?php if ($plan['destacado']): ?>
                <div class="plan-badge">Popular</div>
                <?php endif; ?>
                <div class="plan-nombre"><?= htmlspecialchars($plan['nombre']) ?></div>
                <div class="plan-precio"><?= htmlspecialchars($plan['precio']) ?></div>
                <div class="plan-sep"></div>
                <div class="plan-label">Incluye</div>
                <ul class="plan-beneficios">
                    <?php foreach ($plan['beneficios'] as $b): ?>
                    <li><i class="fas fa-check plan-check"></i><?= htmlspecialchars($b) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="plan-usuarios">
                    <i class="fas fa-users"></i> <?= $plan['usuarios'] ?> usuarios
                </div>
                <div class="plan-actions">
                    <a href="#" class="btn-admin btn-admin--secondary w-100">
                        <i class="fas fa-pen"></i> Editar plan
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div><!-- /admin-content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>
<?php

if (!isset($activePage)) $activePage = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUGGLE — Admin</title>

    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/Muggle/assets/css/admin.css">
</head>
<body>

<div class="admin-shell">

    <!-- ===== SIDEBAR ===== -->
    <nav class="admin-sidebar" id="adminSidebar">

        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-book"></i>
            </div>
            <span class="sidebar-brand">MUGGLE</span>
        </div>

        <div class="sidebar-nav">
            <div class="sidebar-section">General</div>
            <a href="../admin/dashboard.php"
               class="sidebar-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="/Muggle/index.php"
               class="sidebar-item <?= $activePage === 'pagina' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Página</span>
            </a>

            <div class="sidebar-section">Catálogo</div>
            <a href="../admin/catalogo.php"
               class="sidebar-item <?= $activePage === 'catalogo' ? 'active' : '' ?>">
                <i class="fas fa-book-open"></i>
                <span>Libros</span>
            </a>
            <a href="../admin/usuarios.php"
               class="sidebar-item <?= $activePage === 'usuarios' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Usuarios y planes</span>
            </a>

            <div class="sidebar-section">Sistema</div>
            <a href="../admin/reportes.php"
               class="sidebar-item <?= $activePage === 'reportes' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reportes</span>
            </a>
           <a href="../admin/admin_books.php"
               class="sidebar-item <?= $activePage === 'admin_books' ? 'active' : '' ?>">
                <i class="fas fa-book"></i>
                <span>Administrar Libros</span>
            </a>
            <!--<a href="../admin/logs.php"
               class="sidebar-item <?= $activePage === 'logs' ? 'active' : '' ?>">
                <i class="fas fa-terminal"></i>
                <span>Logs</span>
            </a>-->
        </div>

        <div class="sidebar-footer">
            <a href="../admin/configuracion.php" class="sidebar-item">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
            <a href="../admin/logout.php" class="sidebar-item sidebar-item--danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>

    </nav>
    <!-- ===== FIN SIDEBAR ===== -->

    <!-- Botón toggle flotante — siempre visible, fuera del overflow:hidden del sidebar -->
    <button id="sidebarToggle" aria-label="Colapsar menú" style="
        position: fixed;
        top: 14px;
        left: 14px;
        z-index: 200;
        width: 28px;
        height: 28px;
        background: none;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #888780;
        font-size: 16px;
        padding: 0;
        transition: color 0.22s ease;
    " onmouseover="this.style.color='#F5F4F0'" onmouseout="this.style.color='#888780'">
        <i class="fas fa-bars"></i>
    </button>

    <!-- El contenido de cada vista se inyecta aquí -->
    <div class="admin-main" id="adminMain">
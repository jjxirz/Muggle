<nav class="nav-menu">
    <ul>
        <li>
            <a href="index.php" class="<?= (($nav_active_page ?? '') === 'inicio') ? 'active' : ''; ?>">
                Inicio
            </a>
        </li>

        <li>
            <a href="explorar.php" class="<?= (($nav_active_page ?? '') === 'explorar') ? 'active' : ''; ?>">
                Explorar
            </a>
        </li>

        <li>
            <a href="mi-lista.php" class="<?= (($nav_active_page ?? '') === 'mi-lista') ? 'active' : ''; ?>">
                Mi lista
            </a>
        </li>

        <li>
            <a href="categorias.php" class="<?= (($nav_active_page ?? '') === 'categorias') ? 'active' : ''; ?>">
                Categor&iacute;as
            </a>
        </li>

        <?php if ((bool) ($nav_is_admin ?? false)): ?>
            <li>
                <a href="src/views/admin/dashboard.php" class="<?= (($nav_active_page ?? '') === 'admin') ? 'active' : ''; ?>">
                    Admin
                </a>
            </li>
        <?php endif; ?>

        <li class="profile-dropdown-item">
            <div class="profile-dropdown">
                <button
                    type="button"
                    class="profile-dropdown-toggle <?= (($nav_active_page ?? '') === 'perfil') ? 'active' : ''; ?>"
                    data-dropdown-toggle="true"
                    aria-haspopup="true"
                    aria-expanded="false">
                    <i class="fas fa-user-circle"></i>
                    <span><?= htmlspecialchars((string) ($nav_user_name ?? 'Usuario'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>

                <div class="profile-dropdown-menu">
                    <div class="dropdown-user-summary">
                        <i class="fas fa-user-circle"></i>

                        <div>
                            <strong><?= htmlspecialchars((string) ($nav_user_name ?? 'Usuario'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?= htmlspecialchars((string) ($nav_user_secondary ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>

                    <?php if ((bool) ($nav_theme_enabled ?? false)): ?>
                        <form method="GET" action="" class="dropdown-theme-box">
                            <label for="<?= htmlspecialchars((string) ($nav_select_id ?? 'house-select-header'), ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="fas fa-palette"></i>
                                Temas
                            </label>

                            <select id="<?= htmlspecialchars((string) ($nav_select_id ?? 'house-select-header'), ENT_QUOTES, 'UTF-8'); ?>" name="house" onchange="this.form.submit()">
                                <option value="ravenclaw" <?= (($nav_house ?? 'ravenclaw') === 'ravenclaw') ? 'selected' : ''; ?>>
                                    Ravenclaw
                                </option>

                                <option value="gryffindor" <?= (($nav_house ?? 'ravenclaw') === 'gryffindor') ? 'selected' : ''; ?>>
                                    Gryffindor
                                </option>

                                <option value="slytherin" <?= (($nav_house ?? 'ravenclaw') === 'slytherin') ? 'selected' : ''; ?>>
                                    Slytherin
                                </option>

                                <option value="hufflepuff" <?= (($nav_house ?? 'ravenclaw') === 'hufflepuff') ? 'selected' : ''; ?>>
                                    Hufflepuff
                                </option>
                            </select>
                        </form>
                    <?php endif; ?>

                    <a href="perfil.php" class="profile-dropdown-link">
                        <i class="fas fa-id-card"></i>
                        Perfil
                    </a>

                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Sesi&oacute;n
                    </a>
                </div>
            </div>
        </li>
    </ul>
</nav>
<script src="assets/js/nav-dropdown.js" defer></script>

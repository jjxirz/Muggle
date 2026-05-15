

(function () {
    'use strict';

    /* =========================================================
       SIDEBAR — colapsar / expandir
    ========================================================= */
    var sidebar       = document.getElementById('adminSidebar');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var COLLAPSED_KEY = 'sidebar_collapsed';

    function applySidebarState(collapsed) {
        if (!sidebar) return;
        sidebar.classList.toggle('collapsed', collapsed);
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        // Mover el botón flotante junto al sidebar
        if (sidebarToggle) {
            sidebarToggle.style.left = collapsed ? '12px' : '192px';
        }
        // Sincronizar footer que está fuera del admin-shell
        var footer = document.querySelector('.admin-footer');
        if (footer) {
            footer.style.marginLeft = collapsed
                ? 'var(--sidebar-collapsed)'
                : 'var(--sidebar-width)';
        }
    }

    // Aplicar estado guardado al cargar
    applySidebarState(localStorage.getItem(COLLAPSED_KEY) === '1');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            var isCollapsed = !sidebar.classList.contains('collapsed');
            applySidebarState(isCollapsed);
            localStorage.setItem(COLLAPSED_KEY, isCollapsed ? '1' : '0');
        });
    }

    /* =========================================================
       BUSCADOR + FILTROS — Tabla de libros (catálogo)
    ========================================================= */
    var buscadorLibros = document.getElementById('buscadorLibros');
    var filtroCat      = document.getElementById('filtroCat');
    var filtroPlan     = document.getElementById('filtroPlan');
    var tablaLibros    = document.getElementById('tablaLibros');

    function filtrarTablaLibros() {
        if (!tablaLibros) return;
        var q    = buscadorLibros ? buscadorLibros.value.toLowerCase() : '';
        var cat  = filtroCat  ? filtroCat.value  : '';
        var plan = filtroPlan ? filtroPlan.value  : '';

        tablaLibros.querySelectorAll('tbody tr').forEach(function (tr) {
            var texto   = tr.textContent.toLowerCase();
            var trCat   = tr.dataset.cat  || '';
            var trPlan  = tr.dataset.plan || '';
            var ok = (!q || texto.includes(q)) && (!cat || trCat === cat) && (!plan || trPlan === plan);
            tr.style.display = ok ? '' : 'none';
        });
    }

    if (buscadorLibros) buscadorLibros.addEventListener('input', filtrarTablaLibros);
    if (filtroCat)      filtroCat.addEventListener('change', filtrarTablaLibros);
    if (filtroPlan)     filtroPlan.addEventListener('change', filtrarTablaLibros);

    /* =========================================================
       BUSCADOR — Tabla de categorías
    ========================================================= */
    var buscadorCat = document.getElementById('buscadorCat');
    var tablaCats   = document.getElementById('tablaCats');

    if (buscadorCat && tablaCats) {
        buscadorCat.addEventListener('input', function () {
            var q = this.value.toLowerCase();
            tablaCats.querySelectorAll('tbody tr').forEach(function (tr) {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    /* =========================================================
       BUSCADOR + FILTROS — Tabla de usuarios
    ========================================================= */
    var buscadorUsuarios = document.getElementById('buscadorUsuarios');
    var filtroPlanUsr    = document.getElementById('filtroPlanUsr');
    var filtroEstadoUsr  = document.getElementById('filtroEstadoUsr');
    var tablaUsuarios    = document.getElementById('tablaUsuarios');

    function filtrarTablaUsuarios() {
        if (!tablaUsuarios) return;
        var q      = buscadorUsuarios ? buscadorUsuarios.value.toLowerCase() : '';
        var plan   = filtroPlanUsr    ? filtroPlanUsr.value   : '';
        var estado = filtroEstadoUsr  ? filtroEstadoUsr.value : '';

        tablaUsuarios.querySelectorAll('tbody tr').forEach(function (tr) {
            var ok = (!q || tr.textContent.toLowerCase().includes(q)) &&
                     (!plan   || (tr.dataset.plan   || '') === plan) &&
                     (!estado || (tr.dataset.estado || '') === estado);
            tr.style.display = ok ? '' : 'none';
        });
    }

    if (buscadorUsuarios) buscadorUsuarios.addEventListener('input', filtrarTablaUsuarios);
    if (filtroPlanUsr)    filtroPlanUsr.addEventListener('change', filtrarTablaUsuarios);
    if (filtroEstadoUsr)  filtroEstadoUsr.addEventListener('change', filtrarTablaUsuarios);

    /* =========================================================
       UPLOAD AREAS — drag & drop + click
    ========================================================= */
    document.querySelectorAll('.upload-area').forEach(function (area) {
        var inputName = area.dataset.input;
        var input = inputName ? document.getElementById(inputName) : null;
        if (!input) return;

        area.addEventListener('click', function () { input.click(); });

        input.addEventListener('change', function () {
            if (this.files && this.files[0]) mostrarArchivoEnArea(area, this.files[0].name);
        });

        area.addEventListener('dragover', function (e) {
            e.preventDefault(); area.classList.add('dragover');
        });
        area.addEventListener('dragleave', function () {
            area.classList.remove('dragover');
        });
        area.addEventListener('drop', function (e) {
            e.preventDefault(); area.classList.remove('dragover');
            var files = e.dataTransfer.files;
            if (files && files[0]) {
                try { var dt = new DataTransfer(); dt.items.add(files[0]); input.files = dt.files; } catch(err) {}
                mostrarArchivoEnArea(area, files[0].name);
            }
        });
    });

    function mostrarArchivoEnArea(area, nombre) {
        area.classList.add('has-file');
        var span = area.querySelector('span');
        if (span) span.textContent = nombre;
    }

    /* =========================================================
       LOGS — filtros por tipo y búsqueda
    ========================================================= */
    var tablaLogs    = document.getElementById('tablaLogs');
    var buscadorLogs = document.getElementById('buscadorLogs');
    var filterBtns   = document.querySelectorAll('.log-filter-btn');
    var logsVisibles = document.getElementById('logsVisibles');
    var tipoActivo   = 'todos';

    function filtrarLogs() {
        if (!tablaLogs) return;
        var q = buscadorLogs ? buscadorLogs.value.toLowerCase() : '';
        // Marcar con data-filtrado sin tocar display (lo maneja actualizarPaginacion)
        tablaLogs.querySelectorAll('tbody tr.log-row').forEach(function (tr) {
            var ok = (tipoActivo === 'todos' || (tr.dataset.tipo || '') === tipoActivo) &&
                     (!q || tr.textContent.toLowerCase().includes(q));
            tr.dataset.filtrado = ok ? '1' : '0';
        });
        paginaActual = 1;
        actualizarPaginacion();
    }

    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            tipoActivo = btn.dataset.tipo || 'todos';
            filtrarLogs();
        });
    });

    if (buscadorLogs) buscadorLogs.addEventListener('input', filtrarLogs);

    /* =========================================================
       LOGS — Modal de detalle
    ========================================================= */
    var logModalOverlay = document.getElementById('logModalOverlay');
    var logModalBody    = document.getElementById('logModalBody');
    var logModalClose   = document.getElementById('logModalClose');

    document.querySelectorAll('.log-detalle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (logModalBody)    logModalBody.textContent = btn.dataset.detalle || '';
            if (logModalOverlay) logModalOverlay.classList.add('open');
        });
    });

    if (logModalClose) {
        logModalClose.addEventListener('click', function () { logModalOverlay.classList.remove('open'); });
    }
    if (logModalOverlay) {
        logModalOverlay.addEventListener('click', function (e) {
            if (e.target === logModalOverlay) logModalOverlay.classList.remove('open');
        });
    }

    /* =========================================================
       LOGS — Paginación (10 filas por página)
    ========================================================= */
    var ROWS_PER_PAGE   = 10;
    var btnPagAnterior  = document.getElementById('btnPagAnterior');
    var btnPagSiguiente = document.getElementById('btnPagSiguiente');
    var paginaActualEl  = document.getElementById('paginaActual');
    var paginaActual    = 1;

    function actualizarPaginacion() {
        if (!tablaLogs) return;
        var todasFilas = Array.from(tablaLogs.querySelectorAll('tbody tr.log-row'));
        // Usar data-filtrado si existe; si no existe aún, todas pasan
        var filasFiltradas = todasFilas.filter(function (tr) {
            return tr.dataset.filtrado !== '0';
        });
        var total = filasFiltradas.length;
        var totalPags = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
        if (paginaActual > totalPags) paginaActual = totalPags;

        // Ocultar todas primero
        todasFilas.forEach(function (tr) { tr.style.display = 'none'; });
        // Mostrar solo las de la página actual
        filasFiltradas.forEach(function (tr, i) {
            var enPag = i >= (paginaActual - 1) * ROWS_PER_PAGE && i < paginaActual * ROWS_PER_PAGE;
            if (enPag) tr.style.display = '';
        });

        var enPagina = filasFiltradas.slice((paginaActual - 1) * ROWS_PER_PAGE, paginaActual * ROWS_PER_PAGE).length;
        if (paginaActualEl)  paginaActualEl.textContent = paginaActual;
        if (btnPagAnterior)  btnPagAnterior.disabled     = paginaActual <= 1;
        if (btnPagSiguiente) btnPagSiguiente.disabled    = paginaActual >= totalPags;
        if (logsVisibles)    logsVisibles.textContent    = enPagina;
    }

    if (btnPagAnterior)  btnPagAnterior.addEventListener('click',  function () { if (paginaActual > 1) { paginaActual--; actualizarPaginacion(); } });
    if (btnPagSiguiente) btnPagSiguiente.addEventListener('click', function () { paginaActual++; actualizarPaginacion(); });

    if (tablaLogs) actualizarPaginacion();

})();

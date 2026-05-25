document.addEventListener('DOMContentLoaded', function () {
    const config = window.READER_CONFIG || {};

    const flipbookElement = document.getElementById('flipbook');
    const wrapper = document.getElementById('flipbookWrapper');
    const loading = document.getElementById('readerLoading');
    const loadingText = document.getElementById('loadingText');
    const loadingProgressBar = document.getElementById('loadingProgressBar');
    const loadingPercent = document.getElementById('loadingPercent');
    const currentPage = document.getElementById('currentPage');
    const totalPages = document.getElementById('totalPages');
    const pageCounter = document.querySelector('.reader-page-counter');
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    const fullscreenIcon = document.getElementById('fullscreenIcon');
    const fullscreenLabel = document.getElementById('fullscreenLabel');
    const viewModeBtn = document.getElementById('viewModeBtn');
    const viewModeIcon = document.getElementById('viewModeIcon');
    const shortcutsToggle = document.getElementById('shortcutsToggle');
    const shortcutsHelp = document.getElementById('shortcutsHelp');
    const sidebar = document.getElementById('readerSidebar');
    const readerPercent = document.getElementById('readerPercent');

    let pageFlip = null;
    let lastSavedPage = 0;
    let sidebarHideTimer = null;
    let viewMode = localStorage.getItem('reader_view_mode') === 'single' ? 'single' : 'double';

    function applyViewMode(persist) {
        const isSingle = viewMode === 'single';
        document.body.classList.toggle('reader-single-page', isSingle);

        if (viewModeIcon) {
            viewModeIcon.textContent = isSingle ? '1' : '2';
        }

        if (viewModeBtn) {
            const nextTip = isSingle ? 'Cambiar a 2 paginas' : 'Cambiar a 1 pagina';
            viewModeBtn.setAttribute('title', nextTip);
            viewModeBtn.setAttribute('data-tip', nextTip);
        }

        if (persist) {
            localStorage.setItem('reader_view_mode', viewMode);
        }

        if (pageFlip && typeof pageFlip.update === 'function') {
            pageFlip.update();
        }
    }

    function showSidebarTemporarily() {
        if (!document.fullscreenElement) {
            return;
        }

        document.body.classList.add('reader-sidebar-visible');

        if (sidebarHideTimer) {
            clearTimeout(sidebarHideTimer);
        }

        sidebarHideTimer = window.setTimeout(function () {
            const sidebarHovered = sidebar && sidebar.matches(':hover');
            if (!sidebarHovered) {
                document.body.classList.remove('reader-sidebar-visible');
            }
            sidebarHideTimer = null;
        }, 1100);
    }

    function hideSidebarNow() {
        if (sidebarHideTimer) {
            clearTimeout(sidebarHideTimer);
            sidebarHideTimer = null;
        }

        document.body.classList.remove('reader-sidebar-visible');
    }

    function initSidebarAutoHide() {
        document.addEventListener('mousemove', function (event) {
            if (!document.fullscreenElement) {
                return;
            }

            if (event.clientX <= 26) {
                showSidebarTemporarily();
                return;
            }

            if (event.clientX > 90) {
                const sidebarHovered = sidebar && sidebar.matches(':hover');
                if (!sidebarHovered) {
                    hideSidebarNow();
                }
            }
        });

        if (!sidebar) {
            return;
        }

        sidebar.addEventListener('mouseenter', showSidebarTemporarily);
        sidebar.addEventListener('mouseleave', function () {
            if (document.fullscreenElement) {
                hideSidebarNow();
            }
        });
    }

    async function saveProgress(page, total) {
        if (!config.apiUrl || !config.csrfToken || !config.file) {
            return;
        }

        if (page <= 0 || total <= 0 || page === lastSavedPage) {
            return;
        }

        lastSavedPage = page;
        const body = new URLSearchParams({
            action: 'save_progress',
            csrf_token: config.csrfToken,
            file: config.file,
            title: config.title || '',
            author: '',
            description: '',
            type: 'pdf',
            page: String(page),
            total_pages: String(total)
        });

        try {
            await fetch(config.apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            });
        } catch (error) {
            console.error('No se pudo guardar progreso', error);
        }
    }

    function showError(message) {
        if (loadingText) {
            loadingText.textContent = message;
        }
    }

    function setLoadingText(message) {
        if (loadingText) {
            loadingText.textContent = message;
        }
    }

    function setProgress(current, total) {
        if (!total || total <= 0) {
            return;
        }

        const percent = Math.min(100, Math.round((current / total) * 100));

        if (loadingProgressBar) {
            loadingProgressBar.style.width = percent + '%';
        }

        if (loadingPercent) {
            loadingPercent.textContent = percent + '%';
        }
    }

    function createPageElement(canvas) {
        const page = document.createElement('div');
        page.className = 'reader-page';
        page.appendChild(canvas);

        return page;
    }

    async function renderPdfPages(pdf) {
        const pageCount = pdf.numPages;

        if (totalPages) {
            totalPages.textContent = String(pageCount);
        }

        if (currentPage) {
            currentPage.max = String(pageCount);
        }

        for (let pageNumber = 1; pageNumber <= pageCount; pageNumber++) {
            setLoadingText('Preparando página ' + pageNumber + ' de ' + pageCount + '...');
            setProgress(pageNumber, pageCount);

            const page = await pdf.getPage(pageNumber);
            const viewportBase = page.getViewport({ scale: 1 });

            const targetWidth = 900;
            const scale = targetWidth / viewportBase.width;
            const viewport = page.getViewport({ scale: scale });

            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            canvas.width = viewport.width;
            canvas.height = viewport.height;

            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;

            flipbookElement.appendChild(createPageElement(canvas));
        }
    }

    function updateReaderUi(page, total) {
        if (currentPage) {
            currentPage.value = String(page);
        }

        if (totalPages) {
            totalPages.textContent = String(total);
        }

        if (readerPercent) {
            const pct = total > 0 ? Math.round((page / total) * 100) : 0;
            readerPercent.textContent = pct + '%';
        }

        if (prevBtn) {
            prevBtn.disabled = page <= 1;
        }

        if (nextBtn) {
            nextBtn.disabled = total > 0 && page >= total;
        }

        if (pageCounter) {
            const maxDigits = Math.max(String(page).length, String(total).length);
            pageCounter.classList.toggle('is-compact', maxDigits >= 3);
            pageCounter.classList.toggle('is-ultra', maxDigits >= 4);
        }
    }

    function jumpToPage(value) {
        if (!pageFlip) {
            return;
        }

        const total = pageFlip.getPageCount();
        if (total <= 0) {
            return;
        }

        const requested = Number.parseInt(String(value), 10);
        if (Number.isNaN(requested)) {
            updateReaderUi(pageFlip.getCurrentPageIndex() + 1, total);
            return;
        }

        const target = Math.max(1, Math.min(total, requested));
        const current = pageFlip.getCurrentPageIndex() + 1;

        if (target === current) {
            updateReaderUi(current, total);
            return;
        }

        pageFlip.flip(target - 1);
    }

    function syncFullscreenUi() {
        const isFullscreen = Boolean(document.fullscreenElement);

        document.body.classList.toggle('reader-fullscreen', isFullscreen);

        if (fullscreenIcon) {
            fullscreenIcon.textContent = isFullscreen ? '↙' : '⛶';
        }

        if (fullscreenLabel) {
            fullscreenLabel.textContent = isFullscreen ? 'Salir' : 'Maximizar';
        }

        if (isFullscreen) {
            showSidebarTemporarily();
        } else {
            hideSidebarNow();
        }

        if (pageFlip && typeof pageFlip.update === 'function') {
            pageFlip.update();
        }
    }

    function initFlipbook() {
        const pages = flipbookElement.querySelectorAll('.reader-page');
        const viewportWidth = window.innerWidth || 1280;
        const viewportHeight = window.innerHeight || 900;
        const isSingle = viewMode === 'single';

        const adaptiveMaxWidth = isSingle
            ? Math.max(480, Math.min(540, Math.round(viewportWidth * 0.42)))
            : Math.max(900, Math.min(1320, Math.round(viewportWidth * 0.82)));
        const adaptiveMaxHeight = Math.max(1100, Math.min(1700, Math.round(viewportHeight * 1.8)));

        pageFlip = new St.PageFlip(flipbookElement, {
            width: 450,
            height: 620,
            size: 'stretch',

            minWidth: 300,
            maxWidth: adaptiveMaxWidth,
            minHeight: 420,
            maxHeight: adaptiveMaxHeight,

            showCover: true,
            usePortrait: true,

            drawShadow: true,
            maxShadowOpacity: 0.45,
            flippingTime: 650,

            mobileScrollSupport: true
        });

        pageFlip.loadFromHTML(pages);

        pageFlip.on('flip', function (event) {
            const total = pageFlip.getPageCount();
            updateReaderUi(event.data + 1, total);
            saveProgress(event.data + 1, total);
        });

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                pageFlip.flipPrev();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                pageFlip.flipNext();
            });
        }

        if (currentPage) {
            currentPage.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    jumpToPage(currentPage.value);
                    currentPage.blur();
                }
            });

            currentPage.addEventListener('blur', function () {
                jumpToPage(currentPage.value);
            });
        }

        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', async function () {
                try {
                    if (!document.fullscreenElement) {
                        await document.documentElement.requestFullscreen();
                    } else {
                        await document.exitFullscreen();
                    }
                } catch (error) {
                    console.error('No se pudo cambiar el modo pantalla completa', error);
                }
            });
        }

        if (viewModeBtn) {
            viewModeBtn.addEventListener('click', function () {
                viewMode = viewMode === 'single' ? 'double' : 'single';
                applyViewMode(true);
            });
        }

        if (shortcutsToggle && shortcutsHelp) {
            shortcutsToggle.addEventListener('click', function () {
                const isVisible = shortcutsHelp.classList.toggle('is-visible');
                shortcutsHelp.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            });
        }

        document.addEventListener('fullscreenchange', syncFullscreenUi);
        syncFullscreenUi();
        initSidebarAutoHide();
        window.addEventListener('resize', function () {
            if (pageFlip && typeof pageFlip.update === 'function') {
                pageFlip.update();
            }
        });

        document.addEventListener('keydown', function (event) {
            const targetTag = event.target && event.target.tagName ? event.target.tagName.toLowerCase() : '';
            if (targetTag === 'input' || targetTag === 'textarea') {
                return;
            }

            if (event.key === 'ArrowLeft') {
                pageFlip.flipPrev();
            }

            if (event.key === 'ArrowRight') {
                pageFlip.flipNext();
            }

            if (event.key === 'f' || event.key === 'F') {
                event.preventDefault();
                if (fullscreenBtn) {
                    fullscreenBtn.click();
                }
            }
        });

        if (loading) {
            loading.classList.add('is-hidden');
        }

        if (wrapper) {
            wrapper.classList.add('is-ready');
        }

        const total = pageFlip.getPageCount();
        updateReaderUi(1, total);
        saveProgress(1, total);
    }

    async function startReader() {
        if (!config.pdfUrl) {
            showError('No se recibió un PDF válido.');
            return;
        }

        if (!window.pdfjsLib) {
            showError('No se pudo cargar PDF.js.');
            return;
        }

        if (!window.St || !window.St.PageFlip) {
            showError('No se pudo cargar la librería del flipbook.');
            return;
        }

        pdfjsLib.GlobalWorkerOptions.workerSrc = config.workerUrl;
        applyViewMode(false);

        try {
            setLoadingText('Cargando libro...');
            setProgress(0, 100);

            const loadingTask = pdfjsLib.getDocument(config.pdfUrl);
            const pdf = await loadingTask.promise;

            await renderPdfPages(pdf);
            initFlipbook();
        } catch (error) {
            console.error(error);
            showError('No se pudo preparar el libro. Verifica que el PDF exista y no esté dañado.');
        }
    }

    startReader();
});
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

    const jumpPageInput = document.getElementById('jumpPageInput');
    const jumpPageBtn = document.getElementById('jumpPageBtn');

    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');
    const fullscreenBtn = document.getElementById('fullscreenBtn');

    let pageFlip = null;
    let pageCount = 0;

    // ========== OCULTAR FLIPBOOK DURANTE LA CARGA ==========
    if (wrapper) {
        wrapper.style.visibility = 'hidden';
        wrapper.style.opacity = '0';
        wrapper.style.position = 'absolute';
        wrapper.style.pointerEvents = 'none';
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

    function updatePageUI(pageNumber) {
        const safePage = Math.max(1, Math.min(pageNumber, pageCount || 1));

        if (currentPage) {
            currentPage.textContent = String(safePage);
        }

        if (jumpPageInput) {
            jumpPageInput.value = String(safePage);
        }

        if (prevBtn) {
            prevBtn.disabled = safePage <= 1;
        }

        if (nextBtn) {
            nextBtn.disabled = pageCount > 0 && safePage >= pageCount;
        }
    }

    function createPageElement(canvas) {
        const page = document.createElement('div');
        page.className = 'reader-page';
        page.appendChild(canvas);

        return page;
    }

    async function renderPdfPages(pdf) {
        pageCount = pdf.numPages;

        if (totalPages) {
            totalPages.textContent = String(pageCount);
        }

        if (jumpPageInput) {
            jumpPageInput.max = String(pageCount);
        }

        updatePageUI(1);

        // Crear un fragmento para no disparar reflows múltiples
        const fragment = document.createDocumentFragment();

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

            if (!context) {
                throw new Error('No se pudo preparar el canvas de lectura.');
            }

            canvas.width = viewport.width;
            canvas.height = viewport.height;

            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;

            fragment.appendChild(createPageElement(canvas));
        }

        // Insertar todas las páginas de una sola vez
        flipbookElement.appendChild(fragment);
    }

    function goToPage(pageNumber) {
        if (!pageFlip || !pageCount) {
            return;
        }

        const requestedPage = Number.parseInt(String(pageNumber), 10);

        if (Number.isNaN(requestedPage)) {
            updatePageUI(pageFlip.getCurrentPageIndex() + 1);
            return;
        }

        const safePage = Math.max(1, Math.min(requestedPage, pageCount));

        pageFlip.turnToPage(safePage - 1);
        updatePageUI(safePage);
    }

    function toggleFullscreen() {
        const target = wrapper || flipbookElement;
        if (!target) {
            return;
        }

        if (!document.fullscreenElement) {
            target.requestFullscreen().catch(function () {
                showError('No se pudo activar pantalla completa.');
            });
            return;
        }

        document.exitFullscreen();
    }

    function updateFullscreenButton() {
        if (!fullscreenBtn) {
            return;
        }

        if (document.fullscreenElement) {
            fullscreenBtn.textContent = '✕';
            fullscreenBtn.setAttribute('aria-label', 'Salir de pantalla completa');
            return;
        }

        fullscreenBtn.textContent = '⛶';
        fullscreenBtn.setAttribute('aria-label', 'Pantalla completa');
    }

    // ========== MOSTRAR FLIPBOOK CON TRANSICIÓN SUAVE ==========
    function showFlipbook() {
        if (!wrapper) return;

        // Ocultar loading
        if (loading) {
            loading.style.display = 'none';
            loading.classList.add('is-hidden');
        }

        // Mostrar flipbook con transición
        wrapper.style.position = '';
        wrapper.style.pointerEvents = '';
        wrapper.style.visibility = 'visible';

        // Pequeño delay para que el navegador procese el cambio de display
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                wrapper.style.opacity = '1';
                wrapper.classList.add('is-ready');
            });
        });
    }

    function initFlipbook() {
        const pages = flipbookElement.querySelectorAll('.reader-page');

        pageFlip = new St.PageFlip(flipbookElement, {
            width: 450,
            height: 620,
            size: 'stretch',

            minWidth: 300,
            maxWidth: 900,
            minHeight: 420,
            maxHeight: 1200,

            showCover: true,
            usePortrait: true,

            drawShadow: true,
            maxShadowOpacity: 0.45,
            flippingTime: 650,

            mobileScrollSupport: true
        });

        pageFlip.loadFromHTML(pages);

        pageFlip.on('flip', function (event) {
            updatePageUI(event.data + 1);
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

        if (jumpPageBtn && jumpPageInput) {
            jumpPageBtn.addEventListener('click', function () {
                goToPage(jumpPageInput.value);
            });

            jumpPageInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    goToPage(jumpPageInput.value);
                }
            });

            jumpPageInput.addEventListener('blur', function () {
                const val = Number.parseInt(jumpPageInput.value, 10);
                if (!Number.isNaN(val) && pageCount > 0) {
                    if (val > pageCount) {
                        jumpPageInput.value = String(pageCount);
                    } else if (val < 1) {
                        jumpPageInput.value = '1';
                    }
                }
            });
        }

        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', toggleFullscreen);
        }

        document.addEventListener('fullscreenchange', updateFullscreenButton);

        document.addEventListener('keydown', function (event) {
            if (document.activeElement === jumpPageInput) {
                return;
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                pageFlip.flipPrev();
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                pageFlip.flipNext();
            }

            if (event.key.toLowerCase() === 'f' && !event.ctrlKey && !event.metaKey) {
                event.preventDefault();
                toggleFullscreen();
            }
        });

        // Mostrar flipbook solo cuando PageFlip ya está inicializado
        showFlipbook();

        updatePageUI(1);
        updateFullscreenButton();
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
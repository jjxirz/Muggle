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
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');

    let pageFlip = null;

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
            if (currentPage) {
                currentPage.textContent = String(event.data + 1);
            }
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

        document.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                pageFlip.flipPrev();
            }

            if (event.key === 'ArrowRight') {
                pageFlip.flipNext();
            }
        });

        if (loading) {
            loading.classList.add('is-hidden');
        }

        if (wrapper) {
            wrapper.classList.add('is-ready');
        }
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
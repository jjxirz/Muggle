(function () {
    'use strict';

    // Isolated TTS experimental module. It does not modify reader core flow.
    document.addEventListener('DOMContentLoaded', function () {
        const config = window.READER_CONFIG || {};
        const toggleBtn = document.getElementById('ttsToggleBtn');
        const panel = document.getElementById('ttsFloatingPanel');
        const closeBtn = document.getElementById('ttsCloseBtn');
        const readBtn = document.getElementById('ttsReadBtn');
        const stopBtn = document.getElementById('ttsStopBtn');
        const toast = document.getElementById('ttsToast');
        const currentPageInput = document.getElementById('currentPage');

        if (!toggleBtn || !panel || !readBtn || !stopBtn || !toast) {
            return;
        }

        const hasSpeech = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;
        const hasPdf = Boolean(window.pdfjsLib && config.pdfUrl);
        let toastTimer = null;

        if (!hasSpeech || !hasPdf) {
            toggleBtn.disabled = true;
            readBtn.disabled = true;
            stopBtn.disabled = true;
            showToast('TTS no disponible');
            return;
        }

        const cache = new Map();
        let pdfDoc = null;
        let speakingPage = null;
        let panelVisible = false;

        function showToast(message) {
            toast.textContent = message;
            toast.classList.add('is-visible');

            if (toastTimer) {
                clearTimeout(toastTimer);
            }

            toastTimer = window.setTimeout(function () {
                toast.classList.remove('is-visible');
                toastTimer = null;
            }, 1800);
        }

        function setPanelVisible(visible) {
            panelVisible = Boolean(visible);
            panel.classList.toggle('is-visible', panelVisible);
            panel.setAttribute('aria-hidden', panelVisible ? 'false' : 'true');
        }

        async function getPdfDoc() {
            if (pdfDoc) {
                return pdfDoc;
            }

            const loadingTask = window.pdfjsLib.getDocument(config.pdfUrl);
            pdfDoc = await loadingTask.promise;
            return pdfDoc;
        }

        async function getPageText(pageNumber) {
            if (cache.has(pageNumber)) {
                return cache.get(pageNumber);
            }

            const doc = await getPdfDoc();
            const page = await doc.getPage(pageNumber);
            const content = await page.getTextContent();

            const text = content.items
                .map(function (item) {
                    return (item && item.str ? item.str : '').trim();
                })
                .filter(function (part) {
                    return part.length > 0;
                })
                .join(' ')
                .replace(/\s+/g, ' ')
                .trim();

            cache.set(pageNumber, text);
            return text;
        }

        function stopSpeech() {
            window.speechSynthesis.cancel();
            speakingPage = null;
            showToast('Lectura detenida');
        }

        async function readCurrentPage() {
            const raw = currentPageInput ? currentPageInput.value : '1';
            const pageNumber = Math.max(1, Number.parseInt(raw, 10) || 1);

            try {
                showToast('Extrayendo texto...');
                const text = await getPageText(pageNumber);

                if (!text) {
                    showToast('Pagina sin texto seleccionable');
                    return;
                }

                window.speechSynthesis.cancel();

                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'es-ES';
                utterance.rate = 1;
                utterance.pitch = 1;
                utterance.volume = 1;

                utterance.onstart = function () {
                    speakingPage = pageNumber;
                    showToast('Leyendo pagina ' + pageNumber);
                };

                utterance.onend = function () {
                    if (speakingPage === pageNumber) {
                        showToast('Lectura finalizada');
                        speakingPage = null;
                    }
                };

                utterance.onerror = function () {
                    speakingPage = null;
                    showToast('Error de lectura');
                };

                window.speechSynthesis.speak(utterance);
            } catch (error) {
                console.error('TTS error:', error);
                speakingPage = null;
                showToast('No se pudo leer la pagina');
            }
        }

        toggleBtn.addEventListener('click', function () {
            setPanelVisible(!panelVisible);
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                setPanelVisible(false);
            });
        }

        readBtn.addEventListener('click', function () {
            readCurrentPage();
        });

        stopBtn.addEventListener('click', function () {
            stopSpeech();
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden && window.speechSynthesis.speaking) {
                window.speechSynthesis.pause();
                showToast('Lectura pausada');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && panelVisible) {
                setPanelVisible(false);
            }
        });

        document.addEventListener('click', function (event) {
            const target = event.target;
            if (!panelVisible) {
                return;
            }

            if (!target) {
                return;
            }

            if (panel.contains(target) || toggleBtn.contains(target)) {
                return;
            }

            setPanelVisible(false);
        });

        setPanelVisible(false);
    });
})();

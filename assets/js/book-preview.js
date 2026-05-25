document.addEventListener('DOMContentLoaded', function () {
    const backdrop = document.getElementById('bookPreviewBackdrop');
    const closeBtn = document.getElementById('bookPreviewClose');

    if (!backdrop || !closeBtn) {
        return;
    }

    const hero = document.getElementById('bookPreviewHero');
    const title = document.getElementById('bookPreviewTitle');
    const author = document.getElementById('bookPreviewAuthor');
    const rating = document.getElementById('bookPreviewRating');
    const cover = document.getElementById('bookPreviewCover');
    const coverPlaceholder = document.getElementById('bookPreviewCoverPlaceholder');
    const coverTitle = document.getElementById('bookPreviewCoverTitle');
    const year = document.getElementById('bookPreviewYear');
    const category = document.getElementById('bookPreviewCategory');
    const pages = document.getElementById('bookPreviewPages');
    const description = document.getElementById('bookPreviewDescription');
    const tags = document.getElementById('bookPreviewTags');
    const readBtn = document.getElementById('bookPreviewReadBtn');
    const note = document.getElementById('bookPreviewNote');

    function setText(element, value, fallback) {
        if (!element) {
            return;
        }

        if (value && value.trim() !== '') {
            element.textContent = value;
        } else {
            element.textContent = fallback;
        }
    }

    function openPreview(card) {
        const data = card.dataset;

        const bookTitle = data.title || 'Libro seleccionado';
        const bookAuthor = data.author || 'Autor no disponible';
        const bookRating = data.rating || 'PDF';
        const bookCover = data.cover || '';
        const bookBanner = data.banner || '';
        const bookPdf = data.pdf || '';

        setText(title, bookTitle, 'Libro seleccionado');
        setText(author, bookAuthor, 'Autor no disponible');
        setText(rating, bookRating === 'PDF' ? 'PDF' : '⭐ ' + bookRating, 'PDF');
        setText(year, data.year, 'Año no disponible');
        setText(category, data.category, 'Categoría no disponible');
        setText(pages, data.pages, 'PDF disponible');
        setText(description, data.description, 'No hay descripción disponible para este libro.');
        setText(coverTitle, bookTitle, 'Libro');

        if (bookCover !== '') {
            cover.src = bookCover;
            cover.alt = 'Portada de ' + bookTitle;
            cover.style.display = 'block';

            if (coverPlaceholder) {
                coverPlaceholder.style.display = 'none';
            }
        } else {
            cover.removeAttribute('src');
            cover.alt = '';
            cover.style.display = 'none';

            if (coverPlaceholder) {
                coverPlaceholder.style.display = 'flex';
            }
        }

        if (hero) {
            if (bookBanner !== '') {
                hero.style.backgroundImage = 'url("' + bookBanner + '")';
            } else {
                hero.style.backgroundImage = 'none';
            }
        }

        if (tags) {
            tags.innerHTML = '';

            const tagList = (data.tags || data.category || 'Libro')
                .split(',')
                .map(function (tag) {
                    return tag.trim();
                })
                .filter(Boolean);

            tagList.forEach(function (tag) {
                const span = document.createElement('span');
                span.textContent = tag;
                tags.appendChild(span);
            });
        }

        if (bookPdf && bookPdf !== '#') {
            readBtn.href = bookPdf;
            readBtn.classList.remove('is-disabled');
            readBtn.setAttribute('aria-disabled', 'false');
            readBtn.textContent = '▶ Leer PDF';
            setText(note, 'El PDF se abrirá en una pestaña nueva.', '');
        } else {
            readBtn.href = '#';
            readBtn.classList.add('is-disabled');
            readBtn.setAttribute('aria-disabled', 'true');
            readBtn.textContent = 'PDF no disponible';
            setText(note, 'Este libro todavía no tiene PDF cargado en assets/books.', '');
        }

        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.classList.add('preview-open');
        closeBtn.focus();
    }

    function closePreview() {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('preview-open');
    }

    document.querySelectorAll('.js-book-preview').forEach(function (card) {
        card.addEventListener('click', function (event) {
            event.preventDefault();
            openPreview(card);
        });

        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openPreview(card);
            }
        });
    });

    closeBtn.addEventListener('click', closePreview);

    backdrop.addEventListener('click', function (event) {
        if (event.target === backdrop) {
            closePreview();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && backdrop.classList.contains('is-open')) {
            closePreview();
        }
    });
});
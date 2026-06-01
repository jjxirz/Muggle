document.addEventListener('DOMContentLoaded', function () {
    const backdrop = document.getElementById('bookPreviewBackdrop');
    const closeBtn = document.getElementById('bookPreviewClose');

    if (!backdrop || !closeBtn) {
        return;
    }

    const hero = document.getElementById('bookPreviewHero');
    const title = document.getElementById('bookPreviewTitle');
    const author = document.getElementById('bookPreviewAuthor');
    const categoryTop = document.getElementById('bookPreviewCategoryTop');
    const initial = document.getElementById('bookPreviewInitial');
    const coverTitle = document.getElementById('bookPreviewCoverTitle');
    const coverBox = document.getElementById('bookPreviewCoverBox');
    const coverImg = document.getElementById('bookPreviewCoverImg');
    const year = document.getElementById('bookPreviewYear');
    const category = document.getElementById('bookPreviewCategory');
    const pages = document.getElementById('bookPreviewPages');
    const description = document.getElementById('bookPreviewDescription');
    const tags = document.getElementById('bookPreviewTags');
    const readBtn = document.getElementById('bookPreviewReadBtn');
    const favoriteBtn = document.getElementById('bookPreviewFavoriteBtn');
    const favoriteBtnText = favoriteBtn ? favoriteBtn.querySelector('span') : null;
    const favoriteBtnIcon = favoriteBtn ? favoriteBtn.querySelector('i') : null;

    const apiUrl = backdrop.getAttribute('data-api-url') || '';
    const csrfToken = backdrop.getAttribute('data-csrf-token') || '';

    let activeBookPayload = null;
    let favoriteState = false;

    function buildBookPayload(data) {
        return {
            file: data.file || '',
            title: data.title || '',
            author: data.author || '',
            description: data.description || '',
            type: data.type || 'pdf'
        };
    }

    function setFavoriteState(isFavorite) {
        favoriteState = Boolean(isFavorite);

        if (!favoriteBtn) {
            return;
        }

        if (favoriteBtnText) {
            favoriteBtnText.textContent = favoriteState ? 'Quitar de mi lista' : 'Agregar a mi lista';
        }

        favoriteBtn.classList.toggle('is-favorite', favoriteState);
        favoriteBtn.setAttribute('aria-pressed', favoriteState ? 'true' : 'false');

        if (favoriteBtnIcon) {
            favoriteBtnIcon.classList.remove('fas', 'far');
            favoriteBtnIcon.classList.add(favoriteState ? 'fas' : 'far', 'fa-heart');
        }
    }

    async function refreshFavoriteButton(payload) {
        if (!favoriteBtn || !favoriteBtnText || !payload || !apiUrl) {
            return;
        }

        try {
            const params = new URLSearchParams({
                action: 'favorite_status',
                file: payload.file,
                title: payload.title,
                author: payload.author,
                description: payload.description,
                type: payload.type
            });

            const response = await fetch(apiUrl + '?' + params.toString(), {
                credentials: 'same-origin'
            });
            const result = await response.json();

            if (!response.ok || !result.ok) {
                setFavoriteState(false);
                return;
            }

            setFavoriteState(Boolean(result.data.is_favorite));
        } catch (error) {
            setFavoriteState(false);
        }
    }

    function setText(element, value, fallback) {
        if (!element) {
            return;
        }

        element.textContent = value && value.trim() !== '' ? value : fallback;
    }

    function openPreview(card) {
        const data = card.dataset;
        const bookTitle = data.title || 'Obra sin identificar';
        const bookCategory = data.category || 'Lectura digital';
        const bookPdf = data.pdf || '';
        const bookReader = data.reader || '';
        const bookCover = data.cover || '';
        activeBookPayload = buildBookPayload(data);

        setText(title, bookTitle, 'Obra sin identificar');
        setText(author, data.author, 'Autor no especificado');
        setText(categoryTop, bookCategory, 'Lectura digital');
        setText(initial, bookTitle.substring(0, 1), 'L');
        setText(coverTitle, bookTitle, 'Libro');
        setText(year, data.year, 'Disponible');
        setText(category, bookCategory, 'Lectura digital');
        setText(pages, data.pages, 'Archivo disponible');
        setText(description, data.description, 'Obra disponible en el catálogo digital de la biblioteca.');

        if (bookCover && coverImg && coverBox) {
            coverImg.src = bookCover;
            coverImg.alt = 'Portada de ' + bookTitle;
            coverImg.style.display = 'block';
            coverBox.classList.add('has-image');

            if (initial) {
                initial.style.display = 'none';
            }

            if (coverTitle) {
                coverTitle.style.display = 'none';
            }
        } else if (coverImg && coverBox) {
            coverImg.removeAttribute('src');
            coverImg.alt = '';
            coverImg.style.display = 'none';
            coverBox.classList.remove('has-image');

            if (initial) {
                initial.style.display = 'inline-flex';
            }

            if (coverTitle) {
                coverTitle.style.display = 'block';
            }
        }

        if (hero) {
            const backgroundImage = data.banner || bookCover;
            hero.style.backgroundImage = backgroundImage ? 'url("' + backgroundImage + '")' : 'none';
        }

        if (tags) {
            tags.innerHTML = '';

            const tagList = (data.tags || bookCategory)
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

        if (bookPdf) {
            readBtn.href = bookReader || bookPdf;
            readBtn.textContent = 'Comenzar lectura';
            readBtn.style.pointerEvents = 'auto';
            readBtn.style.opacity = '1';
        } else {
            readBtn.href = '#';
            readBtn.textContent = 'Lectura no disponible';
            readBtn.style.pointerEvents = 'none';
            readBtn.style.opacity = '0.55';
        }

        setFavoriteState(false);
        refreshFavoriteButton(activeBookPayload);

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

    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', async function () {
            if (!activeBookPayload || !csrfToken || !apiUrl) {
                return;
            }

            const previousState = favoriteState;
            setFavoriteState(!previousState);

            const payload = new URLSearchParams({
                action: 'toggle_favorite',
                csrf_token: csrfToken,
                file: activeBookPayload.file,
                title: activeBookPayload.title,
                author: activeBookPayload.author,
                description: activeBookPayload.description,
                type: activeBookPayload.type
            });

            favoriteBtn.disabled = true;

            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: payload.toString()
                });

                const result = await response.json();
                if (!response.ok || !result.ok) {
                    throw new Error('No se pudo actualizar favoritos.');
                }

                setFavoriteState(Boolean(result.data.is_favorite));
            } catch (error) {
                setFavoriteState(previousState);
                alert('No se pudo actualizar tu lista en este momento.');
            } finally {
                favoriteBtn.disabled = false;
            }
        });
    }

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

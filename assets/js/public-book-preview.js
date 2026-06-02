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
    const readingListBtn = document.getElementById('bookPreviewReadingListBtn');
    const readingListBtnText = readingListBtn ? readingListBtn.querySelector('span') : null;
    const readingListBtnIcon = readingListBtn ? readingListBtn.querySelector('i') : null;
    const favoriteBtn = document.getElementById('bookPreviewFavoriteBtn');
    const favoriteBtnText = favoriteBtn ? favoriteBtn.querySelector('span') : null;
    const favoriteBtnIcon = favoriteBtn ? favoriteBtn.querySelector('i') : null;

    const apiUrl = backdrop.getAttribute('data-api-url') || '';
    const csrfToken = backdrop.getAttribute('data-csrf-token') || '';
    const favoriteStatusCache = new Map();
    const progressStatusCache = new Map();

    let activeBookPayload = null;
    let activeCard = null;
    let readingListState = false;
    let favoriteState = false;

    function setCardFavoriteState(card, isFavorite) {
        if (!card) {
            return;
        }

        const indicator = card.querySelector('.book-favorite-indicator');
        card.classList.toggle('is-favorite-card', Boolean(isFavorite));

        if (indicator) {
            indicator.classList.toggle('is-visible', Boolean(isFavorite));
        }
    }

    function setCardProgressState(card, hasProgress) {
        if (!card) {
            return;
        }

        const indicator = card.querySelector('.book-progress-indicator');
        card.classList.toggle('is-progress-card', Boolean(hasProgress));

        if (indicator) {
            indicator.classList.toggle('is-visible', Boolean(hasProgress));
        }
    }

    function syncFavoriteStateAcrossCards(payload, isFavorite) {
        if (!payload) {
            return;
        }

        const payloadKey = [payload.file || '', payload.title || '', payload.author || ''].join('||').toLowerCase();

        document.querySelectorAll('.js-book-preview').forEach(function (card) {
            const cardPayload = buildBookPayload(card.dataset);
            const cardKey = [cardPayload.file || '', cardPayload.title || '', cardPayload.author || ''].join('||').toLowerCase();

            if (cardKey === payloadKey) {
                setCardFavoriteState(card, isFavorite);
            }
        });
    }

    function syncProgressStateAcrossCards(payload, hasProgress) {
        if (!payload) {
            return;
        }

        const payloadKey = [payload.file || '', payload.title || '', payload.author || ''].join('||').toLowerCase();

        document.querySelectorAll('.js-book-preview').forEach(function (card) {
            const cardPayload = buildBookPayload(card.dataset);
            const cardKey = [cardPayload.file || '', cardPayload.title || '', cardPayload.author || ''].join('||').toLowerCase();

            if (cardKey === payloadKey) {
                setCardProgressState(card, hasProgress);
            }
        });
    }

    async function refreshCardFavoriteState(card) {
        if (!card || !apiUrl) {
            return;
        }

        const payload = buildBookPayload(card.dataset);
        if (!payload.title && !payload.file) {
            return;
        }

        const cacheKey = [payload.file || '', payload.title || '', payload.author || ''].join('||').toLowerCase();
        if (favoriteStatusCache.has(cacheKey)) {
            setCardFavoriteState(card, favoriteStatusCache.get(cacheKey));
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
                return;
            }

            const isFavorite = Boolean(result.data.is_favorite);
            favoriteStatusCache.set(cacheKey, isFavorite);
            setCardFavoriteState(card, isFavorite);
        } catch (error) {
            // Do not block the UI if indicator status cannot be fetched.
        }
    }

    async function refreshCardProgressState(card) {
        if (!card || !apiUrl) {
            return;
        }

        const payload = buildBookPayload(card.dataset);
        if (!payload.title && !payload.file) {
            return;
        }

        const cacheKey = [payload.file || '', payload.title || '', payload.author || ''].join('||').toLowerCase();
        if (progressStatusCache.has(cacheKey)) {
            setCardProgressState(card, progressStatusCache.get(cacheKey));
            return;
        }

        try {
            const params = new URLSearchParams({
                action: 'progress_status',
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
                return;
            }

            const hasProgress = Boolean(result.data.has_progress);
            progressStatusCache.set(cacheKey, hasProgress);
            setCardProgressState(card, hasProgress);
        } catch (error) {
            // Keep cards usable even if status check fails.
        }
    }

    function buildBookPayload(data) {
        return {
            file: data.file || '',
            title: data.title || '',
            author: data.author || '',
            description: data.description || '',
            type: data.type || 'pdf'
        };
    }

    function setReadingListState(inReadingList) {
        readingListState = Boolean(inReadingList);

        if (!readingListBtn) {
            return;
        }

        if (readingListBtnText) {
            readingListBtnText.textContent = readingListState ? 'Quitar de lectura' : 'Agregar a lectura';
        }

        readingListBtn.classList.toggle('is-reading-list', readingListState);
        readingListBtn.setAttribute('aria-pressed', readingListState ? 'true' : 'false');

        if (readingListBtnIcon) {
            readingListBtnIcon.classList.remove('fas', 'far');
            readingListBtnIcon.classList.add(readingListState ? 'fas' : 'far', 'fa-bookmark');
        }
    }

    function setFavoriteState(isFavorite) {
        favoriteState = Boolean(isFavorite);

        if (!favoriteBtn) {
            return;
        }

        if (favoriteBtnText) {
            favoriteBtnText.textContent = 'Favorito';
        }

        favoriteBtn.classList.toggle('is-favorite', favoriteState);
        favoriteBtn.setAttribute('aria-pressed', favoriteState ? 'true' : 'false');
        favoriteBtn.setAttribute('aria-label', favoriteState ? 'Quitar favorito' : 'Marcar favorito');

        if (favoriteBtnIcon) {
            favoriteBtnIcon.classList.remove('fas', 'far');
            favoriteBtnIcon.classList.add(favoriteState ? 'fas' : 'far', 'fa-star');
        }
    }

    function notify(type, message, timeout) {
        if (window.AppNotify && typeof window.AppNotify[type] === 'function') {
            window.AppNotify[type](message, { timeout: timeout || 2200 });
            return;
        }

        console.warn(message);
    }

    async function refreshReadingListButton(payload) {
        if (!readingListBtn || !payload || !apiUrl) {
            return;
        }

        try {
            const params = new URLSearchParams({
                action: 'reading_list_status',
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
                setReadingListState(false);
                return;
            }

            setReadingListState(Boolean(result.data.in_reading_list));
        } catch (error) {
            setReadingListState(false);
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
        activeCard = card;
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

        setReadingListState(false);
        setFavoriteState(false);

        const pendingReadingList = refreshReadingListButton(activeBookPayload);
        const pendingFavorite = refreshFavoriteButton(activeBookPayload);
        Promise.allSettled([pendingReadingList, pendingFavorite]);

        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.classList.add('preview-open');
        closeBtn.focus();
    }

    function closePreview() {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('preview-open');
        activeCard = null;
    }

    document.querySelectorAll('.js-book-preview').forEach(function (card) {
        setCardProgressState(card, false);
        setCardFavoriteState(card, false);
        refreshCardProgressState(card);
        refreshCardFavoriteState(card);

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

    if (readingListBtn) {
        readingListBtn.addEventListener('click', async function () {
            if (!activeBookPayload || !csrfToken || !apiUrl) {
                return;
            }

            const previousState = readingListState;
            setReadingListState(!previousState);

            const payload = new URLSearchParams({
                action: 'toggle_reading_list',
                csrf_token: csrfToken,
                file: activeBookPayload.file,
                title: activeBookPayload.title,
                author: activeBookPayload.author,
                description: activeBookPayload.description,
                type: activeBookPayload.type
            });

            readingListBtn.disabled = true;

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
                    throw new Error('No se pudo actualizar la lista de lectura.');
                }

                const addedToReadingList = Boolean(result.data.in_reading_list);
                setReadingListState(addedToReadingList);
                notify('success', addedToReadingList ? 'Agregado a lista de lectura' : 'Quitado de lista de lectura', 1900);
            } catch (error) {
                setReadingListState(previousState);
                notify('error', 'No se pudo actualizar tu lista de lectura en este momento.', 2600);
            } finally {
                readingListBtn.disabled = false;
            }
        });
    }

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

                const updatedFavoriteState = Boolean(result.data.is_favorite);
                setFavoriteState(updatedFavoriteState);

                if (activeBookPayload) {
                    const cacheKey = [
                        activeBookPayload.file || '',
                        activeBookPayload.title || '',
                        activeBookPayload.author || ''
                    ].join('||').toLowerCase();
                    favoriteStatusCache.set(cacheKey, updatedFavoriteState);
                }

                if (activeCard) {
                    setCardFavoriteState(activeCard, updatedFavoriteState);
                }

                syncFavoriteStateAcrossCards(activeBookPayload, updatedFavoriteState);
                notify('success', updatedFavoriteState ? 'Libro agregado a favorito' : 'Libro quitado de favorito', 1900);
            } catch (error) {
                setFavoriteState(previousState);
                notify('error', 'No se pudo actualizar tus favoritos en este momento.', 2600);
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

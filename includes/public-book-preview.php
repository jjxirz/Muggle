<div class="book-preview-backdrop" id="bookPreviewBackdrop" aria-hidden="true" data-api-url="<?php echo htmlspecialchars(app_url('src/controllers/library_api.php'), ENT_QUOTES, 'UTF-8'); ?>" data-csrf-token="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="book-preview-modal" role="dialog" aria-modal="true" aria-labelledby="bookPreviewTitle">
        <button type="button" class="book-preview-close" id="bookPreviewClose" aria-label="Cerrar">×</button>

        <div class="book-preview-hero" id="bookPreviewHero">
            <div class="book-preview-gradient"></div>
            <div class="book-preview-hero-content">
                <span class="book-preview-pill" id="bookPreviewCategoryTop">Lectura digital</span>
                <h2 id="bookPreviewTitle">Título</h2>
                <p class="book-preview-author" id="bookPreviewAuthor">Autor</p>
                <div class="book-preview-actions">
                    <a href="#" class="book-preview-read-btn" id="bookPreviewReadBtn">Comenzar lectura</a>
                    <button type="button" class="book-preview-list-btn" id="bookPreviewFavoriteBtn" aria-pressed="false">
                        <i class="far fa-heart" aria-hidden="true"></i>
                        <span>Mi lista</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="book-preview-body">
            <div class="book-preview-cover-box" id="bookPreviewCoverBox">
                <img src="" alt="" class="book-preview-cover-img" id="bookPreviewCoverImg">
                <span id="bookPreviewInitial">L</span>
                <strong id="bookPreviewCoverTitle">Libro</strong>
            </div>

            <div>
                <div class="book-preview-meta">
                    <span id="bookPreviewYear">Disponible</span>
                    <span id="bookPreviewCategory">Lectura digital</span>
                    <span id="bookPreviewPages">Archivo disponible</span>
                </div>

                <p class="book-preview-description" id="bookPreviewDescription">
                    Obra disponible en el catálogo digital de la biblioteca.
                </p>

                <div class="book-preview-tags" id="bookPreviewTags"></div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/public-book-preview.js"></script>

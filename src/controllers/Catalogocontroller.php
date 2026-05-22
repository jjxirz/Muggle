<?php

require_once __DIR__ . '/../models/Catalogomodel.php';
require_once __DIR__ . '/../lib/Auth.php';

class CatalogoController
{
    private CatalogoModel $model;

    public function __construct()
    {
        $this->model = new CatalogoModel();
    }

    /**
     * Punto de entrada principal.
     * Procesa POST si hay uno, luego devuelve los datos para la vista.
     */
    public function handle(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        $tab    = $_GET['tab'] ?? 'listado';
        $bookId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        // Filtros del listado
        $search      = trim((string) ($_GET['q'] ?? ''));
        $categoriaId = (int) ($_GET['cat'] ?? 0);
        $tipo        = trim((string) ($_GET['tipo'] ?? ''));

        // Libro en edición (tab=form&id=X)
        $libroEditar = null;
        if ($tab === 'form' && $bookId > 0) {
            $libroEditar = $this->model->findBook($bookId);
        }

        // Categoría en edición (tab=categorias&edit_cat=X)
        $catEditar = null;
        $editCatId = (int) ($_GET['edit_cat'] ?? 0);
        if ($editCatId > 0) {
            $catEditar = $this->model->findCategory($editCatId);
        }

        return [
            'tab'             => $tab,
            'libros'          => $this->model->getAllBooks($search, $categoriaId, $tipo),
            'categorias'      => $this->model->getCategoryOptions(),   // para filtros/selects
            'catList'         => $this->model->getAllCategories(),      // para tabla de categorías
            'libroEditar'     => $libroEditar,
            'catEditar'       => $catEditar,
            'flash'           => $this->consumeFlash(),
            // Filtros activos (para que la vista los re-pinte)
            'filterSearch'    => $search,
            'filterCatId'     => $categoriaId,
            'filterTipo'      => $tipo,
        ];
    }

    // ─────────────────────────────────────────────
    //  POST
    // ─────────────────────────────────────────────

    private function handlePost(): void
    {
        require_valid_csrf();

        $action = trim((string) ($_POST['action'] ?? ''));

        try {
            switch ($action) {

                // ── Libros ──────────────────────────────
                case 'update_book':
                    $id = (int) ($_POST['id_libro'] ?? 0);
                    if ($id <= 0) {
                        throw new RuntimeException('ID de libro no válido.');
                    }
                    $this->model->updateBook($id, $_POST);
                    $this->setFlash('Libro actualizado correctamente.', 'success');
                    $this->redirect('?tab=listado');

                case 'delete_book':
                    $id = (int) ($_POST['id_libro'] ?? 0);
                    if ($id <= 0) {
                        throw new RuntimeException('ID de libro no válido.');
                    }
                    $this->model->deleteBook($id);
                    $this->setFlash('Libro eliminado correctamente.', 'success');
                    $this->redirect('?tab=listado');

                // ── Categorías ──────────────────────────
                case 'create_category':
                    $this->model->createCategory($_POST);
                    $this->setFlash('Categoría creada correctamente.', 'success');
                    $this->redirect('?tab=categorias');

                case 'update_category':
                    $id = (int) ($_POST['id_categoria'] ?? 0);
                    if ($id <= 0) {
                        throw new RuntimeException('ID de categoría no válido.');
                    }
                    $this->model->updateCategory($id, $_POST);
                    $this->setFlash('Categoría actualizada correctamente.', 'success');
                    $this->redirect('?tab=categorias');

                case 'delete_category':
                    $id = (int) ($_POST['id_categoria'] ?? 0);
                    if ($id <= 0) {
                        throw new RuntimeException('ID de categoría no válido.');
                    }
                    $this->model->deleteCategory($id);
                    $this->setFlash('Categoría eliminada correctamente.', 'success');
                    $this->redirect('?tab=categorias');

                default:
                    $this->setFlash('Acción no reconocida.', 'error');
                    $this->redirect('?tab=listado');
            }
        } catch (Throwable $e) {
            $this->setFlash('Error: ' . $e->getMessage(), 'error');
            // Redirigir al tab correcto según la acción
            $backTab = str_contains($action, 'category') ? 'categorias' : 'listado';
            $this->redirect('?tab=' . $backTab);
        }
    }

    // ─────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────

    private function setFlash(string $message, string $type): void
    {
        $_SESSION['catalogo_flash'] = ['message' => $message, 'type' => $type];
    }

    private function consumeFlash(): ?array
    {
        if (!isset($_SESSION['catalogo_flash'])) {
            return null;
        }
        $flash = $_SESSION['catalogo_flash'];
        unset($_SESSION['catalogo_flash']);
        return $flash;
    }

    private function redirect(string $query): void
    {
        header('Location: catalogo.php' . $query);
        exit();
    }
}
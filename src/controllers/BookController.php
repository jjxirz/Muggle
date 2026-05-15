<?php

require_once __DIR__ . '/../models/BookModel.php';
require_once __DIR__ . '/../models/OpenLibraryService.php';

class BookController
{
    private BookModel $bookModel;
    private OpenLibraryService $openLibraryService;

    public function __construct()
    {
        $this->bookModel = new BookModel();
        $this->openLibraryService = new OpenLibraryService();
    }

    public function handle(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $action = $_GET['action'] ?? '';
            if ($action === 'fetch_metadata') {
                $this->handleMetadataLookup();
            }
            if ($action === 'search_title') {
                $this->handleTitleSearch();
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostAction();
        }

        $editingBook = null;
        $action = $_GET['action'] ?? 'index';
        if ($action === 'edit' && isset($_GET['id'])) {
            $editingBook = $this->bookModel->find((int) $_GET['id']);
        }

        return [
            'books' => $this->bookModel->all(),
            'categories' => $this->bookModel->categories(),
            'editingBook' => $editingBook,
            'flash' => $this->consumeFlash(),
            'existingPdfFiles' => $this->listExistingPdfFiles(),
        ];
    }

    private function handleMetadataLookup(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $identifier = trim((string) ($_GET['identifier'] ?? ''));
        $mode = trim((string) ($_GET['mode'] ?? 'isbn'));

        if ($identifier === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Debes enviar ISBN o DOI.']);
            exit();
        }

        $data = $this->openLibraryService->getBookByIdentifier($identifier, $mode);
        if ($data === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'No se encontro metadata para ese identificador.']);
            exit();
        }

        echo json_encode(['ok' => true, 'data' => $data]);
        exit();
    }

    private function handleTitleSearch(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $query = trim((string) ($_GET['q'] ?? ''));
        if (strlen($query) < 3) {
            echo json_encode(['ok' => true, 'data' => []]);
            exit();
        }

        $data = $this->openLibraryService->searchTitles($query);
        echo json_encode(['ok' => true, 'data' => $data]);
        exit();
    }

    private function handlePostAction(): void
    {
        $action = $_POST['action'] ?? '';

        try {
            $payload = $_POST;
            $this->mergeArchivoSelection($payload);
            $this->attachUploadedPdf($payload);

            if ($action === 'create') {
                $this->bookModel->create($payload);
                $this->setFlash('Libro creado correctamente.', 'success');
                $this->redirectToAdmin();
            }

            if ($action === 'update' && isset($_POST['id_libro'])) {
                $this->bookModel->update((int) $_POST['id_libro'], $payload);
                $this->setFlash('Libro actualizado correctamente.', 'success');
                $this->redirectToAdmin();
            }

            if ($action === 'delete' && isset($_POST['id_libro'])) {
                $this->bookModel->delete((int) $_POST['id_libro']);
                $this->setFlash('Libro eliminado correctamente.', 'success');
                $this->redirectToAdmin();
            }

            $this->setFlash('Accion no valida.', 'error');
            $this->redirectToAdmin();
        } catch (Throwable $exception) {
            $this->setFlash('Error al guardar el libro: ' . $exception->getMessage(), 'error');
            $this->redirectToAdmin();
        }
    }

    private function mergeArchivoSelection(array &$payload): void
    {
        $selectedExisting = trim((string) ($payload['archivo_existente'] ?? ''));
        if ($selectedExisting !== '') {
            $payload['archivo'] = $selectedExisting;
        }
    }

    private function attachUploadedPdf(array &$payload): void
    {
        if (!isset($_FILES['archivo_pdf']) || !is_array($_FILES['archivo_pdf'])) {
            return;
        }

        $file = $_FILES['archivo_pdf'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $uploadError = $file['error'] ?? UPLOAD_ERR_OK;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $uploadMessages = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño máximo indicado en el formulario.',
                UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma parcial.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
                UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida.',
            ];
            $msg = $uploadMessages[$uploadError] ?? "Error de subida desconocido (código $uploadError).";
            throw new RuntimeException($msg);
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? 'documento.pdf');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Archivo temporal invalido.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 50 * 1024 * 1024) {
            throw new RuntimeException('El PDF debe ser mayor a 0 bytes y menor a 50MB.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            throw new RuntimeException('Solo se permiten archivos PDF.');
        }

        $destinationDir = __DIR__ . '/../../assets/books';
        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) {
            throw new RuntimeException('No fue posible crear la carpeta de libros.');
        }

        $safeBase = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $safeBase = trim((string) $safeBase, '_');
        if ($safeBase === '') {
            $safeBase = 'libro';
        }

        $fileName = $safeBase . '_' . date('Ymd_His') . '.pdf';
        $destinationPath = $destinationDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $destinationPath)) {
            throw new RuntimeException('No se pudo mover el PDF al proyecto.');
        }

        $payload['archivo'] = 'assets/books/' . $fileName;
    }

    private function listExistingPdfFiles(): array
    {
        $directory = __DIR__ . '/../../assets/books';
        if (!is_dir($directory)) {
            return [];
        }

        $paths = glob($directory . '/*.pdf');
        if ($paths === false) {
            return [];
        }

        $files = [];
        foreach ($paths as $path) {
            $files[] = 'assets/books/' . basename($path);
        }

        sort($files);
        return $files;
    }

    private function setFlash(string $message, string $type): void
    {
        $_SESSION['admin_books_flash'] = [
            'message' => $message,
            'type' => $type,
        ];
    }

    private function consumeFlash(): ?array
    {
        if (!isset($_SESSION['admin_books_flash'])) {
            return null;
        }

        $flash = $_SESSION['admin_books_flash'];
        unset($_SESSION['admin_books_flash']);
        return $flash;
    }

    private function redirectToAdmin(): void
    {
        header('Location: admin_books.php');
        exit();
    }
}

<?php

require_once __DIR__ . '/../models/BookModel.php';
require_once __DIR__ . '/../models/OpenLibraryService.php';
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/App.php';

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
            'banners' => [],
            'editingBanner' => null,
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
        require_valid_csrf();

        $action = $_POST['action'] ?? '';

        try {
            $payload = $_POST;
            $this->mergeArchivoSelection($payload);
            $this->attachUploadedPdf($payload);
            $this->attachUploadedBannerImage($payload);

            if ($action === 'create') {
                $bookId = $this->bookModel->create($payload);

                if (!empty($payload['imagen'])) {
                    $this->bookModel->saveBannerForBook($bookId, (string) $payload['imagen']);
                }

                $this->setFlash('Libro creado correctamente.', 'success');
                $this->redirectToAdmin();
            }

            if ($action === 'update' && isset($_POST['id_libro'])) {
                $bookId = (int) $_POST['id_libro'];
                $this->bookModel->update($bookId, $payload);

                if (!empty($payload['imagen'])) {
                    $this->bookModel->saveBannerForBook($bookId, (string) $payload['imagen']);
                }

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
            $this->setFlash('Error al procesar la solicitud: ' . $exception->getMessage(), 'error');
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
        $this->ensureWritableDirectory($destinationDir, 'libros');

        $safeBase = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $safeBase = trim((string) $safeBase, '_');
        if ($safeBase === '') {
            $safeBase = 'libro';
        }

        $fileName = $safeBase . '_' . date('Ymd_His') . '.pdf';
        $destinationPath = $destinationDir . '/' . $fileName;

        $this->storeUploadedFile($tmpName, $destinationPath, 'el PDF');

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

    private function attachUploadedBannerImage(array &$payload): void
    {
        if (!isset($_FILES['banner_image']) || !is_array($_FILES['banner_image'])) {
            return;
        }

        $file = $_FILES['banner_image'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $uploadError = $file['error'] ?? UPLOAD_ERR_OK;
        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error al subir la imagen del banner.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? 'banner.jpg');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Archivo temporal de banner invalido.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 20 * 1024 * 1024) {
            throw new RuntimeException('La imagen del banner debe ser mayor a 0 bytes y menor a 20MB.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Formato de imagen no permitido. Usa JPG, PNG, WEBP o GIF.');
        }

        $destinationDir = __DIR__ . '/../../assets/banners';
        $this->ensureWritableDirectory($destinationDir, 'banners');

        $safeBase = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $safeBase = trim((string) $safeBase, '_');
        if ($safeBase === '') {
            $safeBase = 'banner';
        }

        $fileName = $safeBase . '_' . date('Ymd_His') . '.' . $extension;
        $destinationPath = $destinationDir . '/' . $fileName;

        $this->storeUploadedFile($tmpName, $destinationPath, 'la imagen del banner');

        $payload['imagen'] = app_url('assets/banners/' . $fileName);
    }

    private function setFlash(string $message, string $type): void
    {
        $_SESSION['admin_books_flash'] = [
            'message' => $message,
            'type' => $type,
        ];
    }

    private function ensureWritableDirectory(string $destinationDir, string $label): void
    {
        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) {
            throw new RuntimeException("No fue posible crear la carpeta de {$label}: {$destinationDir}");
        }

        if (!is_writable($destinationDir)) {
            @chmod($destinationDir, 0775);
            clearstatcache(true, $destinationDir);
        }

        if (!is_writable($destinationDir)) {
            throw new RuntimeException("La carpeta de {$label} no tiene permisos de escritura: {$destinationDir}");
        }
    }

    private function storeUploadedFile(string $tmpName, string $destinationPath, string $label): void
    {
        if (move_uploaded_file($tmpName, $destinationPath)) {
            return;
        }

        if (is_readable($tmpName) && @copy($tmpName, $destinationPath)) {
            @unlink($tmpName);
            return;
        }

        $lastError = error_get_last();
        $errorDetail = '';
        if (is_array($lastError) && isset($lastError['message'])) {
            $errorDetail = ' Detalle del sistema: ' . $lastError['message'];
        }

        throw new RuntimeException("No se pudo guardar {$label} en {$destinationPath}. Revisa permisos de carpeta, espacio en disco y restriccion open_basedir." . $errorDetail);
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

    private function redirectToAdmin(string $suffix = ''): void
    {
        header('Location: admin_books.php' . $suffix);
        exit();
    }
}

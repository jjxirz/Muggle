<?php

require_once __DIR__ . '/../models/UserModel.php';

class UserController
{
    private UserModel $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    public function handle(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        return [
            'usuarios' => $this->model->allUsuarios(),
            'planes'   => $this->model->allPlanes(),
            'flash'    => $this->consumeFlash(),
        ];
    }

    private function handlePost(): void
    {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create_usuario') {
                $this->validateCreate($_POST);
                $this->model->createUsuario($_POST);
                $this->setFlash('Usuario creado correctamente.', 'success');
            }

            if ($action === 'toggle_estado' && isset($_POST['id_usuario'])) {
                $nuevoEstado = ($_POST['estado_actual'] === 'activo') ? 'inactivo' : 'activo';
                $this->model->updateEstado((int) $_POST['id_usuario'], $nuevoEstado);
                $this->setFlash('Estado del usuario actualizado.', 'success');
            }

            if ($action === 'delete_usuario' && isset($_POST['id_usuario'])) {
                $this->model->deleteUsuario((int) $_POST['id_usuario']);
                $this->setFlash('Usuario eliminado.', 'success');
            }
        } catch (Throwable $e) {
            $this->setFlash('Error: ' . $e->getMessage(), 'error');
        }

        header('Location: usuarios.php?tab=' . ($_POST['tab'] ?? 'usuarios'));
        exit();
    }

    private function validateCreate(array $data): void
    {
        if (empty(trim($data['nombre'] ?? ''))) {
            throw new RuntimeException('El nombre es obligatorio.');
        }
        if (empty(trim($data['email'] ?? '')) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email inválido.');
        }
        if (empty(trim($data['password'] ?? ''))) {
            throw new RuntimeException('La contraseña es obligatoria.');
        }
        if (strlen($data['password']) < 6) {
            throw new RuntimeException('La contraseña debe tener al menos 6 caracteres.');
        }
    }

    private function setFlash(string $msg, string $type): void
    {
        $_SESSION['user_flash'] = ['message' => $msg, 'type' => $type];
    }

    private function consumeFlash(): ?array
    {
        if (!isset($_SESSION['user_flash'])) return null;
        $f = $_SESSION['user_flash'];
        unset($_SESSION['user_flash']);
        return $f;
    }
}

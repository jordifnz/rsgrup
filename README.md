# RSGrup — Plataforma de Gestión de Cursos

Aplicación PHP 8.3 para gestión de cursos con entregas descargables, exámenes, pagos PayPal y panel de administración.

## Requisitos
- PHP 8.3+
- MySQL 5.7+
- Servidor web Apache con mod_rewrite

## Instalación
1. Clonar el repositorio
2. Importar `sql/schema.sql` en la base de datos
3. Configurar `config/config.php`
4. Apuntar el DocumentRoot del servidor a `/public`
5. Configurar Settings desde el panel admin

## Estructura
```
config/       Configuración DB, sesión
src/          Controladores, Modelos, Servicios, Helpers
templates/    Vistas PHP (layouts, admin, alumno, auth)
api/v1/       API REST con autenticación por token
public/       Front controller, assets, uploads
sql/          Schema y migraciones
```

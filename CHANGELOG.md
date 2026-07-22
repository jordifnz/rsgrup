# Changelog

## v1.0.0-rc1 — 2026-07-22

Primera Release Candidate completamente funcional.

### Funcionalidades incluidas

- Gestión de cursos y entregas (matrícula, entregas, práctica)
- Sistema de inscripciones con pago via PayPal (sandbox / live)
- Notificaciones al alumno por **email** (SMTP + plantilla HTML configurable)
- Notificaciones al alumno por **WhatsApp** via Evolution API (plantilla configurable)
- Variables de plantilla: `{{nombre}}` `{{apellidos}}` `{{email}}` `{{entrega}}` `{{curso_titulo}}` `{{fecha}}` `{{precio}}` `{{sitio}}`
- Generación de títulos/certificados en imagen (GD + fuente TTF con fallback automático)
- Panel de administración completo (cursos, entregas, alumnos, inscripciones, exámenes, ajustes)
- Ajustes globales con campos de contraseña con toggle "ojo"
- Tokens API con CSRF
- Log de actividad
- Soporte WYSIWYG en plantillas de email

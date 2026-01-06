## 🚀 Requisitos

- Docker y Docker Compose instalados
- Proyecto configurado para **Laravel 12**

---

## 🐳 Configuración con Docker

El entorno está definido en `docker-compose.yml` con los siguientes servicios:

- **`laravel-app`**: Contenedor de la aplicación PHP (Laravel 12) — expuesto en el puerto **8001**
- **`laravel-nginx`**: Servidor web — expuesto en el puerto **8080**
- **`laravel-postgres`**: Base de datos PostgreSQL 15 — expuesto en el puerto **5432**
- **`laravel-phppgadmin`**: Herramienta de administración de PostgreSQL — accesible en http://localhost:5050

### 📦 Levantar el entorno

```bash
docker compose up --build -d
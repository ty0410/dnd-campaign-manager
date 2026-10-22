# Gestor de Tasha para todo

Aplicación web para gestionar campañas de **Dungeons & Dragons 5.ª edición**, con herramientas para organizar personajes, consultar información del juego y administrar los elementos de una campaña.

El proyecto se desarrolla como trabajo final del ciclo de Desarrollo de Aplicaciones Web (DAW).

## Tecnologías utilizadas

### Backend
- **PHP y Laravel 13** para la lógica de servidor.
- **API REST** para la comunicación entre el cliente y el servidor.
- **Laravel Sanctum** para la autenticación mediante tokens.
- **MySQL/MariaDB** como sistema de gestión de bases de datos.

### Frontend
- **Vue.js** para construir la interfaz de usuario.
- **JavaScript** para la lógica del cliente.
- **CSS** para el diseño y la adaptación visual de la aplicación.
- **Vite** para la compilación de los recursos del frontend.

### Integración externa
- **D&D 5e API:** https://www.dnd5eapi.co/

## Funcionalidades implementadas

### Autenticación y usuarios
- Controladores de registro e inicio de sesión.
- Autenticación mediante Laravel Sanctum.
- Comunicación entre el frontend y la API del servidor.

### Gestión de campañas
- Creación y administración de campañas.
- Operaciones CRUD para gestionar campañas.
- Gestión de la participación de usuarios en las campañas.

### Gestión de personajes
- Creación y consulta de personajes.
- Fichas de personaje con características y atributos.
- Gestión de clases y personajes multiclase.
- Cálculo y validación de características del personaje.
- Gestión de niveles, competencias, conjuros, espacios de conjuro, inventario y monedas.
- Posibilidad de asociar personajes a campañas.

### Recursos de las campañas
La API incluye controladores y modelos para administrar diferentes elementos del juego:

- Personajes no jugadores (NPC).
- Localizaciones.
- Sesiones de juego.
- Encuentros y combatientes.
- Notas de campaña.

### Integración con D&D 5e API
- Servicio para consultar información de la API externa.
- Controladores para acceder a catálogos de razas, clases, conjuros, equipo y monstruos.
- Integración de estos recursos con la lógica del servidor.

### Interfaz web
La aplicación cuenta con vistas de Vue para la navegación principal, campañas, detalle de campaña, personajes, creación de personajes, autenticación y consulta de recursos de D&D.

## Estructura del proyecto

```text
dnd-campaign-manager/
├── app/
│   ├── Http/Controllers/Api/
│   ├── Models/
│   └── Services/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   │   ├── router/
│   │   ├── services/
│   │   └── views/
│   └── views/
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
├── tests/
├── composer.json
├── package.json
└── vite.config.js
```

## Requisitos previos

Para ejecutar el proyecto en un entorno de desarrollo se necesitan:

- PHP compatible con Laravel 13.
- Composer.
- Node.js y npm.
- MySQL o MariaDB.
- Un servidor web o el servidor de desarrollo de Laravel.

## Instalación y ejecución

1. Clonar el repositorio y entrar en el directorio del proyecto.

   ```bash
   git clone https://github.com/ty0410/dnd-campaign-manager.git
   cd dnd-campaign-manager
   ```

2. Instalar las dependencias de PHP.

   ```bash
   composer install
   ```

3. Crear el archivo de configuración local.

   ```bash
   cp .env.example .env
   ```

   En Windows también se puede copiar `.env.example` manualmente y nombrar la copia `.env`.

4. Generar la clave de la aplicación.

   ```bash
   php artisan key:generate
   ```

5. Configurar en `.env` la conexión con la base de datos MySQL/MariaDB y crear la base de datos correspondiente.

6. Ejecutar las migraciones.

   ```bash
   php artisan migrate
   ```

7. Instalar las dependencias del frontend y compilar los recursos.

   ```bash
   npm install
   npm run build
   ```

8. Iniciar el servidor de desarrollo de Laravel.

   ```bash
   php artisan serve
   ```

La aplicación estará disponible normalmente en `http://127.0.0.1:8000`.

> La configuración de la base de datos y del entorno debe adaptarse al equipo donde se ejecute el proyecto. La instalación y el funcionamiento completo deben verificarse en cada entorno.

## Estado del proyecto

El proyecto se encuentra en desarrollo. La estructura de Laravel, la API, los modelos, las migraciones y las vistas principales de Vue están desarrollados.

Queda pendiente verificar y consolidar el funcionamiento integral de las funcionalidades, ampliar las pruebas y documentar los requisitos y casos de uso definitivos.

## Autor

Proyecto desarrollado como trabajo final del ciclo de Desarrollo de Aplicaciones Web (DAW).

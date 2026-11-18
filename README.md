# Gestor de Tasha para todo

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)

Aplicación web para organizar partidas de **Dungeons & Dragons 5.ª
edición**. Reúne en un mismo espacio la gestión de campañas, personajes,
sesiones, encuentros y recursos de juego, con herramientas para
jugadores y directores de juego.

**Nombre público:** Gestor de Tasha para todo\
**Nombre técnico del repositorio:** `dnd-campaign-manager`

Repositorio: <https://github.com/ty0410/dnd-campaign-manager>

> Proyecto académico de Desarrollo de Aplicaciones Web (DAW). La
> funcionalidad descrita corresponde al estado de desarrollo
> documentado; algunas áreas pueden seguir evolucionando.

## Índice

-   [Objetivos](#objetivos)
-   [Funcionalidades](#funcionalidades)
-   [Tecnologías](#tecnologías)
-   [Arquitectura](#arquitectura)
-   [Modelo de datos](#modelo-de-datos)
-   [Requisitos](#requisitos)
-   [Instalación local](#instalación-local)
-   [Ejecución con Docker](#ejecución-con-docker)
-   [Configuración](#configuración)
-   [Pruebas](#pruebas)
-   [Seguridad y buenas prácticas](#seguridad-y-buenas-prácticas)
-   [Estructura del repositorio](#estructura-del-repositorio)
-   [Estado y trabajo futuro](#estado-y-trabajo-futuro)
-   [Créditos](#créditos)

## Objetivos

-   Facilitar la preparación y administración de campañas de D&D 5e.
-   Centralizar fichas de personajes, PNJ, sesiones y encuentros.
-   Ofrecer una interfaz web accesible desde navegador.
-   Separar la interfaz cliente de una API REST desarrollada en Laravel.
-   Facilitar la puesta en marcha reproducible mediante Docker Compose.

## Funcionalidades

Las funcionalidades identificadas en el código y en el desarrollo del
proyecto incluyen:

### Campañas y comunidad

-   Crear y consultar campañas.
-   Gestionar la participación de usuarios en campañas.
-   Incorporar personajes a campañas y tramitar solicitudes para unirse.
-   Gestionar amistades y perfiles de usuario.
-   Consultar campañas propias y campañas a las que se ha unido el
    usuario.

### Personajes

-   Crear, consultar y editar personajes.
-   Mantener características, puntuaciones de característica y datos de
    ficha.
-   Soportar personajes multiclase mediante niveles asociados a cada
    clase.
-   Gestionar competencias, ataques, inventario, monedas y conjuros.
-   Calcular valores derivados de la ficha y datos de lanzamiento de
    conjuros.
-   Registrar decisiones y progresión de nivel.
-   Asociar imágenes a personajes.
-   Preparar encuentros usando personajes disponibles en una campaña.

### Herramientas de dirección de juego

-   Gestionar PNJ y sus detalles.
-   Crear sesiones de juego, encuentros y combatientes.
-   Consultar y utilizar información de monstruos, clases, equipo y
    conjuros.
-   Disponer de herramientas de tiradas de dados.

### Administración

-   Panel administrativo con gestión de usuarios, campañas, personajes y
    administradores.
-   Operaciones de moderación, como desactivar o reactivar usuarios.

### Integración con D&D 5e

El proyecto incorpora un servicio para consultar el catálogo externo de
D&D 5e API. La disponibilidad de datos depende del servicio externo y de
sus respuestas.

## Tecnologías

  -----------------------------------------------------------------------
  Tecnología                          Uso
  ----------------------------------- -----------------------------------
  PHP 8.5                             Lenguaje del servidor (entorno de
                                      desarrollo documentado)

  Laravel 13                          Backend, lógica de negocio y API
                                      REST

  Vue.js 3                            Interfaz de usuario

  Vite                                Compilación y servidor de
                                      desarrollo del frontend

  JavaScript, HTML y CSS              Interfaz y comportamiento cliente

  MySQL / MariaDB                     Base de datos relacional

  Laravel Sanctum                     Autenticación basada en
                                      tokens/sesiones para la API

  D&D 5e API                          Catálogo externo de referencia del
                                      juego

  Docker                              Contenedorización

  Docker Compose                      Orquestación de aplicación y base
                                      de datos

  Git y GitHub                        Control de versiones y alojamiento
                                      del código
  -----------------------------------------------------------------------

Las versiones concretas de dependencias se encuentran en `composer.lock`
y `package-lock.json`.

## Arquitectura

La aplicación utiliza una arquitectura cliente-servidor:

1.  **Frontend:** aplicación Vue que presenta las vistas, formularios y
    componentes interactivos.
2.  **Backend:** Laravel expone endpoints REST bajo el prefijo `/api`,
    valida las peticiones y aplica las reglas de negocio.
3.  **Persistencia:** MySQL/MariaDB almacena usuarios, campañas,
    personajes y demás entidades.
4.  **Servicios externos:** `DndApiService` encapsula consultas al
    catálogo D&D 5e.
5.  **Despliegue:** Docker Compose permite ejecutar el servidor web PHP
    y la base de datos en contenedores separados.

La autenticación y autorización se aplican en el servidor. La interfaz
cliente no debe considerarse una barrera de seguridad.

## Modelo de datos

El esquema evoluciona mediante migraciones de Laravel. Entre las
entidades principales identificadas se encuentran:

-   `users`: cuentas, perfiles y roles.
-   `campaigns`: campañas y su director de juego.
-   `campaign_user`: relación de participación entre usuarios y
    campañas.
-   `characters`: datos generales y características de los personajes.
-   `character_classes`: clases, subclases y niveles por clase; permite
    representar multiclase.
-   `npcs` y tablas de detalle: personajes no jugadores.
-   `locations`: localizaciones de campaña.
-   `game_sessions`: sesiones de juego.
-   `encounters` y `combatants`: encuentros y participantes del combate.
-   `notes`: notas.
-   Tablas auxiliares para ataques, inventario, monedas, competencias,
    conjuros, espacios de conjuro, progresión y solicitudes para unirse
    a campañas.

Para conocer los campos, índices y restricciones exactos debe
consultarse el conjunto de migraciones de `database/migrations`.

## Requisitos

### Desarrollo local

-   PHP compatible con las dependencias de `composer.json` (el entorno
    usado durante el desarrollo dispone de PHP 8.5).
-   Composer 2.
-   Node.js y npm.
-   MySQL o MariaDB.
-   Git.

### Docker

-   Docker Engine o Docker Desktop.
-   Docker Compose v2.

## Instalación local

1.  Clona el repositorio:

    ``` bash
    git clone https://github.com/ty0410/dnd-campaign-manager.git
    cd dnd-campaign-manager
    ```

2.  Instala dependencias PHP y JavaScript:

    ``` bash
    composer install
    npm install
    ```

3.  Crea el archivo de entorno:

    ``` bash
    cp .env.example .env
    ```

    En Windows PowerShell:

    ``` powershell
    Copy-Item .env.example .env
    ```

4.  Configura en `.env` la conexión a una base de datos MySQL/MariaDB
    existente. Crea previamente una base de datos vacía.

5.  Genera la clave de Laravel:

    ``` bash
    php artisan key:generate
    ```

6.  Ejecuta las migraciones:

    ``` bash
    php artisan migrate
    ```

7.  En una terminal, inicia el frontend:

    ``` bash
    npm run dev
    ```

8.  En otra terminal, inicia Laravel:

    ``` bash
    php artisan serve
    ```

Abre la dirección que indique Laravel (habitualmente
`http://127.0.0.1:8000`). Si el proyecto utiliza una configuración de
proxy o URL distinta, ajusta las variables correspondientes en `.env`.

Para compilar los recursos del frontend:

``` bash
npm run build
```

## Ejecución con Docker

El repositorio incluye configuración de Docker mediante `Dockerfile`,
`compose.yaml` y archivos auxiliares bajo `docker/`.

1.  Crea un archivo `.env.docker` a partir de la plantilla de entorno
    Docker que mantengas en tu copia local. **No subas secretos ni
    contraseñas reales al repositorio.**

2.  Revisa los valores de aplicación y base de datos, especialmente las
    credenciales y la clave de Laravel.

3.  Construye e inicia los servicios:

    ``` bash
    docker compose --env-file .env.docker up -d --build
    ```

4.  Comprueba el estado:

    ``` bash
    docker compose --env-file .env.docker ps
    ```

5.  Ejecuta las migraciones dentro del contenedor de aplicación:

    ``` bash
    docker compose --env-file .env.docker exec app php artisan migrate --force
    ```

6.  Consulta los registros si aparece algún problema:

    ``` bash
    docker compose --env-file .env.docker logs -f
    ```

En el entorno local documentado, el servicio web se publicó en el puerto
`8080`; por tanto, la dirección de acceso fue `http://localhost:8080`.
Comprueba `compose.yaml` para confirmar el mapeo de puertos de tu
versión.

Para detener los contenedores:

``` bash
docker compose --env-file .env.docker down
```

Para detenerlos y eliminar también los volúmenes (esto elimina los datos
persistidos de la base de datos):

``` bash
docker compose --env-file .env.docker down -v
```

**Aviso:** no ejecutes `down -v` si quieres conservar los datos.

## Configuración

Las opciones principales se establecen en `.env` o en el archivo de
entorno indicado por Docker:

-   `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`.
-   `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`,
    `DB_PASSWORD`.
-   Variables de correo si se habilita el envío de emails.
-   Variables específicas de servicios externos, si la configuración de
    la versión las requiere.

En producción, establece `APP_ENV=production`, `APP_DEBUG=false`, una
`APP_KEY` segura, contraseñas robustas y HTTPS. No reutilices las
credenciales de desarrollo.

## Pruebas

El proyecto utiliza PHPUnit a través de Laravel. Para ejecutar la suite:

``` bash
php artisan test
```

O directamente:

``` bash
vendor/bin/phpunit
```

La batería de pruebas observada incluye pruebas de ejemplo y una prueba
unitaria del cálculo de lanzamiento de conjuros. Conviene ampliar la
cobertura con pruebas de autenticación, permisos, campañas, personajes,
progresión y endpoints críticos.

## Seguridad y buenas prácticas

-   No subir `.env`, `.env.docker`, contraseñas, tokens, claves privadas
    ni volcados de bases de datos.
-   Mantener `APP_DEBUG=false` en entornos públicos.
-   Utilizar HTTPS y credenciales independientes para cada entorno.
-   Validar y autorizar todas las operaciones en Laravel.
-   Hacer copias de seguridad periódicas de la base de datos y los
    archivos subidos.
-   Revisar `git status` antes de cada commit para evitar incluir
    archivos locales, temporales o secretos.
-   No publicar `Dockerfile.txt` como sustituto del `Dockerfile`: Docker
    espera el nombre `Dockerfile` por defecto.

## Estructura del repositorio

``` text
app/
  Console/          Comandos Artisan
  Http/Controllers/ Controladores web y de API
  Models/           Modelos Eloquent
  Services/         Servicios y cálculos de dominio
  Providers/        Proveedores de Laravel
bootstrap/          Arranque de Laravel
config/             Configuración
database/
  migrations/       Evolución del esquema de base de datos
  seeders/          Datos iniciales
docker/             Configuración auxiliar de contenedores
public/             Punto de entrada y archivos públicos
resources/
  css/              Estilos
  js/
    components/     Componentes Vue reutilizables
    services/       Comunicación con API y lógica cliente
    views/          Vistas de la aplicación
routes/
  api.php           Endpoints REST
  web.php           Rutas web
tests/              Pruebas automatizadas
```

## Estado y trabajo futuro

El proyecto está en desarrollo activo. Entre las líneas de mejora
recomendables se encuentran:

-   Completar pruebas automatizadas de los flujos principales.
-   Mantener documentación de endpoints y ejemplos de respuesta de la
    API.
-   Revisar validaciones, permisos y manejo de errores.
-   Documentar copias de seguridad y restauración.
-   Automatizar integración continua y comprobaciones de calidad.
-   Revisar periódicamente la compatibilidad con la API externa de D&D
    5e.

## Créditos

Proyecto académico personal desarrollado como trabajo de Desarrollo de
Aplicaciones Web (DAW).

La información del juego obtenida mediante D&D 5e API depende de ese
servicio externo. Dungeons & Dragons y sus marcas pertenecen a sus
respectivos titulares. Este proyecto es una herramienta de gestión
creada por fans y no está afiliada ni respaldada por Wizards of the
Coast.

------------------------------------------------------------------------

**Gestor de Tasha para todo** · `dnd-campaign-manager`

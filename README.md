# Gestor de Tasha para todo

Aplicación web para gestionar campañas y personajes de **Dungeons &
Dragons 5.ª edición**.

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Vue](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)

**Nombre público:** Gestor de Tasha para todo\
**Nombre técnico:** `dnd-campaign-manager`\
**Repositorio:** <https://github.com/ty0410/dnd-campaign-manager>

Proyecto final del ciclo de Desarrollo de Aplicaciones Web (DAW).

------------------------------------------------------------------------

## Índice

-   [Introducción](#introducción)
-   [Objetivos](#objetivos)
-   [Tecnologías](#tecnologías)
-   [Arquitectura](#arquitectura)
-   [Funcionalidades](#funcionalidades)
-   [Interfaz y rutas](#interfaz-y-rutas)
-   [API REST](#api-rest)
-   [Modelo de datos](#modelo-de-datos)
-   [Seguridad y permisos](#seguridad-y-permisos)
-   [Instalación en local](#instalación-en-local)
-   [Ejecución con Docker](#ejecución-con-docker)
-   [Publicación temporal con Cloudflare
    Tunnel](#publicación-temporal-con-cloudflare-tunnel)
-   [Pruebas](#pruebas)
-   [Estructura del proyecto](#estructura-del-proyecto)
-   [Estado del desarrollo](#estado-del-desarrollo)
-   [Aviso legal](#aviso-legal)

## Introducción

Gestor de Tasha para todo centraliza la información habitual de una
partida de D&D: campañas, fichas de personajes, personajes no jugadores,
localizaciones, sesiones y encuentros. La aplicación consulta además
**D&D 5e API** (`dnd5eapi.co`) para ofrecer datos del juego ---como
razas, subrazas, trasfondos, clases, conjuros, equipo y monstruos--- sin
tener que introducirlos manualmente.

La aplicación se desarrolla como una SPA (Single Page Application): Vue
gestiona la interfaz y Laravel proporciona una API REST, la
autenticación, las reglas de negocio y el acceso a la base de datos.

## Objetivos

-   Reunir en una aplicación la gestión de campañas y personajes.
-   Facilitar la creación de fichas y el cálculo de sus valores
    derivados.
-   Dar soporte a personajes multiclase.
-   Proporcionar herramientas para la preparación de sesiones y
    encuentros.
-   Separar la interfaz cliente y la lógica del servidor mediante una
    API REST.
-   Facilitar un entorno reproducible de ejecución mediante Docker
    Compose.

## Tecnologías

  -----------------------------------------------------------------------
  Tecnología                          Uso
  ----------------------------------- -----------------------------------
  PHP 8.5                             Lenguaje del servidor en el entorno
                                      de desarrollo

  Laravel 13                          Backend, API REST, validación y
                                      lógica de negocio

  Vue.js 3                            Interfaz de usuario SPA

  JavaScript                          Lógica del cliente

  Vite y npm                          Dependencias y compilación del
                                      frontend

  Axios                               Comunicación HTTP con la API

  Vue Router                          Navegación y protección de rutas
                                      cliente

  Laravel Sanctum                     Autenticación mediante tokens

  MySQL / MariaDB                     Base de datos relacional

  D&D 5e API                          Catálogo externo del juego

  Docker y Docker Compose             Contenedorización y ejecución de
                                      servicios

  Cloudflare Tunnel                   Acceso público temporal sin abrir
                                      puertos del router

  Git y GitHub                        Control de versiones
  -----------------------------------------------------------------------

Las versiones exactas de las dependencias se encuentran en
`composer.lock` y `package-lock.json`.

## Arquitectura

1.  **Frontend Vue:** muestra pantallas, formularios y fichas. Vue
    Router decide qué vista cargar.
2.  **Cliente HTTP:** Axios centraliza las peticiones y adjunta el token
    Bearer cuando la ruta requiere autenticación.
3.  **Backend Laravel:** recibe las peticiones `/api/...`, valida los
    datos, comprueba permisos y responde en JSON.
4.  **Persistencia:** Eloquent gestiona las entidades y relaciones
    almacenadas en MySQL/MariaDB.
5.  **Servicios de dominio:** `CharacterSheetCalculator` calcula valores
    de ficha; `AbilityScoreValidator` valida los métodos de puntuación;
    `DndApiService` consulta el catálogo externo.
6.  **Despliegue:** Docker Compose coordina la aplicación y los
    servicios necesarios, incluida la base de datos según la
    configuración del entorno.

## Funcionalidades

### Cuentas y sesiones

-   Registro con nombre, correo único y contraseña.
-   Inicio y cierre de sesión.
-   Consulta del usuario autenticado.
-   Gestión de sesión mediante tokens de Laravel Sanctum.

### Campañas

-   Crear, listar, consultar, editar y eliminar campañas.
-   El creador queda registrado como Dungeon Master (DM) y miembro.
-   Solo el DM puede modificar o eliminar su campaña.
-   Los recursos de una campaña están protegidos por pertenencia.

### Personajes y fichas

-   Listar los personajes propios.
-   Crear personajes independientes o desde el contexto de una campaña.
-   Formulario de creación en `/characters/create`.
-   Datos básicos: nombre, raza, subraza, trasfondo, nivel, descripción
    y retrato.
-   Rasgos de personalidad, ideal, vínculo, defecto y notas.
-   Seis características: Fuerza, Destreza, Constitución, Inteligencia,
    Sabiduría y Carisma.
-   Bonificadores personalizados para las características.
-   Tres métodos de generación: manual, array estándar y compra de
    puntos.
-   Validación en el cliente y en el servidor.
-   Cálculo automático de modificadores y bonificador de competencia.
-   Soporte de multiclase con nivel individual por clase y cálculo del
    nivel total.
-   Generación de una ficha PDF descargable desde la acción de
    generación de ficha, cuando está disponible en la interfaz.

**Métodos de características** - Manual: puntuaciones dentro del rango
admitido por el formulario y el servidor. - Array estándar: valores
iniciales 15, 14, 13, 12, 10 y 8. - Compra de puntos: presupuesto de 27
puntos, con costes progresivos según las reglas implementadas.

### Contenido de campaña

La API contempla la gestión de: - NPCs: nombre, raza, papel, descripción
y ubicación. - Localizaciones: nombre, tipo, descripción y posible
localización padre. - Sesiones: título, fecha, resumen y notas. -
Encuentros: nombre, descripción, tipo, estado y sesión asociada. -
Combatientes: iniciativa, puntos de golpe y referencia a personaje o
monstruo.

La disponibilidad de operaciones desde la interfaz puede ser distinta de
la disponibilidad en la API; consulta el estado del desarrollo más
abajo.

### Catálogo D&D

El backend ofrece consultas de listado y detalle para: - Monstruos. -
Conjuros. - Clases. - Equipo. - Razas. - Subrazas. - Trasfondos.

El servicio externo puede no estar disponible temporalmente. La
aplicación debe gestionar errores y elementos inexistentes sin asumir
que el catálogo remoto responderá siempre.

## Interfaz y rutas

Las rutas del frontend principales documentadas son:

  -----------------------------------------------------------------------
  Ruta                    Pantalla / función      Acceso
  ----------------------- ----------------------- -----------------------
  `/`                     Inicio                  Público

  `/login`                Inicio de sesión        Sin sesión

  `/register`             Registro                Sin sesión

  `/dashboard`            Panel principal         Autenticado

  `/characters`           Mis personajes          Autenticado

  `/characters/create`    Crear personaje         Autenticado

  `/campaigns`            Mis campañas            Autenticado

  `/campaigns/:id`        Detalle de campaña      Autenticado

  `/other-campaigns`      Área de otras campañas  Según implementación
                                                  actual

  `/api-2014`             Acceso informativo al   Autenticado
                          catálogo                
  -----------------------------------------------------------------------

Las rutas auxiliares de desarrollo pueden cambiar o desaparecer entre
versiones.

## API REST

Las rutas del servidor se agrupan bajo `/api`. Las rutas de
autenticación inicial y el catálogo público pueden no requerir token;
las operaciones privadas utilizan el middleware de Sanctum.

  -----------------------------------------------------------------------
  Grupo                               Endpoints representativos
  ----------------------------------- -----------------------------------
  Autenticación                       `POST /register`, `POST /login`,
                                      `POST /logout`, `GET /me`

  Campañas                            `GET/POST /campaigns`,
                                      `GET/PUT/DELETE /campaigns/{id}`

  Personajes                          `GET/POST /characters`,
                                      `GET/PUT/DELETE /characters/{id}`

  Retrato                             `POST /characters/{id}/image`

  Personajes de campaña               `/campaigns/{id}/characters`

  Clases de personaje                 `/characters/{id}/classes`

  NPCs                                `/campaigns/{id}/npcs`,
                                      `/npcs/{id}`

  Localizaciones                      `/campaigns/{id}/locations`,
                                      `/locations/{id}`

  Sesiones                            `/campaigns/{id}/sessions`,
                                      `/sessions/{id}`

  Encuentros                          `/campaigns/{id}/encounters`,
                                      `/encounters/{id}`

  Combatientes                        `/encounters/{id}/combatants`,
                                      `/combatants/{id}`

  Catálogo D&D                        `/dnd/{category}` y
                                      `/dnd/{category}/{index}`
  -----------------------------------------------------------------------

La tabla es orientativa: `routes/api.php` es la referencia definitiva de
métodos HTTP, middleware y parámetros de la versión instalada. Los
errores de validación de Laravel suelen responder con HTTP `422`.

## Modelo de datos

El esquema se mantiene mediante migraciones de Laravel. Entre las
entidades principales se encuentran:

-   `users`: cuentas de usuario.
-   `campaigns`: campañas y usuario DM.
-   `campaign_user`: relación muchos a muchos entre usuarios y campañas.
-   `characters`: datos generales de las fichas.
-   `character_classes`: clases y nivel individual de cada clase para
    multiclase.
-   Entidades de NPCs, localizaciones, sesiones y encuentros.
-   `combatants`: participantes de los encuentros.
-   Tablas auxiliares para características, competencias, ataques,
    inventario, monedas, conjuros, espacios de conjuro y progresión,
    según las migraciones disponibles.

Las migraciones de `database/migrations` definen el esquema exacto y
prevalecen sobre esta descripción resumida.

## Seguridad y permisos

-   Las contraseñas se almacenan mediante los mecanismos de hash de
    Laravel.
-   Sanctum valida los tokens enviados en la cabecera
    `Authorization: Bearer`.
-   El cierre de sesión invalida el token en el servidor.
-   Las rutas privadas del frontend redirigen a usuarios no
    autenticados.
-   El backend vuelve a comprobar autenticación, pertenencia y permisos;
    la protección visual del cliente no sustituye a la autorización del
    servidor.
-   El acceso a campañas se restringe a sus miembros.
-   Las operaciones de administración de una campaña corresponden al DM.
-   Los datos recibidos se validan en el servidor.
-   Las imágenes de personaje deben cumplir los formatos y límites
    configurados por el backend.

En despliegues públicos se debe usar `APP_DEBUG=false`, una clave
`APP_KEY` segura, credenciales propias y HTTPS.

## Instalación en local

### Requisitos

-   PHP compatible con `composer.json` (el entorno de desarrollo
    utilizado dispone de PHP 8.5).
-   Composer.
-   Node.js y npm.
-   MySQL o MariaDB.
-   Git.

### Pasos

``` bash
git clone https://github.com/ty0410/dnd-campaign-manager.git
cd dnd-campaign-manager

composer install
npm install
```

Copia el archivo de entorno:

``` powershell
Copy-Item .env.example .env
```

Configura la conexión a una base de datos creada previamente en `.env`.
Después:

``` bash
php artisan key:generate
php artisan migrate
```

En una terminal:

``` bash
npm run dev
```

En otra:

``` bash
php artisan serve
```

Laravel mostrará la dirección local, normalmente
`http://127.0.0.1:8000`.

Para compilar los recursos:

``` bash
npm run build
```

## Ejecución con Docker

El proyecto dispone de configuración Docker (`Dockerfile`,
`compose.yaml` y recursos auxiliares). El archivo `.env.docker` debe
crearse localmente a partir de la plantilla de ejemplo disponible en el
repositorio, si está incluida, y rellenarse con valores propios. No se
deben subir secretos reales.

Construir e iniciar:

``` powershell
docker compose --env-file .env.docker up -d --build
```

Comprobar servicios:

``` powershell
docker compose --env-file .env.docker ps
```

Ejecutar migraciones:

``` powershell
docker compose --env-file .env.docker exec app php artisan migrate --force
```

Consultar registros:

``` powershell
docker compose --env-file .env.docker logs -f
```

En la configuración local utilizada durante las pruebas, el servicio web
se publicó en el puerto `8080` (`http://localhost:8080`). Comprueba el
mapeo real en `compose.yaml`.

Detener servicios:

``` powershell
docker compose --env-file .env.docker down
```

> `docker compose down -v` también elimina los volúmenes y puede borrar
> los datos persistidos de la base de datos. Úsalo únicamente si quieres
> eliminar esos datos.

## Publicación temporal con Cloudflare Tunnel

Para facilitar pruebas externas sin contratar un VPS ni abrir puertos
del router, se ha contemplado el uso de **Cloudflare Quick Tunnel**
sobre el contenedor web que escucha localmente en el puerto `8080`.

Con `cloudflared` instalado y Docker Compose en ejecución, se puede
iniciar un túnel temporal hacia el servicio local:

``` powershell
cloudflared tunnel --url http://localhost:8080
```

Cloudflare mostrará una URL pública temporal bajo `trycloudflare.com`.
Esa dirección puede compartirse con testers mientras el túnel y el
equipo anfitrión permanezcan encendidos.

Consideraciones: - Quick Tunnel está pensado para pruebas, no como
alojamiento de producción. - La URL puede cambiar al crear un nuevo
túnel. - El equipo anfitrión debe permanecer encendido y conectado. - No
publiques credenciales ni datos reales de usuarios. - Revisa `APP_URL`,
cookies, CORS y configuración de proxy si la aplicación genera enlaces o
redirecciones con el host local. - Un túnel público no convierte
automáticamente una aplicación de desarrollo en un despliegue seguro.

## Pruebas

Ejecuta las pruebas automatizadas de Laravel con:

``` bash
php artisan test
```

La cobertura debe ampliarse progresivamente para autenticación,
permisos, campañas, personajes, multiclase, generación de fichas y
endpoints de la API.

## Estructura del proyecto

``` text
app/
  Http/Controllers/Api/   Controladores de la API
  Models/                 Modelos Eloquent
  Services/               Cálculos y servicios externos
bootstrap/                 Arranque de Laravel
config/                    Configuración
database/
  migrations/              Esquema de base de datos
  seeders/                 Datos iniciales
docker/                    Recursos auxiliares Docker
public/                    Punto de entrada web
resources/
  js/
    components/            Componentes Vue
    router/                Rutas y guards del frontend
    services/              Cliente API y autenticación
    views/                 Pantallas Vue
  css/                     Estilos
routes/
  api.php                  API REST
  web.php                  Entrada de la SPA
tests/                     Pruebas automatizadas
Dockerfile                 Imagen de la aplicación
compose.yaml               Servicios Docker
```

## Estado del desarrollo

La situación funcional debe entenderse separando backend e interfaz:

  -----------------------------------------------------------------------
  Área                                Situación documentada
  ----------------------------------- -----------------------------------
  Registro, login y logout            Implementado

  CRUD de campañas                    Implementado

  Creación y ficha de personaje       Implementado y ampliado

  Métodos de características y        Implementado
  validación                          

  Cálculo de ficha                    Implementado

  Multiclase                          Modelo y API contemplados; interfaz
                                      en evolución

  Asignación de personajes existentes API disponible; interfaz en
  a campañas                          evolución

  NPCs, localizaciones y sesiones     API desarrollada; interfaz parcial

  Encuentros y combatientes           API desarrollada; interfaz
                                      pendiente/parcial

  Catálogo D&D                        Consultas de backend implementadas

  Inventario, monedas, conjuros y     Desarrollo progresivo
  progresión                          

  Docker                              Configuración incorporada y probada
                                      localmente

  Cloudflare Quick Tunnel             Vía de exposición temporal para
                                      testers

  Pruebas automatizadas               Necesitan ampliación
  -----------------------------------------------------------------------

Este estado es una fotografía del desarrollo, no una garantía de que
todos los apartados estén terminados en cada commit. La implementación
vigente se comprueba en el código del repositorio.

## Aviso legal

Dungeons & Dragons y sus marcas pertenecen a sus respectivos titulares.
Este proyecto es una herramienta independiente creada con fines
académicos y de gestión de partidas; no está afiliada, patrocinada ni
respaldada por Wizards of the Coast. Los datos del catálogo dependen de
D&D 5e API y de su disponibilidad.

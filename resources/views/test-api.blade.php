<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Prueba API - D&D Campaign Manager</title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            font-family: Arial, sans-serif;

            background: #f4f4f4;

            margin: 0;

            padding: 30px;

            color: #222;

        }


        .contenedor {

            max-width: 1100px;

            margin: 0 auto;

        }


        h1 {

            margin-bottom: 5px;

        }


        .subtitulo {

            color: #666;

            margin-bottom: 30px;

        }


        .bloque {

            background: white;

            border-radius: 8px;

            padding: 20px;

            margin-bottom: 20px;

            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);

        }


        .bloque h2 {

            margin-top: 0;

        }


        .grid {

            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(300px, 1fr));

            gap: 15px;

        }


        .prueba {

            border: 1px solid #ddd;

            border-radius: 7px;

            padding: 15px;

            background: #fafafa;

        }


        .prueba h3 {

            margin-top: 0;

        }


        label {

            display: block;

            margin-top: 10px;

            margin-bottom: 5px;

            font-weight: bold;

        }


        input {

            width: 100%;

            max-width: 400px;

            padding: 10px;

            border: 1px solid #ccc;

            border-radius: 5px;

            font-size: 14px;

        }


        button {

            margin-top: 12px;

            padding: 10px 18px;

            border: none;

            border-radius: 6px;

            background: #333;

            color: white;

            cursor: pointer;

            font-size: 14px;

        }


        button:hover {

            background: #555;

        }


        .boton-principal {

            background: #222;

            font-size: 16px;

        }


        .boton-principal:hover {

            background: #444;

        }


        .estado {

            font-weight: bold;

        }


        .pendiente {

            color: #777;

        }


        .correcto {

            color: green;

        }


        .error {

            color: red;

        }


        pre {

            background: #222;

            color: #eee;

            padding: 15px;

            border-radius: 6px;

            overflow-x: auto;

            white-space: pre-wrap;

            word-break: break-word;

            min-height: 80px;

        }


        .resultado-correcto {

            border-left: 4px solid green;

        }


        .resultado-error {

            border-left: 4px solid red;

        }


        .nota {

            color: #666;

            font-size: 13px;

        }

    </style>

</head>


<body>


<div class="contenedor">


    <h1>🎲 D&D Campaign Manager</h1>

    <p class="subtitulo">
        Panel de pruebas de la API REST
    </p>



    <!-- ========================================= -->
    <!-- AUTENTICACIÓN -->
    <!-- ========================================= -->

    <div class="bloque">

        <h2>🔐 Autenticación</h2>


        <label for="email">
            Email
        </label>

        <input
            type="email"
            id="email"
            value="jugador@dnd.local"
        >


        <label for="password">
            Contraseña
        </label>

        <input
            type="password"
            id="password"
            value="12345678"
        >


        <br>


        <button
            class="boton-principal"
            onclick="login()"
        >
            Iniciar sesión
        </button>


        <p>

            Estado:

            <span
                id="estadoLogin"
                class="estado pendiente"
            >
                No iniciado
            </span>

        </p>


        <pre id="resultadoLogin">Esperando inicio de sesión...</pre>

    </div>



    <!-- ========================================= -->
    <!-- CAMPAÑAS -->
    <!-- ========================================= -->

    <div class="bloque">

        <h2>📚 Campañas</h2>


        <div class="grid">


            <div class="prueba">

                <h3>
                    Listar campañas
                </h3>

                <button onclick="probarCampanas()">
                    Probar
                </button>

                <pre id="resultadoCampanas">
Esperando prueba...
                </pre>

            </div>


            <div class="prueba">

                <h3>
                    Consultar campaña 2
                </h3>

                <button onclick="probarCampana()">
                    Probar
                </button>

                <pre id="resultadoCampana">
Esperando prueba...
                </pre>

            </div>


        </div>

    </div>



    <!-- ========================================= -->
    <!-- PERSONAJES -->
    <!-- ========================================= -->

    <div class="bloque">

        <h2>🧙 Personajes</h2>


        <div class="grid">


            <div class="prueba">

                <h3>
                    Personajes de campaña 2
                </h3>

                <button onclick="probarPersonajes()">
                    Probar
                </button>

                <pre id="resultadoPersonajes">
Esperando prueba...
                </pre>

            </div>


            <div class="prueba">

                <h3>
                    Consultar personaje 2
                </h3>

                <button onclick="probarPersonaje()">
                    Probar
                </button>

                <pre id="resultadoPersonaje">
Esperando prueba...
                </pre>

            </div>


            <div class="prueba">

                <h3>
                    Clases del personaje 2
                </h3>

                <button onclick="probarClases()">
                    Probar
                </button>

                <pre id="resultadoClases">
Esperando prueba...
                </pre>

            </div>


        </div>

    </div>



    <!-- ========================================= -->
    <!-- NPCs -->
    <!-- ========================================= -->

    <div class="bloque">

        <h2>👤 NPCs</h2>


        <div class="grid">


            <div class="prueba">

                <h3>
                    NPCs de campaña 2
                </h3>

                <button onclick="probarNpcs()">
                    Probar
                </button>

                <pre id="resultadoNpcs">
Esperando prueba...
                </pre>

            </div>


            <div class="prueba">

                <h3>
                    Consultar NPC 1
                </h3>

                <button onclick="probarNpc()">
                    Probar
                </button>

                <pre id="resultadoNpc">
Esperando prueba...
                </pre>

            </div>


        </div>

    </div>



    <!-- ========================================= -->
    <!-- LUGARES -->
    <!-- ========================================= -->

    <div class="bloque">

        <h2>📍 Lugares</h2>


        <div class="grid">


            <div class="prueba">

                <h3>
                    Lugares de campaña 2
                </h3>

                <button onclick="probarLugares()">
                    Probar
                </button>

                <pre id="resultadoLugares">
Esperando prueba...
                </pre>

            </div>


            <div class="prueba">

                <h3>
                    Consultar Phandalin
                </h3>

                <button onclick="probarLugar()">
                    Probar
                </button>

                <pre id="resultadoLugar">
Esperando prueba...
                </pre>

            </div>


        </div>

    </div>



    <!-- ========================================= -->
    <!-- SESIONES -->
    <!-- ========================================= -->

    <div class="bloque">

        <h2>📖 Sesiones de juego</h2>


        <div class="grid">


            <div class="prueba">

                <h3>
                    Sesiones de campaña 2
                </h3>

                <button onclick="probarSesiones()">
                    Probar
                </button>

                <pre id="resultadoSesiones">
Esperando prueba...
                </pre>

            </div>


            <div class="prueba">

                <h3>
                    Consultar sesión 1
                </h3>

                <button onclick="probarSesion()">
                    Probar
                </button>

                <pre id="resultadoSesion">
Esperando prueba...
                </pre>

            </div>


        </div>

    </div>



    <!-- ========================================= -->
    <!-- ENCUENTROS -->
    <!-- ========================================= -->

    <div class="bloque">

        <h2>⚔️ Encuentros</h2>


        <div class="grid">


            <div class="prueba">

                <h3>
                    Encuentros de campaña 2
                </h3>

                <button onclick="probarEncuentros()">
                    Probar
                </button>

                <pre id="resultadoEncuentros">
Esperando prueba...
                </pre>

            </div>


            <div class="prueba">

                <h3>
                    Consultar encuentro 1
                </h3>

                <button onclick="probarEncuentro()">
                    Probar
                </button>

                <pre id="resultadoEncuentro">
Esperando prueba...
                </pre>

            </div>


        </div>

    </div>



    <!-- ========================================= -->
    <!-- COMBATIENTES -->
    <!-- ========================================= -->

    <div class="bloque">

        <h2>🛡️ Combatientes</h2>


        <div class="grid">


            <div class="prueba">

                <h3>
                    Combatientes del encuentro 1
                </h3>

                <button onclick="probarCombatientes()">
                    Probar
                </button>

                <pre id="resultadoCombatientes">
Esperando prueba...
                </pre>

            </div>


            <div class="prueba">

                <h3>
                    Consultar combatiente 1
                </h3>

                <button onclick="probarCombatiente()">
                    Probar
                </button>

                <pre id="resultadoCombatiente">
Esperando prueba...
                </pre>

            </div>


        </div>

    </div>



    <!-- ========================================= -->
    <!-- PRUEBA GENERAL -->
    <!-- ========================================= -->

    <div class="bloque">

        <h2>🚀 Prueba general</h2>

        <p class="nota">
            Ejecuta las consultas principales de la API de una sola vez.
        </p>


        <button
            class="boton-principal"
            onclick="probarTodo()"
        >
            Probar toda la API
        </button>


        <pre id="resultadoGeneral">
Esperando prueba general...
        </pre>

    </div>


</div>



<script>


/* ============================================
   TOKEN
============================================ */

let token = null;



/* ============================================
   FUNCIÓN AUXILIAR
============================================ */

async function peticion(url) {


    if (!token) {

        throw new Error(
            'Primero debes iniciar sesión.'
        );

    }


    const respuesta = await fetch(url, {

        method: 'GET',

        headers: {

            'Accept': 'application/json',

            'Authorization':
                'Bearer ' + token

        }

    });


    const datos = await respuesta.json();


    if (!respuesta.ok) {

        throw new Error(

            'HTTP ' +
            respuesta.status +
            '\n\n' +

            JSON.stringify(
                datos,
                null,
                4
            )

        );

    }


    return datos;

}



/* ============================================
   LOGIN
============================================ */

async function login() {


    const email =
        document.getElementById('email').value;


    const password =
        document.getElementById('password').value;


    const estado =
        document.getElementById('estadoLogin');


    const resultado =
        document.getElementById('resultadoLogin');


    estado.textContent =
        'Iniciando sesión...';


    estado.className =
        'estado pendiente';


    try {


        const respuesta =
            await fetch('/api/login', {

                method: 'POST',

                headers: {

                    'Accept':
                        'application/json',

                    'Content-Type':
                        'application/json'

                },

                body:
                    JSON.stringify({

                        email: email,

                        password: password

                    })

            });


        const datos =
            await respuesta.json();


        if (!respuesta.ok) {

            throw new Error(

                'HTTP ' +
                respuesta.status +
                '\n\n' +

                JSON.stringify(
                    datos,
                    null,
                    4
                )

            );

        }


        token =
            datos.token;


        estado.textContent =
            '✓ Login correcto';


        estado.className =
            'estado correcto';


        resultado.textContent =
            JSON.stringify({

                message:
                    datos.message,

                user:
                    datos.user,

                token_obtenido:
                    true

            }, null, 4);


    } catch (error) {


        estado.textContent =
            '✗ Error';


        estado.className =
            'estado error';


        resultado.textContent =
            error.message;

    }

}



/* ============================================
   CAMPAÑAS
============================================ */

async function probarCampanas() {


    const elemento =
        document.getElementById(
            'resultadoCampanas'
        );


    try {

        const datos =
            await peticion(
                '/api/campaigns'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



async function probarCampana() {


    const elemento =
        document.getElementById(
            'resultadoCampana'
        );


    try {

        const datos =
            await peticion(
                '/api/campaigns/2'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



/* ============================================
   PERSONAJES
============================================ */

async function probarPersonajes() {


    const elemento =
        document.getElementById(
            'resultadoPersonajes'
        );


    try {

        const datos =
            await peticion(
                '/api/campaigns/2/characters'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



async function probarPersonaje() {


    const elemento =
        document.getElementById(
            'resultadoPersonaje'
        );


    try {

        const datos =
            await peticion(
                '/api/characters/2'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



/* ============================================
   CLASES
============================================ */

async function probarClases() {


    const elemento =
        document.getElementById(
            'resultadoClases'
        );


    try {

        const datos =
            await peticion(
                '/api/characters/2/classes'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



/* ============================================
   NPCs
============================================ */

async function probarNpcs() {


    const elemento =
        document.getElementById(
            'resultadoNpcs'
        );


    try {

        const datos =
            await peticion(
                '/api/campaigns/2/npcs'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



async function probarNpc() {


    const elemento =
        document.getElementById(
            'resultadoNpc'
        );


    try {

        const datos =
            await peticion(
                '/api/npcs/1'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



/* ============================================
   LUGARES
============================================ */

async function probarLugares() {


    const elemento =
        document.getElementById(
            'resultadoLugares'
        );


    try {

        const datos =
            await peticion(
                '/api/campaigns/2/locations'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



async function probarLugar() {


    const elemento =
        document.getElementById(
            'resultadoLugar'
        );


    try {

        const datos =
            await peticion(
                '/api/locations/2'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



/* ============================================
   SESIONES
============================================ */

async function probarSesiones() {


    const elemento =
        document.getElementById(
            'resultadoSesiones'
        );


    try {

        const datos =
            await peticion(
                '/api/campaigns/2/sessions'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



async function probarSesion() {


    const elemento =
        document.getElementById(
            'resultadoSesion'
        );


    try {

        const datos =
            await peticion(
                '/api/sessions/1'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



/* ============================================
   ENCUENTROS
============================================ */

async function probarEncuentros() {


    const elemento =
        document.getElementById(
            'resultadoEncuentros'
        );


    try {

        const datos =
            await peticion(
                '/api/campaigns/2/encounters'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



async function probarEncuentro() {


    const elemento =
        document.getElementById(
            'resultadoEncuentro'
        );


    try {

        const datos =
            await peticion(
                '/api/encounters/1'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



/* ============================================
   COMBATIENTES
============================================ */

async function probarCombatientes() {


    const elemento =
        document.getElementById(
            'resultadoCombatientes'
        );


    try {

        const datos =
            await peticion(
                '/api/encounters/1/combatants'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



async function probarCombatiente() {


    const elemento =
        document.getElementById(
            'resultadoCombatiente'
        );


    try {

        const datos =
            await peticion(
                '/api/combatants/1'
            );


        elemento.textContent =
            JSON.stringify(
                datos,
                null,
                4
            );


    } catch (error) {

        elemento.textContent =
            error.message;

    }

}



/* ============================================
   PRUEBA GENERAL
============================================ */

async function probarTodo() {


    const resultado =
        document.getElementById(
            'resultadoGeneral'
        );


    resultado.textContent =
        'Ejecutando pruebas...\n';


    try {


        if (!token) {

            throw new Error(
                'Primero debes iniciar sesión.'
            );

        }


        const resultados = {};


        resultados.campanas =
            await peticion(
                '/api/campaigns'
            );


        resultados.campana =
            await peticion(
                '/api/campaigns/2'
            );


        resultados.personajes =
            await peticion(
                '/api/campaigns/2/characters'
            );


        resultados.personaje =
            await peticion(
                '/api/characters/2'
            );


        resultados.clases =
            await peticion(
                '/api/characters/2/classes'
            );


        resultados.npcs =
            await peticion(
                '/api/campaigns/2/npcs'
            );


        resultados.npc =
            await peticion(
                '/api/npcs/1'
            );


        resultados.lugares =
            await peticion(
                '/api/campaigns/2/locations'
            );


        resultados.lugar =
            await peticion(
                '/api/locations/2'
            );


        resultados.sesiones =
            await peticion(
                '/api/campaigns/2/sessions'
            );


        resultados.sesion =
            await peticion(
                '/api/sessions/1'
            );


        resultados.encuentros =
            await peticion(
                '/api/campaigns/2/encounters'
            );


        resultados.encuentro =
            await peticion(
                '/api/encounters/1'
            );


        resultados.combatientes =
            await peticion(
                '/api/encounters/1/combatants'
            );


        resultados.combatiente =
            await peticion(
                '/api/combatants/1'
            );


        resultado.textContent =
            '✓ TODAS LAS PRUEBAS CORRECTAS\n\n' +

            JSON.stringify(
                resultados,
                null,
                4
            );


    } catch (error) {


        resultado.textContent =
            '✗ ERROR DURANTE LAS PRUEBAS\n\n' +

            error.message;

    }

}

</script>


</body>

</html>
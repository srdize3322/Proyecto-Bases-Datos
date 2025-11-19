## Estructura del proyecto

E3/  
├── css/  
│   └── style.css # Estilos globales de la aplicación  
├── php/  
│   ├── index.php # Login de usuarios  
│   ├── validar_login.php # Procesamiento del login  
│   ├── main.php # Página principal tras iniciar sesión  
│   ├── registro.php # Registro de nuevos usuarios  
│   ├── procesar_registro.php # Procesa el formulario de registro  
│   ├── crear_viaje.php # Formulario para crear un viaje  
│   ├── desplegar_viaje.php # Vista del itinerario del viaje  
│   ├── cerrar_sesion.php # Cierre de sesión  
│   └── utils.php # Conexión a la base de datos  

## Cómo ejecutar

1. **Sube todos los archivos** al servidor del curso dentro de tu carpeta `Sites/E3/`.
2. **Inicia la aplicación localmente** con los siguientes pasos:

Cambia las variables en `Sites/E3/php/utils.php`

```
$host = 'localhost'; // Cambiar al servidor bdd1.ing.puc.cl si se quiere usar el servidor remoto
$dbname = 'bdd'; // Nombre de usuario
$usuario = 'nombre_usuario'; // Nombre de usuario
$clave = 'contraseña'; // Número de alumno
```
En la terminal, ubicate en la carpeta E3, luego ejecuta el siguiente comando:

```
php -S localhost:8000
```

En tu navegador, ir a la ruta:
http://localhost:8000/php/index.php

3. **Inicia la aplicación en el servidor del curso** con los siguientes pasos:
Entra al servidor con el protocolo ssh como siempre.

No es necesario ejecutar ningún comando! Solo entra a esta URL: https://bdd1.ing.puc.cl/tu_usuario/E3/php/index.php

-Recuerda modificar 'tu_usuario' con tu nombre de usuario.

## Tecnologías utilizadas

- PHP
- HTML5 + CSS3
- PostgreSQL
- Servidor: `bdd1.ing.puc.cl`

### Contacto

Cualquier duda técnica o problema de ejecución puede comunicarse a través de ISSUES según las instrucciones del curso.
# sendMassiveEmails: Envío Masivo de Correos Electrónicos

## Descripción

Este script permite el envío masivo de correos electrónicos en lotes a direcciones que pertenecen a un dominio específico (en este caso, `gmail.com`). Utiliza PHPMailer para gestionar el envío de correos electrónicos a través de SMTP y registra los resultados de cada intento en un archivo CSV.

## Características

- Filtrado de direcciones de correo electrónico por dominio.
- Envío en lotes para evitar el bloqueo del servidor SMTP.
- Registro de resultados en un archivo CSV.
- Carga de contenido HTML desde un archivo externo.

## Instalación y Configuración

1. **Clonar o descargar el repositorio**: Clona o descarga el código fuente de este script en tu servidor local o de producción.

2. **Estructura de directorios**:
   - Crea los siguientes directorios:
     - `db`: Contiene el archivo `emails.txt` con las direcciones de correo.
     - `log`: Para almacenar los archivos CSV generados.
     - `ext`: Para las librerías externas (PHPMailer y Dotenv).

3. **Instalación de librerías**:
   - Descarga PHPMailer y Dotenv:
     - PHPMailer: [GitHub - PHPMailer](https://github.com/PHPMailer/PHPMailer)
     - Dotenv: [GitHub - vlucas/phpdotenv](https://github.com/vlucas/phpdotenv)
   - Coloca las carpetas `src` de ambas librerías dentro del directorio `ext`.

4. **Configuración de SMTP**:
   - Crea un archivo `.env` en la raíz del proyecto con las siguientes variables:
     ```
     SMTP_HOST=smtp.gmail.com
     SMTP_USERNAME=tu_email@gmail.com
     SMTP_PASSWORD=tu_contraseña
     SMTP_PORT=587
     SMTP_SECURE=tls
     ```

5. **Preparar el archivo de correos**:
   - Crea un archivo `emails.txt` en el directorio `db` con las direcciones de correo electrónico, una por línea.

## Uso

- Ejecuta el script desde la línea de comandos:
  ```bash
  php enviarEmails.php
  ```
- El resultado de cada intento se registrará en un archivo CSV en el directorio `log` con la fecha en el nombre del archivo.

## Gestión de Errores

- Si ocurre un error al abrir el archivo de correos electrónicos o al cargar la plantilla de HTML, el script mostrará un mensaje de error correspondiente y terminará su ejecución.
- Los errores de envío se registrarán en el archivo CSV para su revisión posterior.

## Autor

- **Aythami Melian Perdomo**

## Licencia

Este proyecto está bajo la Licencia Pública General GNU (GPL) v3.0. Para más detalles, consulta el archivo `LICENSE`.

### Consideraciones Finales

Asegúrate de tener los permisos correctos en los directorios y archivos para que el script pueda ejecutarse sin problemas. Si necesitas realizar ajustes adicionales, no dudes en decírmelo.

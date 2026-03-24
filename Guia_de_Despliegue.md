# Guía de Despliegue CRM360

Esta guía resume las opciones recomendadas para lanzar a producción el sistema CRM360 desarrollado en Laravel, el cual sincroniza datos con la API de Google Contacts.

## Opción 1: Hosting Compartido (cPanel)
**Recomendado para:** Presupuestos ajustados, equipos pequeños y cuando no se tiene experiencia manejando servidores por consola (SSH).
**Ejemplos:** Hostinger, SiteGround.

### Pasos Generales
1. Asegurarte de que el plan de Hosting soporte **PHP 8.2** o superior y bases de datos MySQL/MariaDB.
2. Comprimir todo el proyecto de tu Laragon en un `.zip` (preferiblemente excluyendo la carpeta pesada `vendor` y generando un nuevo build de dependencias allá, o incluyéndola si no tienes acceso consola en tu cPanel).
3. Subir el `.zip` a través del "Administrador de Archivos" de cPanel.
4. Extraer los archivos.
5. Crear una base de datos nueva en cPanel y actualizar las credenciales en el archivo `.env`.
6. Configurar en tu cPanel para que el dominio principal apunte ("Document Root") a la carpeta `/public` de tu proyecto Laravel, ya que todo el sistema web arranca desde esa carpeta por seguridad.
7. Modificar los límites de PHP en cPanel (ej. `max_execution_time`) para evitar que la sincronización con cientos de Google Contacts se corte por "Time Out".

---

## Opción 2: VPS + Panel Visual (RunCloud / Ploi.io) ⭐ Recomendado
**Recomendado para:** Mejor rendimiento, mayor escalabilidad y evitar bloqueos por tiempo de ejecución. Permite automatizar tareas pesadas.
**Ejemplos de VPS:** DigitalOcean, Vultr, Linode, Hetzner.
**Ejemplos de Paneles:** RunCloud, Ploi.io, Laravel Forge.

### Pasos Generales (Sin ser experto en Linux)
1. Contratar un servidor VPS en blanco (usualmente $5 - $6 dólares mensuales).
2. Crear una cuenta en un panel visual como RunCloud o Ploi.
3. Vincular el VPS al panel dando clic en "Conectar" y corriendo un solo comando. El panel instalará mágicamente Nginx, PHP 8.2, bases de datos y seguridad por ti.
4. Desde la página web de tu panel, crear tu aplicación web y asociar tu dominio real.
5. Para instalar tu código, puedes conectar tu cuenta de GitHub (así la página se actualiza solita cuando subes cambios) o arrastrar los archivos vía SFTP.
6. Habilitar tu certificado SSL de conexión segura gratis con un solo clic desde el panel.

---

## ⚠️ Configuración de Google API (Obligatorio)
Sea cual sea tu opción de hospedaje, una vez que tu CRM360 esté operando en producción (ejemplo `https://www.micrm360.com`), debes hacer lo siguiente:

1. Ingresar a [Google Cloud Console](https://console.cloud.google.com/).
2. Ir a la sección "API y Servicios" > "Credenciales".
3. Ubicar tu ID de cliente OAuth 2.0 y editarlo.
4. En **"URI de redireccionamiento autorizados"**, añadir la nueva URL de internet:
   `https://www.micrm360.com/google/callback`
5. Guardar los cambios. 
*(Si omites esto, el inicio de sesión con Google dará un error de bloqueo rojo al intentar ingresar en producción.)*

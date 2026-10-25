# Medicina del Alma

Sitio público del proyecto. El campus privado de talleres, secciones y actividades está en [campus/README.md](campus/README.md). Requiere alojamiento PHP/MySQL independiente de Netlify.

Las tarjetas de la home y las páginas de detalle de los seis talleres se administran desde el campus. En una base existente, ejecuta `campus/database/20261025_clean_workshop_routes.sql` para actualizar los enlaces y las URL públicas. Las rutas limpias, como `/talleres/reiki-unitario/`, tienen páginas de entrada mínimas que comparten el mismo renderizador y cargan el detalle desde la base. La guía completa está en [campus/README.md](campus/README.md).

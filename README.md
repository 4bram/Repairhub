## Portal del cliente

Módulo que permite a los clientes autenticados consultar el progreso de sus reparaciones de forma segura.

### Funcionalidades

- Listado paginado de órdenes propias.
- Consulta de detalles y estado de reparación.
- Historial de seguimientos visibles.
- Consulta de evidencias autorizadas.
- Descarga protegida de fotografías.
- Control de acceso mediante propiedad de recursos.

### Endpoints

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/portal/ordenes` | Listar órdenes propias |
| GET | `/api/portal/ordenes/{orden_id}` | Consultar una orden |
| GET | `/api/portal/ordenes/{orden_id}/seguimientos` | Consultar seguimientos visibles |
| GET | `/api/portal/ordenes/{orden_id}/evidencias` | Consultar evidencias autorizadas |
| GET | `/api/portal/ordenes/{orden_id}/evidencias/{evidencia_id}/archivo` | Descargar evidencia |

### Seguridad

- Autenticación mediante Laravel Sanctum.
- Autorización mediante la relación `clientes.usuario_id`.
- Protección contra acceso a órdenes ajenas (IDOR).
- Filtrado de notas internas y evidencias privadas.
- Almacenamiento privado mediante Supabase Storage.
- Validación de pertenencia de evidencias a su orden.
- Endpoints de solo lectura.

### Pruebas automatizadas

`php artisan test --filter=PortalClienteTest`

### Integración con el frontend

El listado de órdenes utiliza paginación y devuelve los registros en la propiedad `data`.

Las evidencias incluyen una propiedad `url_archivo` que permite recuperar las imágenes mediante una petición autenticada al backend.

No se requieren credenciales de Supabase Storage en el frontend.
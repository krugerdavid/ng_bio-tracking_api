<x-mail::message>
# Nuevo registro pendiente

**{{ $memberName }}** ({{ $memberEmail }}) se registró en NG Training{{ $groupLine }} y está esperando tu aprobación.

<x-mail::button :url="$pendingUrl">
Ver pendientes
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>

@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ rtrim((string) config('app.frontend_url'), '/') }}/ngtraining.png" class="logo" alt="NG Training">
</a>
</td>
</tr>

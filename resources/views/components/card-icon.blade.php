@props(['item' => []])

{{-- Uploaded icon, or the item's bundled illustration when none is uploaded --}}
@if (! empty($item['iconId']))
    {!! wp_get_attachment_image((int) $item['iconId'], 'medium', false, ['class' => $attributes->get('class') . ' object-contain', 'alt' => '', 'loading' => 'lazy']) !!}
@else
    <x-featherdash-icon :name="$item['icon'] ?? ''" :class="$attributes->get('class')" />
@endif

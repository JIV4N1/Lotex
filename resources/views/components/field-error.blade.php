@props(['name', 'id'])
@error($name)<p id="{{ $id }}" class="text-danger small mt-1 mb-0">{{ $message }}</p>@enderror

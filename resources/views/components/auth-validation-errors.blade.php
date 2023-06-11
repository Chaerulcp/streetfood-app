@props(['errors'])

@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'p-3 rounded-md bg-red-600 text-white']) }}>
        <div class="font-medium">
            {{ __('Ups! Ada yang salah.') }}
        </div>

        <ul class="mt-3 list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

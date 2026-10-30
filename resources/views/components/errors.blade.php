@if ($errors->any())
    <div class="mb-5 flex items-start gap-2.5 rounded-xl bg-ruby/8 px-4 py-3 text-sm font-bold text-ruby" role="alert">
        <x-icon name="alert" class="mt-px h-5 w-5 shrink-0" />
        @if ($errors->count() === 1)
            <p class="leading-relaxed">{{ $errors->first() }}</p>
        @else
            <ul class="space-y-1 leading-relaxed">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

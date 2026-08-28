@if($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700" role="alert">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

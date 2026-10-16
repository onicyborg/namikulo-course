@if ($errors->any())
    <div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary>
        <strong>Periksa kembali data berikut:</strong>
        <ul class="mb-0 mt-2 pl-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

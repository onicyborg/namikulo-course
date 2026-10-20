<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, shrink-to-fit=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $__env->yieldContent('title', config('app.name')) }} | {{ config('app.name') }}</title>
<link rel="icon" type="image/x-icon" href="{{ url('favicon.ico') }}?v=1">
<link rel="icon" type="image/svg+xml" sizes="any" href="{{ url('favicon.svg') }}?v=1">

<link rel="stylesheet" href="{{ url('css/global-toast.css') }}?v=2">
<script src="{{ url('js/global-toast.js') }}?v=1"></script>

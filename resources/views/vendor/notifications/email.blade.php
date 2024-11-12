<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Salutări!')
@endif
@endif

{{-- Intro Lines --}}
{{ Lang::get('Primești acest e-mail deoarece am primit o cerere de resetare a parolei pentru contul tău.') }}

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ Lang::get('Resetează Parola') }}
</x-mail::button>
@endisset

{{-- Expiration Notice --}}
{{ Lang::get('Acest link de resetare a parolei va expira în :count minute.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]) }}

{{-- Outro Lines --}}
{{ Lang::get('Dacă nu ați solicitat resetarea parolei, nu este necesară nicio acțiune suplimentară.') }}

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
<br>
{{ config('app.name') }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
@lang(
    "Dacă întâmpini probleme în a face clic pe butonul \":actionText\", copiază și lipește URL-ul de mai jos:\n".
    'în browserul tău:',
    [
        'actionText' => $actionText,
    ]
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>

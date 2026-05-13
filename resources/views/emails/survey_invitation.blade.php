<x-mail::message>
# Habari {{ $customer->name }},

{{ $bodyContent }}

<x-mail::button :url="$url">
Toa Maoni Yako Hapa
</x-mail::button>

Maoni yako yatafika moja kwa moja kwa uongozi wetu na yatashughulikiwa kwa umuhimu mkubwa.

Asante kwa kuchagua **TRUMARK**.

Kila la kheri,<br>
Uongozi wa {{ config('app.name') }}
</x-mail::message>

<x-mail::message>
# Deneme Süreniz {{ $daysLeft }} Gün İçinde Bitiyor

Merhaba {{ $user->name }},

Cirotik ücretsiz deneme süreniz **{{ $daysLeft }} gün** içinde sona erecek.

Hizmetimizden kesintisiz yararlanmaya devam etmek için bir plan seçmeniz yeterli.

<x-mail::button :url="route('subscription.select')">
Plan Seç ve Devam Et
</x-mail::button>

Deneme süreniz boyunca yaşadığınız deneyim hakkında geri bildiriminizi bekliyoruz.

Cirotik Ekibi
</x-mail::message>

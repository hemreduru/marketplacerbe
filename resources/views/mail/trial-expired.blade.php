<x-mail::message>
# Deneme Süreniz Sona Erdi

Merhaba {{ $user->name }},

Cirotik ücretsiz deneme süreniz sona erdi. Panele erişiminiz geçici olarak askıya alındı.

Hizmetimizden yararlanmaya devam etmek için aşağıdaki butona tıklayarak planınızı seçebilirsiniz.

<x-mail::button :url="route('subscription.select')" color="red">
Planımı Seç
</x-mail::button>

Herhangi bir sorunuz varsa destek ekibimize ulaşabilirsiniz.

Cirotik Ekibi
</x-mail::message>

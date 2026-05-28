<?php

namespace App\Services\Cargo\Contracts;

use App\Services\Cargo\ValueObjects\LabelFormat;
use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Support\ServiceResult;

/**
 * Tüm kargo sağlayıcıları bu arayüzü implemente eder.
 *
 * Bkz. CIROTIK_AGENT_SPEC.md Bölüm 8.3
 */
interface CargoProvider
{
    /**
     * Yeni kargo gönderisi oluşturur, tracking number döner.
     *
     * @return ServiceResult<array{tracking_number: string, label_url: ?string}>
     */
    public function createShipment(ShipmentRequest $request): ServiceResult;

    /**
     * Gönderiyi iptal eder.
     *
     * @return ServiceResult<null>
     */
    public function cancelShipment(string $trackingNumber): ServiceResult;

    /**
     * Etiket dosyasını döner (PDF/ZPL byte array).
     *
     * @return ServiceResult<string> binary label data
     */
    public function getLabel(string $trackingNumber, LabelFormat $format): ServiceResult;

    /**
     * Tekil takip sorgusu — güncel durumu döner.
     *
     * @return ServiceResult<array{status: string, location: ?string, timestamp: string}>
     */
    public function track(string $trackingNumber): ServiceResult;

    /**
     * Toplu durum sorgusu — belirtilen tarihten beri değişen gönderiler.
     *
     * @return ServiceResult<array<int, array{tracking_number: string, status: string, location: ?string, timestamp: string}>>
     */
    public function listStatusUpdates(\DateTimeInterface $since): ServiceResult;

    /**
     * Sağlayıcı kodunu döner (örn: 'yurtici', 'aras').
     */
    public function getServiceCode(): string;

    /**
     * Sağlayıcının desteklediği özellikler.
     *
     * @return array{webhook: bool, cod: bool, label_zpl: bool, label_a4: bool, tracking_batch: bool}
     */
    public function getCapabilities(): array;
}

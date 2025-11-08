<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Response Messages (Turkish)
    |--------------------------------------------------------------------------
    */

    // Genel
    'success' => 'İşlem başarıyla tamamlandı',
    'error' => 'Bir hata oluştu',
    'not_found' => 'Kaynak bulunamadı',
    'unauthorized' => 'Yetkisiz erişim',
    'forbidden' => 'Erişim yasak',
    'validation_error' => 'Doğrulama hatası',
    'server_error' => 'Sunucu hatası',

    // Pazaryerleri
    'marketplace' => [
        'list_success' => 'Pazaryerleri başarıyla getirildi',
        'show_success' => 'Pazaryeri başarıyla getirildi',
        'not_found' => 'Pazaryeri bulunamadı',
        'not_active' => 'Pazaryeri aktif değil',
        'not_implemented' => 'Pazaryeri servisi henüz hazır değil',
    ],

    // API Bilgileri
    'credential' => [
        'list_success' => 'API bilgileri başarıyla getirildi',
        'show_success' => 'API bilgisi başarıyla getirildi',
        'create_success' => 'API bilgisi başarıyla oluşturuldu',
        'update_success' => 'API bilgisi başarıyla güncellendi',
        'delete_success' => 'API bilgisi başarıyla silindi',
        'not_found' => 'API bilgisi bulunamadı',
        'already_exists' => 'Bu pazaryeri için API bilgisi zaten mevcut',
        'test_success' => 'Bağlantı testi başarılı',
        'test_failed' => 'Bağlantı testi başarısız',
    ],

    // Ürünler
    'product' => [
        'list_success' => 'Ürünler başarıyla getirildi',
        'show_success' => 'Ürün başarıyla getirildi',
        'create_success' => 'Ürün başarıyla oluşturuldu',
        'update_success' => 'Ürün başarıyla güncellendi',
        'delete_success' => 'Ürün başarıyla silindi',
        'restore_success' => 'Ürün başarıyla geri yüklendi',
        'not_found' => 'Ürün bulunamadı',
        'already_exists' => 'Bu SKU ile ürün zaten mevcut',
        'bulk_create_success' => ':count ürün başarıyla oluşturuldu',
    ],

    // Pazaryeri Ürünleri
    'marketplace_product' => [
        'list_success' => 'Pazaryeri ürünleri başarıyla getirildi',
        'show_success' => 'Pazaryeri ürünü başarıyla getirildi',
        'push_success' => 'Ürün pazaryerine başarıyla gönderildi',
        'push_failed' => 'Ürün pazaryerine gönderilemedi',
        'pull_success' => 'Ürünler pazaryerinden başarıyla çekildi',
        'pull_failed' => 'Ürünler pazaryerinden çekilemedi',
        'sync_success' => 'Ürün başarıyla senkronize edildi',
        'sync_failed' => 'Ürün senkronize edilemedi',
        'not_found' => 'Pazaryeri ürünü bulunamadı',
        'already_synced' => 'Ürün bu pazaryeri ile zaten senkronize',
        'bulk_push_success' => 'Toplu gönderme işlemi tamamlandı',
        'bulk_push_failed' => 'Toplu gönderme işlemi başarısız',
        'bulk_sync_success' => 'Toplu senkronizasyon tamamlandı',
        'bulk_sync_failed' => 'Toplu senkronizasyon başarısız',
    ],

    // Siparişler
    'order' => [
        'list_success' => 'Siparişler başarıyla getirildi',
        'show_success' => 'Sipariş başarıyla getirildi',
        'fetch_success' => 'Siparişler pazaryerinden başarıyla çekildi',
        'fetch_failed' => 'Siparişler pazaryerinden çekilemedi',
        'update_status_success' => 'Sipariş durumu başarıyla güncellendi',
        'update_status_failed' => 'Sipariş durumu güncellenemedi',
        'update_tracking_success' => 'Kargo takip numarası başarıyla güncellendi',
        'update_tracking_failed' => 'Kargo takip numarası güncellenemedi',
        'send_invoice_success' => 'Fatura başarıyla gönderildi',
        'send_invoice_failed' => 'Fatura gönderilemedi',
        'not_found' => 'Sipariş bulunamadı',
    ],

    // İadeler
    'claim' => [
        'list_success' => 'İadeler başarıyla getirildi',
        'show_success' => 'İade başarıyla getirildi',
        'fetch_success' => 'İadeler pazaryerinden başarıyla çekildi',
        'fetch_failed' => 'İadeler pazaryerinden çekilemedi',
        'approve_success' => 'İade başarıyla onaylandı',
        'approve_failed' => 'İade onaylanamadı',
        'reject_success' => 'İade başarıyla reddedildi',
        'reject_failed' => 'İade reddedilemedi',
        'not_found' => 'İade bulunamadı',
    ],

    // Sorular
    'question' => [
        'list_success' => 'Sorular başarıyla getirildi',
        'show_success' => 'Soru başarıyla getirildi',
        'fetch_success' => 'Sorular pazaryerinden başarıyla çekildi',
        'fetch_failed' => 'Sorular pazaryerinden çekilemedi',
        'answer_success' => 'Cevap başarıyla gönderildi',
        'answer_failed' => 'Cevap gönderilemedi',
        'not_found' => 'Soru bulunamadı',
    ],

    // Senkronizasyon
    'sync' => [
        'categories_success' => 'Kategoriler başarıyla senkronize edildi',
        'categories_failed' => 'Kategoriler senkronize edilemedi',
        'brands_success' => 'Markalar başarıyla senkronize edildi',
        'brands_failed' => 'Markalar senkronize edilemedi',
        'log_list_success' => 'Senkronizasyon logları başarıyla getirildi',
    ],

    // Doğrulama
    'validation' => [
        'credential_id_required' => 'Pazaryeri API bilgisi ID gerekli',
        'credential_not_found' => 'Pazaryeri API bilgisi bulunamadı',
        'product_ids_required' => 'Ürün ID\'leri gerekli',
        'product_ids_array' => 'Ürün ID\'leri dizi olmalı',
        'product_ids_min' => 'En az bir ürün ID gerekli',
        'product_id_required' => 'Ürün ID gerekli',
        'product_id_integer' => 'Ürün ID sayı olmalı',
        'product_not_found' => 'Ürün bulunamadı',
        'marketplace_product_ids_required' => 'Pazaryeri ürün ID\'leri gerekli',
        'marketplace_product_ids_array' => 'Pazaryeri ürün ID\'leri dizi olmalı',
        'marketplace_product_ids_min' => 'En az bir pazaryeri ürün ID gerekli',
        'marketplace_product_id_required' => 'Pazaryeri ürün ID gerekli',
        'marketplace_product_id_integer' => 'Pazaryeri ürün ID sayı olmalı',
        'marketplace_product_not_found' => 'Pazaryeri ürünü bulunamadı',
        'sync_type_required' => 'Senkronizasyon tipi gerekli',
        'sync_type_invalid' => 'Senkronizasyon tipi stock, price veya both olmalı',
    ],

];

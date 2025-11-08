<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Response Messages (English)
    |--------------------------------------------------------------------------
    */

    // General
    'success' => 'Operation completed successfully',
    'error' => 'An error occurred',
    'not_found' => 'Resource not found',
    'unauthorized' => 'Unauthorized access',
    'forbidden' => 'Access forbidden',
    'validation_error' => 'Validation error',
    'server_error' => 'Server error',

    // Marketplaces
    'marketplace' => [
        'list_success' => 'Marketplaces retrieved successfully',
        'show_success' => 'Marketplace retrieved successfully',
        'not_found' => 'Marketplace not found',
        'not_active' => 'Marketplace is not active',
        'not_implemented' => 'Marketplace service not implemented yet',
    ],

    // User Settings
    'settings' => [
        'show_success' => 'Settings retrieved successfully',
        'update_success' => 'Settings updated successfully',
        'theme_updated' => 'Theme updated successfully',
        'language_updated' => 'Language updated successfully',
    ],

    // Languages
    'language' => [
        'list_success' => 'Languages retrieved successfully',
        'show_success' => 'Language retrieved successfully',
        'not_found' => 'Language not found',
    ],

    // Credentials
    'credential' => [
        'list_success' => 'Credentials retrieved successfully',
        'show_success' => 'Credential retrieved successfully',
        'create_success' => 'Credential created successfully',
        'update_success' => 'Credential updated successfully',
        'delete_success' => 'Credential deleted successfully',
        'not_found' => 'Credential not found',
        'already_exists' => 'Credential already exists for this marketplace',
        'test_success' => 'Connection test successful',
        'test_failed' => 'Connection test failed',
    ],

    // Products
    'product' => [
        'list_success' => 'Products retrieved successfully',
        'show_success' => 'Product retrieved successfully',
        'create_success' => 'Product created successfully',
        'update_success' => 'Product updated successfully',
        'delete_success' => 'Product deleted successfully',
        'restore_success' => 'Product restored successfully',
        'not_found' => 'Product not found',
        'already_exists' => 'Product with this SKU already exists',
        'bulk_create_success' => ':count products created successfully',
    ],

    // Marketplace Products
    'marketplace_product' => [
        'list_success' => 'Marketplace products retrieved successfully',
        'show_success' => 'Marketplace product retrieved successfully',
        'push_success' => 'Product pushed to marketplace successfully',
        'push_failed' => 'Failed to push product to marketplace',
        'pull_success' => 'Products pulled from marketplace successfully',
        'pull_failed' => 'Failed to pull products from marketplace',
        'sync_success' => 'Product synchronized successfully',
        'sync_failed' => 'Failed to synchronize product',
        'not_found' => 'Marketplace product not found',
        'already_synced' => 'Product already synced with this marketplace',
        'bulk_push_success' => 'Bulk push operation completed',
        'bulk_push_failed' => 'Bulk push operation failed',
        'bulk_sync_success' => 'Bulk sync operation completed',
        'bulk_sync_failed' => 'Bulk sync operation failed',
    ],

    // Orders
    'order' => [
        'list_success' => 'Orders retrieved successfully',
        'show_success' => 'Order retrieved successfully',
        'fetch_success' => 'Orders fetched from marketplace successfully',
        'fetch_failed' => 'Failed to fetch orders from marketplace',
        'update_status_success' => 'Order status updated successfully',
        'update_status_failed' => 'Failed to update order status',
        'update_tracking_success' => 'Tracking number updated successfully',
        'update_tracking_failed' => 'Failed to update tracking number',
        'send_invoice_success' => 'Invoice sent successfully',
        'send_invoice_failed' => 'Failed to send invoice',
        'not_found' => 'Order not found',
    ],

    // Claims
    'claim' => [
        'list_success' => 'Claims retrieved successfully',
        'show_success' => 'Claim retrieved successfully',
        'fetch_success' => 'Claims fetched from marketplace successfully',
        'fetch_failed' => 'Failed to fetch claims from marketplace',
        'approve_success' => 'Claim approved successfully',
        'approve_failed' => 'Failed to approve claim',
        'reject_success' => 'Claim rejected successfully',
        'reject_failed' => 'Failed to reject claim',
        'not_found' => 'Claim not found',
    ],

    // Questions
    'question' => [
        'list_success' => 'Questions retrieved successfully',
        'show_success' => 'Question retrieved successfully',
        'fetch_success' => 'Questions fetched from marketplace successfully',
        'fetch_failed' => 'Failed to fetch questions from marketplace',
        'answer_success' => 'Answer submitted successfully',
        'answer_failed' => 'Failed to submit answer',
        'not_found' => 'Question not found',
    ],

    // Sync
    'sync' => [
        'categories_success' => 'Categories synchronized successfully',
        'categories_failed' => 'Failed to synchronize categories',
        'brands_success' => 'Brands synchronized successfully',
        'brands_failed' => 'Failed to synchronize brands',
        'log_list_success' => 'Sync logs retrieved successfully',
    ],

    // Validation
    'validation' => [
        'credential_id_required' => 'Marketplace credential ID is required',
        'credential_not_found' => 'Marketplace credential not found',
        'product_ids_required' => 'Product IDs are required',
        'product_ids_array' => 'Product IDs must be an array',
        'product_ids_min' => 'At least one product ID is required',
        'product_id_required' => 'Product ID is required',
        'product_id_integer' => 'Product ID must be an integer',
        'product_not_found' => 'Product not found',
        'marketplace_product_ids_required' => 'Marketplace product IDs are required',
        'marketplace_product_ids_array' => 'Marketplace product IDs must be an array',
        'marketplace_product_ids_min' => 'At least one marketplace product ID is required',
        'marketplace_product_id_required' => 'Marketplace product ID is required',
        'marketplace_product_id_integer' => 'Marketplace product ID must be an integer',
        'marketplace_product_not_found' => 'Marketplace product not found',
        'sync_type_required' => 'Sync type is required',
        'sync_type_invalid' => 'Sync type must be: stock, price, or both',
    ],

];

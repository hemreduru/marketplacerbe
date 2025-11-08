<?php

namespace App\Services;

use App\Models\Product;

interface MarketplaceServiceInterface
{
    /**
     * Get products from marketplace.
     *
     * @param array $filters
     * @return array
     */
    public function getProducts(array $filters = []): array;

    /**
     * Create a product on the marketplace.
     *
     * @param Product $product
     * @return array
     */
    public function createProduct(Product $product): array;

    /**
     * Update a product on the marketplace.
     *
     * @param Product $product
     * @return array
     */
    public function updateProduct(Product $product): array;

    /**
     * Update stock quantity for a product.
     *
     * @param string $barcode
     * @param int $quantity
     * @return array
     */
    public function updateStock(string $barcode, int $quantity): array;

    /**
     * Update price for a product.
     *
     * @param string $barcode
     * @param float $price
     * @return array
     */
    public function updatePrice(string $barcode, float $price): array;

    /**
     * Get orders from marketplace.
     *
     * @param array $filters
     * @return array
     */
    public function getOrders(array $filters = []): array;

    /**
     * Update order status.
     *
     * @param string $orderId
     * @param string $status
     * @return array
     */
    public function updateOrderStatus(string $orderId, string $status): array;

    /**
     * Update tracking number for an order.
     *
     * @param string $orderId
     * @param string $trackingNumber
     * @return array
     */
    public function updateTrackingNumber(string $orderId, string $trackingNumber): array;

    /**
     * Send invoice information for an order.
     *
     * @param string $orderId
     * @param string $invoiceNumber
     * @param string $invoiceLink
     * @return array
     */
    public function sendInvoice(string $orderId, string $invoiceNumber, string $invoiceLink): array;

    /**
     * Get claims/returns from marketplace.
     *
     * @param array $filters
     * @return array
     */
    public function getClaims(array $filters = []): array;

    /**
     * Approve a claim/return.
     *
     * @param string $claimId
     * @return array
     */
    public function approveClaim(string $claimId): array;

    /**
     * Reject a claim/return.
     *
     * @param string $claimId
     * @param string $reason
     * @return array
     */
    public function rejectClaim(string $claimId, string $reason): array;

    /**
     * Get customer questions from marketplace.
     *
     * @param array $filters
     * @return array
     */
    public function getQuestions(array $filters = []): array;

    /**
     * Answer a customer question.
     *
     * @param string $questionId
     * @param string $answer
     * @return array
     */
    public function answerQuestion(string $questionId, string $answer): array;

    /**
     * Get categories from marketplace.
     *
     * @return array
     */
    public function getCategories(): array;

    /**
     * Get brands from marketplace.
     *
     * @return array
     */
    public function getBrands(): array;

    /**
     * Get category attributes from marketplace.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryAttributes(int $categoryId): array;

    /**
     * Get CHE settlements (financial transactions) from marketplace.
     *
     * @param array $filters - startDate, endDate, transactionType, page, size
     * @return array
     */
    public function getSettlements(array $filters = []): array;

    /**
     * Get CHE other financials (deductions, fees) from marketplace.
     *
     * @param array $filters - startDate, endDate, transactionType, page, size
     * @return array
     */
    public function getOtherFinancials(array $filters = []): array;

    /**
     * Get cargo invoice items for a specific invoice.
     *
     * @param string $invoiceId
     * @return array
     */
    public function getCargoInvoiceItems(string $invoiceId): array;
}

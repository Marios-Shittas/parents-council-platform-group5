<?php

final class ParentProfilePageHelper
{
    /**
     * Converts account status keys to display labels.
     */
    public static function formatAccountStatusLabel(string $status): string
    {
        $map = [
            'pending' => 'Σε Αναμονή',
            'approved' => 'Εγκεκριμένος',
            'rejected' => 'Απορριφθείς',
            'waiting_payment' => 'Αναμονή Πληρωμής',
            'active' => 'Ενεργός',
        ];

        return $map[$status] ?? ucfirst($status);
    }

    /**
     * Returns CSS class for account status badges.
     */
    public static function accountStatusClass(string $status): string
    {
        switch ($status) {
            case 'active':
                return 'status-badge status-success';
            case 'approved':
                return 'status-badge status-primary';
            case 'waiting_payment':
                return 'status-badge status-warning';
            case 'rejected':
                return 'status-badge status-danger';
            default:
                return 'status-badge status-muted';
        }
    }

    /**
     * Converts order status keys to display labels.
     */
    public static function formatOrderStatusLabel(string $status): string
    {
        $map = [
            'pending' => 'Σε Αναμονή',
            'paid' => 'Πληρωμένη',
            'cancelled' => 'Ακυρωμένη',
        ];

        return $map[$status] ?? ucfirst($status);
    }

    /**
     * Returns CSS class for order status badges.
     */
    public static function orderStatusClass(string $status): string
    {
        switch ($status) {
            case 'paid':
                return 'status-badge status-success';
            case 'cancelled':
                return 'status-badge status-danger';
            default:
                return 'status-badge status-warning';
        }
    }

    /**
     * Converts payment status keys to display labels.
     */
    public static function formatPaymentStatusLabel(string $status): string
    {
        $map = [
            'pending' => 'Σε Αναμονή',
            'completed' => 'Ολοκληρωμένη',
            'failed' => 'Αποτυχημένη',
            'refunded' => 'Επιστροφή',
        ];

        return $map[$status] ?? ucfirst($status);
    }

    /**
     * Returns CSS class for payment status badges.
     */
    public static function paymentStatusClass(string $status): string
    {
        switch ($status) {
            case 'completed':
                return 'status-badge status-success';
            case 'failed':
                return 'status-badge status-danger';
            case 'refunded':
                return 'status-badge status-info';
            default:
                return 'status-badge status-warning';
        }
    }

    /**
     * Converts payment type keys to display labels.
     */
    public static function formatPaymentTypeLabel(string $type): string
    {
        $map = [
            'membership' => 'Συνδρομή',
            'insurance' => 'Ασφάλεια',
            'product' => 'Προϊόν',
        ];

        return $map[$type] ?? ucfirst($type);
    }
}

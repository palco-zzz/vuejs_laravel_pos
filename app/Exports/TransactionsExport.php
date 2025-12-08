<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class TransactionsExport
{
    protected $startDate;
    protected $endDate;
    protected $branchId;
    protected $paymentMethod;

    public function __construct(
        string $startDate,
        string $endDate,
        ?int $branchId = null,
        ?string $paymentMethod = null
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->branchId = $branchId;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Get the query for transactions
     */
    protected function query()
    {
        return Order::withTrashed()
            ->with(['branch', 'user', 'items.menu', 'editor', 'deleter'])
            ->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ])
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->when($this->paymentMethod, fn($q) => $q->where('payment_method', $this->paymentMethod))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get payment method label in Indonesian
     */
    protected function getPaymentMethodLabel(string $method): string
    {
        $labels = [
            'cash' => 'Tunai',
            'bca_va' => 'BCA Virtual Account',
            'bri_va' => 'BRI Virtual Account',
            'gopay' => 'GoPay',
            'ovo' => 'OVO',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
        ];

        return $labels[$method] ?? $method;
    }

    /**
     * Get status label in Indonesian
     */
    protected function getStatusLabel(string $status, $order): string
    {
        // Check if it's a voided transaction
        if ($order->deleted_at) {
            return 'Dibatalkan/Void';
        }

        $labels = [
            'success' => 'Berhasil',
            'completed' => 'Berhasil',
            'pending' => 'Menunggu',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Refund',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Format items into a readable string
     */
    protected function formatItems($items): string
    {
        if ($items->isEmpty()) {
            return '-';
        }

        return $items->map(function ($item) {
            $name = $item->item_name ?? ($item->menu->nama ?? 'Unknown');
            $custom = $item->is_custom ? ' (Custom)' : '';
            return "{$item->quantity}x {$name}{$custom}";
        })->implode(', ');
    }

    /**
     * Format audit log (edit/void info)
     */
    protected function formatAuditLog($order): string
    {
        $logs = [];

        // Check for void/cancellation
        if ($order->deleted_at) {
            $deleterName = $order->deleter->name ?? 'Admin';
            $reason = $order->delete_reason ? " ({$order->delete_reason})" : '';
            $logs[] = "Dibatalkan oleh {$deleterName}{$reason}";
        }

        // Check for edit
        if ($order->edited_at) {
            $editorName = $order->editor->name ?? 'Admin';
            $reason = $order->edit_reason ? " ({$order->edit_reason})" : '';
            $logs[] = "Diedit oleh {$editorName}{$reason}";
        }

        return empty($logs) ? '-' : implode('; ', $logs);
    }

    /**
     * Get the headings for the export
     */
    protected function headings(): array
    {
        return [
            'No Order',
            'Waktu',
            'Cabang',
            'Kasir',
            'Detail Item',
            'Total (Rp)',
            'Metode Bayar',
            'Status',
            'Audit Log',
        ];
    }

    /**
     * Map order to row data
     */
    protected function mapRow($order): array
    {
        return [
            $order->order_number,
            $order->created_at->format('d/m/Y H:i'),
            $order->branch->nama ?? '-',
            $order->user->name ?? '-',
            $this->formatItems($order->items),
            $order->total,
            $this->getPaymentMethodLabel($order->payment_method),
            $this->getStatusLabel($order->status, $order),
            $this->formatAuditLog($order),
        ];
    }

    /**
     * Generate and download as CSV (Excel-compatible)
     */
    public function download(string $filename = 'Laporan-Transaksi.csv')
    {
        $orders = $this->query();
        
        // Create CSV content with BOM for Excel UTF-8 compatibility
        $output = chr(0xEF) . chr(0xBB) . chr(0xBF); // UTF-8 BOM
        
        // Add filter info header
        $filterInfo = "Laporan Transaksi | Periode: {$this->startDate} s/d {$this->endDate}";
        if ($this->branchId) {
            $branchName = \App\Models\Branch::find($this->branchId)?->nama ?? 'Unknown';
            $filterInfo .= " | Cabang: {$branchName}";
        }
        if ($this->paymentMethod) {
            $filterInfo .= " | Metode: {$this->getPaymentMethodLabel($this->paymentMethod)}";
        }
        $output .= $filterInfo . "\n\n";
        
        // Add headings
        $output .= implode(',', array_map(fn($h) => '"' . $h . '"', $this->headings())) . "\n";
        
        // Add data rows
        foreach ($orders as $order) {
            $row = $this->mapRow($order);
            $output .= implode(',', array_map(function ($cell) {
                // Escape quotes and wrap in quotes for CSV
                $cell = str_replace('"', '""', (string) $cell);
                return '"' . $cell . '"';
            }, $row)) . "\n";
        }
        
        // Add summary at the bottom
        $successOrders = $orders->whereNull('deleted_at');
        $totalRevenue = $successOrders->sum('total');
        $totalCount = $successOrders->count();
        $voidedCount = $orders->whereNotNull('deleted_at')->count();
        
        $output .= "\n";
        $output .= "\"RINGKASAN\"\n";
        $output .= "\"Total Transaksi Berhasil\",\"{$totalCount}\"\n";
        $output .= "\"Total Transaksi Dibatalkan\",\"{$voidedCount}\"\n";
        $output .= "\"Total Pendapatan (Berhasil)\",\"{$totalRevenue}\"\n";
        
        // Generate response
        return Response::make($output, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
}

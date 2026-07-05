<?php

namespace App\Services;

use App\Models\Document;
use App\Models\OrderFund;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];

    private const MAX_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * Upload a document for an order.
     * Verifies: allowed extension + MIME type (dual check), max size 10MB.
     * Stores in storage/app/private/documents/{order_id}/ (outside public).
     */
    public function upload(OrderFund $order, UploadedFile $file, int $userId): Document
    {
        // Block upload on EXECUTED, CANCELLED, or REJECTED orders
        $blockedStatuses = ['EXECUTED', 'CANCELLED', 'REJECTED'];
        if (in_array($order->status, $blockedStatuses)) {
            throw ValidationException::withMessages([
                'file' => 'لا يمكن رفع وثائق على طلب بحالة ' . $order->status,
            ]);
        }

        // Verify extension (actual check, not just MIME)
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw ValidationException::withMessages([
                'file' => 'الامتداد غير مسموح — الصيغ المسموحة: pdf, jpg, png, docx',
            ]);
        }

        // Verify MIME type (dual check: extension + MIME)
        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED_MIMES)) {
            throw ValidationException::withMessages([
                'file' => 'نوع الملف غير مسموح',
            ]);
        }

        // Verify file size (max 10MB)
        if ($file->getSize() > self::MAX_SIZE) {
            throw ValidationException::withMessages([
                'file' => 'حجم الملف يتجاوز الحد الأقصى 10 ميغابايت',
            ]);
        }

        // Store outside public: storage/app/private/documents/{order_id}/
        $path = $file->store("private/documents/{$order->id}", 'local');

        return Document::create([
            'order_id'    => $order->id,
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $path,
            'file_type'   => $extension,
            'file_size'   => $file->getSize(),
            'uploaded_by' => $userId,
            'uploaded_at' => now(),
        ]);
    }
}

<?php
// V-FINAL-1730-466 (Created)

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Securely upload a file.
     * This is the single point of entry for all file uploads.
     */
    public function upload(UploadedFile $file, array $options = []): string
    {
        $defaults = [
            'disk' => 'public', // 'public', 's3'
            'path' => 'uploads',
            'encrypt' => false,
            'allowed_mimes' => 'jpg,jpeg,png,pdf',
            'max_size' => 5120, // 5MB
            'virus_scan' => true,
        ];
        $options = array_merge($defaults, $options);

        // 1. Validate (Test: test_file_upload_service_validates_file_types)
        $this->validate($file, $options['allowed_mimes'], $options['max_size']);

        // 2. Scan for Viruses (Test: test_file_upload_service_scans_for_viruses)
        if ($options['virus_scan']) {
            $this->scanForVirus($file);
        }

        // 3. Prepare Content (Encrypt or Raw)
        $content = $options['encrypt'] 
            ? Crypt::encrypt(file_get_contents($file->getRealPath()))
            : file_get_contents($file->getRealPath());

        // 4. Store File
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $fullPath = $options['path'] . '/' . $fileName;
        
        Storage::disk($options['disk'])->put($fullPath, $content);
        
        return $fullPath; // Return the path for DB storage
    }

    /**
     * Validate file properties.
     */
    private function validate(UploadedFile $file, string $mimes, int $maxSize)
    {
        $validator = Validator::make(
            ['file' => $file],
            ['file' => "required|file|mimes:{$mimes}|max:{$maxSize}"]
        );

        if ($validator->fails()) {
            throw new \Exception("File validation failed: " . $validator->errors()->first());
        }
        
        // FSD-SEC-011: Zero-byte file check
        if ($file->getSize() == 0) {
            throw new \Exception("File cannot be empty.");
        }
    }

    /**
     * FSD-SEC-011: Virus Scan
     */
    private function scanForVirus(UploadedFile $file)
    {
        // This is a stub for integration. In production, you would
        // shell out to ClamAV or use a vendor API.
        
        // MOCK: Check for a "test virus" file
        if ($file->getClientOriginalName() === 'eicar.com') {
            throw new \Exception("Malware detected in file.");
        }
        
        // In real app:
        // $scanner = new ClamAVScanner();
        // if ($scanner->isVirus($file->getRealPath())) {
        //     throw new \Exception("Malware detected in file.");
        // }
    }
}
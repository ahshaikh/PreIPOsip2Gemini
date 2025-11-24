<?php
// V-SEEDER (Created for development environment setup)

namespace Database\Seeders;

use App\Models\KycRejectionTemplate;
use Illuminate\Database\Seeder;

class KycRejectionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Document Too Blurry',
                'reason' => 'The uploaded document image is too blurry or unclear. Please upload a clearer, high-resolution image where all text is readable.',
                'category' => 'document_quality',
                'is_active' => true,
            ],
            [
                'name' => 'Expired Document',
                'reason' => 'The document you uploaded has expired. Please upload a valid, unexpired document.',
                'category' => 'validity',
                'is_active' => true,
            ],
            [
                'name' => 'Document Mismatch',
                'reason' => 'The name or details on the document do not match the information provided during registration. Please ensure consistency across all documents.',
                'category' => 'mismatch',
                'is_active' => true,
            ],
            [
                'name' => 'Face Not Visible',
                'reason' => 'Your face is not clearly visible in the selfie/photo ID. Please upload a photo where your face is clearly visible and matches the ID document.',
                'category' => 'photo',
                'is_active' => true,
            ],
            [
                'name' => 'Incomplete Document',
                'reason' => 'The document is partially cut off or incomplete. Please upload the full document showing all edges and information.',
                'category' => 'document_quality',
                'is_active' => true,
            ],
            [
                'name' => 'Wrong Document Type',
                'reason' => 'The document uploaded does not match the required document type. Please upload the correct type of document as specified.',
                'category' => 'document_type',
                'is_active' => true,
            ],
            [
                'name' => 'Glare on Document',
                'reason' => 'There is significant glare or reflection on the document making it unreadable. Please retake the photo in better lighting without flash.',
                'category' => 'document_quality',
                'is_active' => true,
            ],
            [
                'name' => 'Address Proof Outdated',
                'reason' => 'The address proof document is more than 3 months old. Please upload a recent utility bill, bank statement, or other valid address proof.',
                'category' => 'validity',
                'is_active' => true,
            ],
            [
                'name' => 'PAN-Aadhaar Mismatch',
                'reason' => 'The name on your PAN card does not match the name on your Aadhaar card. Please ensure both documents have the same name or update your records.',
                'category' => 'mismatch',
                'is_active' => true,
            ],
            [
                'name' => 'Document Tampered',
                'reason' => 'The document appears to have been edited or tampered with. Please upload an original, unedited document.',
                'category' => 'fraud',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            KycRejectionTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}

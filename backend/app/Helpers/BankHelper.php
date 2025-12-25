<?php

if (!function_exists('getBankNameFromIFSC')) {
    function getBankNameFromIFSC(string $ifsc): string {
        $bankCode = substr($ifsc, 0, 4);
        
        $banks = [
            'SBIN' => 'State Bank of India',
            'HDFC' => 'HDFC Bank',
            'ICIC' => 'ICICI Bank',
            'AXIS' => 'Axis Bank',
            'KKBK' => 'Kotak Mahindra Bank',
            'PUNB' => 'Punjab National Bank',
            'UBIN' => 'Union Bank of India',
            'BARB' => 'Bank of Baroda',
            'CNRB' => 'Canara Bank',
            'IOBA' => 'Indian Overseas Bank',
            'BKID' => 'Bank of India',
            'IDIB' => 'Indian Bank',
            'UTIB' => 'Axis Bank',
            'YESB' => 'Yes Bank',
            'INDB' => 'IndusInd Bank',
        ];
        
        return $banks[$bankCode] ?? $bankCode . ' Bank';
    }
}

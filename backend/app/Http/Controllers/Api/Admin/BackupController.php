<?php
// V-FINAL-1730-226

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Stream a full database dump to the browser.
     * This avoids saving large files to the server disk.
     */
    public function downloadDbDump()
    {
        $dbName = env('DB_DATABASE');
        $fileName = 'backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';

        $headers = [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function() {
            $handle = fopen('php://output', 'w');
            
            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $key = "Tables_in_" . env('DB_DATABASE');

            foreach ($tables as $table) {
                $tableName = $table->$key;
                
                // Drop table if exists
                fwrite($handle, "\nDROP TABLE IF EXISTS `$tableName`;\n");
                
                // Create table structure
                $createTable = DB::select("SHOW CREATE TABLE `$tableName`")[0]->{'Create Table'};
                fwrite($handle, $createTable . ";\n\n");

                // Insert data
                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $values = array_map(function ($value) {
                        return is_null($value) ? "NULL" : "'" . addslashes($value) . "'";
                    }, (array) $row);
                    
                    $sql = "INSERT INTO `$tableName` VALUES (" . implode(", ", $values) . ");\n";
                    fwrite($handle, $sql);
                }
            }
            
            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
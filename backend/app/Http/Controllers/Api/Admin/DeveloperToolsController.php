<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiTestCase;
use App\Models\ApiTestResult;
use App\Models\ScheduledTask;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class DeveloperToolsController extends Controller
{
    /**
     * Execute SQL query (with safety limits)
     * POST /api/v1/admin/developer/sql
     */
    public function executeSql(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:5000',
            'limit' => 'sometimes|integer|min:1|max:1000',
        ]);

        $query = trim($validated['query']);
        $limit = $validated['limit'] ?? 100;

        // Security: Only allow SELECT queries
        if (!preg_match('/^SELECT\s+/i', $query)) {
            return response()->json([
                'error' => 'Only SELECT queries are allowed for security reasons',
            ], 403);
        }

        // Security: Prevent dangerous operations
        $forbidden = ['DROP', 'TRUNCATE', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE'];
        foreach ($forbidden as $keyword) {
            if (stripos($query, $keyword) !== false) {
                return response()->json([
                    'error' => "Keyword '{$keyword}' is not allowed",
                ], 403);
            }
        }

        try {
            $start = microtime(true);
            $results = DB::select(DB::raw($query));
            $executionTime = round((microtime(true) - $start) * 1000, 2);

            // Limit results
            $results = array_slice($results, 0, $limit);

            // Log query execution
            AuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => 'sql_query',
                'module' => 'developer_tools',
                'description' => 'Executed SQL query',
                'new_values' => ['query' => $query],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'results' => $results,
                'row_count' => count($results),
                'execution_time_ms' => $executionTime,
                'limited' => count($results) === $limit,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Query execution failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get database schema information
     * GET /api/v1/admin/developer/schema
     */
    public function getSchema()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $database = env('DB_DATABASE');
            $key = "Tables_in_{$database}";

            $schema = [];
            foreach ($tables as $table) {
                $tableName = $table->$key;
                $columns = DB::select("DESCRIBE {$tableName}");
                $rowCount = DB::table($tableName)->count();

                $schema[] = [
                    'name' => $tableName,
                    'columns' => $columns,
                    'row_count' => $rowCount,
                ];
            }

            return response()->json(['schema' => $schema]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch schema',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List API test cases
     * GET /api/v1/admin/developer/api-tests
     */
    public function listApiTests(Request $request)
    {
        $query = ApiTestCase::with('creator:id,username', 'results')
            ->orderBy('created_at', 'desc');

        if ($request->has('active_only') && $request->active_only === 'true') {
            $query->where('is_active', true);
        }

        $tests = $query->paginate(50);

        return response()->json($tests);
    }

    /**
     * Create API test case
     * POST /api/v1/admin/developer/api-tests
     */
    public function createApiTest(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'method' => 'required|in:GET,POST,PUT,DELETE,PATCH',
            'endpoint' => 'required|string',
            'headers' => 'nullable|array',
            'body' => 'nullable|array',
            'expected_response' => 'nullable|array',
            'expected_status_code' => 'required|integer|min:100|max:599',
            'is_active' => 'required|boolean',
        ]);

        $test = ApiTestCase::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'API test case created',
            'test' => $test,
        ], 201);
    }

    /**
     * Update API test case
     * PUT /api/v1/admin/developer/api-tests/{test}
     */
    public function updateApiTest(Request $request, ApiTestCase $test)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'method' => 'sometimes|in:GET,POST,PUT,DELETE,PATCH',
            'endpoint' => 'sometimes|string',
            'headers' => 'nullable|array',
            'body' => 'nullable|array',
            'expected_response' => 'nullable|array',
            'expected_status_code' => 'sometimes|integer|min:100|max:599',
            'is_active' => 'sometimes|boolean',
        ]);

        $test->update($validated);

        return response()->json([
            'message' => 'API test case updated',
            'test' => $test,
        ]);
    }

    /**
     * Execute API test case
     * POST /api/v1/admin/developer/api-tests/{test}/execute
     */
    public function executeApiTest(Request $request, ApiTestCase $test)
    {
        $start = microtime(true);

        try {
            // Build full URL
            $url = config('app.url') . $test->endpoint;

            // Make HTTP request
            $httpRequest = Http::timeout(30);

            // Add headers
            if ($test->headers) {
                $httpRequest = $httpRequest->withHeaders($test->headers);
            }

            // Execute request based on method
            $response = match ($test->method) {
                'GET' => $httpRequest->get($url),
                'POST' => $httpRequest->post($url, $test->body ?? []),
                'PUT' => $httpRequest->put($url, $test->body ?? []),
                'DELETE' => $httpRequest->delete($url, $test->body ?? []),
                'PATCH' => $httpRequest->patch($url, $test->body ?? []),
                default => throw new \Exception('Invalid HTTP method'),
            };

            $responseTime = (int) ((microtime(true) - $start) * 1000);
            $statusCode = $response->status();
            $body = $response->json();

            // Determine if test passed
            $passed = $statusCode === $test->expected_status_code;

            // If expected response is set, check it matches
            if ($test->expected_response && $passed) {
                $passed = $this->matchesExpectedResponse($body, $test->expected_response);
            }

            // Save result
            $result = ApiTestResult::create([
                'test_case_id' => $test->id,
                'status' => $passed ? 'passed' : 'failed',
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'response_body' => $body,
                'error_message' => $passed ? null : 'Response did not match expected values',
                'executed_by' => $request->user()->id,
            ]);

            return response()->json([
                'result' => $result,
                'passed' => $passed,
            ]);
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $start) * 1000);

            $result = ApiTestResult::create([
                'test_case_id' => $test->id,
                'status' => 'failed',
                'response_time' => $responseTime,
                'status_code' => null,
                'response_body' => null,
                'error_message' => $e->getMessage(),
                'executed_by' => $request->user()->id,
            ]);

            return response()->json([
                'result' => $result,
                'passed' => false,
            ]);
        }
    }

    /**
     * Run all active API tests
     * POST /api/v1/admin/developer/api-tests/run-all
     */
    public function runAllApiTests(Request $request)
    {
        $tests = ApiTestCase::where('is_active', true)->get();
        $results = [];

        foreach ($tests as $test) {
            $result = $this->executeApiTest($request, $test);
            $results[] = [
                'test_id' => $test->id,
                'test_name' => $test->name,
                'result' => $result->getData(),
            ];
        }

        $passed = collect($results)->where('result.passed', true)->count();
        $failed = collect($results)->where('result.passed', false)->count();

        return response()->json([
            'summary' => [
                'total' => count($results),
                'passed' => $passed,
                'failed' => $failed,
            ],
            'results' => $results,
        ]);
    }

    /**
     * Delete API test case
     * DELETE /api/v1/admin/developer/api-tests/{test}
     */
    public function deleteApiTest(ApiTestCase $test)
    {
        $test->delete();

        return response()->json([
            'message' => 'API test case deleted',
        ]);
    }

    /**
     * List scheduled tasks
     * GET /api/v1/admin/developer/tasks
     */
    public function listTasks(Request $request)
    {
        $query = ScheduledTask::with('creator:id,username')
            ->orderBy('created_at', 'desc');

        if ($request->has('active_only') && $request->active_only === 'true') {
            $query->where('is_active', true);
        }

        $tasks = $query->get();

        return response()->json(['tasks' => $tasks]);
    }

    /**
     * Create scheduled task
     * POST /api/v1/admin/developer/tasks
     */
    public function createTask(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'command' => 'required|string',
            'expression' => 'required|string', // Cron expression
            'description' => 'nullable|string',
            'parameters' => 'nullable|array',
            'is_active' => 'required|boolean',
        ]);

        // Validate cron expression
        if (!$this->isValidCronExpression($validated['expression'])) {
            return response()->json([
                'error' => 'Invalid cron expression',
            ], 422);
        }

        $task = ScheduledTask::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'next_run_at' => $this->calculateNextRun($validated['expression']),
        ]);

        return response()->json([
            'message' => 'Scheduled task created',
            'task' => $task,
        ], 201);
    }

    /**
     * Update scheduled task
     * PUT /api/v1/admin/developer/tasks/{task}
     */
    public function updateTask(Request $request, ScheduledTask $task)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'command' => 'sometimes|string',
            'expression' => 'sometimes|string',
            'description' => 'nullable|string',
            'parameters' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['expression'])) {
            if (!$this->isValidCronExpression($validated['expression'])) {
                return response()->json(['error' => 'Invalid cron expression'], 422);
            }
            $validated['next_run_at'] = $this->calculateNextRun($validated['expression']);
        }

        $task->update($validated);

        return response()->json([
            'message' => 'Scheduled task updated',
            'task' => $task,
        ]);
    }

    /**
     * Run scheduled task manually
     * POST /api/v1/admin/developer/tasks/{task}/run
     */
    public function runTask(Request $request, ScheduledTask $task)
    {
        $start = microtime(true);

        try {
            Artisan::call($task->command, $task->parameters ?? []);
            $output = Artisan::output();
            $duration = (int) (microtime(true) - $start);

            $task->update([
                'last_run_at' => now(),
                'last_run_status' => 'success',
                'last_run_output' => $output,
                'last_run_duration' => $duration,
                'run_count' => $task->run_count + 1,
            ]);

            return response()->json([
                'message' => 'Task executed successfully',
                'output' => $output,
                'duration' => $duration,
            ]);
        } catch (\Exception $e) {
            $duration = (int) (microtime(true) - $start);

            $task->update([
                'last_run_at' => now(),
                'last_run_status' => 'failed',
                'last_run_output' => $e->getMessage(),
                'last_run_duration' => $duration,
                'failure_count' => $task->failure_count + 1,
            ]);

            return response()->json([
                'message' => 'Task execution failed',
                'error' => $e->getMessage(),
                'duration' => $duration,
            ], 500);
        }
    }

    /**
     * Delete scheduled task
     * DELETE /api/v1/admin/developer/tasks/{task}
     */
    public function deleteTask(ScheduledTask $task)
    {
        $task->delete();

        return response()->json([
            'message' => 'Scheduled task deleted',
        ]);
    }

    /**
     * Helper: Match expected response
     */
    private function matchesExpectedResponse($actual, $expected)
    {
        foreach ($expected as $key => $value) {
            if (!isset($actual[$key]) || $actual[$key] !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Helper: Validate cron expression
     */
    private function isValidCronExpression($expression)
    {
        // Basic cron validation (5 or 6 parts)
        $parts = explode(' ', $expression);
        return count($parts) >= 5 && count($parts) <= 6;
    }

    /**
     * Helper: Calculate next run time (simplified)
     */
    private function calculateNextRun($expression)
    {
        // This is a simplified version
        // In production, use a library like cron-expression
        return now()->addMinutes(5);
    }
}

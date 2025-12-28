<?php

namespace App\Exceptions\Financial;

use Exception;

class ComplianceBlockedException extends Exception
{
    protected $requirements;

    public function __construct(string $message = "Compliance requirements not met", array $requirements = [], int $code = 403)
    {
        parent::__construct($message, $code);
        $this->requirements = $requirements;
    }

    /**
     * Get the requirements that blocked the operation
     *
     * @return array
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * Convert exception to array format for API responses
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'requirements' => $this->requirements,
            'code' => $this->getCode(),
        ];
    }

    /**
     * Render the exception as an HTTP response
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json($this->toArray(), $this->getCode());
    }
}

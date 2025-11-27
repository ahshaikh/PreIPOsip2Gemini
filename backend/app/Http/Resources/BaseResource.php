<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base API Resource for standardized response format
 *
 * All API responses should extend this class to ensure consistency
 *
 * Standard Response Format:
 * {
 *   "data": { ... },
 *   "message": "Success message",
 *   "meta": { ... }  // Optional
 * }
 */
class BaseResource extends JsonResource
{
    /**
     * Success message for the response
     *
     * @var string|null
     */
    protected $message = null;

    /**
     * Additional meta information
     *
     * @var array|null
     */
    protected $meta = null;

    /**
     * Set success message
     *
     * @param string $message
     * @return $this
     */
    public function withMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set meta information
     *
     * @param array $meta
     * @return $this
     */
    public function withMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        $response = [];

        if ($this->message !== null) {
            $response['message'] = $this->message;
        }

        if ($this->meta !== null) {
            $response['meta'] = $this->meta;
        }

        return $response;
    }
}

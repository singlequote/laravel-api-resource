<?php

namespace SingleQuote\LaravelApiResource\Infra;

use Closure;

use function __;
use function back;
use function redirect;
use function response;

/**
 * Description of Action
 *
 * @author wim_p
 */
class Action
{
    protected string $redirectUrl;

    protected bool $result;

    protected string|Closure $onError;

    protected string|Closure $onSuccess;

    protected string $errorMessage;

    protected string $successMessage;

    protected mixed $data = [];

    protected Closure $onSuccessClosureCallback;

    protected Closure $onErrorClosureCallback;

    protected int $statusCode = 200;

    /**
     * @param  mixed  $data
     * @return $this
     */
    public function withData(...$data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the code of the JsonResponse
     *
     * @return $this
     */
    public function statusCode(int $code): self
    {
        $this->statusCode = $code;

        return $this;
    }

    /**
     * @return $this
     */
    public function onError(string|Closure $route): self
    {
        $this->onError = $route;

        return $this;
    }

    public function onSuccess(string|Closure $route): self
    {
        $this->onSuccess = $route;

        return $this;
    }

    /**
     * @return $this
     */
    public function onSuccessClosure(Closure $closure): self
    {
        $this->onSuccessClosureCallback = $closure;

        return $this;
    }

    /**
     * @return $this
     */
    public function onErrorClosure(Closure $closure): self
    {
        $this->onErrorClosureCallback = $closure;

        return $this;
    }

    public function json(): self
    {
        $this->onSuccess = fn () => response()->json(count($this->data) === 1 ? $this->data[0] : $this->data, $this->statusCode);
        $this->onError = fn () => response()->json(count($this->data) === 1 ? $this->data[0] : $this->data, $this->statusCode);

        return $this;
    }

    public function data(): self
    {
        $this->onSuccess = fn () => count($this->data) === 1 ? $this->data[0] : $this->data;
        $this->onError = fn () => count($this->data) === 1 ? $this->data[0] : $this->data;

        return $this;
    }

    public function void(): self
    {
        $this->onSuccess = fn () => null;
        $this->onError = fn () => null;

        return $this;
    }

    public function api(): self
    {
        $this->onSuccess = fn () => response()->json([
            'result' => true,
            'status' => 'success',
            'data' => count($this->data) === 1 ? $this->data[0] : $this->data,
        ], $this->statusCode);

        $this->onError = fn () => response()->json([
            'result' => false,
            'status' => 'failed',
            'data' => count($this->data) === 1 ? $this->data[0] : $this->data,
        ], $this->statusCode === 200 ? 422 : $this->statusCode);

        return $this;
    }

    public function response(string $response = '', int $code = 204): self
    {
        $this->onSuccess = fn () => response($response, $code);

        return $this;
    }

    /**
     * @return $this
     */
    public function errorMessage(string $message): self
    {
        $this->errorMessage = $message;

        return $this;
    }

    /**
     * @return $this
     */
    public function successMessage(string $message): self
    {
        $this->successMessage = $message;

        return $this;
    }

    public function result(bool $result = true): mixed
    {
        if (! isset($this->onError)) {
            $this->onError(back()->getTargetUrl());
        }

        if (! isset($this->onSuccess)) {
            $this->onSuccess(back()->getTargetUrl());
        }

        if (! isset($this->errorMessage)) {
            $this->errorMessage(__('Something went wrong!'));
        }

        if (! isset($this->successMessage)) {
            $this->successMessage(__('Success!'));
        }

        if ($result === false) {
            return $this->failed();
        }

        return $this->success();
    }

    private function failed(): mixed
    {
        if (isset($this->onErrorClosureCallback)) {
            ($this->onErrorClosureCallback)(...$this->data ?? []);
        }

        if ($this->onError instanceof Closure) {
            return ($this->onError)(...$this->data ?? []);
        }

        return redirect($this->onError)
            ->with('failed', $this->errorMessage);
    }

    /**
     * @return mixed
     */
    private function success(): mixed
    {
        if (isset($this->onSuccessClosureCallback)) {
            return ($this->onSuccessClosureCallback)(...$this->data ?? []);
        }

        if ($this->onSuccess instanceof Closure) {
            return ($this->onSuccess)(...$this->data ?? []);
        }

        return redirect($this->onSuccess)
            ->with('success', $this->successMessage);
    }
}

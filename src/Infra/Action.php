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

    /**
     * @var string
     */
    protected string $redirectUrl;

    /**
     * @var bool
     */
    protected bool $result;

    /**
     * @var string|Closure
     */
    protected string|Closure $onError;

    /**
     * @var string|Closure
     */
    protected string|Closure $onSuccess;

    /**
     * @var string
     */
    protected string $errorMessage;

    /**
     * @var string
     */
    protected string $successMessage;

    /**
     * @var mixed
     */
    protected mixed $data = [];

    /**
     * @var Closure
     */
    protected Closure $onSuccessClosureCallback;

    /**
     * @var Closure
     */
    protected Closure $onErrorClosureCallback;

    /**
     * @var int $responseCode
     */
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
     * @param int $code
     * @return $this
     */
    public function statusCode(int $code): self
    {
        $this->statusCode = $code;

        return $this;
    }

    /**
     * @param  string|Closure  $route
     * @return $this
     */
    public function onError(string|Closure $route): self
    {
        $this->onError = $route;

        return $this;
    }

    /**
     * @param  string|Closure  $route
     * @return self
     */
    public function onSuccess(string|Closure $route): self
    {
        $this->onSuccess = $route;

        return $this;
    }

    /**
     * @param  Closure  $closure
     * @return $this
     */
    public function onSuccessClosure(Closure $closure): self
    {
        $this->onSuccessClosureCallback = $closure;

        return $this;
    }

    /**
     * @param  Closure  $closure
     * @return $this
     */
    public function onErrorClosure(Closure $closure): self
    {
        $this->onErrorClosureCallback = $closure;

        return $this;
    }

    /**
     * @return self
     */
    public function json(): self
    {
        $this->onSuccess = fn() => response()->json(count($this->data) === 1 ? $this->data[0] : $this->data, $this->statusCode);
        $this->onError = fn() => response()->json(count($this->data) === 1 ? $this->data[0] : $this->data, $this->statusCode);

        return $this;
    }

    /**
     * @return self
     */
    public function data(): self
    {
        $this->onSuccess = fn() => count($this->data) === 1 ? $this->data[0] : $this->data;
        $this->onError = fn() => count($this->data) === 1 ? $this->data[0] : $this->data;

        return $this;
    }

    /**
     * @return self
     */
    public function void(): self
    {
        $this->onSuccess = fn() => null;
        $this->onError = fn() => null;

        return $this;
    }

    /**
     * @return self
     */
    public function api(): self
    {
        $this->onSuccess = fn() => response()->json([
                'result' => true,
                'status' => 'success',
                'data' => count($this->data) === 1 ? $this->data[0] : $this->data,
                ], $this->statusCode);

        $this->onError = fn() => response()->json([
                'result' => false,
                'status' => 'failed',
                'data' => count($this->data) === 1 ? $this->data[0] : $this->data,
                ], $this->statusCode === 200 ? 422 : $this->statusCode);

        return $this;
    }

    /**
     * @param string $response
     * @param int $code
     * @return self
     */
    public function response(string $response = "", int $code = 204): self
    {
        $this->onSuccess = fn() => response($response, $code);

        return $this;
    }

    /**
     * @param  string  $message
     * @return $this
     */
    public function errorMessage(string $message): self
    {
        $this->errorMessage = $message;

        return $this;
    }

    /**
     * @param  string  $message
     * @return $this
     */
    public function successMessage(string $message): self
    {
        $this->successMessage = $message;

        return $this;
    }

    /**
     * @param bool $result
     * @return mixed
     */
    public function result(bool $result = true): mixed
    {
        if (!isset($this->onError)) {
            $this->onError(back()->getTargetUrl());
        }

        if (!isset($this->onSuccess)) {
            $this->onSuccess(back()->getTargetUrl());
        }

        if (!isset($this->errorMessage)) {
            $this->errorMessage(__('Something went wrong!'));
        }

        if (!isset($this->successMessage)) {
            $this->successMessage(__('Success!'));
        }

        if ($result === false) {
            return $this->failed();
        }

        return $this->success();
    }

    /**
     * @return mixed
     */
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
            ($this->onSuccessClosureCallback)(...$this->data ?? []);
        }

        if ($this->onSuccess instanceof Closure) {
            return ($this->onSuccess)(...$this->data ?? []);
        }

        return redirect($this->onSuccess)
                ->with('success', $this->successMessage);
    }
}

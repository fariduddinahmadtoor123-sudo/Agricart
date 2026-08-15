<?php

namespace App\Http\Exceptions;

use App\Support\SafeInternalRedirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class MethodNotAllowedRecovery
{
    public function toResponse(MethodNotAllowedHttpException $exception, Request $request): ?Response
    {
        $allowed = $this->allowedMethods($exception);

        if ($request->isMethod('OPTIONS')) {
            return response()->noContent()->header('Allow', implode(', ', $allowed));
        }

        if ($this->isReadMethod($request) && ! $this->allowsRead($allowed)) {
            if ($this->isBackgroundProbe($request)) {
                return response()->noContent();
            }

            return redirect()->to($this->fallbackUrl($request));
        }

        if (
            $this->isWriteMethod($request)
            && $this->allowsRead($allowed)
            && $this->shouldReplayWriteAsGet($request)
        ) {
            return redirect()->to($request->fullUrl(), Response::HTTP_SEE_OTHER);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function allowedMethods(MethodNotAllowedHttpException $exception): array
    {
        $header = $exception->getHeaders()['Allow'] ?? '';

        if (is_array($header)) {
            $header = implode(',', $header);
        }

        return array_values(array_filter(array_map(
            static fn (string $method): string => strtoupper(trim($method)),
            explode(',', (string) $header),
        )));
    }

    /**
     * @param  list<string>  $allowed
     */
    private function allowsRead(array $allowed): bool
    {
        return in_array('GET', $allowed, true) || in_array('HEAD', $allowed, true);
    }

    private function isReadMethod(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD'], true);
    }

    private function isWriteMethod(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function isBackgroundProbe(Request $request): bool
    {
        $purpose = strtolower(trim(
            $request->headers->get('Sec-Purpose', '').' '.$request->headers->get('Purpose', '')
        ));

        return $request->ajax()
            || $request->wantsJson()
            || $request->expectsJson()
            || $request->headers->has('X-Livewire')
            || $request->is('livewire/*')
            || $request->is('analytics/presence')
            || str_contains($purpose, 'prefetch')
            || str_contains($purpose, 'prerender');
    }

    private function shouldReplayWriteAsGet(Request $request): bool
    {
        return ! $request->is('admin/*')
            && ! $request->is('livewire/*')
            && ! $request->ajax()
            && ! $request->wantsJson()
            && ! $request->headers->has('X-Livewire');
    }

    private function fallbackUrl(Request $request): string
    {
        $path = '/'.ltrim($request->path(), '/');

        foreach ([
            'cart' => '/cart',
            'checkout' => '/checkout',
            'login' => '/login',
            'logout' => '/',
            'customer-register' => '/login',
            'contact-message' => '/',
            'analytics' => '/',
            'account' => '/account',
        ] as $prefix => $url) {
            if ($path === '/'.$prefix || str_starts_with($path, '/'.$prefix.'/')) {
                return $url;
            }
        }

        $referer = SafeInternalRedirect::normalize($request->headers->get('referer'));
        $refererPath = is_string($referer) ? (parse_url($referer, PHP_URL_PATH) ?: '/') : null;

        if (is_string($referer) && $refererPath !== $path) {
            return $referer;
        }

        return '/';
    }
}

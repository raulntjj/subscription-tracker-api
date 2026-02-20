<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Locale\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para configurar o locale da aplicação baseado no header Accept-Language
 */
final class SetLocaleMiddleware
{
    /**
     * Lista de locales suportados pela aplicação
     */
    private const SUPPORTED_LOCALES = ['en', 'pt-BR'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Determina o locale a ser usado baseado no header Accept-Language
     */
    private function determineLocale(Request $request): string
    {
        // Tenta pegar do header Accept-Language
        $headerLocale = $request->header('Accept-Language');

        if ($headerLocale !== null && $this->isSupported($headerLocale)) {
            return $headerLocale;
        }

        // Fallback para o locale configurado na .env
        return config('app.locale', 'en');
    }

    /**
     * Verifica se o locale é suportado
     */
    private function isSupported(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES, true);
    }
}

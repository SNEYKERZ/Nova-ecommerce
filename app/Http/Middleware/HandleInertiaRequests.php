<?php

namespace App\Http\Middleware;

use App\Services\StylingService;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $store = app(TenantManager::class)->getStore();
        $defaults = StylingService::getDefaultColors();

        $bgColor = StylingService::validateColor($store?->bg_color, $defaults['bg_color']);
        $navbarColor = StylingService::validateColor($store?->navbar_color, '#ffffff');
        $footerColor = StylingService::validateColor($store?->footer_color, '#e8e6e3');

        // Use explicit text colors if set, otherwise use defaults (black text on white background)
        $navbarTextColor = $store?->navbar_text_color
            ? StylingService::validateColor($store->navbar_text_color, '#111111')
            : '#111111';

        $footerTextColor = $store?->footer_text_color
            ? StylingService::validateColor($store->footer_text_color, '#111111')
            : '#111111';

        // Social links from store configuracion JSON
        $config = $store?->configuracion ?? [];
        $social = $config['social'] ?? [];

        return array_merge(parent::share($request), [
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'role' => $request->user()->role,
                        'impersonating' => session()->has('impersonator_id'),
                    ]
                    : null,
            ],
            'app' => [
                'name' => $store?->nombre ?? config('app.name', 'Vendex'),
                'logo' => $store?->logo_path ? asset('storage/'.$store->logo_path) : null,
                'env' => config('app.env'),
                'whatsapp' => $store?->telefono,
                'bg_color' => $bgColor,
                'navbar_color' => $navbarColor,
                'footer_color' => $footerColor,
                'navbar_text_color' => $navbarTextColor,
                'footer_text_color' => $footerTextColor,
                'social' => [
                    'facebook' => $social['facebook'] ?? null,
                    'instagram' => $social['instagram'] ?? null,
                    'tiktok' => $social['tiktok'] ?? null,
                ],
                'empresas_url' => $config['empresas_url'] ?? null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}

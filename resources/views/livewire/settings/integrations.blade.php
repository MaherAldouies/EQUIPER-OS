<div class="space-y-8 max-w-3xl">
    <h1 class="text-2xl font-bold">إعدادات التكاملات</h1>
    <p class="text-sm text-gray-500">
        اربط سلة ومنصات التواصل الاجتماعي من هنا مباشرة — القيم دي بتتشفّر وتتخزن، ومفيش داعي لتعديل ملف .env يدويًا.
        بعد الحفظ، الحقول الحسّاسة (Access Token, Secrets) بترجع فاضية دايمًا لأسباب أمنية — ده طبيعي، معناه إن القيمة القديمة محفوظة.
    </p>

    @php
        $statusBadge = function ($integration) {
            if (! $integration) return ['bg-gray-100 text-gray-600', 'غير مهيأ'];
            return match ($integration->status) {
                'connected' => ['bg-green-100 text-green-800', 'متصل'],
                'degraded' => ['bg-red-100 text-red-800', 'به مشكلة'],
                default => ['bg-yellow-100 text-yellow-800', 'قيد الإعداد'],
            };
        };
    @endphp

    {{-- Salla --}}
    <div class="bg-white shadow rounded-lg p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">سلة (Salla)</h2>
            @php [$cls, $label] = $statusBadge($statuses['salla']); @endphp
            <span class="px-2 py-0.5 rounded-full text-xs {{ $cls }}">{{ $label }}</span>
        </div>

        <div class="mb-3 text-xs bg-gray-50 rounded p-2">
            <span class="text-gray-500">رابط الـ Webhook (الصقه في Salla Partners Portal → Webhooks):</span>
            <code class="block mt-1 select-all">{{ $webhookUrls['salla'] }}</code>
        </div>

        <form wire:submit="saveSalla" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700">Client ID</label>
                <input type="text" wire:model="salla_client_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Client Secret</label>
                <input type="password" wire:model="salla_client_secret" placeholder="{{ $statuses['salla']?->credential?->secrets['client_secret'] ?? null ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Webhook Secret</label>
                <input type="password" wire:model="salla_webhook_secret" placeholder="{{ $statuses['salla']?->credential?->secrets['webhook_secret'] ?? null ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div></div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Access Token</label>
                <input type="password" wire:model="salla_access_token" placeholder="{{ $statuses['salla']?->credential?->access_token ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Refresh Token</label>
                <input type="password" wire:model="salla_refresh_token" placeholder="{{ $statuses['salla']?->credential?->refresh_token ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">حفظ إعدادات سلة</button>
            </div>
        </form>
    </div>

    {{-- WhatsApp --}}
    <div class="bg-white shadow rounded-lg p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">WhatsApp Business</h2>
            @php [$cls, $label] = $statusBadge($statuses['whatsapp']); @endphp
            <span class="px-2 py-0.5 rounded-full text-xs {{ $cls }}">{{ $label }}</span>
        </div>

        <div class="mb-3 text-xs bg-gray-50 rounded p-2">
            <span class="text-gray-500">رابط الـ Webhook (الصقه في Meta App Dashboard → WhatsApp → Configuration):</span>
            <code class="block mt-1 select-all">{{ $webhookUrls['whatsapp'] }}</code>
        </div>

        <form wire:submit="saveWhatsapp" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700">Phone Number ID</label>
                <input type="text" wire:model="whatsapp_phone_number_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Access Token</label>
                <input type="password" wire:model="whatsapp_access_token" placeholder="{{ $statuses['whatsapp']?->credential?->access_token ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Verify Token (اختره بنفسك)</label>
                <input type="password" wire:model="whatsapp_verify_token" placeholder="{{ $statuses['whatsapp']?->credential?->secrets['verify_token'] ?? null ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">App Secret</label>
                <input type="password" wire:model="whatsapp_app_secret" placeholder="{{ $statuses['whatsapp']?->credential?->secrets['app_secret'] ?? null ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">حفظ إعدادات واتساب</button>
            </div>
        </form>
    </div>

    {{-- Meta (Instagram + Facebook) --}}
    <div class="bg-white shadow rounded-lg p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">Instagram + Facebook</h2>
            @php [$cls, $label] = $statusBadge($statuses['meta']); @endphp
            <span class="px-2 py-0.5 rounded-full text-xs {{ $cls }}">{{ $label }}</span>
        </div>
        <p class="text-xs text-amber-600 mb-3">النشر والرد الفعلي على حسابات حقيقية يحتاج موافقة Meta App Review أولًا (إجراء خارجي من ميتا).</p>

        <div class="mb-3 text-xs bg-gray-50 rounded p-2">
            <span class="text-gray-500">رابط الـ Webhook (الصقه في Meta App Dashboard → Instagram/Webhooks):</span>
            <code class="block mt-1 select-all">{{ $webhookUrls['meta'] }}</code>
        </div>

        <form wire:submit="saveMeta">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700">App ID</label>
                    <input type="text" wire:model="meta_client_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">App Secret</label>
                    <input type="password" wire:model="meta_app_secret" placeholder="{{ $statuses['meta']?->credential?->secrets['app_secret'] ?? null ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Verify Token (اختره بنفسك)</label>
                    <input type="password" wire:model="meta_verify_token" placeholder="{{ $statuses['meta']?->credential?->secrets['verify_token'] ?? null ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                </div>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">حفظ</button>
                <a href="{{ route('integrations.meta.connect') }}" class="inline-flex items-center gap-2 border border-gray-300 text-sm rounded-md px-4 py-2 hover:bg-gray-50">ربط بحساب Meta</a>
                @if ($statuses['meta']?->status === 'connected')
                    <span class="text-xs text-green-700">✓ متصل — صفحة {{ $statuses['meta']->settings['page_id'] ?? '' }}</span>
                @endif
            </div>

            <details class="mt-4">
                <summary class="text-xs text-gray-500 cursor-pointer select-none">أو أدخل Page ID / Instagram User ID / Access Token يدويًا بدل تسجيل الدخول</summary>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Instagram User ID</label>
                        <input type="text" wire:model="meta_ig_user_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Facebook Page ID</label>
                        <input type="text" wire:model="meta_page_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700">Access Token</label>
                        <input type="password" wire:model="meta_access_token" placeholder="{{ $statuses['meta']?->credential?->access_token ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="bg-gray-100 text-gray-800 text-sm rounded-md px-4 py-2 hover:bg-gray-200">حفظ يدويًا</button>
                    </div>
                </div>
            </details>
        </form>
    </div>

    {{-- TikTok --}}
    <div class="bg-white shadow rounded-lg p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">TikTok</h2>
            @php [$cls, $label] = $statusBadge($statuses['tiktok']); @endphp
            <span class="px-2 py-0.5 rounded-full text-xs {{ $cls }}">{{ $label }}</span>
        </div>
        <p class="text-xs text-amber-600 mb-3">نشر فقط — تيك توك لا يوفر API للرد على التعليقات (قيد من المنصة نفسها).</p>

        <div class="mb-3 text-xs bg-gray-50 rounded p-2">
            <span class="text-gray-500">رابط إعادة التوجيه (الصقه في TikTok Developers ‹ تطبيقك ‹ Login Kit ‹ Redirect URI):</span>
            <code class="block mt-1 select-all">{{ $tiktokRedirectUri }}</code>
        </div>

        <form wire:submit="saveTiktok">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700">Client Key</label>
                    <input type="text" wire:model="tiktok_client_key" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Client Secret</label>
                    <input type="password" wire:model="tiktok_client_secret" placeholder="{{ $statuses['tiktok']?->credential?->secrets['client_secret'] ?? null ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Privacy Level</label>
                    <select wire:model="tiktok_privacy_level" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option value="PUBLIC_TO_EVERYONE">عام للجميع</option>
                        <option value="MUTUAL_FOLLOW_FRIENDS">المتابعون المتبادلون</option>
                        <option value="SELF_ONLY">أنا فقط (مسودة)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">حفظ</button>
                <a href="{{ route('integrations.tiktok.connect') }}" class="inline-flex items-center gap-2 border border-gray-300 text-sm rounded-md px-4 py-2 hover:bg-gray-50">ربط بحساب TikTok</a>
                @if ($statuses['tiktok']?->credential?->refresh_token)
                    <span class="text-xs text-green-700">✓ متصل عبر حساب TikTok</span>
                @endif
            </div>

            <details class="mt-4">
                <summary class="text-xs text-gray-500 cursor-pointer select-none">أو الصق Access Token يدويًا بدل تسجيل الدخول</summary>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Access Token</label>
                        <input type="password" wire:model="tiktok_access_token" placeholder="{{ $statuses['tiktok']?->credential?->access_token ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="bg-gray-100 text-gray-800 text-sm rounded-md px-4 py-2 hover:bg-gray-200">حفظ Access Token</button>
                    </div>
                </div>
            </details>
        </form>
    </div>

    {{-- X (Twitter) --}}
    <div class="bg-white shadow rounded-lg p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">X (Twitter)</h2>
            @php [$cls, $label] = $statusBadge($statuses['x']); @endphp
            <span class="px-2 py-0.5 rounded-full text-xs {{ $cls }}">{{ $label }}</span>
        </div>
        <p class="text-xs text-amber-600 mb-3">تكلفة فعلية مستمرة لكل نشر/قراءة — لا يوجد Webhook، الردود تُستطلع كل 30 دقيقة.</p>

        <form wire:submit="saveX" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700">Client ID</label>
                <input type="text" wire:model="x_client_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">User ID</label>
                <input type="text" wire:model="x_user_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Access Token</label>
                <input type="password" wire:model="x_access_token" placeholder="{{ $statuses['x']?->credential?->access_token ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Refresh Token</label>
                <input type="password" wire:model="x_refresh_token" placeholder="{{ $statuses['x']?->credential?->refresh_token ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">حفظ إعدادات X</button>
            </div>
        </form>
    </div>

    {{-- Google OAuth App — powers the "ربط بحساب Google" one-click buttons below --}}
    <div class="bg-white shadow rounded-lg p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">تطبيق Google (تسجيل الدخول بضغطة واحدة)</h2>
            @php [$cls, $label] = $statusBadge($statuses['google']); @endphp
            <span class="px-2 py-0.5 rounded-full text-xs {{ $cls }}">{{ $label }}</span>
        </div>
        <p class="text-xs text-gray-500 mb-3">
            بيانات تُنشأ مرة واحدة بس من Google Cloud Console (Credentials ‹ Create Credentials ‹ OAuth client ID ‹ Web application) —
            بعدها هتقدر تربط Analytics أو Merchant Center بضغطة واحدة من غير نسخ/لصق أي مفتاح. رابط إعادة التوجيه (Redirect URI) اللي تحطه هناك:
        </p>
        <div class="mb-3 text-xs bg-gray-50 rounded p-2">
            <code class="block mt-1 select-all">{{ $googleRedirectUri }}</code>
        </div>
        <form wire:submit="saveGoogleOAuthApp" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700">Client ID</label>
                <input type="text" wire:model="google_client_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Client Secret</label>
                <input type="password" wire:model="google_client_secret" placeholder="{{ $statuses['google']?->credential?->secrets['client_secret'] ?? null ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">حفظ بيانات تطبيق Google</button>
            </div>
        </form>
    </div>

    {{-- Google Analytics (GA4) --}}
    <div class="bg-white shadow rounded-lg p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">Google Analytics (GA4)</h2>
            @php [$cls, $label] = $statusBadge($statuses['google_analytics']); @endphp
            <span class="px-2 py-0.5 rounded-full text-xs {{ $cls }}">{{ $label }}</span>
        </div>

        <form wire:submit="saveGoogleAnalytics">
            <div class="mb-4 max-w-xs">
                <label class="block text-xs font-medium text-gray-700 mb-1">Property ID</label>
                <input type="text" wire:model="google_analytics_property_id" class="w-full rounded-md border-gray-300 text-sm">
                <p class="text-xs text-gray-400 mt-1">GA4 ‹ Admin ‹ Property Details</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">حفظ</button>
                <a href="{{ route('integrations.google.connect', 'google_analytics') }}" class="inline-flex items-center gap-2 border border-gray-300 text-sm rounded-md px-4 py-2 hover:bg-gray-50">ربط بحساب Google</a>
                @if ($statuses['google_analytics']?->credential?->refresh_token)
                    <span class="text-xs text-green-700">✓ متصل عبر حساب Google</span>
                @endif
            </div>

            <details class="mt-4">
                <summary class="text-xs text-gray-500 cursor-pointer select-none">أو استخدم حساب خدمة (Service Account) بدل تسجيل الدخول</summary>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Service Account Email</label>
                        <input type="text" wire:model="google_analytics_client_email" placeholder="{{ $statuses['google_analytics']?->credential?->secrets['client_email'] ?? null ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700">Private Key</label>
                        <textarea wire:model="google_analytics_private_key" rows="3" placeholder="{{ $statuses['google_analytics']?->credential?->secrets['private_key'] ?? null ? '•••• محفوظ' : '-----BEGIN PRIVATE KEY-----...' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm font-mono"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="bg-gray-100 text-gray-800 text-sm rounded-md px-4 py-2 hover:bg-gray-200">حفظ بيانات Service Account</button>
                    </div>
                </div>
            </details>
        </form>
    </div>

    {{-- Google Merchant Center --}}
    <div class="bg-white shadow rounded-lg p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">Google Merchant Center</h2>
            @php [$cls, $label] = $statusBadge($statuses['google_merchant']); @endphp
            <span class="px-2 py-0.5 rounded-full text-xs {{ $cls }}">{{ $label }}</span>
        </div>

        <form wire:submit="saveGoogleMerchant">
            <div class="mb-4 max-w-xs">
                <label class="block text-xs font-medium text-gray-700 mb-1">Merchant ID</label>
                <input type="text" wire:model="google_merchant_id" class="w-full rounded-md border-gray-300 text-sm">
                <p class="text-xs text-gray-400 mt-1">أعلى صفحة Merchant Center</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">حفظ</button>
                <a href="{{ route('integrations.google.connect', 'google_merchant') }}" class="inline-flex items-center gap-2 border border-gray-300 text-sm rounded-md px-4 py-2 hover:bg-gray-50">ربط بحساب Google</a>
                @if ($statuses['google_merchant']?->credential?->refresh_token)
                    <span class="text-xs text-green-700">✓ متصل عبر حساب Google</span>
                @endif
            </div>

            <details class="mt-4">
                <summary class="text-xs text-gray-500 cursor-pointer select-none">أو استخدم حساب خدمة (Service Account) بدل تسجيل الدخول</summary>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Service Account Email</label>
                        <input type="text" wire:model="google_merchant_client_email" placeholder="{{ $statuses['google_merchant']?->credential?->secrets['client_email'] ?? null ? '•••• محفوظ' : '' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700">Private Key</label>
                        <textarea wire:model="google_merchant_private_key" rows="3" placeholder="{{ $statuses['google_merchant']?->credential?->secrets['private_key'] ?? null ? '•••• محفوظ' : '-----BEGIN PRIVATE KEY-----...' }}" class="mt-1 w-full rounded-md border-gray-300 text-sm font-mono"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="bg-gray-100 text-gray-800 text-sm rounded-md px-4 py-2 hover:bg-gray-200">حفظ بيانات Service Account</button>
                    </div>
                </div>
            </details>
        </form>
    </div>

    {{-- Google Tag Manager --}}
    <div class="bg-white shadow rounded-lg p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">Google Tag Manager</h2>
            @php [$cls, $label] = $statusBadge($statuses['google_tag_manager']); @endphp
            <span class="px-2 py-0.5 rounded-full text-xs {{ $cls }}">{{ $label }}</span>
        </div>
        <p class="text-xs text-gray-500 mb-3">مدير الوسوم لا يوفر بيانات أداء مباشرة — هنا فقط لحفظ رقم الحاوية (Container ID) لتركيبه على المتجر.</p>

        <form wire:submit="saveGoogleTagManager" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700">Container ID</label>
                <input type="text" wire:model="google_tag_manager_container_id" placeholder="GTM-XXXXXXX" class="mt-1 w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2 hover:bg-gray-800">حفظ إعدادات Google Tag Manager</button>
            </div>
        </form>
    </div>
</div>

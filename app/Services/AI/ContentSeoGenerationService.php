<?php

namespace App\Services\AI;

use App\Models\Approval;
use App\Models\BrandVoice;
use App\Models\Content;
use App\Models\ContentAsset;
use App\Models\Product;
use App\Models\SeoAsset;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * ContentSeoGenerationService — F6 from the PRD, and the first real,
 * narrowly-scoped instance of an "AI Agent" per the AI Operating Core
 * document's Agent definition (Section 2): identity = Content & SEO
 * generation; memory scope = this Product + the active Brand Voice;
 * permissions = draft-only, never auto-publish.
 *
 * Triggered by the ProductEnriched event (see Product::markEnriched()).
 * Every output starts in "Under Review" state and immediately requests
 * an Approval (F7) — this class NEVER marks anything published.
 */
class ContentSeoGenerationService
{
    public function __construct(
        private readonly Client $httpClient = new Client(),
    ) {}

    public function generateForProduct(Product $product): Content
    {
        // Loop-detection guardrail (AI Operating Core doc, Section 8):
        // one generation attempt per Product per enrichment event.
        $maxAttempts = (int) config('equiperos.ai.max_generations_per_product_per_event', 1);
        $existingAttempts = Content::query()
            ->where('product_id', $product->id)
            ->where('generated_by', 'ai')
            ->count();

        if ($existingAttempts >= $maxAttempts) {
            throw new RuntimeException(
                "Generation attempt limit ({$maxAttempts}) already reached for Product {$product->id} — ".
                'no silent retries, per the loop-detection guardrail (AI Operating Core doc, Section 8).'
            );
        }

        $brandVoice = BrandVoice::query()
            ->where('organization_id', $product->organization_id)
            ->where('status', 'active')
            ->firstOrFail(); // F6 acceptance criteria: generation always references an active Brand Voice

        $generated = $this->callAnthropic($product, $brandVoice);

        return DB::transaction(function () use ($product, $brandVoice, $generated) {
            $content = Content::query()->create([
                'organization_id' => $product->organization_id,
                'product_id' => $product->id,
                'title' => $generated['product_description_title'],
                'body' => $generated['product_description'],
                'generated_by' => 'ai',
                'brand_voice_id' => $brandVoice->id,
                'status' => 'drafted',
            ]);

            $content->recordEvent(eventType: 'ContentDrafted', payload: [
                'product_id' => $product->id,
                'brand_voice_id' => $brandVoice->id,
            ]);

            // Content Asset: Instagram caption
            $caption = ContentAsset::query()->create([
                'organization_id' => $product->organization_id,
                'content_id' => $content->id,
                'channel' => 'instagram_caption',
                'body' => $generated['instagram_caption'],
                'status' => 'generated',
            ]);
            Approval::requestFor($caption);

            // SEO Asset: meta title
            $metaTitle = SeoAsset::query()->create([
                'organization_id' => $product->organization_id,
                'product_id' => $product->id,
                'asset_type' => 'meta_title',
                'value' => $generated['seo_meta_title'],
                'status' => 'generated',
            ]);
            $metaTitle->recordEvent(eventType: 'SEOAssetGenerated', payload: ['asset_type' => 'meta_title']);
            Approval::requestFor($metaTitle, roleKey: 'seo_specialist');

            // SEO Asset: meta description
            $metaDescription = SeoAsset::query()->create([
                'organization_id' => $product->organization_id,
                'product_id' => $product->id,
                'asset_type' => 'meta_description',
                'value' => $generated['seo_meta_description'],
                'status' => 'generated',
            ]);
            $metaDescription->recordEvent(eventType: 'SEOAssetGenerated', payload: ['asset_type' => 'meta_description']);
            Approval::requestFor($metaDescription, roleKey: 'seo_specialist');

            return $content;
        });
    }

    /**
     * Calls the Anthropic API with Product + Brand Voice as mandatory
     * context (F5/F6 acceptance criteria: "every generation call
     * demonstrably references the Brand Voice document").
     *
     * Returns a structured array — the system prompt instructs the
     * model to respond in JSON only, per the "Structured Outputs"
     * pattern used elsewhere in EQUIPER OS's AI integrations.
     */
    private function callAnthropic(Product $product, BrandVoice $brandVoice): array
    {
        $systemPrompt = <<<PROMPT
            You are the Content & SEO reasoning role of EQUIPER OS's AI Operating Core,
            acting for EQUIPER, a Saudi HORECA equipment e-commerce business.

            Brand voice — tone: {$brandVoice->tone_guidelines}
            Vocabulary notes: {$brandVoice->vocabulary_notes}
            Things to avoid: {$brandVoice->things_to_avoid}
            Brand facts: {$brandVoice->brand_facts}

            Respond ONLY with a JSON object, no other text, with exactly these keys:
            seo_meta_title, seo_meta_description, product_description_title,
            product_description, instagram_caption.
            PROMPT;

        $userPrompt = "Product name: {$product->name}\n".
            'Category: '.($product->category?->name ?? 'غير محدد')."\n".
            'Official agency brand: '.($product->is_agency_brand ? 'Yes' : 'No')."\n".
            'Price: '.($product->price ?? 'N/A');

        try {
            $response = $this->httpClient->post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => config('equiperos.ai.anthropic_api_key'),
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'model' => config('equiperos.ai.model'),
                    'max_tokens' => 1000,
                    'system' => $systemPrompt,
                    'messages' => [
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $text = $body['content'][0]['text'] ?? '{}';
            $cleaned = trim(str_replace(['```json', '```'], '', $text));

            return json_decode($cleaned, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::error('AI Content & SEO generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}

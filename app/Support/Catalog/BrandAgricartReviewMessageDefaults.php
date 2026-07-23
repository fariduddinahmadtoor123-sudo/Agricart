<?php

namespace App\Support\Catalog;

/**
 * Factory defaults for manageable Positive / Neutral / Needs Improvement messages.
 * Runtime values live in brand_agricart_review_messages.
 */
final class BrandAgricartReviewMessageDefaults
{
    /**
     * @return array<string, array{
     *     positive_en: string,
     *     positive_ur: string,
     *     neutral_en: string,
     *     neutral_ur: string,
     *     needs_improvement_en: string,
     *     needs_improvement_ur: string
     * }>
     */
    public static function all(): array
    {
        return [
            'product_quality' => [
                'positive_en' => 'Consistently delivers high-quality products.',
                'positive_ur' => 'مسلسل اعلیٰ معیار کی مصنوعات فراہم کرتا ہے۔',
                'neutral_en' => 'Delivers satisfactory product quality for most applications.',
                'neutral_ur' => 'زیادہ تر استعمالات کے لیے تسلی بخش معیار فراہم کرتا ہے۔',
                'needs_improvement_en' => 'Product quality is not consistently maintained.',
                'needs_improvement_ur' => 'مصنوعات کا معیار ہمیشہ یکساں برقرار نہیں رہتا۔',
            ],
            'value_for_money' => [
                'positive_en' => 'Offers excellent value for money.',
                'positive_ur' => 'قیمت کے مقابلے میں بہترین معیار فراہم کرتا ہے۔',
                'neutral_en' => 'Offers reasonable value for its price.',
                'neutral_ur' => 'اپنی قیمت کے مطابق مناسب معیار فراہم کرتا ہے۔',
                'needs_improvement_en' => 'Comparable alternatives are available at lower prices.',
                'needs_improvement_ur' => 'اسی معیار کے متبادل برانڈز نسبتاً کم قیمت پر دستیاب ہیں۔',
            ],
            'market_reputation' => [
                'positive_en' => 'Widely trusted across the market.',
                'positive_ur' => 'مارکیٹ میں وسیع پیمانے پر قابلِ اعتماد سمجھا جاتا ہے۔',
                'neutral_en' => 'Maintains a generally acceptable market reputation.',
                'neutral_ur' => 'مارکیٹ میں مجموعی طور پر قابلِ قبول ساکھ رکھتا ہے۔',
                'needs_improvement_en' => 'Market confidence is comparatively limited.',
                'needs_improvement_ur' => 'مارکیٹ میں اعتماد کا درجہ نسبتاً محدود ہے۔',
            ],
            'durability' => [
                'positive_en' => 'Products are built for long-lasting durability.',
                'positive_ur' => 'مصنوعات طویل عرصے تک پائیدار کارکردگی فراہم کرتی ہیں۔',
                'neutral_en' => 'Provides satisfactory durability under normal use.',
                'neutral_ur' => 'معمول کے استعمال میں مناسب پائیداری فراہم کرتا ہے۔',
                'needs_improvement_en' => 'Durability may vary under continuous use.',
                'needs_improvement_ur' => 'مسلسل استعمال میں پائیداری میں فرق آ سکتا ہے۔',
            ],
            'performance' => [
                'positive_en' => 'Consistently delivers reliable performance.',
                'positive_ur' => 'مسلسل قابلِ اعتماد کارکردگی فراہم کرتا ہے۔',
                'neutral_en' => 'Delivers satisfactory performance in most situations.',
                'neutral_ur' => 'زیادہ تر حالات میں تسلی بخش کارکردگی فراہم کرتا ہے۔',
                'needs_improvement_en' => 'Performance may not consistently meet expectations.',
                'needs_improvement_ur' => 'کارکردگی ہمیشہ توقعات کے مطابق نہیں رہتی۔',
            ],
            'innovation_technology' => [
                'positive_en' => 'Regularly introduces modern technologies and innovations.',
                'positive_ur' => 'جدید ٹیکنالوجی اور جدت متعارف کرانے میں سرگرم رہتا ہے۔',
                'neutral_en' => 'Keeps pace with standard industry developments.',
                'neutral_ur' => 'صنعت کے عمومی معیار کے مطابق ٹیکنالوجی اور جدت برقرار رکھتا ہے۔',
                'needs_improvement_en' => 'Innovation is comparatively limited.',
                'needs_improvement_ur' => 'جدت اور جدید ٹیکنالوجی کا استعمال نسبتاً محدود ہے۔',
            ],
            'warranty_after_sales' => [
                'positive_en' => 'Provides dependable warranty and after-sales support.',
                'positive_ur' => 'قابلِ اعتماد وارنٹی اور بعد از فروخت خدمات فراہم کرتا ہے۔',
                'neutral_en' => 'Provides satisfactory warranty and after-sales support.',
                'neutral_ur' => 'وارنٹی اور بعد از فروخت خدمات مجموعی طور پر تسلی بخش ہیں۔',
                'needs_improvement_en' => 'There is room for improvement in after-sales support.',
                'needs_improvement_ur' => 'بعد از فروخت خدمات میں مزید بہتری کی گنجائش موجود ہے۔',
            ],
            'spare_parts_availability' => [
                'positive_en' => 'Spare parts are readily available.',
                'positive_ur' => 'اسپیئر پارٹس آسانی سے دستیاب ہیں۔',
                'neutral_en' => 'Spare parts are generally available in most markets.',
                'neutral_ur' => 'اسپیئر پارٹس زیادہ تر مارکیٹوں میں دستیاب رہتے ہیں۔',
                'needs_improvement_en' => 'Spare parts may not always be readily available.',
                'needs_improvement_ur' => 'اسپیئر پارٹس ہر وقت آسانی سے دستیاب نہیں ہوتے۔',
            ],
            'brand_reliability' => [
                'positive_en' => 'Consistently maintains customer confidence.',
                'positive_ur' => 'مسلسل صارفین کا اعتماد برقرار رکھتا ہے۔',
                'neutral_en' => 'Maintains a satisfactory level of customer confidence.',
                'neutral_ur' => 'صارفین کے اعتماد کا مجموعی طور پر تسلی بخش معیار برقرار رکھتا ہے۔',
                'needs_improvement_en' => 'Reliability may vary across products.',
                'needs_improvement_ur' => 'مختلف مصنوعات میں اعتماد کا معیار مختلف ہو سکتا ہے۔',
            ],
            'product_availability' => [
                'positive_en' => 'Products remain consistently available throughout the year.',
                'positive_ur' => 'مصنوعات سال بھر مسلسل دستیاب رہتی ہیں۔',
                'neutral_en' => 'Products are generally available, with occasional supply fluctuations.',
                'neutral_ur' => 'مصنوعات عموماً دستیاب رہتی ہیں، تاہم بعض اوقات سپلائی میں معمولی اتار چڑھاؤ آ سکتا ہے۔',
                'needs_improvement_en' => 'Product availability becomes limited during peak seasons.',
                'needs_improvement_ur' => 'مصروف سیزن میں مصنوعات کی دستیابی محدود ہو جاتی ہے۔',
            ],
            'overall_market_acceptance' => [
                'positive_en' => 'Highly accepted by customers and dealers.',
                'positive_ur' => 'صارفین، ڈیلرز اور مارکیٹ میں وسیع پیمانے پر مقبول ہے۔',
                'neutral_en' => 'Maintains a satisfactory level of market acceptance.',
                'neutral_ur' => 'مارکیٹ میں مجموعی طور پر قابلِ قبول مقام رکھتا ہے۔',
                'needs_improvement_en' => 'Market acceptance is comparatively limited.',
                'needs_improvement_ur' => 'مارکیٹ میں مجموعی قبولیت کا درجہ نسبتاً محدود ہے۔',
            ],
        ];
    }

    /**
     * @return array{
     *     positive_en: string,
     *     positive_ur: string,
     *     neutral_en: string,
     *     neutral_ur: string,
     *     needs_improvement_en: string,
     *     needs_improvement_ur: string
     * }|null
     */
    public static function for(string $criterionKey): ?array
    {
        return self::all()[$criterionKey] ?? null;
    }
}
